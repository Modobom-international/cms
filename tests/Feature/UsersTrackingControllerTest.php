<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UsersTrackingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_user_tracking_records()
    {
        $trackingRecords = UserTracking::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users-tracking');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'ip_address',
                        'user_agent',
                        'page_visited',
                        'session_duration',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_tracking_record()
    {
        $trackingRecord = UserTracking::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users-tracking/{$trackingRecord->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $trackingRecord->id,
                    'user_id' => $trackingRecord->user_id,
                    'ip_address' => $trackingRecord->ip_address,
                    'user_agent' => $trackingRecord->user_agent,
                    'page_visited' => $trackingRecord->page_visited
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_tracking_record()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/users-tracking/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_a_tracking_record()
    {
        $trackingData = [
            'user_id' => $this->user->id,
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'page_visited' => '/dashboard',
            'session_duration' => 300
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/users-tracking', $trackingData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'user_id' => $trackingData['user_id'],
                    'ip_address' => $trackingData['ip_address'],
                    'user_agent' => $trackingData['user_agent'],
                    'page_visited' => $trackingData['page_visited'],
                    'session_duration' => $trackingData['session_duration']
                ]
            ]);

        $this->assertDatabaseHas('user_trackings', $trackingData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_tracking_record()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/users-tracking', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'ip_address']);
    }

    /** @test */
    public function it_can_update_a_tracking_record()
    {
        $trackingRecord = UserTracking::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'page_visited' => '/updated-page',
            'session_duration' => 600
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/users-tracking/{$trackingRecord->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $trackingRecord->id,
                    'page_visited' => $updateData['page_visited'],
                    'session_duration' => $updateData['session_duration']
                ]
            ]);

        $this->assertDatabaseHas('user_trackings', [
            'id' => $trackingRecord->id,
            'page_visited' => $updateData['page_visited'],
            'session_duration' => $updateData['session_duration']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_tracking_record()
    {
        $updateData = [
            'page_visited' => '/updated-page'
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/users-tracking/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_a_tracking_record()
    {
        $trackingRecord = UserTracking::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/users-tracking/{$trackingRecord->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Tracking record deleted successfully']);

        $this->assertDatabaseMissing('user_trackings', ['id' => $trackingRecord->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_tracking_record()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/users-tracking/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_get_user_activity_summary()
    {
        UserTracking::factory()->count(10)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users-tracking/user/{$this->user->id}/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_sessions',
                    'total_duration',
                    'most_visited_pages',
                    'average_session_duration',
                    'last_activity'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_user_session_history()
    {
        UserTracking::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users-tracking/user/{$this->user->id}/sessions");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'ip_address',
                        'user_agent',
                        'page_visited',
                        'session_duration',
                        'created_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_get_tracking_analytics()
    {
        $params = [
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->toDateString(),
            'group_by' => 'day'
        ];

        $response = $this->actingAs($this->user)
            ->getJson('/api/users-tracking/analytics?' . http_build_query($params));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_users',
                    'total_sessions',
                    'average_session_duration',
                    'most_active_users',
                    'page_views_by_date'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_real_time_activity()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/users-tracking/realtime');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'active_users',
                    'current_sessions',
                    'recent_activity'
                ]
            ]);
    }

    /** @test */
    public function it_can_export_tracking_data()
    {
        UserTracking::factory()->count(10)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users-tracking/export?format=csv');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv');
    }

    /** @test */
    public function it_can_filter_tracking_records_by_date_range()
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        UserTracking::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(3)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users-tracking?start_date={$startDate->toDateString()}&end_date={$endDate->toDateString()}");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_can_filter_tracking_records_by_user()
    {
        $otherUser = User::factory()->create();
        
        UserTracking::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);
        
        UserTracking::factory()->count(2)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users-tracking?user_id={$this->user->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/users-tracking');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_other_user_data()
    {
        $otherUser = User::factory()->create();
        $trackingRecord = UserTracking::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users-tracking/{$trackingRecord->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_page_visit_statistics()
    {
        UserTracking::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'page_visited' => '/dashboard'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users-tracking/pages/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'most_visited_pages',
                    'page_views_count',
                    'average_time_on_page'
                ]
            ]);
    }
} 