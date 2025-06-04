<?php

namespace Database\Seeders;

use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PublicHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = Carbon::now()->year;

        $holidays = [
            [
                'name' => "New Year's Day",
                'original_date' => Carbon::create($year, 1, 1),
                'holiday_type' => 'public',
                'is_recurring' => true,
                'adjustment_rule' => 'next_workday'
            ],
            [
                'name' => 'National Day',
                'original_date' => Carbon::create($year, 8, 9),
                'holiday_type' => 'national',
                'is_recurring' => true,
                'adjustment_rule' => 'company_decision'
            ],
            [
                'name' => 'Independence Day',
                'original_date' => Carbon::create($year, 8, 31),
                'holiday_type' => 'national',
                'is_recurring' => true,
                'adjustment_rule' => 'company_decision'
            ],
            [
                'name' => 'Christmas Day',
                'original_date' => Carbon::create($year, 12, 25),
                'holiday_type' => 'religious',
                'is_recurring' => true,
                'adjustment_rule' => 'next_workday'
            ],
            [
                'name' => 'Eid al-Fitr',
                'original_date' => Carbon::create($year, 4, 10), // Approximate date
                'holiday_type' => 'religious',
                'is_recurring' => true,
                'adjustment_rule' => 'company_decision'
            ],
            [
                'name' => 'Labor Day',
                'original_date' => Carbon::create($year, 5, 1),
                'holiday_type' => 'public',
                'is_recurring' => true,
                'adjustment_rule' => 'next_workday'
            ]
        ];

        foreach ($holidays as $holiday) {
            $originalDate = $holiday['original_date'];
            $observedDate = $this->calculateObservedDate($originalDate, $holiday['adjustment_rule']);

            PublicHoliday::create([
                'name' => $holiday['name'],
                'original_date' => $originalDate->format('Y-m-d'),
                'observed_date' => $observedDate->format('Y-m-d'),
                'year' => $year,
                'is_recurring' => $holiday['is_recurring'],
                'adjustment_rule' => $holiday['adjustment_rule'],
                'holiday_type' => $holiday['holiday_type'],
                'is_paid' => true,
                'affects_salary' => false,
                'requires_coverage' => false,
                'overtime_multiplier' => 2.0,
                'description' => $holiday['name'] . ' - ' . $year,
                'is_active' => true,
                'affected_departments' => null
            ]);
        }

        $this->command->info("Created " . count($holidays) . " public holidays for {$year}");
    }

    private function calculateObservedDate(Carbon $originalDate, string $adjustmentRule): Carbon
    {
        $observedDate = $originalDate->copy();

        switch ($adjustmentRule) {
            case 'previous_workday':
                while ($observedDate->isWeekend()) {
                    $observedDate->subDay();
                }
                break;

            case 'next_workday':
                while ($observedDate->isWeekend()) {
                    $observedDate->addDay();
                }
                break;

            case 'company_decision':
                // For this example, if it falls on weekend, move to Monday
                if ($observedDate->isSaturday()) {
                    $observedDate->addDays(2); // Move to Monday
                } elseif ($observedDate->isSunday()) {
                    $observedDate->addDay(); // Move to Monday
                }
                break;

            case 'none':
            default:
                // Keep original date
                break;
        }

        return $observedDate;
    }
}
