<?php

namespace App\Console;

use App\Jobs\UpdateDueDateReminderStatusBatch;
use App\Jobs\SyncDnsRecords;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new UpdateDueDateReminderStatusBatch)->everyMinute();
        $schedule->command('domain:sync-domain-for-account')->everyFiveMinutes();
        $schedule->command('dns:sync --all')->everyFiveMinutes();

        // Alternative schedules (uncomment as needed):

        // Daily sync at 2 AM
        // $schedule->command('dns:sync --all')->dailyAt('02:00')
        //     ->name('sync-dns-records-daily')
        //     ->withoutOverlapping(240)
        //     ->onOneServer()
        //     ->runInBackground();

        // Hourly sync (for high-traffic environments)
        // $schedule->command('dns:sync --all')->hourly()
        //     ->name('sync-dns-records-hourly')
        //     ->withoutOverlapping(30)
        //     ->onOneServer()
        //     ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
