<?php

namespace App\Jobs;

use App\Repositories\PushSystemConfigRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdatePushSystemConfig implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $id;

    /**
     * Create a new job instance.
     */
    public function __construct($data, $id)
    {
        $this->data = $data;
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(PushSystemConfigRepository $pushSystemConfigRepository): void
    {
        $pushSystemConfigRepository->updateById($this->id, $this->data);
    }
}
