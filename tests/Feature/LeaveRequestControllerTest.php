<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_leave_requests()
    {
        LeaveRequest::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/leave-requests');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_leave_request_success',
            ]);
    }

    public function test_index_with_filters()
    {
        LeaveRequest::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/leave-requests?status=pending&date_from=2024-01-01&date_to=2024-12-31');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_leave_request_success',
            ]);
    }

    public function test_store_creates_leave_request_successfully()
    {
        $data = [
            'user_id' => $this->user->id,
            'leave_type' => 'annual',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'),
            'reason' => 'Vacation',
            'status' => 'pending',
        ];
        $response = $this->postJson('/api/leave-requests', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_leave_request_success',
            ]);
        $this->assertDatabaseHas('leave_requests', ['user_id' => $this->user->id]);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/leave-requests', []);
        $response->assertStatus(422);
    }

    public function test_store_fails_with_invalid_dates()
    {
        $data = [
            'user_id' => $this->user->id,
            'leave_type' => 'annual',
            'start_date' => now()->addDays(3)->format('Y-m-d'),
            'end_date' => now()->addDays(1)->format('Y-m-d'), // End date before start date
            'reason' => 'Vacation',
        ];
        $response = $this->postJson('/api/leave-requests', $data);
        $response->assertStatus(422);
    }

    public function test_show_returns_leave_request_detail()
    {
        $leaveRequest = LeaveRequest::factory()->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/leave-requests/' . $leaveRequest->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_leave_request_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/leave-requests/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'leave_request_not_found',
            ]);
    }

    public function test_update_leave_request_successfully()
    {
        $leaveRequest = LeaveRequest::factory()->create(['user_id' => $this->user->id]);
        $data = [
            'reason' => 'Updated reason',
            'start_date' => now()->addDays(2)->format('Y-m-d'),
            'end_date' => now()->addDays(4)->format('Y-m-d'),
        ];
        $response = $this->putJson('/api/leave-requests/' . $leaveRequest->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_leave_request_success',
            ]);
        $this->assertDatabaseHas('leave_requests', ['reason' => 'Updated reason']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'reason' => 'Updated reason',
        ];
        $response = $this->putJson('/api/leave-requests/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'leave_request_not_found',
            ]);
    }

    public function test_update_returns_403_if_not_owner()
    {
        $otherUser = User::factory()->create();
        $leaveRequest = LeaveRequest::factory()->create(['user_id' => $otherUser->id]);
        $data = [
            'reason' => 'Updated reason',
        ];
        $response = $this->putJson('/api/leave-requests/' . $leaveRequest->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_destroy_deletes_leave_request_successfully()
    {
        $leaveRequest = LeaveRequest::factory()->create(['user_id' => $this->user->id]);
        $response = $this->deleteJson('/api/leave-requests/' . $leaveRequest->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_leave_request_success',
            ]);
        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveRequest->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/leave-requests/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'leave_request_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_not_owner()
    {
        $otherUser = User::factory()->create();
        $leaveRequest = LeaveRequest::factory()->create(['user_id' => $otherUser->id]);
        $response = $this->deleteJson('/api/leave-requests/' . $leaveRequest->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_approve_leave_request_successfully()
    {
        $leaveRequest = LeaveRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
        $data = [
            'approved_by' => $this->user->id,
            'approval_notes' => 'Approved',
        ];
        $response = $this->postJson('/api/leave-requests/' . $leaveRequest->id . '/approve', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'approve_leave_request_success',
            ]);
        $this->assertDatabaseHas('leave_requests', ['status' => 'approved']);
    }

    public function test_approve_returns_404_if_not_found()
    {
        $data = [
            'approved_by' => $this->user->id,
            'approval_notes' => 'Approved',
        ];
        $response = $this->postJson('/api/leave-requests/9999/approve', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'leave_request_not_found',
            ]);
    }

    public function test_approve_returns_400_if_already_processed()
    {
        $leaveRequest = LeaveRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
        $data = [
            'approved_by' => $this->user->id,
            'approval_notes' => 'Approved',
        ];
        $response = $this->postJson('/api/leave-requests/' . $leaveRequest->id . '/approve', $data);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'type' => 'leave_request_already_processed',
            ]);
    }

    public function test_reject_leave_request_successfully()
    {
        $leaveRequest = LeaveRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
        $data = [
            'rejected_by' => $this->user->id,
            'rejection_reason' => 'Not enough leave balance',
        ];
        $response = $this->postJson('/api/leave-requests/' . $leaveRequest->id . '/reject', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'reject_leave_request_success',
            ]);
        $this->assertDatabaseHas('leave_requests', ['status' => 'rejected']);
    }

    public function test_reject_returns_404_if_not_found()
    {
        $data = [
            'rejected_by' => $this->user->id,
            'rejection_reason' => 'Not enough leave balance',
        ];
        $response = $this->postJson('/api/leave-requests/9999/reject', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'leave_request_not_found',
            ]);
    }

    public function test_reject_returns_400_if_already_processed()
    {
        $leaveRequest = LeaveRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'rejected',
        ]);
        $data = [
            'rejected_by' => $this->user->id,
            'rejection_reason' => 'Not enough leave balance',
        ];
        $response = $this->postJson('/api/leave-requests/' . $leaveRequest->id . '/reject', $data);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'type' => 'leave_request_already_processed',
            ]);
    }

    public function test_index_with_pagination()
    {
        LeaveRequest::factory()->count(15)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/leave-requests?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_leave_request_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_returns_empty_list_when_no_requests()
    {
        $response = $this->getJson('/api/leave-requests');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_leave_request_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }
} 