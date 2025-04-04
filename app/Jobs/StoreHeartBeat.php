<?php

namespace App\Jobs;

use App\Repositories\HeartBeatRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreHeartBeat implements ShouldQueue
{
    use Queueable;

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
    public function handle(HeartBeatRepository $heartBeatRepository): void
    {
        $heartBeatRepository->create($this->data);
    }
}
