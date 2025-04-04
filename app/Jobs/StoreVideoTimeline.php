<?php

namespace App\Jobs;

use App\Repositories\VideoTimelineRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreVideoTimeline implements ShouldQueue
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
    public function handle(VideoTimelineRepository $videoTimelineRepository): void
    {
        $videoTimelineRepository->create($this->data);
    }
}
