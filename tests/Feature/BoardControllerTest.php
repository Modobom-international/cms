<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_boards()
    {
        Board::factory()->count(3)->create();
        $this->user->boards()->attach(Board::all()->pluck('id')->toArray());
        $response = $this->getJson('/api/boards');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_board_success',
            ]);
    }

    public function test_index_with_search_filter()
    {
        Board::factory()->create(['name' => 'Test Board']);
        $this->user->boards()->attach(Board::all()->pluck('id')->toArray());
        $response = $this->getJson('/api/boards?search=Test');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_board_success',
            ]);
    }

    public function test_store_creates_board_successfully()
    {
        $workspace = Workspace::factory()->create();
        $this->user->workspaces()->attach($workspace->id);
        $data = [
            'name' => 'Test Board',
            'description' => 'Test Description',
            'workspace_id' => $workspace->id,
        ];
        $response = $this->postJson('/api/boards', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_board_success',
            ]);
        $this->assertDatabaseHas('boards', ['name' => 'Test Board']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/boards', []);
        $response->assertStatus(422);
    }

    public function test_store_returns_403_if_unauthorized()
    {
        $workspace = Workspace::factory()->create();
        $data = [
            'name' => 'Test Board',
            'workspace_id' => $workspace->id,
        ];
        $response = $this->postJson('/api/boards', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_show_returns_board_detail()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $response = $this->getJson('/api/boards/' . $board->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_board_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/boards/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'board_not_found',
            ]);
    }

    public function test_show_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $response = $this->getJson('/api/boards/' . $board->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_update_board_successfully()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $data = [
            'name' => 'Updated Board',
            'description' => 'Updated Description',
        ];
        $response = $this->putJson('/api/boards/' . $board->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_board_success',
            ]);
        $this->assertDatabaseHas('boards', ['name' => 'Updated Board']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'name' => 'Updated Board',
        ];
        $response = $this->putJson('/api/boards/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'board_not_found',
            ]);
    }

    public function test_update_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $data = [
            'name' => 'Updated Board',
        ];
        $response = $this->putJson('/api/boards/' . $board->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_destroy_deletes_board_successfully()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $response = $this->deleteJson('/api/boards/' . $board->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_board_success',
            ]);
        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/boards/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'board_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $response = $this->deleteJson('/api/boards/' . $board->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_add_member_successfully()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $member = User::factory()->create();
        $data = [
            'user_id' => $member->id,
            'role' => 'member',
        ];
        $response = $this->postJson('/api/boards/' . $board->id . '/add-member', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'add_member_success',
            ]);
    }

    public function test_add_member_returns_404_if_board_not_found()
    {
        $member = User::factory()->create();
        $data = [
            'user_id' => $member->id,
            'role' => 'member',
        ];
        $response = $this->postJson('/api/boards/9999/add-member', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'board_not_found',
            ]);
    }

    public function test_add_member_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $member = User::factory()->create();
        $data = [
            'user_id' => $member->id,
            'role' => 'member',
        ];
        $response = $this->postJson('/api/boards/' . $board->id . '/add-member', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_remove_member_successfully()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $member = User::factory()->create();
        $board->users()->attach($member->id);
        $response = $this->deleteJson('/api/boards/' . $board->id . '/remove-member/' . $member->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'remove_member_success',
            ]);
    }

    public function test_remove_member_returns_404_if_board_not_found()
    {
        $member = User::factory()->create();
        $response = $this->deleteJson('/api/boards/9999/remove-member/' . $member->id);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'board_not_found',
            ]);
    }

    public function test_remove_member_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $member = User::factory()->create();
        $board->users()->attach($member->id);
        $response = $this->deleteJson('/api/boards/' . $board->id . '/remove-member/' . $member->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_index_with_pagination()
    {
        Board::factory()->count(15)->create();
        $this->user->boards()->attach(Board::all()->pluck('id')->toArray());
        $response = $this->getJson('/api/boards?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_board_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_returns_empty_list_when_no_boards()
    {
        $response = $this->getJson('/api/boards');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_board_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }
} 