<?php

namespace App\Jobs;

use App\Models\DueDate;
use Carbon\Carbon;

class UpdateDueDateStatus extends Job
{
    protected $dueDate;
    
    public function __construct(DueDate $dueDate)
    {
        $this->dueDate = $dueDate;
    }
    
    public function handle()
    {
        $dueDate = $this->dueDate;
        $currentTime = Carbon::now();
        $dueDateTime = Carbon::parse($dueDate->due_date);
        
        // Tính toán color và text dựa trên due_date và trạng thái
        if ($dueDate->is_completed) {
            $dueDate->status_color = 'green';  // Màu cho "Hoàn thành"
            $dueDate->status_text = 'Completed';
        } else {
            // Nếu quá hạn
            if ($currentTime->greaterThan($dueDateTime)) {
                $dueDate->status_color = 'red';  // Màu cho "Quá hạn"
                $dueDate->status_text = 'Overdue';
            } else {
                // Nếu chưa quá hạn
                $dueDate->status_color = 'yellow';  // Màu cho "Chưa hoàn thành"
                $dueDate->status_text = 'In progress';
            }
        }
    
        $dueDate->save();
    }
}
