<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_attendance()
    {
        Attendance::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_attendance_success',
            ]);
    }

    public function test_index_with_filters()
    {
        Attendance::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance?date_from=2024-01-01&date_to=2024-12-31&user_id=' . $this->user->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_attendance_success',
            ]);
    }

    public function test_store_creates_attendance_successfully()
    {
        $data = [
            'user_id' => $this->user->id,
            'check_in_time' => now(),
            'check_out_time' => now()->addHours(8),
            'status' => 'present',
        ];
        $response = $this->postJson('/api/attendance', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_attendance_success',
            ]);
        $this->assertDatabaseHas('attendance', ['user_id' => $this->user->id]);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/attendance', []);
        $response->assertStatus(422);
    }

    public function test_show_returns_attendance_detail()
    {
        $attendance = Attendance::factory()->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance/' . $attendance->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_attendance_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/attendance/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_not_found',
            ]);
    }

    public function test_update_attendance_successfully()
    {
        $attendance = Attendance::factory()->create(['user_id' => $this->user->id]);
        $data = [
            'check_out_time' => now()->addHours(9),
            'status' => 'overtime',
        ];
        $response = $this->putJson('/api/attendance/' . $attendance->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_attendance_success',
            ]);
        $this->assertDatabaseHas('attendance', ['status' => 'overtime']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'status' => 'overtime',
        ];
        $response = $this->putJson('/api/attendance/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_not_found',
            ]);
    }

    public function test_destroy_deletes_attendance_successfully()
    {
        $attendance = Attendance::factory()->create(['user_id' => $this->user->id]);
        $response = $this->deleteJson('/api/attendance/' . $attendance->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_attendance_success',
            ]);
        $this->assertDatabaseMissing('attendance', ['id' => $attendance->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/attendance/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_not_found',
            ]);
    }

    public function test_check_in_successfully()
    {
        $data = [
            'user_id' => $this->user->id,
            'location' => 'Office',
            'device_info' => 'Test Device',
        ];
        $response = $this->postJson('/api/attendance/check-in', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'check_in_success',
            ]);
        $this->assertDatabaseHas('attendance', ['user_id' => $this->user->id]);
    }

    public function test_check_in_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/attendance/check-in', []);
        $response->assertStatus(422);
    }

    public function test_check_out_successfully()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in_time' => now()->subHours(8),
            'check_out_time' => null,
        ]);
        $data = [
            'user_id' => $this->user->id,
            'location' => 'Office',
            'device_info' => 'Test Device',
        ];
        $response = $this->postJson('/api/attendance/check-out', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'check_out_success',
            ]);
        $this->assertDatabaseHas('attendance', ['check_out_time' => now()]);
    }

    public function test_check_out_fails_when_no_check_in()
    {
        $data = [
            'user_id' => $this->user->id,
            'location' => 'Office',
        ];
        $response = $this->postJson('/api/attendance/check-out', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'no_check_in_found',
            ]);
    }

    public function test_get_stats_successfully()
    {
        Attendance::factory()->count(5)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance/stats?user_id=' . $this->user->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_attendance_stats_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'total_days',
                    'present_days',
                    'absent_days',
                    'late_days',
                    'overtime_hours'
                ]
            ]);
    }

    public function test_get_stats_with_date_filters()
    {
        Attendance::factory()->count(5)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance/stats?user_id=' . $this->user->id . '&date_from=2024-01-01&date_to=2024-12-31');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_attendance_stats_success',
            ]);
    }

    public function test_index_with_pagination()
    {
        Attendance::factory()->count(15)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_attendance_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_returns_empty_list_when_no_attendance()
    {
        $response = $this->getJson('/api/attendance');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_attendance_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }

    public function test_check_in_fails_when_already_checked_in()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in_time' => now(),
            'check_out_time' => null,
        ]);
        $data = [
            'user_id' => $this->user->id,
            'location' => 'Office',
        ];
        $response = $this->postJson('/api/attendance/check-in', $data);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'type' => 'already_checked_in',
            ]);
    }
} 