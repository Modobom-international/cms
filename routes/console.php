<?php

use App\Jobs\StoreServerStat;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\Domain\SyncDomainForAccount;

Schedule::job(new StoreServerStat)->everyFiveSeconds();
Schedule::command(new SyncDomainForAccount)->everyFiveMinutes()->withoutOverlapping()->runInBackground();
