<?php

namespace App\Modules\ApplicationTracking\Policies;

use App\Models\Department;
use App\Models\User;
use App\Modules\ApplicationTracking\Models\Application;
use App\Modules\ApplicationTracking\Models\Office;
use App\Modules\ApplicationTracking\Models\WorkflowStep;

class ApplicationPolicy
{
    public function view(User $user, Application $application): bool
    {
        if ($user->isAdmin() || $application->applicant_user_id === $user->id) {
            return true;
        }

        if ($this->holdsCurrentStepAuthority($user, $application)) {
            return true;
        }

        return $application->actions()->where('actor_user_id', $user->id)->exists();
    }

    public function act(User $user, Application $application): bool
    {
        if (! in_array($application->status, ['pending', 'returned_for_revision'], true)) {
            return false;
        }

        return $this->holdsCurrentStepAuthority($user, $application);
    }

    public function resubmit(User $user, Application $application): bool
    {
        return $application->applicant_user_id === $user->id
            && $application->status === 'returned_for_revision';
    }

    public function cancel(User $user, Application $application): bool
    {
        return $application->applicant_user_id === $user->id
            && in_array($application->status, ['pending', 'returned_for_revision'], true);
    }

    /**
     * Whether $user currently holds the acting authority for the
     * application's current step — either the step's configured office,
     * the applicant's department HOD (for applicant_department_hod steps),
     * or an office this specific application was explicitly forwarded to
     * at this same step (allowed only when the step has allow_forward).
     */
    private function holdsCurrentStepAuthority(User $user, Application $application): bool
    {
        $step = $application->currentStep;

        if (! $step) {
            return false;
        }

        if ($this->holdsOffice($user, $step->approver_office_id) || $this->isApplicantDepartmentHod($user, $application, $step)) {
            return true;
        }

        $forwardedOfficeIds = $application->actions()
            ->where('workflow_step_id', $step->id)
            ->where('action', 'forwarded')
            ->pluck('forwarded_to_office_id');

        foreach ($forwardedOfficeIds as $officeId) {
            if ($this->holdsOffice($user, $officeId)) {
                return true;
            }
        }

        return false;
    }

    private function holdsOffice(User $user, ?int $officeId): bool
    {
        if (! $officeId) {
            return false;
        }

        return Office::find($officeId)?->users()->where('users.id', $user->id)->exists() ?? false;
    }

    private function isApplicantDepartmentHod(User $user, Application $application, WorkflowStep $step): bool
    {
        if ($step->approver_type !== 'applicant_department_hod' || ! $user->teacher) {
            return false;
        }

        $departmentId = $application->applicant->student?->department_id
            ?? $application->applicant->teacher?->department_id;

        if (! $departmentId) {
            return false;
        }

        $department = Department::find($departmentId);

        return $department && $department->hod_teacher_id === $user->teacher->id;
    }
}
