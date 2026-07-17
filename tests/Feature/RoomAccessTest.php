<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoomAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_list_rooms_to_pick_a_session_override(): void
    {
        Room::create(['room_no' => 'R-101', 'beacon_major' => 1001, 'beacon_uuid' => '550E8400-E29B-41D4-A716-446655440000', 'rssi_threshold' => -70]);

        Sanctum::actingAs(User::factory()->create(['role' => 'teacher']));
        $response = $this->getJson('/api/rooms');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_hod_can_list_rooms(): void
    {
        Room::create(['room_no' => 'R-102', 'beacon_major' => 1002, 'beacon_uuid' => '550E8400-E29B-41D4-A716-446655440001', 'rssi_threshold' => -70]);

        Sanctum::actingAs(User::factory()->create(['role' => 'hod']));
        $response = $this->getJson('/api/rooms');

        $response->assertOk();
    }

    public function test_student_cannot_list_rooms(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'student']));
        $response = $this->getJson('/api/rooms');

        $response->assertStatus(403);
    }
}
