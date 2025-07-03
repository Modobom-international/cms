<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Card;
use App\Models\User;
use App\Models\Board;
use App\Models\ListBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_comments()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        Comment::factory()->count(3)->create(['card_id' => $card->id]);
        $response = $this->getJson('/api/cards/' . $card->id . '/comments');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_comment',
            ]);
    }

    public function test_index_returns_404_if_card_not_found()
    {
        $response = $this->getJson('/api/cards/9999/comments');
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
        Comment::factory()->count(3)->create(['card_id' => $card->id]);
        $response = $this->getJson('/api/cards/' . $card->id . '/comments');
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_store_creates_comment_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $data = [
            'content' => 'Test comment',
        ];
        $response = $this->postJson('/api/cards/' . $card->id . '/comments', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_comment_success',
            ]);
        $this->assertDatabaseHas('comments', ['content' => 'Test comment']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $response = $this->postJson('/api/cards/' . $card->id . '/comments', []);
        $response->assertStatus(422);
    }

    public function test_store_returns_404_if_card_not_found()
    {
        $data = [
            'content' => 'Test comment',
        ];
        $response = $this->postJson('/api/cards/9999/comments', $data);
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
            'content' => 'Test comment',
        ];
        $response = $this->postJson('/api/cards/' . $card->id . '/comments', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_update_comment_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $comment = Comment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
        ]);
        $data = [
            'content' => 'Updated comment',
        ];
        $response = $this->putJson('/api/comments/' . $comment->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_comment_success',
            ]);
        $this->assertDatabaseHas('comments', ['content' => 'Updated comment']);
    }

    public function test_update_returns_404_if_comment_not_found()
    {
        $data = [
            'content' => 'Updated comment',
        ];
        $response = $this->putJson('/api/comments/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'comment_not_found',
            ]);
    }

    public function test_update_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $comment = Comment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
        ]);
        $data = [
            'content' => 'Updated comment',
        ];
        $response = $this->putJson('/api/comments/' . $comment->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_destroy_deletes_comment_successfully()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $this->user->boards()->attach($board->id);
        $comment = Comment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
        ]);
        $response = $this->deleteJson('/api/comments/' . $comment->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_comment_success',
            ]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_destroy_returns_404_if_comment_not_found()
    {
        $response = $this->deleteJson('/api/comments/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'comment_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_unauthorized()
    {
        $board = Board::factory()->create();
        $listBoard = ListBoard::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['list_board_id' => $listBoard->id]);
        $comment = Comment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
        ]);
        $response = $this->deleteJson('/api/comments/' . $comment->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }
} 