<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Salary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SalaryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_salaries()
    {
        $salaries = Salary::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salaries');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'basic_salary',
                        'allowances',
                        'deductions',
                        'net_salary',
                        'effective_date',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_salary()
    {
        $salary = Salary::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/salaries/{$salary->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $salary->id,
                    'user_id' => $salary->user_id,
                    'basic_salary' => $salary->basic_salary,
                    'allowances' => $salary->allowances,
                    'deductions' => $salary->deductions,
                    'net_salary' => $salary->net_salary,
                    'effective_date' => $salary->effective_date
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_salary()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/salaries/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_a_salary()
    {
        $salaryData = [
            'user_id' => $this->user->id,
            'basic_salary' => 5000.00,
            'allowances' => 500.00,
            'deductions' => 200.00,
            'net_salary' => 5300.00,
            'effective_date' => now()->toDateString()
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/salaries', $salaryData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'user_id' => $salaryData['user_id'],
                    'basic_salary' => $salaryData['basic_salary'],
                    'allowances' => $salaryData['allowances'],
                    'deductions' => $salaryData['deductions'],
                    'net_salary' => $salaryData['net_salary'],
                    'effective_date' => $salaryData['effective_date']
                ]
            ]);

        $this->assertDatabaseHas('salaries', $salaryData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_salary()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/salaries', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'basic_salary', 'effective_date']);
    }

    /** @test */
    public function it_validates_salary_amounts()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/salaries', [
                'user_id' => $this->user->id,
                'basic_salary' => -1000, // Invalid negative amount
                'effective_date' => now()->toDateString()
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['basic_salary']);
    }

    /** @test */
    public function it_can_update_a_salary()
    {
        $salary = Salary::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'basic_salary' => 6000.00,
            'allowances' => 600.00,
            'deductions' => 300.00,
            'net_salary' => 6300.00
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/salaries/{$salary->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $salary->id,
                    'basic_salary' => $updateData['basic_salary'],
                    'allowances' => $updateData['allowances'],
                    'deductions' => $updateData['deductions'],
                    'net_salary' => $updateData['net_salary']
                ]
            ]);

        $this->assertDatabaseHas('salaries', [
            'id' => $salary->id,
            'basic_salary' => $updateData['basic_salary'],
            'allowances' => $updateData['allowances'],
            'deductions' => $updateData['deductions'],
            'net_salary' => $updateData['net_salary']
        ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_salary()
    {
        $updateData = [
            'basic_salary' => 6000.00
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/salaries/999', $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_a_salary()
    {
        $salary = Salary::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/salaries/{$salary->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Salary deleted successfully']);

        $this->assertDatabaseMissing('salaries', ['id' => $salary->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_salary()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/salaries/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_get_salary_by_user()
    {
        Salary::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $otherUser = User::factory()->create();
        Salary::factory()->count(2)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}/salaries");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_current_salary()
    {
        Salary::factory()->create([
            'user_id' => $this->user->id,
            'effective_date' => now()->subMonths(6)
        ]);

        $currentSalary = Salary::factory()->create([
            'user_id' => $this->user->id,
            'effective_date' => now()->subMonths(1)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}/salary/current");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $currentSalary->id,
                    'user_id' => $currentSalary->user_id
                ]
            ]);
    }

    /** @test */
    public function it_can_get_salary_history()
    {
        Salary::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}/salary/history");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_can_calculate_salary_increase()
    {
        $oldSalary = Salary::factory()->create([
            'user_id' => $this->user->id,
            'basic_salary' => 5000.00,
            'effective_date' => now()->subMonths(12)
        ]);

        $newSalary = Salary::factory()->create([
            'user_id' => $this->user->id,
            'basic_salary' => 6000.00,
            'effective_date' => now()
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/salaries/{$newSalary->id}/increase");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'percentage_increase' => 20.0,
                    'amount_increase' => 1000.00
                ]
            ]);
    }

    /** @test */
    public function it_can_get_salary_statistics()
    {
        Salary::factory()->count(5)->create([
            'basic_salary' => 5000.00
        ]);

        Salary::factory()->count(3)->create([
            'basic_salary' => 6000.00
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salaries/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_employees',
                    'average_salary',
                    'highest_salary',
                    'lowest_salary',
                    'salary_distribution'
                ]
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/salaries');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_salaries()
    {
        $otherUser = User::factory()->create();
        $salary = Salary::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/salaries/{$salary->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_export_salary_data()
    {
        Salary::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/salaries/export?format=csv');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv');
    }

    /** @test */
    public function it_can_get_salary_by_date_range()
    {
        Salary::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'effective_date' => now()->subMonths(3)
        ]);

        Salary::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'effective_date' => now()->subMonths(8)
        ]);

        $startDate = now()->subMonths(6)->toDateString();
        $endDate = now()->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/salaries/range?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
} 