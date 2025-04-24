<?php

namespace App\Jobs;

use App\Enums\Utility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Repositories\ServerStatRepository;
use Spatie\ServerMonitor\Models\Check;

class StoreServerStat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(Utility $utility, ServerStatRepository $serverStatRepository): void
    {
        $checks = Check::all();
        $stat = [
            'cpu' => $checks->where('type', 'cpu_load')->first()->value ?? 0,
            'ram' => $checks->where('type', 'memory_usage')->first()->value ?? 0,
            'disk' => $checks->where('type', 'disk_usage')->first()->value ?? 0,
            'timestamp' => $utility->covertDateTimeToMongoBSONDateGMT7(date('Y-m-d H:i:s')),
        ];

        $serverStatRepository->create($stat);
    }
}
