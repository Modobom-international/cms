<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'employee_id',
        'date',
        'type',
        'checkin_time',
        'checkout_time',
        'total_work_hours',
        'status',
        'description'
    ];

    protected $casts = [
        'date' => 'date',
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
        'total_work_hours' => 'decimal:2'
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function calculateWorkHours()
    {
        if (!$this->checkout_time) {
            return null;
        }

        $checkin = Carbon::parse($this->checkin_time);
        $checkout = Carbon::parse($this->checkout_time);

        $totalHours = $checkout->diffInMinutes($checkin) / 60;

        // Subtract lunch break for full day
        if ($this->type === 'full_day') {
            $totalHours -= 1.5;
        }

        return round($totalHours, 2);
    }

    public function updateStatus()
    {
        if (!$this->total_work_hours) {
            return;
        }

        $requiredHours = $this->type === 'full_day' ? 8 : 4;
        $this->status = $this->total_work_hours >= $requiredHours ? 'completed' : 'incomplete';
    }
}
