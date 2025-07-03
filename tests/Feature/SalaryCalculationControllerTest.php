<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SalaryCalculation;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SalaryCalculationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_salary_calculations()
    {
        $calculations = SalaryCalculation::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salary-calculations');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'month',
                        'year',
                        'basic_salary',
                        'overtime_hours',
                        'overtime_rate',
                        'overtime_amount',
                        'deductions',
                        'net_salary',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_salary_calculation()
    {
        $calculation = SalaryCalculation::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/salary-calculations/{$calculation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $calculation->id,
                    'user_id' => $calculation->user_id,
                    'month' => $calculation->month,
                    'year' => $calculation->year,
                    'basic_salary' => $calculation->basic_salary,
                    'overtime_hours' => $calculation->overtime_hours,
                    'overtime_rate' => $calculation->overtime_rate,
                    'overtime_amount' => $calculation->overtime_amount,
                    'deductions' => $calculation->deductions,
                    'net_salary' => $calculation->net_salary,
                    'status' => $calculation->status
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_salary_calculation()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/salary-calculations/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_a_salary_calculation()
    {
        $calculationData = [
            'user_id' => $this->user->id,
            'month' => 12,
            'year' => 2024,
            'basic_salary' => 5000.00,
            'overtime_hours' => 10,
            'overtime_rate' => 25.00,
            'overtime_amount' => 250.00,
            'deductions' => 500.00,
            'net_salary' => 4750.00,
            'status' => 'pending'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/salary-calculations', $calculationData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'user_id' => $calculationData['user_id'],
                    'month' => $calculationData['month'],
                    'year' => $calculationData['year'],
                    'basic_salary' => $calculationData['basic_salary'],
                    'overtime_hours' => $calculationData['overtime_hours'],
                    'overtime_rate' => $calculationData['overtime_rate'],
                    'overtime_amount' => $calculationData['overtime_amount'],
                    'deductions' => $calculationData['deductions'],
                    'net_salary' => $calculationData['net_salary'],
                    'status' => $calculationData['status']
                ]
            ]);

        $this->assertDatabaseHas('salary_calculations', $calculationData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_salary_calculation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/salary-calculations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'month', 'year', 'basic_salary']);
    }

    /** @test */
    public function it_validates_month_range()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/salary-calculations', [
                'user_id' => $this->user->id,
                'month' => 13,
                'year' => 2024,
                'basic_salary' => 5000.00
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['month']);
    }

    /** @test */
    public function it_can_update_a_salary_calculation()
    {
        $calculation = SalaryCalculation::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'overtime_hours' => 15,
            'overtime_amount' => 375.00,
            'deductions' => 600.00,
            'net_salary' => 4775.00,
            'status' => 'approved'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/salary-calculations/{$calculation->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $calculation->id,
                    'overtime_hours' => $updateData['overtime_hours'],
                    'overtime_amount' => $updateData['overtime_amount'],
                    'deductions' => $updateData['deductions'],
                    'net_salary' => $updateData['net_salary'],
                    'status' => $updateData['status']
                ]
            ]);

        $this->assertDatabaseHas('salary_calculations', [
            'id' => $calculation->id,
            'overtime_hours' => $updateData['overtime_hours'],
            'overtime_amount' => $updateData['overtime_amount'],
            'deductions' => $updateData['deductions'],
            'net_salary' => $updateData['net_salary'],
            'status' => $updateData['status']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_salary_calculation()
    {
        $updateData = [
            'overtime_hours' => 15,
            'status' => 'approved'
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/salary-calculations/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_a_salary_calculation()
    {
        $calculation = SalaryCalculation::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/salary-calculations/{$calculation->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Salary calculation deleted successfully']);

        $this->assertDatabaseMissing('salary_calculations', ['id' => $calculation->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_salary_calculation()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/salary-calculations/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_approve_a_salary_calculation()
    {
        $calculation = SalaryCalculation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/salary-calculations/{$calculation->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $calculation->id,
                    'status' => 'approved'
                ]
            ]);

        $this->assertDatabaseHas('salary_calculations', [
            'id' => $calculation->id,
            'status' => 'approved'
        ]);
    }

    /** @test */
    public function it_can_reject_a_salary_calculation()
    {
        $calculation = SalaryCalculation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/salary-calculations/{$calculation->id}/reject", [
                'reason' => 'Incorrect overtime calculation'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $calculation->id,
                    'status' => 'rejected'
                ]
            ]);

        $this->assertDatabaseHas('salary_calculations', [
            'id' => $calculation->id,
            'status' => 'rejected'
        ]);
    }

    /** @test */
    public function it_can_calculate_salary_for_user()
    {
        // Create attendance records
        Attendance::factory()->count(20)->create([
            'user_id' => $this->user->id,
            'date' => now()->startOfMonth(),
            'check_in' => '09:00:00',
            'check_out' => '17:00:00'
        ]);

        // Create leave requests
        LeaveRequest::factory()->create([
            'user_id' => $this->user->id,
            'start_date' => now()->startOfMonth()->addDays(5),
            'end_date' => now()->startOfMonth()->addDays(5),
            'status' => 'approved'
        ]);

        $calculationData = [
            'user_id' => $this->user->id,
            'month' => now()->month,
            'year' => now()->year,
            'basic_salary' => 5000.00,
            'overtime_rate' => 25.00
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/salary-calculations/calculate', $calculationData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'month',
                    'year',
                    'basic_salary',
                    'overtime_hours',
                    'overtime_amount',
                    'deductions',
                    'net_salary',
                    'working_days',
                    'leave_days',
                    'public_holidays'
                ]
            ]);
    }

    /** @test */
    public function it_validates_calculation_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/salary-calculations/calculate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'month', 'year', 'basic_salary']);
    }

    /** @test */
    public function it_can_get_salary_calculations_by_user()
    {
        SalaryCalculation::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}/salary-calculations");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_salary_calculations_by_month_year()
    {
        SalaryCalculation::factory()->count(3)->create([
            'month' => 12,
            'year' => 2024
        ]);

        SalaryCalculation::factory()->count(2)->create([
            'month' => 11,
            'year' => 2024
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salary-calculations/month/12/2024');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_pending_salary_calculations()
    {
        SalaryCalculation::factory()->count(3)->create([
            'status' => 'pending'
        ]);

        SalaryCalculation::factory()->count(2)->create([
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salary-calculations/pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_approved_salary_calculations()
    {
        SalaryCalculation::factory()->count(3)->create([
            'status' => 'approved'
        ]);

        SalaryCalculation::factory()->count(2)->create([
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salary-calculations/approved');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_export_salary_calculations()
    {
        SalaryCalculation::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salary-calculations/export?format=csv');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv');
    }

    /** @test */
    public function it_can_get_salary_statistics()
    {
        SalaryCalculation::factory()->count(5)->create([
            'status' => 'approved',
            'net_salary' => 5000.00
        ]);

        SalaryCalculation::factory()->count(3)->create([
            'status' => 'pending',
            'net_salary' => 4500.00
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salary-calculations/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_calculations',
                    'approved_calculations',
                    'pending_calculations',
                    'rejected_calculations',
                    'total_payroll',
                    'average_salary'
                ]
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/salary-calculations');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_other_user_calculations()
    {
        $otherUser = User::factory()->create();
        $calculation = SalaryCalculation::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/salary-calculations/{$calculation->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_bulk_approve_salary_calculations()
    {
        $calculations = SalaryCalculation::factory()->count(3)->create([
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/salary-calculations/bulk-approve', [
                'calculation_ids' => $calculations->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Salary calculations approved successfully']);

        foreach ($calculations as $calculation) {
            $this->assertDatabaseHas('salary_calculations', [
                'id' => $calculation->id,
                'status' => 'approved'
            ]);
        }
    }

    /** @test */
    public function it_can_get_salary_report()
    {
        SalaryCalculation::factory()->count(5)->create([
            'month' => 12,
            'year' => 2024,
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salary-calculations/report?month=12&year=2024');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'month',
                    'year',
                    'total_employees',
                    'total_payroll',
                    'average_salary',
                    'calculations'
                ]
            ]);
    }
} 