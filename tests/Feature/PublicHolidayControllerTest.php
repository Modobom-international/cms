<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PublicHoliday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PublicHolidayControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_public_holidays()
    {
        $publicHolidays = PublicHoliday::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/public-holidays');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'date',
                        'description',
                        'is_active',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_public_holiday()
    {
        $publicHoliday = PublicHoliday::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/public-holidays/{$publicHoliday->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $publicHoliday->id,
                    'name' => $publicHoliday->name,
                    'date' => $publicHoliday->date,
                    'description' => $publicHoliday->description,
                    'is_active' => $publicHoliday->is_active
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_public_holiday()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/public-holidays/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_a_public_holiday()
    {
        $holidayData = [
            'name' => 'New Year\'s Day',
            'date' => '2024-01-01',
            'description' => 'First day of the year',
            'is_active' => true
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays', $holidayData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => $holidayData['name'],
                    'date' => $holidayData['date'],
                    'description' => $holidayData['description'],
                    'is_active' => $holidayData['is_active']
                ]
            ]);

        $this->assertDatabaseHas('public_holidays', $holidayData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_public_holiday()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'date']);
    }

    /** @test */
    public function it_validates_date_format()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays', [
                'name' => 'Test Holiday',
                'date' => 'invalid-date'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /** @test */
    public function it_can_update_a_public_holiday()
    {
        $publicHoliday = PublicHoliday::factory()->create();

        $updateData = [
            'name' => 'Updated Holiday Name',
            'date' => '2024-12-25',
            'description' => 'Updated description',
            'is_active' => false
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/public-holidays/{$publicHoliday->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $publicHoliday->id,
                    'name' => $updateData['name'],
                    'date' => $updateData['date'],
                    'description' => $updateData['description'],
                    'is_active' => $updateData['is_active']
                ]
            ]);

        $this->assertDatabaseHas('public_holidays', [
            'id' => $publicHoliday->id,
            'name' => $updateData['name'],
            'date' => $updateData['date'],
            'description' => $updateData['description'],
            'is_active' => $updateData['is_active']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_public_holiday()
    {
        $updateData = [
            'name' => 'Updated Holiday',
            'date' => '2024-12-25'
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/public-holidays/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_a_public_holiday()
    {
        $publicHoliday = PublicHoliday::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/public-holidays/{$publicHoliday->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Public holiday deleted successfully']);

        $this->assertDatabaseMissing('public_holidays', ['id' => $publicHoliday->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_public_holiday()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/public-holidays/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_activate_a_public_holiday()
    {
        $publicHoliday = PublicHoliday::factory()->create([
            'is_active' => false
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/public-holidays/{$publicHoliday->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $publicHoliday->id,
                    'is_active' => true
                ]
            ]);

        $this->assertDatabaseHas('public_holidays', [
            'id' => $publicHoliday->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_deactivate_a_public_holiday()
    {
        $publicHoliday = PublicHoliday::factory()->create([
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/public-holidays/{$publicHoliday->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $publicHoliday->id,
                    'is_active' => false
                ]
            ]);

        $this->assertDatabaseHas('public_holidays', [
            'id' => $publicHoliday->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_can_get_active_public_holidays()
    {
        // Create active holidays
        PublicHoliday::factory()->count(3)->create([
            'is_active' => true
        ]);

        // Create inactive holidays
        PublicHoliday::factory()->count(2)->create([
            'is_active' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/public-holidays/active');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_public_holidays_by_year()
    {
        PublicHoliday::factory()->count(3)->create([
            'date' => '2024-01-01'
        ]);

        PublicHoliday::factory()->count(2)->create([
            'date' => '2023-12-25'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/public-holidays/year/2024');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_public_holidays_by_month()
    {
        PublicHoliday::factory()->count(2)->create([
            'date' => '2024-01-01'
        ]);

        PublicHoliday::factory()->count(1)->create([
            'date' => '2024-02-14'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/public-holidays/month/2024-01');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_check_if_date_is_public_holiday()
    {
        PublicHoliday::factory()->create([
            'date' => '2024-01-01',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays/check', [
                'date' => '2024-01-01'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_holiday' => true,
                    'holiday_name' => 'New Year\'s Day'
                ]
            ]);
    }

    /** @test */
    public function it_returns_false_for_non_holiday_date()
    {
        PublicHoliday::factory()->create([
            'date' => '2024-01-01',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays/check', [
                'date' => '2024-01-02'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_holiday' => false,
                    'holiday_name' => null
                ]
            ]);
    }

    /** @test */
    public function it_returns_false_for_inactive_holiday()
    {
        PublicHoliday::factory()->create([
            'date' => '2024-01-01',
            'is_active' => false
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays/check', [
                'date' => '2024-01-01'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_holiday' => false,
                    'holiday_name' => null
                ]
            ]);
    }

    /** @test */
    public function it_validates_date_in_check_request()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays/check', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /** @test */
    public function it_can_bulk_import_public_holidays()
    {
        $holidaysData = [
            ['name' => 'New Year\'s Day', 'date' => '2024-01-01', 'description' => 'First day of year'],
            ['name' => 'Christmas Day', 'date' => '2024-12-25', 'description' => 'Christmas celebration'],
            ['name' => 'Independence Day', 'date' => '2024-07-04', 'description' => 'Independence celebration']
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays/bulk-import', [
                'holidays' => $holidaysData
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Public holidays imported successfully']);

        foreach ($holidaysData as $holidayData) {
            $this->assertDatabaseHas('public_holidays', $holidayData);
        }
    }

    /** @test */
    public function it_validates_bulk_import_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays/bulk-import', [
                'holidays' => [
                    ['name' => 'Test Holiday', 'date' => 'invalid-date'],
                    ['date' => '2024-01-01'] // Missing name
                ]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['holidays.0.date', 'holidays.1.name']);
    }

    /** @test */
    public function it_can_export_public_holidays()
    {
        PublicHoliday::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/public-holidays/export?format=csv');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv');
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/public-holidays');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_duplicate_holiday_dates()
    {
        PublicHoliday::factory()->create([
            'date' => '2024-01-01'
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/public-holidays', [
                'name' => 'Another Holiday',
                'date' => '2024-01-01'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /** @test */
    public function it_can_get_public_holiday_statistics()
    {
        // Create various public holidays
        PublicHoliday::factory()->count(5)->create(['is_active' => true]);
        PublicHoliday::factory()->count(3)->create(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/public-holidays/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_holidays',
                    'active_holidays',
                    'inactive_holidays',
                    'holidays_this_year',
                    'upcoming_holidays'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_upcoming_public_holidays()
    {
        // Create past holidays
        PublicHoliday::factory()->count(2)->create([
            'date' => now()->subDays(10)->toDateString(),
            'is_active' => true
        ]);

        // Create upcoming holidays
        PublicHoliday::factory()->count(3)->create([
            'date' => now()->addDays(10)->toDateString(),
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/public-holidays/upcoming');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
} 