<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Board;
use App\Models\Card;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_dashboard_data()
    {
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'dashboard_data_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'stats',
                    'recent_activity',
                    'upcoming_tasks',
                    'quick_actions'
                ]
            ]);
    }

    public function test_index_returns_401_when_not_authenticated()
    {
        $this->withoutMiddleware();
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(401);
    }

    public function test_get_stats_returns_dashboard_statistics()
    {
        // Create some test data
        Board::factory()->count(3)->create();
        $this->user->boards()->attach(Board::all()->pluck('id')->toArray());
        
        Card::factory()->count(5)->create();
        ActivityLog::factory()->count(10)->create(['user_id' => $this->user->id]);
        Attendance::factory()->count(7)->create(['user_id' => $this->user->id]);
        LeaveRequest::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/dashboard/stats');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'dashboard_stats_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'total_boards',
                    'total_cards',
                    'total_activities',
                    'attendance_stats',
                    'leave_stats',
                    'completion_rate'
                ]
            ]);
    }

    public function test_get_stats_with_date_filters()
    {
        $response = $this->getJson('/api/dashboard/stats?date_from=2024-01-01&date_to=2024-12-31');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'dashboard_stats_success',
            ]);
    }

    public function test_get_stats_with_invalid_date_filters()
    {
        $response = $this->getJson('/api/dashboard/stats?date_from=invalid-date');
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'type' => 'validation_error',
            ]);
    }

    public function test_get_recent_activity_returns_activity_list()
    {
        ActivityLog::factory()->count(10)->create(['user_id' => $this->user->id]);
        
        $response = $this->getJson('/api/dashboard/recent-activity');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'recent_activity_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'activities',
                    'total_count'
                ]
            ]);
    }

    public function test_get_recent_activity_with_limit()
    {
        ActivityLog::factory()->count(15)->create(['user_id' => $this->user->id]);
        
        $response = $this->getJson('/api/dashboard/recent-activity?limit=5');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'recent_activity_success',
            ]);
        $this->assertCount(5, $response->json('data.activities'));
    }

    public function test_get_recent_activity_with_filters()
    {
        ActivityLog::factory()->count(10)->create(['user_id' => $this->user->id]);
        
        $response = $this->getJson('/api/dashboard/recent-activity?type=card&date_from=2024-01-01');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'recent_activity_success',
            ]);
    }

    public function test_get_recent_activity_returns_empty_when_no_activities()
    {
        $response = $this->getJson('/api/dashboard/recent-activity');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'recent_activity_success',
            ]);
        $this->assertEmpty($response->json('data.activities'));
    }

    public function test_get_upcoming_tasks_returns_task_list()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        Card::factory()->count(5)->create([
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/dashboard/upcoming-tasks');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'upcoming_tasks_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'tasks',
                    'total_count'
                ]
            ]);
    }

    public function test_get_upcoming_tasks_with_limit()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        Card::factory()->count(10)->create([
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/dashboard/upcoming-tasks?limit=3');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'upcoming_tasks_success',
            ]);
        $this->assertCount(3, $response->json('data.tasks'));
    }

    public function test_get_upcoming_tasks_returns_empty_when_no_tasks()
    {
        $response = $this->getJson('/api/dashboard/upcoming-tasks');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'upcoming_tasks_success',
            ]);
        $this->assertEmpty($response->json('data.tasks'));
    }

    public function test_get_quick_actions_returns_available_actions()
    {
        $response = $this->getJson('/api/dashboard/quick-actions');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'quick_actions_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'actions'
                ]
            ]);
    }

    public function test_get_stats_returns_zero_when_no_data()
    {
        $response = $this->getJson('/api/dashboard/stats');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'dashboard_stats_success',
            ]);
        
        $data = $response->json('data');
        $this->assertEquals(0, $data['total_boards']);
        $this->assertEquals(0, $data['total_cards']);
        $this->assertEquals(0, $data['total_activities']);
    }

    public function test_get_stats_with_user_specific_data()
    {
        $otherUser = User::factory()->create();
        
        // Create data for other user
        Board::factory()->count(5)->create();
        $otherUser->boards()->attach(Board::all()->pluck('id')->toArray());
        ActivityLog::factory()->count(10)->create(['user_id' => $otherUser->id]);
        
        // Create data for current user
        $userBoard = Board::factory()->create();
        $this->user->boards()->attach($userBoard->id);
        ActivityLog::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/dashboard/stats');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'dashboard_stats_success',
            ]);
        
        $data = $response->json('data');
        $this->assertEquals(1, $data['total_boards']); // Only user's boards
        $this->assertEquals(3, $data['total_activities']); // Only user's activities
    }
} 