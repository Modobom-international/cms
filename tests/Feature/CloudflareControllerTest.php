<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Domain;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CloudflareControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_get_cloudflare_zones()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/cloudflare/zones');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'status',
                        'name_servers',
                        'created_on',
                        'modified_on'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_get_zone_records()
    {
        $zoneId = $this->faker->uuid;

        $response = $this->actingAs($this->user)
            ->getJson("/api/cloudflare/zones/{$zoneId}/records");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'name',
                        'content',
                        'ttl',
                        'proxied'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_create_dns_record()
    {
        $zoneId = $this->faker->uuid;
        $recordData = [
            'type' => 'A',
            'name' => 'test.example.com',
            'content' => '192.168.1.1',
            'ttl' => 1,
            'proxied' => false
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/cloudflare/zones/{$zoneId}/records", $recordData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => $recordData['type'],
                    'name' => $recordData['name'],
                    'content' => $recordData['content'],
                    'ttl' => $recordData['ttl'],
                    'proxied' => $recordData['proxied']
                ]
            ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_dns_record()
    {
        $zoneId = $this->faker->uuid;

        $response = $this->actingAs($this->user)
            ->postJson("/api/cloudflare/zones/{$zoneId}/records", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'name', 'content']);
    }

    /** @test */
    public function it_can_update_dns_record()
    {
        $zoneId = $this->faker->uuid;
        $recordId = $this->faker->uuid;
        
        $updateData = [
            'type' => 'A',
            'name' => 'updated.example.com',
            'content' => '192.168.1.2',
            'ttl' => 300,
            'proxied' => true
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/cloudflare/zones/{$zoneId}/records/{$recordId}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $recordId,
                    'type' => $updateData['type'],
                    'name' => $updateData['name'],
                    'content' => $updateData['content'],
                    'ttl' => $updateData['ttl'],
                    'proxied' => $updateData['proxied']
                ]
            ]);
    }

    /** @test */
    public function it_can_delete_dns_record()
    {
        $zoneId = $this->faker->uuid;
        $recordId = $this->faker->uuid;

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/cloudflare/zones/{$zoneId}/records/{$recordId}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'DNS record deleted successfully']);
    }

    /** @test */
    public function it_can_get_ssl_certificates()
    {
        $zoneId = $this->faker->uuid;

        $response = $this->actingAs($this->user)
            ->getJson("/api/cloudflare/zones/{$zoneId}/ssl/certificates");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'hosts',
                        'issuer',
                        'signature',
                        'status',
                        'validity_days'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_get_ssl_settings()
    {
        $zoneId = $this->faker->uuid;

        $response = $this->actingAs($this->user)
            ->getJson("/api/cloudflare/zones/{$zoneId}/ssl/settings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'value',
                    'editable',
                    'modified_on'
                ]
            ]);
    }

    /** @test */
    public function it_can_update_ssl_settings()
    {
        $zoneId = $this->faker->uuid;
        $settingsData = [
            'value' => 'full'
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/api/cloudflare/zones/{$zoneId}/ssl/settings", $settingsData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'value' => $settingsData['value']
                ]
            ]);
    }

    /** @test */
    public function it_can_get_firewall_rules()
    {
        $zoneId = $this->faker->uuid;

        $response = $this->actingAs($this->user)
            ->getJson("/api/cloudflare/zones/{$zoneId}/firewall/rules");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'description',
                        'filter',
                        'action',
                        'priority',
                        'paused'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_create_firewall_rule()
    {
        $zoneId = $this->faker->uuid;
        $ruleData = [
            'description' => 'Block malicious IPs',
            'filter' => [
                'expression' => '(ip.src eq 192.168.1.100)'
            ],
            'action' => 'block',
            'priority' => 1,
            'paused' => false
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/cloudflare/zones/{$zoneId}/firewall/rules", $ruleData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'description' => $ruleData['description'],
                    'action' => $ruleData['action'],
                    'priority' => $ruleData['priority'],
                    'paused' => $ruleData['paused']
                ]
            ]);
    }

    /** @test */
    public function it_can_get_analytics()
    {
        $zoneId = $this->faker->uuid;
        $params = [
            'since' => now()->subDays(7)->toISOString(),
            'until' => now()->toISOString(),
            'dimensions' => 'date'
        ];

        $response = $this->actingAs($this->user)
            ->getJson("/api/cloudflare/zones/{$zoneId}/analytics?" . http_build_query($params));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'totals',
                    'timeseries'
                ]
            ]);
    }

    /** @test */
    public function it_can_purge_cache()
    {
        $zoneId = $this->faker->uuid;
        $purgeData = [
            'files' => [
                'https://example.com/css/style.css',
                'https://example.com/js/app.js'
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/cloudflare/zones/{$zoneId}/purge_cache", $purgeData);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Cache purged successfully']);
    }

    /** @test */
    public function it_can_purge_all_cache()
    {
        $zoneId = $this->faker->uuid;

        $response = $this->actingAs($this->user)
            ->postJson("/api/cloudflare/zones/{$zoneId}/purge_cache", [
                'purge_everything' => true
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'All cache purged successfully']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/cloudflare/zones');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_invalid_zone_id()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/cloudflare/zones/invalid-zone/records');

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid zone ID']);
    }

    /** @test */
    public function it_handles_api_errors()
    {
        $zoneId = $this->faker->uuid;

        $response = $this->actingAs($this->user)
            ->getJson("/api/cloudflare/zones/{$zoneId}/records");

        // This test assumes the API might return an error for invalid zone
        $response->assertStatus(400)
            ->assertJsonStructure(['error']);
    }
} 