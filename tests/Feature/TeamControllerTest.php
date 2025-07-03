<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Team;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TeamControllerTest extends TestCase
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
    public function it_can_list_teams()
    {
        $teams = Team::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/teams');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'workspace_id',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_team()
    {
        $team = Team::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/teams/{$team->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                    'workspace_id' => $team->workspace_id
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_team()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/teams/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_a_team()
    {
        $teamData = [
            'name' => $this->faker->company,
            'description' => $this->faker->sentence,
            'workspace_id' => $this->workspace->id
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/teams', $teamData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => $teamData['name'],
                    'description' => $teamData['description'],
                    'workspace_id' => $teamData['workspace_id']
                ]
            ]);

        $this->assertDatabaseHas('teams', $teamData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_team()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/teams', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'workspace_id']);
    }

    /** @test */
    public function it_can_update_a_team()
    {
        $team = Team::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $updateData = [
            'name' => 'Updated Team Name',
            'description' => 'Updated description'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/teams/{$team->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $team->id,
                    'name' => $updateData['name'],
                    'description' => $updateData['description']
                ]
            ]);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => $updateData['name'],
            'description' => $updateData['description']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_team()
    {
        $updateData = [
            'name' => 'Updated Team Name',
            'description' => 'Updated description'
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/teams/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_a_team()
    {
        $team = Team::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/teams/{$team->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Team deleted successfully']);

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_team()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/teams/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_add_member_to_team()
    {
        $team = Team::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);
        
        $member = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/teams/{$team->id}/members", [
                'user_id' => $member->id
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Member added to team successfully']);

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id
        ]);
    }

    /** @test */
    public function it_can_remove_member_from_team()
    {
        $team = Team::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);
        
        $member = User::factory()->create();
        $team->users()->attach($member->id);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/teams/{$team->id}/members/{$member->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Member removed from team successfully']);

        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id
        ]);
    }

    /** @test */
    public function it_can_get_team_members()
    {
        $team = Team::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);
        
        $members = User::factory()->count(3)->create();
        $team->users()->attach($members->pluck('id'));

        $response = $this->actingAs($this->user)
            ->getJson("/api/teams/{$team->id}/members");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/teams');
        $response->assertStatus(401);
    }
} 