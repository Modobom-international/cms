<?php

namespace App\Jobs;

use App\Repositories\MonitorServerRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class StoreMonitorServer implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(MonitorServerRepository $monitorServerRepository): void
    {
        try {
            $monitorServerRepository->create($this->data);
        } catch (\Throwable $e) {
            Log::error("Job failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
            ]);
        }
    }
}
