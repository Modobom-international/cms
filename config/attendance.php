<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lunch Break Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control the lunch break deduction for attendance 
    | calculation. Modify these values to change lunch break policy.
    |
    */

    'lunch_break' => [
        // Whether lunch break deduction is enabled
        'enabled' => env('LUNCH_BREAK_ENABLED', true),

        // Lunch break start time (24-hour format)
        'start_time' => env('LUNCH_BREAK_START', '12:00'),

        // Lunch break end time (24-hour format)
        'end_time' => env('LUNCH_BREAK_END', '13:30'),

        // Whether lunch break only applies to full day attendance
        'full_day_only' => env('LUNCH_BREAK_FULL_DAY_ONLY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Work Hours Configuration
    |--------------------------------------------------------------------------
    |
    | Standard work hours required for different attendance types
    |
    */

    'required_hours' => [
        'full_day' => 8,
        'half_day' => 4,
    ],

];