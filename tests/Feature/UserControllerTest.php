<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_me_returns_authenticated_user()
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'data_user_success',
            ]);
    }

    public function test_show_returns_user_detail()
    {
        $user = User::factory()->create();
        $response = $this->getJson('/api/users/' . $user->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_user_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/users/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'user_not_found',
            ]);
    }

    public function test_update_user_successfully()
    {
        $user = User::factory()->create();
        $data = [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
        ];
        $response = $this->putJson('/api/users/' . $user->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_user_success',
            ]);
        $this->assertDatabaseHas('users', ['name' => 'Updated User']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
        ];
        $response = $this->putJson('/api/users/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'user_not_found',
            ]);
    }

    public function test_destroy_deletes_user_successfully()
    {
        $user = User::factory()->create();
        $response = $this->deleteJson('/api/users/' . $user->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_user_success',
            ]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/users/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'user_not_found',
            ]);
    }

    public function test_store_creates_user_successfully()
    {
        $data = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password',
        ];
        $response = $this->postJson('/api/users', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_user_success',
            ]);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/users', []);
        $response->assertStatus(422);
    }
} 