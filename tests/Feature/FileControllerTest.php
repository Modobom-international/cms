<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
        Storage::fake('local');
    }

    public function test_upload_file_successfully()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $data = [
            'file' => $file,
            'path' => 'documents',
        ];
        $response = $this->postJson('/api/files/upload', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'upload_file_success',
            ]);
        $this->assertDatabaseHas('files', ['name' => 'document.pdf']);
    }

    public function test_upload_fails_without_file()
    {
        $data = [
            'path' => 'documents',
        ];
        $response = $this->postJson('/api/files/upload', $data);
        $response->assertStatus(422);
    }

    public function test_upload_fails_with_invalid_file()
    {
        $data = [
            'file' => 'invalid_file',
            'path' => 'documents',
        ];
        $response = $this->postJson('/api/files/upload', $data);
        $response->assertStatus(422);
    }

    public function test_download_file_successfully()
    {
        $file = File::factory()->create([
            'name' => 'test.pdf',
            'path' => '/files/documents/test.pdf',
        ]);
        $response = $this->getJson('/api/files/' . $file->id . '/download');
        $response->assertStatus(200);
    }

    public function test_download_returns_404_if_not_found()
    {
        $response = $this->getJson('/api/files/9999/download');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'file_not_found',
            ]);
    }

    public function test_download_returns_404_if_file_not_exists()
    {
        $file = File::factory()->create([
            'name' => 'nonexistent.pdf',
            'path' => '/files/documents/nonexistent.pdf',
        ]);
        $response = $this->getJson('/api/files/' . $file->id . '/download');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'file_not_exists',
            ]);
    }
} 