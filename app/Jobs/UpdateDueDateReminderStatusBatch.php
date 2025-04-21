<?php

namespace App\Jobs;

use App\Models\DueDate;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateDueDateReminderStatusBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(): void
    {
        $dueDates = DueDate::where('is_completed', false)
            ->whereNotNull('due_date')
            ->get();
        
        foreach ($dueDates as $dueDate) {
            UpdateCardReminderColor::dispatch($dueDate);
        }
    }
}

