<?php

use App\Jobs\StoreServerStat;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\Domain\SyncDomainForAccount;

Schedule::job(new StoreServerStat)->everyFiveSeconds();
Schedule::command(SyncDomainForAccount::class)->everyFiveMinutes()->withoutOverlapping();
