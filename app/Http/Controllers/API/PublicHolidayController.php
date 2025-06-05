<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    /**
     * Display a listing of public holidays
     */
    public function index(Request $request)
    {
        $query = PublicHoliday::query();

        // Filter by year
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        // Filter by holiday type
        if ($request->has('holiday_type')) {
            $query->where('holiday_type', $request->holiday_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('observed_date', [$request->start_date, $request->end_date]);
        }

        $holidays = $query->orderBy('observed_date')
            ->paginate($request->get('per_page', 15));

        return response()->json($holidays);
    }

    /**
     * Store a newly created public holiday
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'original_date' => 'required|date',
            'year' => 'required|integer',
            'holiday_type' => 'required|in:public,company,new_year,religious,national',
            'adjustment_rule' => 'required|in:none,previous_workday,next_workday,company_decision',
            'is_recurring' => 'boolean',
            'is_paid' => 'boolean',
            'affects_salary' => 'boolean',
            'requires_coverage' => 'boolean',
            'coverage_requirements' => 'nullable|string',
            'overtime_multiplier' => 'nullable|numeric|min:1|max:5',
            'description' => 'nullable|string',
            'affected_departments' => 'nullable|array'
        ]);

        // Calculate observed date based on adjustment rule
        $originalDate = Carbon::parse($request->original_date);
        $observedDate = $this->calculateObservedDate($originalDate, $request->adjustment_rule);

        $holiday = PublicHoliday::create([
            'name' => $request->name,
            'original_date' => $originalDate->format('Y-m-d'),
            'observed_date' => $observedDate->format('Y-m-d'),
            'year' => $request->year,
            'is_recurring' => $request->boolean('is_recurring', true),
            'adjustment_rule' => $request->adjustment_rule,
            'holiday_type' => $request->holiday_type,
            'is_paid' => $request->boolean('is_paid', true),
            'affects_salary' => $request->boolean('affects_salary', false),
            'requires_coverage' => $request->boolean('requires_coverage', false),
            'coverage_requirements' => $request->coverage_requirements,
            'overtime_multiplier' => $request->get('overtime_multiplier', 2.0),
            'description' => $request->description,
            'is_active' => true,
            'affected_departments' => $request->affected_departments
        ]);

        return response()->json([
            'message' => 'Public holiday created successfully',
            'holiday' => $holiday
        ], 201);
    }

    /**
     * Display the specified public holiday
     */
    public function show($id)
    {
        $holiday = PublicHoliday::findOrFail($id);
        return response()->json($holiday);
    }

    /**
     * Update the specified public holiday
     */
    public function update(Request $request, $id)
    {
        $holiday = PublicHoliday::findOrFail($id);

        $request->validate([
            'name' => 'string|max:255',
            'original_date' => 'date',
            'year' => 'integer',
            'holiday_type' => 'in:public,company,new_year,religious,national',
            'adjustment_rule' => 'in:none,previous_workday,next_workday,company_decision',
            'is_recurring' => 'boolean',
            'is_paid' => 'boolean',
            'affects_salary' => 'boolean',
            'requires_coverage' => 'boolean',
            'coverage_requirements' => 'nullable|string',
            'overtime_multiplier' => 'nullable|numeric|min:1|max:5',
            'description' => 'nullable|string',
            'affected_departments' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        // If date or adjustment rule changed, recalculate observed date
        if ($request->has('original_date') || $request->has('adjustment_rule')) {
            $originalDate = Carbon::parse($request->get('original_date', $holiday->original_date));
            $adjustmentRule = $request->get('adjustment_rule', $holiday->adjustment_rule);
            $observedDate = $this->calculateObservedDate($originalDate, $adjustmentRule);

            $holiday->original_date = $originalDate->format('Y-m-d');
            $holiday->observed_date = $observedDate->format('Y-m-d');
            $holiday->adjustment_rule = $adjustmentRule;
        }

        $holiday->update($request->except(['original_date', 'adjustment_rule']));

        return response()->json([
            'message' => 'Public holiday updated successfully',
            'holiday' => $holiday
        ]);
    }

    /**
     * Remove the specified public holiday
     */
    public function destroy($id)
    {
        $holiday = PublicHoliday::findOrFail($id);
        $holiday->delete();

        return response()->json([
            'message' => 'Public holiday deleted successfully'
        ]);
    }

    /**
     * Get holidays for a specific month
     */
    public function getHolidaysForMonth(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12'
        ]);

        $startDate = Carbon::create($request->year, $request->month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $holidays = PublicHoliday::whereBetween('observed_date', [$startDate, $endDate])
            ->where('is_active', true)
            ->orderBy('observed_date')
            ->get();

        return response()->json($holidays);
    }

    /**
     * Check if a date is a public holiday
     */
    public function checkHoliday(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $date = Carbon::parse($request->date)->format('Y-m-d');

        $holiday = PublicHoliday::where('observed_date', $date)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'date' => $date,
            'is_holiday' => !is_null($holiday),
            'holiday' => $holiday
        ]);
    }

    /**
     * Get upcoming holidays
     */
    public function getUpcomingHolidays(Request $request)
    {
        $limit = $request->get('limit', 5);
        $today = Carbon::today();

        $holidays = PublicHoliday::where('observed_date', '>=', $today)
            ->where('is_active', true)
            ->orderBy('observed_date')
            ->limit($limit)
            ->get();

        return response()->json($holidays);
    }

    /**
     * Generate holidays for a new year based on recurring holidays
     */
    public function generateYearlyHolidays(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:' . (Carbon::now()->year),
            'source_year' => 'nullable|integer'
        ]);

        $year = $request->year;
        $sourceYear = $request->get('source_year', $year - 1);

        // Check if holidays already exist for this year
        $existingCount = PublicHoliday::where('year', $year)->count();
        if ($existingCount > 0) {
            return response()->json([
                'message' => "Holidays for year {$year} already exist",
                'existing_count' => $existingCount
            ], 400);
        }

        // Get recurring holidays from source year
        $recurringHolidays = PublicHoliday::where('year', $sourceYear)
            ->where('is_recurring', true)
            ->where('is_active', true)
            ->get();

        $createdHolidays = [];

        foreach ($recurringHolidays as $holiday) {
            $originalDate = Carbon::parse($holiday->original_date);
            $newOriginalDate = $originalDate->copy()->year($year);
            $observedDate = $this->calculateObservedDate($newOriginalDate, $holiday->adjustment_rule);

            $newHoliday = PublicHoliday::create([
                'name' => $holiday->name,
                'original_date' => $newOriginalDate->format('Y-m-d'),
                'observed_date' => $observedDate->format('Y-m-d'),
                'year' => $year,
                'is_recurring' => $holiday->is_recurring,
                'adjustment_rule' => $holiday->adjustment_rule,
                'holiday_type' => $holiday->holiday_type,
                'is_paid' => $holiday->is_paid,
                'affects_salary' => $holiday->affects_salary,
                'requires_coverage' => $holiday->requires_coverage,
                'coverage_requirements' => $holiday->coverage_requirements,
                'overtime_multiplier' => $holiday->overtime_multiplier,
                'description' => $holiday->name . ' - ' . $year,
                'is_active' => true,
                'affected_departments' => $holiday->affected_departments
            ]);

            $createdHolidays[] = $newHoliday;
        }

        return response()->json([
            'message' => "Generated {$year} holidays from {$sourceYear}",
            'created_count' => count($createdHolidays),
            'holidays' => $createdHolidays
        ]);
    }

    /**
     * Calculate observed date based on adjustment rule
     */
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
