<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_servers()
    {
        Server::factory()->count(3)->create();
        $response = $this->getJson('/api/servers');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_server_success',
            ]);
    }

    public function test_index_with_search_filter()
    {
        Server::factory()->create(['name' => 'Test Server']);
        $response = $this->getJson('/api/servers?search=Test');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_server_success',
            ]);
    }

    public function test_store_creates_server_successfully()
    {
        $data = [
            'name' => 'Test Server',
            'ip' => '192.168.1.1',
            'port' => 22,
        ];
        $response = $this->postJson('/api/servers', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_server_success',
            ]);
        $this->assertDatabaseHas('servers', ['name' => 'Test Server']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/servers', []);
        $response->assertStatus(422);
    }

    public function test_show_returns_server_detail()
    {
        $server = Server::factory()->create();
        $response = $this->getJson('/api/servers/' . $server->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_server_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/servers/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'server_not_found',
            ]);
    }

    public function test_update_server_successfully()
    {
        $server = Server::factory()->create();
        $data = [
            'name' => 'Updated Server',
            'ip' => '192.168.1.2',
        ];
        $response = $this->putJson('/api/servers/' . $server->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_server_success',
            ]);
        $this->assertDatabaseHas('servers', ['name' => 'Updated Server']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'name' => 'Updated Server',
        ];
        $response = $this->putJson('/api/servers/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'server_not_found',
            ]);
    }

    public function test_destroy_deletes_server_successfully()
    {
        $server = Server::factory()->create();
        $response = $this->deleteJson('/api/servers/' . $server->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_server_success',
            ]);
        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/servers/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'server_not_found',
            ]);
    }

    public function test_list_only_returns_servers_list()
    {
        Server::factory()->count(3)->create();
        $response = $this->getJson('/api/servers/list-only');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_server_only_success',
            ]);
    }

    public function test_detail_returns_server_detail()
    {
        $server = Server::factory()->create();
        $response = $this->getJson('/api/servers/' . $server->id . '/detail');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'detail_server_success',
            ]);
    }

    public function test_detail_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/servers/9999/detail');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'server_not_found',
            ]);
    }
} 