<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\MonitorServer;

Schedule::command(MonitorServer::class)->everyFiveMinutes()->withoutOverlapping();
