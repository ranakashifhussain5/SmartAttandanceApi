<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\ClassSession;
use App\Models\Department;
use App\Models\Notification;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SessionRoomOverrideTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom(string $suffix, bool $withBeacon = true): Room
    {
        return Room::create([
            'room_no' => "R-$suffix",
            'beacon_major' => $withBeacon ? crc32($suffix) % 60000 : null,
            'beacon_uuid' => $withBeacon ? '550E8400-E29B-41D4-A716-44665544'.substr(md5($suffix), 0, 4) : null,
            'rssi_threshold' => -70,
        ]);
    }

    private function makeTimetable(string $suffix, Room $room): array
    {
        $department = Department::create(['name' => "Dept $suffix"]);
        $program = Program::create(['department_id' => $department->id, 'name' => "Prog $suffix", 'code' => "PRG-$suffix"]);
        $course = ProgramCourse::create(['program_id' => $program->id, 'course_code' => "CS-$suffix", 'course_title' => "Course $suffix", 'credit_hours' => 3]);
        $batch = Batch::create(['program_id' => $program->id, 'batch_name' => "Batch $suffix", 'start_year' => 2024, 'end_year' => 2028, 'semester' => 1, 'shift' => 'Morning']);

        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'department_id' => $department->id, 'employee_no' => "EMP-$suffix", 'designation' => 'Lecturer']);

        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'registration_no' => "REG-$suffix", 'department_id' => $department->id, 'batch_id' => $batch->id]);

        $slot = TimeSlot::create(['start_time' => '00:00:00', 'end_time' => '23:59:59']);

        $timetable = Timetable::create([
            'batch_id' => $batch->id,
            'program_course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'day' => now()->format('l'),
            'slot_id' => $slot->id,
        ]);

        return compact('timetable', 'teacherUser', 'studentUser');
    }

    public function test_starting_without_override_uses_timetables_default_room(): void
    {
        $defaultRoom = $this->makeRoom('default');
        ['timetable' => $timetable, 'teacherUser' => $teacherUser, 'studentUser' => $studentUser] = $this->makeTimetable('A', $defaultRoom);

        Sanctum::actingAs($teacherUser);
        $response = $this->postJson("/api/sessions/{$timetable->id}/start");

        $response->assertCreated();
        $session = ClassSession::first();
        $this->assertNull($session->room_id);
        $this->assertSame($defaultRoom->id, $session->effectiveRoom()->id);

        $notification = Notification::where('user_id', $studentUser->id)->first();
        $this->assertStringContainsString($defaultRoom->room_no, $notification->message);
        $this->assertStringNotContainsString('Room changed', $notification->message);
    }

    public function test_teacher_can_override_room_before_starting_session(): void
    {
        $defaultRoom = $this->makeRoom('default2');
        $overrideRoom = $this->makeRoom('override');
        ['timetable' => $timetable, 'teacherUser' => $teacherUser, 'studentUser' => $studentUser] = $this->makeTimetable('B', $defaultRoom);

        Sanctum::actingAs($teacherUser);
        $response = $this->postJson("/api/sessions/{$timetable->id}/start", ['room_id' => $overrideRoom->id]);

        $response->assertCreated();
        $session = ClassSession::first();
        $this->assertSame($overrideRoom->id, $session->room_id);
        $this->assertSame($overrideRoom->id, $session->effectiveRoom()->id);

        // Timetable's normal weekly room must stay untouched.
        $this->assertSame($defaultRoom->id, $timetable->fresh()->room_id);

        $notification = Notification::where('user_id', $studentUser->id)->first();
        $this->assertStringContainsString($overrideRoom->room_no, $notification->message);
        $this->assertStringContainsString('Room changed', $notification->message);
    }

    public function test_override_is_rejected_when_target_room_has_no_beacon(): void
    {
        $defaultRoom = $this->makeRoom('default3');
        $beaconlessRoom = $this->makeRoom('nobeacon', withBeacon: false);
        ['timetable' => $timetable, 'teacherUser' => $teacherUser] = $this->makeTimetable('C', $defaultRoom);

        Sanctum::actingAs($teacherUser);
        $response = $this->postJson("/api/sessions/{$timetable->id}/start", ['room_id' => $beaconlessRoom->id]);

        $response->assertStatus(422);
        $this->assertSame(0, ClassSession::count());
    }

    public function test_override_is_rejected_when_target_room_already_occupied(): void
    {
        $roomInUse = $this->makeRoom('inuse');
        ['timetable' => $busyTimetable] = $this->makeTimetable('D', $roomInUse);
        ClassSession::create([
            'timetable_id' => $busyTimetable->id,
            'session_date' => now()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'status' => 'active',
        ]);

        $defaultRoom = $this->makeRoom('default4');
        ['timetable' => $timetable, 'teacherUser' => $teacherUser] = $this->makeTimetable('E', $defaultRoom);

        Sanctum::actingAs($teacherUser);
        $response = $this->postJson("/api/sessions/{$timetable->id}/start", ['room_id' => $roomInUse->id]);

        $response->assertStatus(409);
    }

    public function test_next_days_session_falls_back_to_default_room_after_a_prior_override(): void
    {
        $defaultRoom = $this->makeRoom('default5');
        $overrideRoom = $this->makeRoom('override2');
        ['timetable' => $timetable, 'teacherUser' => $teacherUser] = $this->makeTimetable('F', $defaultRoom);

        Sanctum::actingAs($teacherUser);
        $this->postJson("/api/sessions/{$timetable->id}/start", ['room_id' => $overrideRoom->id])->assertCreated();

        $firstSession = ClassSession::first();
        $firstSession->update(['status' => 'ended']);
        $firstSession->update(['session_date' => now()->subDay()->toDateString()]);

        $response = $this->postJson("/api/sessions/{$timetable->id}/start");
        $response->assertCreated();

        $secondSession = ClassSession::where('id', '!=', $firstSession->id)->first();
        $this->assertNull($secondSession->room_id);
        $this->assertSame($defaultRoom->id, $secondSession->effectiveRoom()->id);
    }
}
