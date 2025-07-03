<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Card;
use App\Models\DueDate;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DueDateControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create([
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_list_due_dates()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $dueDates = DueDate::factory()->count(3)->create([
            'card_id' => $card->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/due-dates');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'card_id',
                        'due_date',
                        'reminder_date',
                        'is_completed',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_due_date()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $dueDate = DueDate::factory()->create([
            'card_id' => $card->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/due-dates/{$dueDate->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $dueDate->id,
                    'card_id' => $dueDate->card_id,
                    'due_date' => $dueDate->due_date,
                    'reminder_date' => $dueDate->reminder_date,
                    'is_completed' => $dueDate->is_completed
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_due_date()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/due-dates/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_a_due_date()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $dueDateData = [
            'card_id' => $card->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'reminder_date' => now()->addDays(5)->toDateString(),
            'is_completed' => false
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/due-dates', $dueDateData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'card_id' => $dueDateData['card_id'],
                    'due_date' => $dueDateData['due_date'],
                    'reminder_date' => $dueDateData['reminder_date'],
                    'is_completed' => $dueDateData['is_completed']
                ]
            ]);

        $this->assertDatabaseHas('due_dates', $dueDateData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_due_date()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/due-dates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['card_id', 'due_date']);
    }

    /** @test */
    public function it_validates_due_date_is_in_future()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/due-dates', [
                'card_id' => $card->id,
                'due_date' => now()->subDays(1)->toDateString()
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    /** @test */
    public function it_can_update_a_due_date()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $dueDate = DueDate::factory()->create([
            'card_id' => $card->id
        ]);

        $updateData = [
            'due_date' => now()->addDays(14)->toDateString(),
            'reminder_date' => now()->addDays(12)->toDateString(),
            'is_completed' => true
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/due-dates/{$dueDate->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $dueDate->id,
                    'due_date' => $updateData['due_date'],
                    'reminder_date' => $updateData['reminder_date'],
                    'is_completed' => $updateData['is_completed']
                ]
            ]);

        $this->assertDatabaseHas('due_dates', [
            'id' => $dueDate->id,
            'due_date' => $updateData['due_date'],
            'reminder_date' => $updateData['reminder_date'],
            'is_completed' => $updateData['is_completed']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_due_date()
    {
        $updateData = [
            'due_date' => now()->addDays(7)->toDateString()
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/due-dates/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_a_due_date()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $dueDate = DueDate::factory()->create([
            'card_id' => $card->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/due-dates/{$dueDate->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Due date deleted successfully']);

        $this->assertDatabaseMissing('due_dates', ['id' => $dueDate->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_due_date()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/due-dates/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_mark_due_date_as_completed()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $dueDate = DueDate::factory()->create([
            'card_id' => $card->id,
            'is_completed' => false
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/due-dates/{$dueDate->id}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $dueDate->id,
                    'is_completed' => true
                ]
            ]);

        $this->assertDatabaseHas('due_dates', [
            'id' => $dueDate->id,
            'is_completed' => true
        ]);
    }

    /** @test */
    public function it_can_mark_due_date_as_incomplete()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $dueDate = DueDate::factory()->create([
            'card_id' => $card->id,
            'is_completed' => true
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/due-dates/{$dueDate->id}/incomplete");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $dueDate->id,
                    'is_completed' => false
                ]
            ]);

        $this->assertDatabaseHas('due_dates', [
            'id' => $dueDate->id,
            'is_completed' => false
        ]);
    }

    /** @test */
    public function it_can_get_overdue_due_dates()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        // Create overdue due dates
        DueDate::factory()->count(3)->create([
            'card_id' => $card->id,
            'due_date' => now()->subDays(1)->toDateString(),
            'is_completed' => false
        ]);

        // Create future due dates
        DueDate::factory()->count(2)->create([
            'card_id' => $card->id,
            'due_date' => now()->addDays(1)->toDateString(),
            'is_completed' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/due-dates/overdue');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_upcoming_due_dates()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        // Create upcoming due dates
        DueDate::factory()->count(3)->create([
            'card_id' => $card->id,
            'due_date' => now()->addDays(1)->toDateString(),
            'is_completed' => false
        ]);

        // Create past due dates
        DueDate::factory()->count(2)->create([
            'card_id' => $card->id,
            'due_date' => now()->subDays(1)->toDateString(),
            'is_completed' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/due-dates/upcoming');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_due_dates_by_card()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $dueDates = DueDate::factory()->count(3)->create([
            'card_id' => $card->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/cards/{$card->id}/due-dates");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'card_id',
                        'due_date',
                        'reminder_date',
                        'is_completed',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_get_due_date_statistics()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        // Create various due dates
        DueDate::factory()->count(5)->create([
            'card_id' => $card->id,
            'is_completed' => true
        ]);

        DueDate::factory()->count(3)->create([
            'card_id' => $card->id,
            'is_completed' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/due-dates/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_due_dates',
                    'completed_due_dates',
                    'overdue_due_dates',
                    'upcoming_due_dates',
                    'completion_rate'
                ]
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/due-dates');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_due_dates()
    {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $otherCard = Card::factory()->create([
            'workspace_id' => $otherWorkspace->id
        ]);
        $dueDate = DueDate::factory()->create([
            'card_id' => $otherCard->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/due-dates/{$dueDate->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_bulk_update_due_dates()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $dueDates = DueDate::factory()->count(3)->create([
            'card_id' => $card->id,
            'is_completed' => false
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/due-dates/bulk-complete', [
                'due_date_ids' => $dueDates->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Due dates updated successfully']);

        foreach ($dueDates as $dueDate) {
            $this->assertDatabaseHas('due_dates', [
                'id' => $dueDate->id,
                'is_completed' => true
            ]);
        }
    }
} 