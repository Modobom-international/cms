<?php

namespace Tests\Unit;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_leave_request()
    {
        $leave = LeaveRequest::create([
            'employee_id' => 1,
            'leave_type' => 'sick',
            'request_type' => 'absence',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-02',
            'is_full_day' => true,
            'reason' => 'Test reason',
            'status' => 'pending',
            'total_days' => 1.0,
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => 1,
            'leave_type' => 'sick',
            'status' => 'pending',
        ]);
    }
} 