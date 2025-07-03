<?php

namespace Tests\Feature;

use App\Models\CheckList;
use App\Models\Card;
use App\Models\User;
use App\Models\Board;
use App\Models\ListBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckListControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_checklists()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        CheckList::factory()->count(3)->create(['card_id' => $card->id]);
        $response = $this->getJson('/api/cards/' . $card->id . '/checklists');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_checklist_success',
            ]);
    }

    public function test_index_returns_404_if_card_not_found()
    {
        $response = $this->getJson('/api/cards/9999/checklists');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'card_not_found',
            ]);
    }

    public function test_index_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        CheckList::factory()->count(3)->create(['card_id' => $card->id]);
        $response = $this->getJson('/api/cards/' . $card->id . '/checklists');
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_store_creates_checklist_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $data = [
            'title' => 'Test Checklist',
            'position' => 1,
        ];
        $response = $this->postJson('/api/cards/' . $card->id . '/checklists', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_checklist_success',
            ]);
        $this->assertDatabaseHas('check_lists', ['title' => 'Test Checklist']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $response = $this->postJson('/api/cards/' . $card->id . '/checklists', []);
        $response->assertStatus(422);
    }

    public function test_store_returns_404_if_card_not_found()
    {
        $data = [
            'title' => 'Test Checklist',
        ];
        $response = $this->postJson('/api/cards/9999/checklists', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'card_not_found',
            ]);
    }

    public function test_store_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $data = [
            'title' => 'Test Checklist',
        ];
        $response = $this->postJson('/api/cards/' . $card->id . '/checklists', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_show_returns_checklist_detail()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $response = $this->getJson('/api/checklists/' . $checklist->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_checklist_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/checklists/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'checklist_not_found',
            ]);
    }

    public function test_show_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $response = $this->getJson('/api/checklists/' . $checklist->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_update_checklist_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $data = [
            'title' => 'Updated Checklist',
            'position' => 2,
        ];
        $response = $this->putJson('/api/checklists/' . $checklist->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_checklist_success',
            ]);
        $this->assertDatabaseHas('check_lists', ['title' => 'Updated Checklist']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'title' => 'Updated Checklist',
        ];
        $response = $this->putJson('/api/checklists/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'checklist_not_found',
            ]);
    }

    public function test_update_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $data = [
            'title' => 'Updated Checklist',
        ];
        $response = $this->putJson('/api/checklists/' . $checklist->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_destroy_deletes_checklist_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $response = $this->deleteJson('/api/checklists/' . $checklist->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_checklist_success',
            ]);
        $this->assertDatabaseMissing('check_lists', ['id' => $checklist->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/checklists/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'checklist_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $response = $this->deleteJson('/api/checklists/' . $checklist->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_index_returns_empty_list_when_no_checklists()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $response = $this->getJson('/api/cards/' . $card->id . '/checklists');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_checklist_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }
} 