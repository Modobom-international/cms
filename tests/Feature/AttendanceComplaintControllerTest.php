<?php

namespace Tests\Feature;

use App\Models\AttendanceComplaint;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceComplaintControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_complaints()
    {
        AttendanceComplaint::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance-complaints');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_attendance_complaint_success',
            ]);
    }

    public function test_index_with_filters()
    {
        AttendanceComplaint::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance-complaints?status=pending&date_from=2024-01-01&date_to=2024-12-31');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_attendance_complaint_success',
            ]);
    }

    public function test_store_creates_complaint_successfully()
    {
        $attendance = Attendance::factory()->create(['user_id' => $this->user->id]);
        $data = [
            'user_id' => $this->user->id,
            'attendance_id' => $attendance->id,
            'complaint_type' => 'late_check_in',
            'reason' => 'System error',
            'status' => 'pending',
        ];
        $response = $this->postJson('/api/attendance-complaints', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_attendance_complaint_success',
            ]);
        $this->assertDatabaseHas('attendance_complaints', ['user_id' => $this->user->id]);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/attendance-complaints', []);
        $response->assertStatus(422);
    }

    public function test_store_fails_with_invalid_attendance_id()
    {
        $data = [
            'user_id' => $this->user->id,
            'attendance_id' => 9999,
            'complaint_type' => 'late_check_in',
            'reason' => 'System error',
        ];
        $response = $this->postJson('/api/attendance-complaints', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_not_found',
            ]);
    }

    public function test_show_returns_complaint_detail()
    {
        $complaint = AttendanceComplaint::factory()->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance-complaints/' . $complaint->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_attendance_complaint_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/attendance-complaints/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_complaint_not_found',
            ]);
    }

    public function test_update_complaint_successfully()
    {
        $complaint = AttendanceComplaint::factory()->create(['user_id' => $this->user->id]);
        $data = [
            'reason' => 'Updated reason',
            'complaint_type' => 'early_check_out',
        ];
        $response = $this->putJson('/api/attendance-complaints/' . $complaint->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_attendance_complaint_success',
            ]);
        $this->assertDatabaseHas('attendance_complaints', ['reason' => 'Updated reason']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'reason' => 'Updated reason',
        ];
        $response = $this->putJson('/api/attendance-complaints/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_complaint_not_found',
            ]);
    }

    public function test_update_returns_403_if_not_owner()
    {
        $otherUser = User::factory()->create();
        $complaint = AttendanceComplaint::factory()->create(['user_id' => $otherUser->id]);
        $data = [
            'reason' => 'Updated reason',
        ];
        $response = $this->putJson('/api/attendance-complaints/' . $complaint->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_destroy_deletes_complaint_successfully()
    {
        $complaint = AttendanceComplaint::factory()->create(['user_id' => $this->user->id]);
        $response = $this->deleteJson('/api/attendance-complaints/' . $complaint->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_attendance_complaint_success',
            ]);
        $this->assertDatabaseMissing('attendance_complaints', ['id' => $complaint->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/attendance-complaints/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_complaint_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_not_owner()
    {
        $otherUser = User::factory()->create();
        $complaint = AttendanceComplaint::factory()->create(['user_id' => $otherUser->id]);
        $response = $this->deleteJson('/api/attendance-complaints/' . $complaint->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_approve_complaint_successfully()
    {
        $complaint = AttendanceComplaint::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
        $data = [
            'approved_by' => $this->user->id,
            'approval_notes' => 'Approved',
        ];
        $response = $this->postJson('/api/attendance-complaints/' . $complaint->id . '/approve', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'approve_attendance_complaint_success',
            ]);
        $this->assertDatabaseHas('attendance_complaints', ['status' => 'approved']);
    }

    public function test_approve_returns_404_if_not_found()
    {
        $data = [
            'approved_by' => $this->user->id,
            'approval_notes' => 'Approved',
        ];
        $response = $this->postJson('/api/attendance-complaints/9999/approve', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_complaint_not_found',
            ]);
    }

    public function test_approve_returns_400_if_already_processed()
    {
        $complaint = AttendanceComplaint::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
        $data = [
            'approved_by' => $this->user->id,
            'approval_notes' => 'Approved',
        ];
        $response = $this->postJson('/api/attendance-complaints/' . $complaint->id . '/approve', $data);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_complaint_already_processed',
            ]);
    }

    public function test_reject_complaint_successfully()
    {
        $complaint = AttendanceComplaint::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
        $data = [
            'rejected_by' => $this->user->id,
            'rejection_reason' => 'Invalid complaint',
        ];
        $response = $this->postJson('/api/attendance-complaints/' . $complaint->id . '/reject', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'reject_attendance_complaint_success',
            ]);
        $this->assertDatabaseHas('attendance_complaints', ['status' => 'rejected']);
    }

    public function test_reject_returns_404_if_not_found()
    {
        $data = [
            'rejected_by' => $this->user->id,
            'rejection_reason' => 'Invalid complaint',
        ];
        $response = $this->postJson('/api/attendance-complaints/9999/reject', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_complaint_not_found',
            ]);
    }

    public function test_reject_returns_400_if_already_processed()
    {
        $complaint = AttendanceComplaint::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'rejected',
        ]);
        $data = [
            'rejected_by' => $this->user->id,
            'rejection_reason' => 'Invalid complaint',
        ];
        $response = $this->postJson('/api/attendance-complaints/' . $complaint->id . '/reject', $data);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'type' => 'attendance_complaint_already_processed',
            ]);
    }

    public function test_index_with_pagination()
    {
        AttendanceComplaint::factory()->count(15)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/attendance-complaints?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_attendance_complaint_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_returns_empty_list_when_no_complaints()
    {
        $response = $this->getJson('/api/attendance-complaints');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_attendance_complaint_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }
} 