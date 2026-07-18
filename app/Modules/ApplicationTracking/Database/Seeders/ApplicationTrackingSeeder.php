<?php

namespace App\Modules\ApplicationTracking\Database\Seeders;

use App\Models\User;
use App\Modules\ApplicationTracking\Models\ApplicationCategory;
use App\Modules\ApplicationTracking\Models\Office;
use App\Modules\ApplicationTracking\Models\WorkflowStep;
use App\Modules\ApplicationTracking\Models\WorkflowTemplate;
use Illuminate\Database\Seeder;

/**
 * Standalone demo data for the Application Tracking module. Deliberately
 * NOT wired into the core DatabaseSeeder::run() — run it explicitly:
 *   php artisan db:seed --class="App\Modules\ApplicationTracking\Database\Seeders\ApplicationTrackingSeeder"
 */
class ApplicationTrackingSeeder extends Seeder
{
    public function run(): void
    {
        $examinationOfficer = $this->makeOfficial('Examination Officer', 'examination.officer@university.edu');
        $transportOfficer = $this->makeOfficial('Transport Officer', 'transport.officer@university.edu');
        $itOfficer = $this->makeOfficial('IT Officer', 'it.officer@university.edu');

        $examinationOffice = Office::firstOrCreate(['name' => 'Examination Officer'], ['department_id' => null]);
        $examinationOffice->users()->syncWithoutDetaching([$examinationOfficer->id]);

        $transportOffice = Office::firstOrCreate(['name' => 'Transport Officer'], ['department_id' => null]);
        $transportOffice->users()->syncWithoutDetaching([$transportOfficer->id]);

        $itOffice = Office::firstOrCreate(['name' => 'IT Officer'], ['department_id' => null]);
        $itOffice->users()->syncWithoutDetaching([$itOfficer->id]);

        $this->seedTranscriptRequest($examinationOffice);
        $this->seedLeaveApplication();
        $this->seedTransportPass($transportOffice);
    }

    /** Multi-step, HOD-gated: applicant's department HOD -> Examination Officer. */
    private function seedTranscriptRequest(Office $examinationOffice): void
    {
        $template = WorkflowTemplate::firstOrCreate(['name' => 'Transcript Request Workflow'], ['is_active' => true]);

        if ($template->steps()->count() === 0) {
            $hodStep = WorkflowStep::create([
                'workflow_template_id' => $template->id,
                'step_order' => 1,
                'name' => "Applicant's Department HOD",
                'approver_type' => 'applicant_department_hod',
                'on_reject_action' => 'return_to_applicant',
                'allow_forward' => false,
            ]);

            $examStep = WorkflowStep::create([
                'workflow_template_id' => $template->id,
                'step_order' => 2,
                'name' => 'Examination Officer',
                'approver_type' => 'office',
                'approver_office_id' => $examinationOffice->id,
                'on_reject_action' => 'terminate',
                'allow_forward' => false,
            ]);

            $hodStep->update(['on_approve_next_step_id' => $examStep->id]);
        }

        ApplicationCategory::firstOrCreate(['name' => 'Transcript Request'], [
            'description' => 'Request an official academic transcript.',
            'form_schema' => [
                ['key' => 'reason', 'label' => 'Reason for request', 'type' => 'textarea', 'required' => true],
                ['key' => 'semester', 'label' => 'Semester', 'type' => 'number', 'required' => true],
                ['key' => 'copies_needed', 'label' => 'Copies needed', 'type' => 'number', 'required' => true, 'max' => 5],
            ],
            'workflow_template_id' => $template->id,
            'applicant_roles' => ['student'],
            'allow_multiple_active' => false,
            'is_active' => true,
        ]);
    }

    /** HOD-only, final: direct approval, demonstrates a single-step chain. */
    private function seedLeaveApplication(): void
    {
        $template = WorkflowTemplate::firstOrCreate(['name' => 'Leave Application Workflow'], ['is_active' => true]);

        if ($template->steps()->count() === 0) {
            WorkflowStep::create([
                'workflow_template_id' => $template->id,
                'step_order' => 1,
                'name' => "Applicant's Department HOD",
                'approver_type' => 'applicant_department_hod',
                'on_reject_action' => 'terminate',
                'allow_forward' => false,
            ]);
        }

        ApplicationCategory::firstOrCreate(['name' => 'Leave Application'], [
            'description' => 'Request leave from duty.',
            'form_schema' => [
                ['key' => 'reason', 'label' => 'Reason for leave', 'type' => 'textarea', 'required' => true],
                ['key' => 'start_date', 'label' => 'Start date', 'type' => 'date', 'required' => true],
                ['key' => 'end_date', 'label' => 'End date', 'type' => 'date', 'required' => true],
            ],
            'workflow_template_id' => $template->id,
            'applicant_roles' => ['teacher', 'hod'],
            'allow_multiple_active' => false,
            'is_active' => true,
        ]);
    }

    /** Direct-to-officer, no HOD step — matches "some applications don't need HOD approval". */
    private function seedTransportPass(Office $transportOffice): void
    {
        $template = WorkflowTemplate::firstOrCreate(['name' => 'Transport Pass Workflow'], ['is_active' => true]);

        if ($template->steps()->count() === 0) {
            WorkflowStep::create([
                'workflow_template_id' => $template->id,
                'step_order' => 1,
                'name' => 'Transport Officer',
                'approver_type' => 'office',
                'approver_office_id' => $transportOffice->id,
                'on_reject_action' => 'terminate',
                'allow_forward' => false,
            ]);
        }

        ApplicationCategory::firstOrCreate(['name' => 'Transport Pass'], [
            'description' => 'Request a campus transport pass.',
            'form_schema' => [
                ['key' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => true],
                ['key' => 'pickup_point', 'label' => 'Pickup point', 'type' => 'text', 'required' => true],
            ],
            'workflow_template_id' => $template->id,
            'applicant_roles' => null,
            'allow_multiple_active' => false,
            'is_active' => true,
        ]);
    }

    private function makeOfficial(string $name, string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password', 'role' => 'teacher', 'status' => 'active'],
        );
    }
}
