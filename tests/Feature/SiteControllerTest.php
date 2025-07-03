<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_sites()
    {
        Site::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/sites');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_site_success',
            ]);
    }

    public function test_index_with_search_filter()
    {
        Site::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'test.com'
        ]);
        $response = $this->getJson('/api/sites?search=test');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_site_success',
            ]);
    }

    public function test_store_creates_site_successfully()
    {
        $data = [
            'user_id' => $this->user->id,
            'domain' => 'test.com',
            'name' => 'Test Site',
            'description' => 'Test Description',
        ];
        $response = $this->postJson('/api/sites', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_site_success',
            ]);
        $this->assertDatabaseHas('sites', ['domain' => 'test.com']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/sites', []);
        $response->assertStatus(422);
    }

    public function test_store_fails_with_duplicate_domain()
    {
        Site::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'test.com'
        ]);
        $data = [
            'user_id' => $this->user->id,
            'domain' => 'test.com',
            'name' => 'Another Test Site',
        ];
        $response = $this->postJson('/api/sites', $data);
        $response->assertStatus(422);
    }

    public function test_show_returns_site_detail()
    {
        $site = Site::factory()->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/sites/' . $site->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_site_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/sites/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'site_not_found',
            ]);
    }

    public function test_show_returns_403_if_unauthorized()
    {
        $otherUser = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $otherUser->id]);
        $response = $this->getJson('/api/sites/' . $site->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_update_site_successfully()
    {
        $site = Site::factory()->create(['user_id' => $this->user->id]);
        $data = [
            'name' => 'Updated Site',
            'description' => 'Updated Description',
        ];
        $response = $this->putJson('/api/sites/' . $site->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_site_success',
            ]);
        $this->assertDatabaseHas('sites', ['name' => 'Updated Site']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'name' => 'Updated Site',
        ];
        $response = $this->putJson('/api/sites/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'site_not_found',
            ]);
    }

    public function test_update_returns_403_if_unauthorized()
    {
        $otherUser = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $otherUser->id]);
        $data = [
            'name' => 'Updated Site',
        ];
        $response = $this->putJson('/api/sites/' . $site->id, $data);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_destroy_deletes_site_successfully()
    {
        $site = Site::factory()->create(['user_id' => $this->user->id]);
        $response = $this->deleteJson('/api/sites/' . $site->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_site_success',
            ]);
        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/sites/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'site_not_found',
            ]);
    }

    public function test_destroy_returns_403_if_unauthorized()
    {
        $otherUser = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $otherUser->id]);
        $response = $this->deleteJson('/api/sites/' . $site->id);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'unauthorized',
            ]);
    }

    public function test_index_with_pagination()
    {
        Site::factory()->count(15)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/sites?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_site_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_returns_empty_list_when_no_sites()
    {
        $response = $this->getJson('/api/sites');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_site_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }

    public function test_index_with_user_filter()
    {
        $otherUser = User::factory()->create();
        Site::factory()->count(3)->create(['user_id' => $this->user->id]);
        Site::factory()->count(2)->create(['user_id' => $otherUser->id]);
        $response = $this->getJson('/api/sites?user_id=' . $this->user->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_site_success',
            ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_store_with_optional_fields()
    {
        $data = [
            'user_id' => $this->user->id,
            'domain' => 'test.com',
            'name' => 'Test Site',
            'description' => 'Test Description',
            'status' => 'active',
            'ssl_enabled' => true,
        ];
        $response = $this->postJson('/api/sites', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_site_success',
            ]);
        $this->assertDatabaseHas('sites', [
            'domain' => 'test.com',
            'ssl_enabled' => true,
        ]);
    }
} 