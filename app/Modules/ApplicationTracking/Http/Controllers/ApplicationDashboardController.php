<?php

namespace App\Modules\ApplicationTracking\Http\Controllers;

use App\Modules\ApplicationTracking\Models\Application;
use App\Modules\ApplicationTracking\Models\Office;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ApplicationDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $countsByStatus = Application::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $avgTurnaroundHoursByCategory = Application::query()
            ->whereNotNull('resolved_at')
            ->join('application_categories', 'application_categories.id', '=', 'applications.application_category_id')
            ->selectRaw('application_categories.name as category, applications.submitted_at, applications.resolved_at')
            ->get()
            ->groupBy('category')
            ->map(fn ($rows) => round($rows->avg(
                fn ($row) => Carbon::parse($row->submitted_at)->diffInHours(Carbon::parse($row->resolved_at))
            ), 1));

        $pendingApplications = Application::whereIn('status', ['pending', 'returned_for_revision'])
            ->with('currentStep')
            ->get();

        $pendingPerOfficeId = $pendingApplications
            ->filter(fn ($application) => $application->currentStep && $application->currentStep->approver_type === 'office')
            ->groupBy(fn ($application) => $application->currentStep->approver_office_id)
            ->map->count();

        $officeNames = Office::whereIn('id', $pendingPerOfficeId->keys())->pluck('name', 'id');

        $pendingPerOffice = $pendingPerOfficeId->mapWithKeys(
            fn ($count, $officeId) => [$officeNames[$officeId] ?? "Office #{$officeId}" => $count]
        );

        return $this->ok([
            'counts_by_status' => $countsByStatus,
            'avg_turnaround_hours_by_category' => $avgTurnaroundHoursByCategory,
            'pending_per_office' => $pendingPerOffice,
        ]);
    }
}
