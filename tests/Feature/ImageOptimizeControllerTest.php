<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Storage::fake('local');
    }

    /** @test */
    public function it_can_optimize_an_image()
    {
        $image = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'quality' => 80,
                'width' => 800,
                'height' => 600
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'original_size',
                    'optimized_size',
                    'compression_ratio',
                    'file_path',
                    'width',
                    'height'
                ]
            ]);
    }

    /** @test */
    public function it_validates_image_file()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_validates_image_file_type()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_validates_quality_parameter()
    {
        $image = UploadedFile::fake()->image('test-image.jpg');

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'quality' => 150 // Invalid quality
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quality']);
    }

    /** @test */
    public function it_validates_dimension_parameters()
    {
        $image = UploadedFile::fake()->image('test-image.jpg');

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'width' => -100, // Invalid width
                'height' => 0 // Invalid height
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['width', 'height']);
    }

    /** @test */
    public function it_can_optimize_image_with_default_settings()
    {
        $image = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'original_size' => $response->json('data.original_size'),
                    'optimized_size' => $response->json('data.optimized_size')
                ]
            ]);

        // Optimized size should be smaller than original
        $this->assertLessThan(
            $response->json('data.original_size'),
            $response->json('data.optimized_size')
        );
    }

    /** @test */
    public function it_can_optimize_image_with_custom_quality()
    {
        $image = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'quality' => 50
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'quality' => 50
                ]
            ]);
    }

    /** @test */
    public function it_can_resize_image()
    {
        $image = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'width' => 800,
                'height' => 600
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'width' => 800,
                    'height' => 600
                ]
            ]);
    }

    /** @test */
    public function it_can_optimize_multiple_images()
    {
        $images = [
            UploadedFile::fake()->image('image1.jpg', 1920, 1080),
            UploadedFile::fake()->image('image2.jpg', 1280, 720),
            UploadedFile::fake()->image('image3.jpg', 800, 600)
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize/bulk', [
                'images' => $images,
                'quality' => 80
            ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'original_size',
                        'optimized_size',
                        'compression_ratio',
                        'file_path'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_validates_bulk_optimization_input()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize/bulk', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images']);
    }

    /** @test */
    public function it_can_get_optimization_statistics()
    {
        // Simulate some optimization history
        $response = $this->actingAs($this->user)
            ->getJson('/api/image-optimize/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_optimizations',
                    'total_size_saved',
                    'average_compression_ratio',
                    'most_optimized_formats'
                ]
            ]);
    }

    /** @test */
    public function it_can_optimize_image_with_format_conversion()
    {
        $image = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'format' => 'webp',
                'quality' => 85
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'format' => 'webp'
                ]
            ]);
    }

    /** @test */
    public function it_validates_format_parameter()
    {
        $image = UploadedFile::fake()->image('test-image.jpg');

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'format' => 'invalid-format'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function it_can_optimize_image_with_watermark()
    {
        $image = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);
        $watermark = UploadedFile::fake()->image('watermark.png', 200, 100);

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'watermark' => $watermark,
                'watermark_position' => 'bottom-right'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'watermark_applied' => true
                ]
            ]);
    }

    /** @test */
    public function it_validates_watermark_position()
    {
        $image = UploadedFile::fake()->image('test-image.jpg');
        $watermark = UploadedFile::fake()->image('watermark.png');

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'watermark' => $watermark,
                'watermark_position' => 'invalid-position'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['watermark_position']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/image-optimize/statistics');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_large_image_files()
    {
        $image = UploadedFile::fake()->image('large-image.jpg', 4000, 3000);

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'quality' => 70
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'original_size',
                    'optimized_size',
                    'compression_ratio'
                ]
            ]);
    }

    /** @test */
    public function it_can_optimize_image_with_custom_filters()
    {
        $image = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);

        $response = $this->actingAs($this->user)
            ->postJson('/api/image-optimize', [
                'image' => $image,
                'filters' => [
                    'brightness' => 10,
                    'contrast' => 5,
                    'saturation' => -5
                ]
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'filters_applied' => true
                ]
            ]);
    }

    /** @test */
    public function it_can_get_supported_formats()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/image-optimize/formats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'input_formats',
                    'output_formats'
                ]
            ]);
    }
} 