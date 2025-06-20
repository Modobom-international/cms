<?php

namespace App\Jobs;

use App\Repositories\EventRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class StoreEvents implements ShouldQueue
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
    public function handle(EventRepository $eventRepository): void
    {
        try {
            $insert = [
                'title' => $this->data['title'],
                'description' => $this->data['description'] ?? '',
                'start_datetime' => $this->data['start_datetime'],
                'end_datetime' => $this->data['end_datetime'],
                'color' => $this->data['color'],
                'location' => $this->data['location'] ?? '',
            ];

            $eventRepository->create($insert);
        } catch (\Throwable $e) {
            Log::error("Job failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
            ]);
        }
    }
}
