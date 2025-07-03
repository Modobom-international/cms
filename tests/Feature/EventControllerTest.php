<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_events()
    {
        $events = Event::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'start_date',
                        'end_date',
                        'location',
                        'user_id',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_an_event()
    {
        $event = Event::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'location' => $event->location,
                    'user_id' => $event->user_id
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_event()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/events/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_an_event()
    {
        $eventData = [
            'title' => 'Team Meeting',
            'description' => 'Weekly team sync meeting',
            'start_date' => now()->addDays(1)->toDateTimeString(),
            'end_date' => now()->addDays(1)->addHours(1)->toDateTimeString(),
            'location' => 'Conference Room A',
            'user_id' => $this->user->id
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/events', $eventData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => $eventData['title'],
                    'description' => $eventData['description'],
                    'start_date' => $eventData['start_date'],
                    'end_date' => $eventData['end_date'],
                    'location' => $eventData['location'],
                    'user_id' => $eventData['user_id']
                ]
            ]);

        $this->assertDatabaseHas('events', $eventData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_event()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/events', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'start_date', 'end_date']);
    }

    /** @test */
    public function it_validates_end_date_is_after_start_date()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/events', [
                'title' => 'Test Event',
                'start_date' => now()->addDays(2)->toDateTimeString(),
                'end_date' => now()->addDays(1)->toDateTimeString()
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_can_update_an_event()
    {
        $event = Event::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'title' => 'Updated Event Title',
            'description' => 'Updated event description',
            'location' => 'Updated Location'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/events/{$event->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $event->id,
                    'title' => $updateData['title'],
                    'description' => $updateData['description'],
                    'location' => $updateData['location']
                ]
            ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => $updateData['title'],
            'description' => $updateData['description'],
            'location' => $updateData['location']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_event()
    {
        $updateData = [
            'title' => 'Updated Title'
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/events/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_an_event()
    {
        $event = Event::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Event deleted successfully']);

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_event()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/events/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_get_upcoming_events()
    {
        // Create upcoming events
        Event::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'start_date' => now()->addDays(1)
        ]);

        // Create past events
        Event::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'start_date' => now()->subDays(1)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/events/upcoming');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_events_by_date_range()
    {
        Event::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'start_date' => now()->addDays(5)
        ]);

        Event::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'start_date' => now()->addDays(15)
        ]);

        $startDate = now()->addDays(1)->toDateString();
        $endDate = now()->addDays(10)->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/events/range?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_events_by_user()
    {
        Event::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $otherUser = User::factory()->create();
        Event::factory()->count(2)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}/events");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/events');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_events()
    {
        $otherUser = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/events/{$event->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_search_events()
    {
        Event::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Important Meeting',
            'description' => 'Critical project discussion'
        ]);

        Event::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Team Lunch',
            'description' => 'Casual team gathering'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/events/search?q=important');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_get_today_events()
    {
        Event::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'start_date' => now()->startOfDay()
        ]);

        Event::factory()->count(1)->create([
            'user_id' => $this->user->id,
            'start_date' => now()->addDays(1)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/events/today');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_get_this_week_events()
    {
        Event::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'start_date' => now()->addDays(3)
        ]);

        Event::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'start_date' => now()->addDays(10)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/events/this-week');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
} 