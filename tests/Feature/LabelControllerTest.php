<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_index_returns_list_of_labels()
    {
        Label::factory()->count(3)->create();
        $response = $this->getJson('/api/labels');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message', 'data'
            ]);
    }

    public function test_store_creates_label_successfully()
    {
        $data = [
            'name' => 'Test Label',
            'color' => '#ff0000',
        ];
        $response = $this->postJson('/api/labels', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Tạo label thành công',
            ]);
        $this->assertDatabaseHas('labels', ['name' => 'Test Label']);
    }

    public function test_store_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/labels', []);
        $response->assertStatus(422);
    }

    public function test_show_returns_label_detail()
    {
        $label = Label::factory()->create();
        $response = $this->getJson('/api/labels/' . $label->id);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'label_information',
            ]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/labels/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'label_not_found',
            ]);
    }

    public function test_update_label_successfully()
    {
        $label = Label::factory()->create();
        $data = [
            'name' => 'Updated Label',
            'color' => '#00ff00',
        ];
        $response = $this->putJson('/api/labels/' . $label->id, $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'update_label_success',
            ]);
        $this->assertDatabaseHas('labels', ['name' => 'Updated Label']);
    }

    public function test_update_returns_404_if_not_found()
    {
        $data = [
            'name' => 'Updated Label',
            'color' => '#00ff00',
        ];
        $response = $this->putJson('/api/labels/9999', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'label_not_found',
            ]);
    }

    public function test_destroy_deletes_label_successfully()
    {
        $label = Label::factory()->create();
        $response = $this->deleteJson('/api/labels/' . $label->id);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'delete_label_success',
            ]);
        $this->assertDatabaseMissing('labels', ['id' => $label->id]);
    }

    public function test_destroy_returns_404_if_not_found()
    {
        $response = $this->deleteJson('/api/labels/9999');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'label_not_found',
            ]);
    }
} 