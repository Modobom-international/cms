<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_notifications()
    {
        $notifications = Notification::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'title',
                        'message',
                        'type',
                        'is_read',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_notification()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $notification->id,
                    'user_id' => $notification->user_id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'is_read' => $notification->is_read
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_notification()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $notification->id,
                    'is_read' => true
                ]
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true
        ]);
    }

    /** @test */
    public function it_can_mark_notification_as_unread()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/notifications/{$notification->id}/unread");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $notification->id,
                    'is_read' => false
                ]
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => false
        ]);
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson(['message' => 'All notifications marked as read']);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'is_read' => false
        ]);
    }

    /** @test */
    public function it_can_delete_a_notification()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Notification deleted successfully']);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_notification()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/notifications/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_get_unread_notifications()
    {
        // Create unread notifications
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        // Create read notifications
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_read' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/unread');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_notifications_by_type()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'alert'
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'type' => 'info'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/type/alert');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_notification_count()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_read' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/count');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total' => 5,
                    'unread' => 3,
                    'read' => 2
                ]
            ]);
    }

    /** @test */
    public function it_can_clear_all_notifications()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/notifications/clear-all');

        $response->assertStatus(200)
            ->assertJson(['message' => 'All notifications cleared']);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_get_recent_notifications()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/recent');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_notifications()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_notifications_by_date_range()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2)
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(10)
        ]);

        $startDate = now()->subDays(5)->toDateString();
        $endDate = now()->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/notifications/range?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_search_notifications()
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Important Update',
            'message' => 'Critical system update required'
        ]);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Welcome Message',
            'message' => 'Welcome to our platform'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/search?q=important');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_get_notification_statistics()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'type' => 'alert',
            'is_read' => false
        ]);

        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'info',
            'is_read' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_notifications',
                    'unread_count',
                    'read_count',
                    'notifications_by_type',
                    'recent_activity'
                ]
            ]);
    }
} 