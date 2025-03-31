<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;

class FetchFullPage implements ShouldQueue
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
        $tempFilePath = storage_path('app/temp_screenshot.png');
        $disk = Storage::disk('browsershot');
        $file = 'browsershot_fullpage_' . $this->data['domain'] . '_' . str_replace('/', '_', $this->data['path']) . '.png';

        Browsershot::url($this->data['url'])
            ->setNodeBinary('/usr/bin/node')
            ->setNpmBinary('/usr/bin/npm')
            ->setChromePath('/usr/bin/google-chrome')
            ->noSandbox()
            ->fullPage()
            ->mobile()
            ->paperSize($this->data['width'], $this->data['height'], 'px')
            ->windowSize($this->data['width'], $this->data['height'])
            ->waitUntilNetworkIdle()
            ->save($tempFilePath);

        Browsershot::closeBrowser();

        $disk->put($file, file_get_contents($tempFilePath));
        unlink($tempFilePath);
    }
}
