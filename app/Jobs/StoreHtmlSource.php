<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Helper\Common;
use DB;

class StoreHtmlSource implements ShouldQueue
{
    use Queueable;

    private $data;

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
        DB::table('html_sources')
            ->insert([
                'url' => $this->data['url'],
                'source' => $this->data['source'],
                'app_id' => $this->data['appId'],
                'version' => $this->data['version'],
                'note' => $this->data['note'],
                'device_id' => $this->data['deviceId'],
                'country' => $this->data['country'],
                'platform' => $this->data['platform'],
                'created_at' => Common::getCurrentVNTime(),
                'created_date' => Common::getCurrentVNTime('Y-m-d'),
            ]);
    }
}
