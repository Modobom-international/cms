<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\MonitorServer;
use App\Jobs\UpdateDueDateReminderStatusBatch;

// All scheduled tasks defined here
Schedule::job(new UpdateDueDateReminderStatusBatch)->everyMinute();
Schedule::command('domain:sync-domain-for-account')->everySixHours();
Schedule::command('dns:sync --all')->everySixHours()->withoutOverlapping();
Schedule::command(MonitorServer::class)->everyFiveMinutes()->withoutOverlapping();

// Alternative schedules (uncomment as needed):

// Daily sync at 2 AM
// Schedule::command('dns:sync --all')->dailyAt('02:00')
//     ->name('sync-dns-records-daily')
//     ->withoutOverlapping(240)
//     ->onOneServer()
//     ->runInBackground();

// Hourly sync (for high-traffic environments)
// Schedule::command('dns:sync --all')->hourly()
//     ->name('sync-dns-records-hourly')
//     ->withoutOverlapping(30)
//     ->onOneServer()
//     ->runInBackground();
