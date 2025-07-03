<?php

namespace Tests\Feature;

use App\Models\CheckListItem;
use App\Models\CheckList;
use App\Models\Card;
use App\Models\User;
use App\Models\Board;
use App\Models\ListBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckListItemControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_checklist_items()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        CheckListItem::factory()->count(3)->create(['check_list_id' => $checklist->id]);
        $response = $this->getJson('/api/checklists/' . $checklist->id . '/items');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_checklist_item_success',
            ]);
    }

    public function test_index_returns_404_if_checklist_not_found()
    {
        $response = $this->getJson('/api/checklists/9999/items');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'checklist_not_found',
            ]);
    }

    public function test_index_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        CheckListItem::factory()->count(3)->create(['check_list_id' => $checklist->id]);
        $response = $this->getJson('/api/checklists/' . $checklist->id . '/items');
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_store_creates_checklist_item_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        $data = [
            'content' => 'Test Item',
            'position' => 1,
        ];
        $response = $this->postJson('/api/checklists/' . $checklist->id . '/items', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_checklist_item_success',
            ]);
        $this->assertDatabaseHas('check_list_items', ['content' => 'Test Item']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        $response = $this->postJson('/api/checklists/' . $checklist->id . '/items', []);
        $response->assertStatus(422);
    }

    public function test_store_returns_404_if_checklist_not_found()
    {
        $data = [
            'content' => 'Test Item',
        ];
        $response = $this->postJson('/api/checklists/9999/items', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'checklist_not_found',
            ]);
    }

    public function test_store_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $data = [
            'content' => 'Test Item',
        ];
        $response = $this->postJson('/api/checklists/' . $checklist->id . '/items', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_update_checklist_item_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        $item = CheckListItem::factory()->create(['check_list_id' => $checklist->id]);
        $data = [
            'content' => 'Updated Item',
            'position' => 2,
        ];
        $response = $this->putJson('/api/checklists/' . $checklist->id . '/items/' . $item->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_checklist_item_success',
            ]);
        $this->assertDatabaseHas('check_list_items', ['content' => 'Updated Item']);
    }

    public function test_update_returns_404_if_checklist_not_found()
    {
        $data = [
            'content' => 'Updated Item',
        ];
        $response = $this->putJson('/api/checklists/9999/items/1', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'checklist_not_found',
            ]);
    }

    public function test_update_returns_404_if_item_not_found()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        $data = [
            'content' => 'Updated Item',
        ];
        $response = $this->putJson('/api/checklists/' . $checklist->id . '/items/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'item_not_found',
            ]);
    }

    public function test_update_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $item = CheckListItem::factory()->create(['check_list_id' => $checklist->id]);
        $data = [
            'content' => 'Updated Item',
        ];
        $response = $this->putJson('/api/checklists/' . $checklist->id . '/items/' . $item->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_toggle_status_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        $item = CheckListItem::factory()->create([
            'check_list_id' => $checklist->id,
            'is_completed' => 0,
        ]);
        $response = $this->patchJson('/api/checklists/' . $checklist->id . '/items/' . $item->id . '/toggle');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'toggle_checklist_item_success',
            ]);
        $this->assertDatabaseHas('check_list_items', ['is_completed' => 1]);
    }

    public function test_toggle_status_returns_404_if_checklist_not_found()
    {
        $response = $this->patchJson('/api/checklists/9999/items/1/toggle');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'checklist_not_found',
            ]);
    }

    public function test_toggle_status_returns_404_if_item_not_found()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        $response = $this->patchJson('/api/checklists/' . $checklist->id . '/items/9999/toggle');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'item_not_found',
            ]);
    }

    public function test_toggle_status_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $item = CheckListItem::factory()->create(['check_list_id' => $checklist->id]);
        $response = $this->patchJson('/api/checklists/' . $checklist->id . '/items/' . $item->id . '/toggle');
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_destroy_deletes_checklist_item_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        $item = CheckListItem::factory()->create(['check_list_id' => $checklist->id]);
        $response = $this->deleteJson('/api/checklists/' . $checklist->id . '/items/' . $item->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_checklist_item_success',
            ]);
        $this->assertDatabaseMissing('check_list_items', ['id' => $item->id]);
    }

    public function test_destroy_returns_404_if_checklist_not_found()
    {
        $response = $this->deleteJson('/api/checklists/9999/items/1');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'checklist_not_found',
            ]);
    }

    public function test_destroy_returns_404_if_item_not_found()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        $response = $this->deleteJson('/api/checklists/' . $checklist->id . '/items/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'item_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $item = CheckListItem::factory()->create(['check_list_id' => $checklist->id]);
        $response = $this->deleteJson('/api/checklists/' . $checklist->id . '/items/' . $item->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_index_returns_empty_list_when_no_items()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $checklist = CheckList::factory()->create(['card_id' => $card->id]);
        $this->user->boards()->attach($board->id);
        $response = $this->getJson('/api/checklists/' . $checklist->id . '/items');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_checklist_item_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }
} 