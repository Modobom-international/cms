<?php

namespace Tests\Feature;

use App\Models\Workspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_workspaces()
    {
        Workspace::factory()->count(3)->create();
        $this->user->workspaces()->attach(Workspace::all()->pluck('id')->toArray());
        $response = $this->getJson('/api/workspaces');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_workspace_success',
            ]);
    }

    public function test_index_with_search_filter()
    {
        Workspace::factory()->create(['name' => 'Test Workspace']);
        $this->user->workspaces()->attach(Workspace::all()->pluck('id')->toArray());
        $response = $this->getJson('/api/workspaces?search=Test');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_workspace_success',
            ]);
    }

    public function test_store_creates_workspace_successfully()
    {
        $data = [
            'name' => 'Test Workspace',
            'description' => 'Test Description',
        ];
        $response = $this->postJson('/api/workspaces', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_workspace_success',
            ]);
        $this->assertDatabaseHas('workspaces', ['name' => 'Test Workspace']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/workspaces', []);
        $response->assertStatus(422);
    }

    public function test_show_returns_workspace_detail()
    {
        $workspace = Workspace::factory()->create();
        $this->user->workspaces()->attach($workspace->id);
        $response = $this->getJson('/api/workspaces/' . $workspace->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_workspace_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/workspaces/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'workspace_not_found',
            ]);
    }

    public function test_show_returns_403_if_unauthorized()
    {
        $workspace = Workspace::factory()->create();
        $response = $this->getJson('/api/workspaces/' . $workspace->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_update_workspace_successfully()
    {
        $workspace = Workspace::factory()->create();
        $this->user->workspaces()->attach($workspace->id);
        $data = [
            'name' => 'Updated Workspace',
            'description' => 'Updated Description',
        ];
        $response = $this->putJson('/api/workspaces/' . $workspace->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_workspace_success',
            ]);
        $this->assertDatabaseHas('workspaces', ['name' => 'Updated Workspace']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'name' => 'Updated Workspace',
        ];
        $response = $this->putJson('/api/workspaces/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'workspace_not_found',
            ]);
    }

    public function test_update_returns_403_if_unauthorized()
    {
        $workspace = Workspace::factory()->create();
        $data = [
            'name' => 'Updated Workspace',
        ];
        $response = $this->putJson('/api/workspaces/' . $workspace->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_destroy_deletes_workspace_successfully()
    {
        $workspace = Workspace::factory()->create();
        $this->user->workspaces()->attach($workspace->id);
        $response = $this->deleteJson('/api/workspaces/' . $workspace->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_workspace_success',
            ]);
        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/workspaces/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'workspace_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_unauthorized()
    {
        $workspace = Workspace::factory()->create();
        $response = $this->deleteJson('/api/workspaces/' . $workspace->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_add_member_successfully()
    {
        $workspace = Workspace::factory()->create();
        $this->user->workspaces()->attach($workspace->id);
        $member = User::factory()->create();
        $data = [
            'user_id' => $member->id,
            'role' => 'member',
        ];
        $response = $this->postJson('/api/workspaces/' . $workspace->id . '/add-member', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'add_member_success',
            ]);
    }

    public function test_add_member_returns_404_if_workspace_not_found()
    {
        $member = User::factory()->create();
        $data = [
            'user_id' => $member->id,
            'role' => 'member',
        ];
        $response = $this->postJson('/api/workspaces/9999/add-member', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'workspace_not_found',
            ]);
    }

    public function test_add_member_returns_403_if_unauthorized()
    {
        $workspace = Workspace::factory()->create();
        $member = User::factory()->create();
        $data = [
            'user_id' => $member->id,
            'role' => 'member',
        ];
        $response = $this->postJson('/api/workspaces/' . $workspace->id . '/add-member', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_remove_member_successfully()
    {
        $workspace = Workspace::factory()->create();
        $this->user->workspaces()->attach($workspace->id);
        $member = User::factory()->create();
        $workspace->users()->attach($member->id);
        $response = $this->deleteJson('/api/workspaces/' . $workspace->id . '/remove-member/' . $member->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'remove_member_success',
            ]);
    }

    public function test_remove_member_returns_404_if_workspace_not_found()
    {
        $member = User::factory()->create();
        $response = $this->deleteJson('/api/workspaces/9999/remove-member/' . $member->id);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'workspace_not_found',
            ]);
    }

    public function test_remove_member_returns_403_if_unauthorized()
    {
        $workspace = Workspace::factory()->create();
        $member = User::factory()->create();
        $workspace->users()->attach($member->id);
        $response = $this->deleteJson('/api/workspaces/' . $workspace->id . '/remove-member/' . $member->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_index_with_pagination()
    {
        Workspace::factory()->count(15)->create();
        $this->user->workspaces()->attach(Workspace::all()->pluck('id')->toArray());
        $response = $this->getJson('/api/workspaces?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_workspace_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_returns_empty_list_when_no_workspaces()
    {
        $response = $this->getJson('/api/workspaces');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_workspace_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }
} 