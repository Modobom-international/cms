<?php

namespace Tests\Unit;

use App\Models\PublicHoliday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHolidayTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_public_holiday()
    {
        $holiday = PublicHoliday::create([
            'name' => 'Test Holiday',
            'original_date' => '2025-01-01',
            'observed_date' => '2025-01-01',
            'year' => 2025,
        ]);

        $this->assertDatabaseHas('public_holidays', [
            'name' => 'Test Holiday',
            'year' => 2025,
        ]);
    }
} 