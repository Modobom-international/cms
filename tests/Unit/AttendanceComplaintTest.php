<?php

namespace Tests\Unit;

use App\Models\AttendanceComplaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceComplaintTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_complaint()
    {
        $complaint = AttendanceComplaint::create([
            'employee_id' => 1,
            'attendance_id' => 1,
            'complaint_type' => 'incorrect_time',
            'description' => 'Test complaint',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('attendance_complaints', [
            'employee_id' => 1,
            'attendance_id' => 1,
            'status' => 'pending',
        ]);
    }
} 