<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreStatusCodeStat implements ShouldQueue
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
    public function handle(): void
    {
        $urls = ['https://example.com', 'https://example.org'];
        $statusCodes = [];

        foreach ($urls as $url) {
            $response = Http::get($url);
            $code = $response->status();
            $status = StatusCode::create([
                'url' => $url,
                'code' => $code,
                'timestamp' => now(),
            ]);
            $statusCodes[] = ['url' => $url, 'code' => $code, 'timestamp' => now()];
        }

        broadcast(new StatUpdated('status-codes', $statusCodes));
    }
}
