<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
// Import job ở đây
use App\Jobs\UpdateDueDateReminderStatusBatch;

class Kernel extends ConsoleKernel
{
    /**
     * Đăng ký các command tùy chỉnh (nếu có).
     */
    protected $commands = [
        //
    ];
    
    /**
     * Định nghĩa các schedule job.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Chạy job mỗi phút
        $schedule->job(new UpdateDueDateReminderStatusBatch)->everyMinute();
        // Có thể thêm log vào đây nếu muốn debug
         \Log::info('Scheduler is running...');
    }
    
    /**
     * Đăng ký các command cho console kernel.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        
        require base_path('routes/console.php');
    }
}
