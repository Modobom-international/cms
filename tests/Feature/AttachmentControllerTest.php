<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attachment;
use App\Models\Card;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create([
            'user_id' => $this->user->id
        ]);
        
        Storage::fake('local');
    }

    /** @test */
    public function it_can_list_attachments()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $attachments = Attachment::factory()->count(5)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/attachments');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'filename',
                        'original_filename',
                        'file_path',
                        'file_size',
                        'mime_type',
                        'card_id',
                        'user_id',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_an_attachment()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $attachment = Attachment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'original_filename' => $attachment->original_filename,
                    'file_path' => $attachment->file_path,
                    'file_size' => $attachment->file_size,
                    'mime_type' => $attachment->mime_type,
                    'card_id' => $attachment->card_id,
                    'user_id' => $attachment->user_id
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_attachment()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/attachments/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_upload_an_attachment()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->actingAs($this->user)
            ->postJson("/api/cards/{$card->id}/attachments", [
                'file' => $file
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'original_filename' => 'document.pdf',
                    'card_id' => $card->id,
                    'user_id' => $this->user->id
                ]
            ]);

        $this->assertDatabaseHas('attachments', [
            'original_filename' => 'document.pdf',
            'card_id' => $card->id,
            'user_id' => $this->user->id
        ]);

        Storage::disk('local')->assertExists('attachments/' . $response->json('data.filename'));
    }

    /** @test */
    public function it_validates_file_upload()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/cards/{$card->id}/attachments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_file_size()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $file = UploadedFile::fake()->create('large-file.pdf', 10240); // 10MB

        $response = $this->actingAs($this->user)
            ->postJson("/api/cards/{$card->id}/attachments", [
                'file' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_file_type()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $file = UploadedFile::fake()->create('script.exe', 1024);

        $response = $this->actingAs($this->user)
            ->postJson("/api/cards/{$card->id}/attachments", [
                'file' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_can_update_attachment_metadata()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $attachment = Attachment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'description' => 'Updated file description'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/attachments/{$attachment->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $attachment->id,
                    'description' => $updateData['description']
                ]
            ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'description' => $updateData['description']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_attachment()
    {
        $updateData = [
            'description' => 'Updated description'
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/attachments/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_an_attachment()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $attachment = Attachment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Attachment deleted successfully']);

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_attachment()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/attachments/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_download_an_attachment()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $attachment = Attachment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/attachments/{$attachment->id}/download");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_get_attachments_by_card()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $attachments = Attachment::factory()->count(3)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/cards/{$card->id}/attachments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_attachments_by_user()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        Attachment::factory()->count(3)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id
        ]);

        $otherUser = User::factory()->create();
        Attachment::factory()->count(2)->create([
            'card_id' => $card->id,
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}/attachments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_bulk_upload_attachments()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $files = [
            UploadedFile::fake()->create('document1.pdf', 1024),
            UploadedFile::fake()->create('document2.pdf', 1024),
            UploadedFile::fake()->create('image.jpg', 1024)
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/cards/{$card->id}/attachments/bulk", [
                'files' => $files
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Attachments uploaded successfully']);

        $this->assertDatabaseCount('attachments', 3);
    }

    /** @test */
    public function it_validates_bulk_upload_files()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/cards/{$card->id}/attachments/bulk", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['files']);
    }

    /** @test */
    public function it_can_get_attachment_statistics()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        Attachment::factory()->count(5)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
            'mime_type' => 'application/pdf'
        ]);

        Attachment::factory()->count(3)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
            'mime_type' => 'image/jpeg'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/attachments/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_attachments',
                    'total_size',
                    'file_types',
                    'recent_uploads'
                ]
            ]);
    }

    /** @test */
    public function it_can_search_attachments()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        Attachment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
            'original_filename' => 'important-document.pdf'
        ]);

        Attachment::factory()->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
            'original_filename' => 'other-file.pdf'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/attachments/search?q=important');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_get_attachments_by_type()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        Attachment::factory()->count(3)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
            'mime_type' => 'application/pdf'
        ]);

        Attachment::factory()->count(2)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
            'mime_type' => 'image/jpeg'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/attachments/type/application/pdf');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/attachments');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_attachments()
    {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $otherCard = Card::factory()->create([
            'workspace_id' => $otherWorkspace->id
        ]);
        $attachment = Attachment::factory()->create([
            'card_id' => $otherCard->id,
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_recent_attachments()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        Attachment::factory()->count(5)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/attachments/recent');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_can_get_large_attachments()
    {
        $card = Card::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        Attachment::factory()->count(3)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
            'file_size' => 5000000 // 5MB
        ]);

        Attachment::factory()->count(2)->create([
            'card_id' => $card->id,
            'user_id' => $this->user->id,
            'file_size' => 1000000 // 1MB
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/attachments/large');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
} 