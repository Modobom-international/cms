<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_domains()
    {
        Domain::factory()->count(3)->create();
        $response = $this->getJson('/api/domains');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_domain_success',
            ]);
    }

    public function test_index_with_search_filter()
    {
        Domain::factory()->create(['name' => 'test.com']);
        $response = $this->getJson('/api/domains?search=test');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_domain_success',
            ]);
    }

    public function test_store_creates_domain_successfully()
    {
        $data = [
            'domain' => 'test.com',
        ];
        $response = $this->postJson('/api/domains', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'store_domain_success',
            ]);
        $this->assertDatabaseHas('domains', ['name' => 'test.com']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/domains', []);
        $response->assertStatus(422);
    }

    public function test_show_returns_domain_detail()
    {
        $domain = Domain::factory()->create();
        $response = $this->getJson('/api/domains/' . $domain->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_domain_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/domains/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'domain_not_found',
            ]);
    }

    public function test_update_domain_successfully()
    {
        $domain = Domain::factory()->create();
        $data = [
            'name' => 'updated.com',
        ];
        $response = $this->putJson('/api/domains/' . $domain->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_domain_success',
            ]);
        $this->assertDatabaseHas('domains', ['name' => 'updated.com']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'name' => 'updated.com',
        ];
        $response = $this->putJson('/api/domains/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'domain_not_found',
            ]);
    }

    public function test_destroy_deletes_domain_successfully()
    {
        $domain = Domain::factory()->create();
        $response = $this->deleteJson('/api/domains/' . $domain->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_domain_success',
            ]);
        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/domains/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'domain_not_found',
            ]);
    }

    public function test_get_list_domain_for_tracking_successfully()
    {
        Site::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/domains/tracking?user_id=' . $this->user->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_domain_success',
            ]);
    }

    public function test_get_list_domain_for_tracking_with_search()
    {
        Site::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'test.com'
        ]);
        $response = $this->getJson('/api/domains/tracking?user_id=' . $this->user->id . '&search=test');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_domain_success',
            ]);
    }

    public function test_show_dns_records_successfully()
    {
        $domain = Domain::factory()->create();
        $response = $this->getJson('/api/domains/' . $domain->id . '/dns-records');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_dns_records_success',
            ]);
    }

    public function test_show_dns_records_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/domains/9999/dns-records');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'domain_not_found',
            ]);
    }

    public function test_index_with_pagination()
    {
        Domain::factory()->count(15)->create();
        $response = $this->getJson('/api/domains?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_domain_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_returns_empty_list_when_no_domains()
    {
        $response = $this->getJson('/api/domains');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_domain_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }
} 