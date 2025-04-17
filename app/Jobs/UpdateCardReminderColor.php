<?php

// app/Jobs/UpdateDueDateReminderStatus.php

namespace App\Jobs;

use App\Models\DueDate;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateCardReminderColor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $dueDate;
    
    public function __construct(DueDate $dueDate)
    {
        $this->dueDate = $dueDate;
    }
    
    public function handle(): void
    {
        $now = Carbon::now();
        $dueAt = Carbon::parse($this->dueDate->due_date);
        $reminderMinutes = (int) $this->dueDate->due_reminder;
        
        if ($this->dueDate->is_completed) {
            $this->dueDate->status_color = 'green';
            $this->dueDate->status_text = 'Đã hoàn thành';
        } elseif ($now->greaterThan($dueAt)) {
            $this->dueDate->status_color = 'red';
            $this->dueDate->status_text = 'Quá hạn';
        } elseif ($now->greaterThanOrEqualTo($dueAt->copy()->subMinutes($reminderMinutes))) {
            $this->dueDate->status_color = 'orange';
            $this->dueDate->status_text = 'Sắp đến hạn';
        } else {
            $this->dueDate->status_color = 'gray';
            $this->dueDate->status_text = 'Chưa đến hạn';
        }
        
        $this->dueDate->save();
    }
}
