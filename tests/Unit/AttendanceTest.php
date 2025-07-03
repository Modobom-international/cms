<?php

namespace Tests\Unit;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_an_attendance()
    {
        $attendance = Attendance::create([
            'employee_id' => 1,
            'date' => '2025-01-01',
            'type' => 'full_day',
            'checkin_time' => '2025-01-01 09:00:00',
            'checkout_time' => '2025-01-01 17:00:00',
            'total_work_hours' => 8.0,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => 1,
            'date' => '2025-01-01',
            'status' => 'completed',
        ]);
    }
} 