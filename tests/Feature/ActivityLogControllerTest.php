<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_activity_logs()
    {
        ActivityLog::factory()->count(3)->create();
        $response = $this->getJson('/api/activity-logs');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_activity_log_success',
            ]);
    }

    public function test_index_with_filters()
    {
        ActivityLog::factory()->count(3)->create();
        $response = $this->getJson('/api/activity-logs?date_from=2024-01-01&date_to=2024-12-31&user_id=1');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_activity_log_success',
            ]);
    }

    public function test_index_with_invalid_filters()
    {
        $response = $this->getJson('/api/activity-logs?date_from=invalid-date');
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'type' => 'list_activity_log_validation_error',
            ]);
    }

    public function test_get_activity_stats_successfully()
    {
        ActivityLog::factory()->count(5)->create();
        $response = $this->getJson('/api/activity-logs/stats');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_activity_stats_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'total_activities',
                    'actions_by_group',
                    'top_users',
                    'daily_activities',
                    'filters_applied'
                ]
            ]);
    }

    public function test_get_activity_stats_with_filters()
    {
        ActivityLog::factory()->count(5)->create();
        $response = $this->getJson('/api/activity-logs/stats?date_from=2024-01-01&date_to=2024-12-31&user_id=1');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_activity_stats_success',
            ]);
    }

    public function test_get_activity_stats_with_invalid_filters()
    {
        $response = $this->getJson('/api/activity-logs/stats?date_from=invalid-date');
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'type' => 'get_activity_stats_validation_error',
            ]);
    }

    public function test_get_available_filters_successfully()
    {
        $response = $this->getJson('/api/activity-logs/filters');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_available_filters_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'users',
                    'actions',
                    'date_ranges'
                ]
            ]);
    }

    public function test_index_returns_empty_list_when_no_logs()
    {
        $response = $this->getJson('/api/activity-logs');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_activity_log_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }

    public function test_index_with_pagination()
    {
        ActivityLog::factory()->count(15)->create();
        $response = $this->getJson('/api/activity-logs?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_activity_log_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_get_activity_stats_returns_zero_when_no_logs()
    {
        $response = $this->getJson('/api/activity-logs/stats');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_activity_stats_success',
            ]);
        $this->assertEquals(0, $response->json('data.total_activities'));
    }

    public function test_index_with_multiple_user_ids()
    {
        ActivityLog::factory()->count(3)->create();
        $response = $this->getJson('/api/activity-logs?user_id=1,2,3');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_activity_log_success',
            ]);
    }
} 