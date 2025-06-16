<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\UpdateDueDateReminderStatusBatch;

// All scheduled tasks defined here
Schedule::job(new UpdateDueDateReminderStatusBatch)->everyMinute();
Schedule::command('domain:sync-domain-for-account')->everySixHours();
Schedule::command('dns:sync --all')->everySixHours()->withoutOverlapping();
