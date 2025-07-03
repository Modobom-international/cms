<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\ListBoard;
use App\Models\Board;
use App\Models\User;
use App\Models\Label;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_cards()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        Card::factory()->count(3)->create(['list_board_id' => $listBoard->id]);
        $response = $this->getJson('/api/lists/' . $listBoard->id . '/cards');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_cards_success',
            ]);
    }

    public function test_index_returns_404_if_list_not_found()
    {
        $response = $this->getJson('/api/lists/9999/cards');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'listBoard_not_found',
            ]);
    }

    public function test_index_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        Card::factory()->count(3)->create(['list_board_id' => $listBoard->id]);
        $response = $this->getJson('/api/lists/' . $listBoard->id . '/cards');
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_store_creates_card_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        $data = [
            'title' => 'Test Card',
            'description' => 'Test Description',
        ];
        $response = $this->postJson('/api/lists/' . $listBoard->id . '/cards', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_card_success',
            ]);
        $this->assertDatabaseHas('cards', ['title' => 'Test Card']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        $response = $this->postJson('/api/lists/' . $listBoard->id . '/cards', []);
        $response->assertStatus(422);
    }

    public function test_store_returns_404_if_list_not_found()
    {
        $data = [
            'title' => 'Test Card',
        ];
        $response = $this->postJson('/api/lists/9999/cards', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'listBoard_not_found',
            ]);
    }

    public function test_store_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $data = [
            'title' => 'Test Card',
        ];
        $response = $this->postJson('/api/lists/' . $listBoard->id . '/cards', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_show_returns_card_detail()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $response = $this->getJson('/api/cards/' . $card->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_card_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/cards/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'card_not_found',
            ]);
    }

    public function test_show_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $response = $this->getJson('/api/cards/' . $card->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_update_card_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $data = [
            'title' => 'Updated Card',
            'description' => 'Updated Description',
        ];
        $response = $this->putJson('/api/cards/' . $card->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_card_success',
            ]);
        $this->assertDatabaseHas('cards', ['title' => 'Updated Card']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'title' => 'Updated Card',
        ];
        $response = $this->putJson('/api/cards/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'card_not_found',
            ]);
    }

    public function test_update_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $data = [
            'title' => 'Updated Card',
        ];
        $response = $this->putJson('/api/cards/' . $card->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_destroy_deletes_card_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $response = $this->deleteJson('/api/cards/' . $card->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_card_success',
            ]);
        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/cards/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'card_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $response = $this->deleteJson('/api/cards/' . $card->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'Unauthorized',
            ]);
    }

    public function test_assign_members_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $member = User::factory()->create();
        $data = [
            'user_id' => $member->id,
        ];
        $response = $this->postJson('/api/cards/' . $card->id . '/assign-member', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'assign_member_success',
            ]);
    }

    public function test_remove_member_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $member = User::factory()->create();
        $card->users()->attach($member->id);
        $response = $this->deleteJson('/api/cards/' . $card->id . '/remove-member/' . $member->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'remove_member_success',
            ]);
    }

    public function test_assign_multiple_members_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $members = User::factory()->count(3)->create();
        $data = [
            'user_ids' => $members->pluck('id')->toArray(),
        ];
        $response = $this->postJson('/api/cards/' . $card->id . '/assign-multiple-members', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'assign_multiple_members_success',
            ]);
    }

    public function test_update_positions_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $this->user->boards()->attach($board->id);
        $cards = Card::factory()->count(3)->create(['list_board_id' => $listBoard->id]);
        $data = [
            'cards' => $cards->map(function ($card, $index) {
                return [
                    'id' => $card->id,
                    'position' => $index + 1,
                ];
            })->toArray(),
        ];
        $response = $this->putJson('/api/cards/update-positions', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_positions_success',
            ]);
    }
} 