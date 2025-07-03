<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_api_keys()
    {
        ApiKey::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/api-keys');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_api_key_success',
            ]);
    }

    public function test_store_creates_api_key_successfully()
    {
        $data = [
            'name' => 'Test API Key',
        ];
        $response = $this->postJson('/api/api-keys', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_api_key_success',
            ]);
        $this->assertDatabaseHas('api_keys', ['name' => 'Test API Key']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/api-keys', []);
        $response->assertStatus(422);
    }

    public function test_show_returns_api_key_detail()
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/api-keys/' . $apiKey->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_api_key_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/api-keys/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'api_key_not_found',
            ]);
    }

    public function test_update_api_key_successfully()
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);
        $data = [
            'name' => 'Updated API Key',
            'is_active' => false,
        ];
        $response = $this->putJson('/api/api-keys/' . $apiKey->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_api_key_success',
            ]);
        $this->assertDatabaseHas('api_keys', ['name' => 'Updated API Key']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'name' => 'Updated API Key',
        ];
        $response = $this->putJson('/api/api-keys/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'api_key_not_found',
            ]);
    }

    public function test_destroy_deletes_api_key_successfully()
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);
        $response = $this->deleteJson('/api/api-keys/' . $apiKey->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_api_key_success',
            ]);
        $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/api-keys/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'api_key_not_found',
            ]);
    }

    public function test_regenerate_api_key_successfully()
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);
        $response = $this->postJson('/api/api-keys/' . $apiKey->id . '/regenerate');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'regenerate_api_key_success',
            ]);
    }

    public function test_regenerate_returns_404_if_not_found()
    {
        $response = $this->postJson('/api/api-keys/9999/regenerate');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'api_key_not_found',
            ]);
    }

    public function test_get_server_api_key_successfully()
    {
        $server = Server::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);
        $data = [
            'server_id' => $server->id,
            'api_key' => $apiKey->key_prefix,
        ];
        $response = $this->postJson('/api/api-keys/server', $data);
        $response->assertStatus(200);
    }
} 