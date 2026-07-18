<?php

namespace App\Modules\ApplicationTracking\Http\Controllers;

use App\Modules\ApplicationTracking\Http\Requests\Application\ActOnApplicationRequest;
use App\Modules\ApplicationTracking\Http\Requests\Application\ResubmitApplicationRequest;
use App\Modules\ApplicationTracking\Http\Requests\Application\SubmitApplicationRequest;
use App\Modules\ApplicationTracking\Http\Resources\ApplicationCategoryResource;
use App\Modules\ApplicationTracking\Http\Resources\ApplicationResource;
use App\Modules\ApplicationTracking\Models\Application;
use App\Modules\ApplicationTracking\Models\ApplicationCategory;
use App\Modules\ApplicationTracking\Models\Office;
use App\Modules\ApplicationTracking\Services\ApplicationWorkflowService;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ApplicationController extends Controller
{
    public function __construct(private ApplicationWorkflowService $workflow) {}

    public function categories(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ApplicationCategory::with('workflowTemplate.steps')->latest();

        // Admins manage every category (including inactive ones); everyone
        // else only sees what they could actually submit right now.
        if (! $user->isAdmin()) {
            $query->where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->whereNull('applicant_roles')->orWhereJsonContains('applicant_roles', $user->role);
                });
        }

        $categories = $query->paginate(15);

        return $this->paginated(ApplicationCategoryResource::collection($categories), $categories);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Application::with(['category', 'currentStep.office', 'applicant.student', 'applicant.teacher']);

        if ($request->boolean('assigned')) {
            $query->whereIn('status', ['pending', 'returned_for_revision']);
        } else {
            $query->where('applicant_user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('application_category_id', $request->integer('category_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('submitted_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('submitted_at', '<=', $request->date('to'));
        }

        $sort = (string) $request->string('sort', '-submitted_at');
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $query->orderBy(in_array($column, ['submitted_at', 'status'], true) ? $column : 'submitted_at', $direction);

        if (! $request->boolean('assigned')) {
            $applications = $query->paginate(15);

            return $this->paginated(ApplicationResource::collection($applications), $applications);
        }

        // "Which office(s) does this user hold" spans a pivot table plus two
        // possible applicant relation paths (Student or Teacher department),
        // the same authority rules ApplicationPolicy enforces per-application.
        // At FYP scale, filtering the (small) pending set in PHP keeps this
        // logic in one place instead of duplicating it as diverging SQL.
        $officeIds = Office::whereHas('users', fn ($q) => $q->where('users.id', $user->id))->pluck('id');
        $hodDepartmentIds = $user->teacher
            ? Department::where('hod_teacher_id', $user->teacher->id)->pluck('id')
            : collect();

        $filtered = $query->get()->filter(function (Application $application) use ($officeIds, $hodDepartmentIds) {
            $step = $application->currentStep;

            if (! $step) {
                return false;
            }

            if ($step->approver_type === 'office') {
                return $officeIds->contains($step->approver_office_id);
            }

            if ($step->approver_type === 'applicant_department_hod') {
                $departmentId = $application->applicant->student?->department_id
                    ?? $application->applicant->teacher?->department_id;

                return $departmentId && $hodDepartmentIds->contains($departmentId);
            }

            return false;
        })->values();

        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 15;
        $paginator = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return $this->paginated(ApplicationResource::collection($paginator), $paginator);
    }

    public function show(Request $request, Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        return $this->ok(ApplicationResource::make($application->load([
            'category', 'currentStep.office', 'applicant',
            'actions.step', 'actions.actor', 'actions.attachments',
            'attachments',
        ])));
    }

    public function store(SubmitApplicationRequest $request): JsonResponse
    {
        $category = ApplicationCategory::findOrFail($request->validated('application_category_id'));

        $application = $this->workflow->submit(
            $request->user(),
            $category,
            $request->input('form_data', []),
            $request->file('attachments', []),
        );

        return $this->ok(ApplicationResource::make($application), 'Application submitted', 201);
    }

    public function act(ActOnApplicationRequest $request, Application $application): JsonResponse
    {
        $this->authorize('act', $application);

        $updated = $this->workflow->act(
            $request->user(),
            $application,
            $request->validated('action'),
            $request->validated('remarks'),
            $request->validated('forward_to_office_id'),
        );

        return $this->ok(ApplicationResource::make($updated), 'Application updated');
    }

    public function resubmit(ResubmitApplicationRequest $request, Application $application): JsonResponse
    {
        $this->authorize('resubmit', $application);

        $updated = $this->workflow->resubmit(
            $request->user(),
            $application,
            $request->input('form_data', []),
            $request->file('attachments', []),
        );

        return $this->ok(ApplicationResource::make($updated), 'Application resubmitted');
    }

    public function cancel(Request $request, Application $application): JsonResponse
    {
        $this->authorize('cancel', $application);

        $updated = $this->workflow->cancel($request->user(), $application);

        return $this->ok(ApplicationResource::make($updated), 'Application cancelled');
    }
}
