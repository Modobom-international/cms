<?php

namespace Tests\Feature;

use App\Models\AppInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppInformationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_app_information()
    {
        AppInformation::factory()->count(3)->create();
        $response = $this->getJson('/api/app-information');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_app_information_success',
            ]);
    }

    public function test_index_with_filters()
    {
        AppInformation::factory()->count(3)->create();
        $response = $this->getJson('/api/app-information?user_id=1&app_name=test');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_app_information_success',
            ]);
    }

    public function test_store_creates_app_information_successfully()
    {
        $data = [
            'user_id' => $this->user->id,
            'app_name' => 'Test App',
            'app_version' => '1.0.0',
            'device_info' => 'Test Device',
        ];
        $response = $this->postJson('/api/app-information', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'create_app_information_success',
            ]);
        $this->assertDatabaseHas('app_information', ['app_name' => 'Test App']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/app-information', []);
        $response->assertStatus(422);
    }

    public function test_show_returns_app_information_detail()
    {
        $appInfo = AppInformation::factory()->create();
        $response = $this->getJson('/api/app-information/' . $appInfo->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'get_app_information_success',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/app-information/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'app_information_not_found',
            ]);
    }

    public function test_update_app_information_successfully()
    {
        $appInfo = AppInformation::factory()->create();
        $data = [
            'app_name' => 'Updated App',
            'app_version' => '2.0.0',
        ];
        $response = $this->putJson('/api/app-information/' . $appInfo->id, $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'update_app_information_success',
            ]);
        $this->assertDatabaseHas('app_information', ['app_name' => 'Updated App']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'app_name' => 'Updated App',
        ];
        $response = $this->putJson('/api/app-information/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'app_information_not_found',
            ]);
    }

    public function test_destroy_deletes_app_information_successfully()
    {
        $appInfo = AppInformation::factory()->create();
        $response = $this->deleteJson('/api/app-information/' . $appInfo->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'delete_app_information_success',
            ]);
        $this->assertDatabaseMissing('app_information', ['id' => $appInfo->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/app-information/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'app_information_not_found',
            ]);
    }

    public function test_get_by_user_id_successfully()
    {
        AppInformation::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/app-information/user/' . $this->user->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'detail_app_information_success',
            ]);
    }

    public function test_get_by_user_id_returns_empty_when_no_data()
    {
        $response = $this->getJson('/api/app-information/user/9999');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'detail_app_information_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }

    public function test_data_chart_successfully()
    {
        AppInformation::factory()->count(5)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/app-information/chart?user_id=' . $this->user->id);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'data_chart_success',
            ]);
    }

    public function test_data_chart_with_date_filters()
    {
        AppInformation::factory()->count(5)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/app-information/chart?user_id=' . $this->user->id . '&date_from=2024-01-01&date_to=2024-12-31');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'data_chart_success',
            ]);
    }

    public function test_index_with_pagination()
    {
        AppInformation::factory()->count(15)->create();
        $response = $this->getJson('/api/app-information?page=1&pageSize=10');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_app_information_success',
            ]);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_returns_empty_list_when_no_data()
    {
        $response = $this->getJson('/api/app-information');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'list_app_information_success',
            ]);
        $this->assertEmpty($response->json('data'));
    }
} 