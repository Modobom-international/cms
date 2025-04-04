<?php

namespace App\Jobs;

use App\Repositories\TrackingEventRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreTrackingEvent implements ShouldQueue
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
    public function handle(TrackingEventRepository $trackingEventRepository): void
    {
        $trackingEventRepository->create($this->data);
    }
}
