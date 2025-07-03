<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CompanyIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyIpControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_company_ips()
    {
        $companyIps = CompanyIp::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/company-ips');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'ip_address',
                        'description',
                        'is_active',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_company_ip()
    {
        $companyIp = CompanyIp::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/company-ips/{$companyIp->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $companyIp->id,
                    'ip_address' => $companyIp->ip_address,
                    'description' => $companyIp->description,
                    'is_active' => $companyIp->is_active
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_company_ip()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/company-ips/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_a_company_ip()
    {
        $companyIpData = [
            'ip_address' => $this->faker->ipv4,
            'description' => $this->faker->sentence,
            'is_active' => true
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips', $companyIpData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'ip_address' => $companyIpData['ip_address'],
                    'description' => $companyIpData['description'],
                    'is_active' => $companyIpData['is_active']
                ]
            ]);

        $this->assertDatabaseHas('company_ips', $companyIpData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_company_ip()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /** @test */
    public function it_validates_ip_address_format()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips', [
                'ip_address' => 'invalid-ip',
                'description' => 'Test IP'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /** @test */
    public function it_can_update_a_company_ip()
    {
        $companyIp = CompanyIp::factory()->create();

        $updateData = [
            'ip_address' => '192.168.1.100',
            'description' => 'Updated description',
            'is_active' => false
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/company-ips/{$companyIp->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $companyIp->id,
                    'ip_address' => $updateData['ip_address'],
                    'description' => $updateData['description'],
                    'is_active' => $updateData['is_active']
                ]
            ]);

        $this->assertDatabaseHas('company_ips', [
            'id' => $companyIp->id,
            'ip_address' => $updateData['ip_address'],
            'description' => $updateData['description'],
            'is_active' => $updateData['is_active']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_company_ip()
    {
        $updateData = [
            'ip_address' => '192.168.1.100',
            'description' => 'Updated description'
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/company-ips/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_a_company_ip()
    {
        $companyIp = CompanyIp::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/company-ips/{$companyIp->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Company IP deleted successfully']);

        $this->assertDatabaseMissing('company_ips', ['id' => $companyIp->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_company_ip()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/company-ips/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_activate_a_company_ip()
    {
        $companyIp = CompanyIp::factory()->create([
            'is_active' => false
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/company-ips/{$companyIp->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $companyIp->id,
                    'is_active' => true
                ]
            ]);

        $this->assertDatabaseHas('company_ips', [
            'id' => $companyIp->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_deactivate_a_company_ip()
    {
        $companyIp = CompanyIp::factory()->create([
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/company-ips/{$companyIp->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $companyIp->id,
                    'is_active' => false
                ]
            ]);

        $this->assertDatabaseHas('company_ips', [
            'id' => $companyIp->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_can_get_active_company_ips()
    {
        // Create active IPs
        CompanyIp::factory()->count(3)->create([
            'is_active' => true
        ]);

        // Create inactive IPs
        CompanyIp::factory()->count(2)->create([
            'is_active' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/company-ips/active');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_check_if_ip_is_company_ip()
    {
        $companyIp = CompanyIp::factory()->create([
            'ip_address' => '192.168.1.100',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips/check', [
                'ip_address' => '192.168.1.100'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_company_ip' => true,
                    'ip_address' => '192.168.1.100'
                ]
            ]);
    }

    /** @test */
    public function it_returns_false_for_non_company_ip()
    {
        CompanyIp::factory()->create([
            'ip_address' => '192.168.1.100',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips/check', [
                'ip_address' => '10.0.0.1'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_company_ip' => false,
                    'ip_address' => '10.0.0.1'
                ]
            ]);
    }

    /** @test */
    public function it_returns_false_for_inactive_company_ip()
    {
        CompanyIp::factory()->create([
            'ip_address' => '192.168.1.100',
            'is_active' => false
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips/check', [
                'ip_address' => '192.168.1.100'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_company_ip' => false,
                    'ip_address' => '192.168.1.100'
                ]
            ]);
    }

    /** @test */
    public function it_validates_ip_address_in_check_request()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips/check', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /** @test */
    public function it_can_bulk_import_company_ips()
    {
        $ipsData = [
            ['ip_address' => '192.168.1.100', 'description' => 'Office Network'],
            ['ip_address' => '192.168.1.101', 'description' => 'Server Room'],
            ['ip_address' => '192.168.1.102', 'description' => 'Conference Room']
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips/bulk-import', [
                'ips' => $ipsData
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Company IPs imported successfully']);

        foreach ($ipsData as $ipData) {
            $this->assertDatabaseHas('company_ips', $ipData);
        }
    }

    /** @test */
    public function it_validates_bulk_import_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips/bulk-import', [
                'ips' => [
                    ['ip_address' => 'invalid-ip'],
                    ['description' => 'Missing IP']
                ]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ips.0.ip_address', 'ips.1.ip_address']);
    }

    /** @test */
    public function it_can_export_company_ips()
    {
        CompanyIp::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/company-ips/export?format=csv');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv');
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/company-ips');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_duplicate_ip_addresses()
    {
        CompanyIp::factory()->create([
            'ip_address' => '192.168.1.100'
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/company-ips', [
                'ip_address' => '192.168.1.100',
                'description' => 'Duplicate IP'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /** @test */
    public function it_can_get_company_ip_statistics()
    {
        // Create various company IPs
        CompanyIp::factory()->count(5)->create(['is_active' => true]);
        CompanyIp::factory()->count(3)->create(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/company-ips/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_ips',
                    'active_ips',
                    'inactive_ips',
                    'recently_added'
                ]
            ]);
    }
} 