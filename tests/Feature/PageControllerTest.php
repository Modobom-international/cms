<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Page;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PageControllerTest extends TestCase
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
    }

    /** @test */
    public function it_can_list_pages()
    {
        $pages = Page::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/pages');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'content',
                        'meta_description',
                        'meta_keywords',
                        'is_published',
                        'workspace_id',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_page()
    {
        $page = Page::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/pages/{$page->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'content' => $page->content,
                    'meta_description' => $page->meta_description,
                    'meta_keywords' => $page->meta_keywords,
                    'is_published' => $page->is_published,
                    'workspace_id' => $page->workspace_id
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_page()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/pages/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_a_page()
    {
        $pageData = [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => '<h1>Test Content</h1>',
            'meta_description' => 'Test meta description',
            'meta_keywords' => 'test, page, content',
            'is_published' => true,
            'workspace_id' => $this->workspace->id
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/pages', $pageData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => $pageData['title'],
                    'slug' => $pageData['slug'],
                    'content' => $pageData['content'],
                    'meta_description' => $pageData['meta_description'],
                    'meta_keywords' => $pageData['meta_keywords'],
                    'is_published' => $pageData['is_published'],
                    'workspace_id' => $pageData['workspace_id']
                ]
            ]);

        $this->assertDatabaseHas('pages', $pageData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_page()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/pages', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'slug', 'workspace_id']);
    }

    /** @test */
    public function it_validates_unique_slug()
    {
        Page::factory()->create([
            'slug' => 'existing-page',
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/pages', [
                'title' => 'New Page',
                'slug' => 'existing-page',
                'workspace_id' => $this->workspace->id
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function it_can_update_a_page()
    {
        $page = Page::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $updateData = [
            'title' => 'Updated Page Title',
            'content' => '<h1>Updated Content</h1>',
            'meta_description' => 'Updated meta description',
            'is_published' => false
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/pages/{$page->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $page->id,
                    'title' => $updateData['title'],
                    'content' => $updateData['content'],
                    'meta_description' => $updateData['meta_description'],
                    'is_published' => $updateData['is_published']
                ]
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => $updateData['title'],
            'content' => $updateData['content'],
            'meta_description' => $updateData['meta_description'],
            'is_published' => $updateData['is_published']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_page()
    {
        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content'
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/pages/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_a_page()
    {
        $page = Page::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/pages/{$page->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Page deleted successfully']);

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_page()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/pages/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_publish_a_page()
    {
        $page = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_published' => false
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/pages/{$page->id}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $page->id,
                    'is_published' => true
                ]
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'is_published' => true
        ]);
    }

    /** @test */
    public function it_can_unpublish_a_page()
    {
        $page = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_published' => true
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/pages/{$page->id}/unpublish");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $page->id,
                    'is_published' => false
                ]
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'is_published' => false
        ]);
    }

    /** @test */
    public function it_can_get_published_pages()
    {
        // Create published pages
        Page::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'is_published' => true
        ]);

        // Create unpublished pages
        Page::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'is_published' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/pages/published');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_page_by_slug()
    {
        $page = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'slug' => 'test-page-slug',
            'is_published' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/pages/slug/test-page-slug');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_slug()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/pages/slug/nonexistent-slug');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_search_pages()
    {
        Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'title' => 'Test Page One',
            'content' => 'Content about testing'
        ]);

        Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'title' => 'Another Page',
            'content' => 'Different content'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/pages/search?q=test');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_get_pages_by_workspace()
    {
        $otherWorkspace = Workspace::factory()->create([
            'user_id' => $this->user->id
        ]);

        Page::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id
        ]);

        Page::factory()->count(2)->create([
            'workspace_id' => $otherWorkspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/workspaces/{$this->workspace->id}/pages");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_duplicate_a_page()
    {
        $page = Page::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/pages/{$page->id}/duplicate");

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => $page->title . ' (Copy)',
                    'content' => $page->content,
                    'workspace_id' => $page->workspace_id,
                    'is_published' => false
                ]
            ]);

        $this->assertDatabaseHas('pages', [
            'title' => $page->title . ' (Copy)',
            'content' => $page->content,
            'workspace_id' => $page->workspace_id,
            'is_published' => false
        ]);
    }

    /** @test */
    public function it_can_export_pages()
    {
        Page::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/pages/export?format=json');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }

    /** @test */
    public function it_can_import_pages()
    {
        $pagesData = [
            [
                'title' => 'Imported Page 1',
                'slug' => 'imported-page-1',
                'content' => 'Content for page 1',
                'meta_description' => 'Meta description 1'
            ],
            [
                'title' => 'Imported Page 2',
                'slug' => 'imported-page-2',
                'content' => 'Content for page 2',
                'meta_description' => 'Meta description 2'
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/pages/import', [
                'pages' => $pagesData,
                'workspace_id' => $this->workspace->id
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Pages imported successfully']);

        foreach ($pagesData as $pageData) {
            $this->assertDatabaseHas('pages', array_merge($pageData, [
                'workspace_id' => $this->workspace->id
            ]));
        }
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/pages');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_pages()
    {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $page = Page::factory()->create([
            'workspace_id' => $otherWorkspace->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/pages/{$page->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_page_statistics()
    {
        Page::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'is_published' => true
        ]);

        Page::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'is_published' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/pages/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_pages',
                    'published_pages',
                    'unpublished_pages',
                    'recent_pages',
                    'popular_pages'
                ]
            ]);
    }
} 