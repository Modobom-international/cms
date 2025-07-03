<?php

namespace Tests\Feature;

use App\Models\ListBoard;
use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListBoardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_lists()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        ListBoard::factory()->count(3)->create(['board_id' => $board->id]);
        $response = $this->getJson('/api/boards/' . $board->id . '/lists');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_lists_success',
            ]);
    }

    public function test_index_returns_404_if_board_not_found()
    {
        $response = $this->getJson('/api/boards/9999/lists');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'board_not_found',
            ]);
    }

    public function test_index_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        ListBoard::factory()->count(3)->create(['board_id' => $board->id]);
        $response = $this->getJson('/api/boards/' . $board->id . '/lists');
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_index_returns_empty_list_when_no_lists()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $response = $this->getJson('/api/boards/' . $board->id . '/lists');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'board_empty',
            ]);
        $this->assertEmpty($response->json('data'));
    }

    public function test_store_creates_list_successfully()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $data = [
            'name' => 'Test List',
            'position' => 1,
        ];
        $response = $this->postJson('/api/lists', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_list_success',
            ]);
        $this->assertDatabaseHas('list_boards', ['name' => 'Test List']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/lists', []);
        $response->assertStatus(422);
    }

    public function test_store_returns_403_if_unauthorized()
    {
        $data = [
            'name' => 'Test List',
        ];
        $response = $this->postJson('/api/lists', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_show_returns_list_detail()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $response = $this->getJson('/api/lists/' . $listBoard->id);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'listBoard_information',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/lists/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'listBoard_not_found',
            ]);
    }

    public function test_update_list_successfully()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $data = [
            'name' => 'Updated List',
            'position' => 2,
        ];
        $response = $this->putJson('/api/lists/' . $listBoard->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_list_success',
            ]);
        $this->assertDatabaseHas('list_boards', ['name' => 'Updated List']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'name' => 'Updated List',
        ];
        $response = $this->putJson('/api/lists/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'listBoard_not_found',
            ]);
    }

    public function test_update_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $data = [
            'name' => 'Updated List',
        ];
        $response = $this->putJson('/api/lists/' . $listBoard->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_destroy_deletes_list_successfully()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $response = $this->deleteJson('/api/lists/' . $listBoard->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_list_success',
            ]);
        $this->assertDatabaseMissing('list_boards', ['id' => $listBoard->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/lists/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'listBoard_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $response = $this->deleteJson('/api/lists/' . $listBoard->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_update_positions_successfully()
    {
        $board = Board::factory()->create();
        $this->user->boards()->attach($board->id);
        $lists = ListBoard::factory()->count(3)->create(['board_id' => $board->id]);
        $data = [
            'lists' => $lists->map(function ($list, $index) {
                return [
                    'id' => $list->id,
                    'position' => $index + 1,
                ];
            })->toArray(),
        ];
        $response = $this->putJson('/api/lists/update-positions', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_positions_success',
            ]);
    }

    public function test_update_positions_returns_403_if_unauthorized()
    {
        $lists = ListBoard::factory()->count(3)->create();
        $data = [
            'lists' => $lists->map(function ($list, $index) {
                return [
                    'id' => $list->id,
                    'position' => $index + 1,
                ];
            })->toArray(),
        ];
        $response = $this->putJson('/api/lists/update-positions', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }
} 