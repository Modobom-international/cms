<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use DB;

class StoreUsersTracking implements ShouldQueue
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
    public function handle(): void
    {
        DB::connection('mongodb')->table('users_tracking')->insert([
            'event_name' => $this->data['eventName'],
            'event_data' => $this->data['eventData'],
            'user_agent' => $this->data['user']['userAgent'],
            'ip' => $this->data['user']['ip'],
            'platform' => $this->data['user']['platform'],
            'language' => $this->data['user']['language'],
            'cookies_enabled' => $this->data['user']['cookiesEnabled'],
            'screen_width' => $this->data['user']['screenWidth'],
            'screen_height' => $this->data['user']['screenHeight'],
            'timezone' => $this->data['user']['timezone'],
            'timestamp' => $this->data['timestamp'],
            'domain' => $this->data['domain'],
            'uuid' => $this->data['uuid'],
            'path' => $this->data['path']
        ]);
    }
}
