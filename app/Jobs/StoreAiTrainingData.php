<?php

namespace App\Jobs;

use App\Repositories\AiTrainingDataRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreAiTrainingData implements ShouldQueue
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
    public function handle(AiTrainingDataRepository $aiTrainingDataRepository): void
    {
        $aiTrainingDataRepository->create($this->data);
    }
}
