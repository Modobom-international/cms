<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\Domain\SyncDomainForAccount;
use App\Console\Commands\MonitorServer;

Schedule::command(SyncDomainForAccount::class)->everyFiveMinutes()->withoutOverlapping();
Schedule::command(MonitorServer::class)->everyFiveMinutes()->withoutOverlapping();
