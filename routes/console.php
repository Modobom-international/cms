<?php

use App\Jobs\StoreServerStat;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new StoreServerStat)->everyFiveSeconds();
