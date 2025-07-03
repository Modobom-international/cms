<?php

namespace Tests\Feature;

use App\Models\MonitorServer;
use App\Models\Server;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorServerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_monitor_servers()
    {
        MonitorServer::factory()->count(3)->create();
        $response = $this->getJson('/api/monitor-servers');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_monitor_server_success',
            ]);
    }

    public function test_index_with_filters()
    {
        MonitorServer::factory()->count(3)->create();
        $response = $this->getJson('/api/monitor-servers?server_id=1&date_from=2024-01-01&date_to=2024-12-31');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_monitor_server_success',
            ]);
    }

    public function test_store_creates_monitor_server_successfully()
    {
        $server = Server::factory()->create();
        $data = [
            'server_id' => $server->id,
            'cpu_usage' => 50.5,
            'memory_usage' => 75.2,
            'disk_usage' => 60.0,
        ];
        $response = $this->postJson('/api/monitor-servers', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'create_monitor_server_success',
            ]);
        $this->assertDatabaseHas('monitor_server_logs', ['server_id' => $server->id]);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/monitor-servers', []);
        $response->assertStatus(422);
    }

    public function test_store_fails_with_invalid_server_id()
    {
        $data = [
            'server_id' => 9999,
            'cpu_usage' => 50.5,
            'memory_usage' => 75.2,
            'disk_usage' => 60.0,
        ];
        $response = $this->postJson('/api/monitor-servers', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'server_not_found',
            ]);
    }

    public function test_logs_stores_server_logs_successfully()
    {
        $server = Server::factory()->create(['ip' => '192.168.1.1']);
        $apiKey = ApiKey::factory()->create(['server_id' => $server->id]);
        $data = [
            'ip' => '192.168.1.1',
            'api_key' => $apiKey->key_prefix,
            'cpu_usage' => 50.5,
            'memory_usage' => 75.2,
            'disk_usage' => 60.0,
        ];
        $response = $this->postJson('/api/monitor-servers/logs', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'store_monitor_server_log_success',
            ]);
    }

    public function test_logs_fails_with_invalid_server_ip()
    {
        $data = [
            'ip' => '192.168.1.999',
            'api_key' => 'test_key',
            'cpu_usage' => 50.5,
        ];
        $response = $this->postJson('/api/monitor-servers/logs', $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'invalid_server',
            ]);
    }

    public function test_logs_fails_with_invalid_api_key()
    {
        $server = Server::factory()->create(['ip' => '192.168.1.1']);
        $data = [
            'ip' => '192.168.1.1',
            'api_key' => 'invalid_key',
            'cpu_usage' => 50.5,
        ];
        $response = $this->postJson('/api/monitor-servers/logs', $data);
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'type' => 'invalid_api_key',
            ]);
    }

    public function test_index_with_pagination()
    {
        MonitorServer::factory()->count(15)->create();
        $response = $this->getJson('/api/monitor-servers?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_monitor_server_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_returns_empty_list_when_no_logs()
    {
        $response = $this->getJson('/api/monitor-servers');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_monitor_server_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }

    public function test_store_with_optional_fields()
    {
        $server = Server::factory()->create();
        $data = [
            'server_id' => $server->id,
            'cpu_usage' => 50.5,
            'memory_usage' => 75.2,
            'disk_usage' => 60.0,
            'network_in' => 1024,
            'network_out' => 2048,
        ];
        $response = $this->postJson('/api/monitor-servers', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'create_monitor_server_success',
            ]);
    }
} 