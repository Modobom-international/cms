<?php

namespace App\Jobs;

use App\Repositories\AppInformationRepository;
use App\Repositories\CachePoolRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class StoreAppInformation implements ShouldQueue
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
    public function handle(AppInformationRepository $appInformationRepository, CachePoolRepository $cachePoolRepository): void
    {
        try {
            $key = 'menu_filter_app_information';
            $cacheMenu = $cachePoolRepository->getCacheByKey($key);

            if ($this->data['app_name'] && !in_array($this->data['app_name'], $cacheMenu['app_name'])) {
                $cacheMenu['app_name'][] = $this->data['app_name'];
            }

            if ($this->data['os_name'] && !in_array($this->data['os_name'], $cacheMenu['os_name'])) {
                $cacheMenu['os_name'][] = $this->data['os_name'];
            }

            if ($this->data['os_version'] && !in_array($this->data['os_version'], $cacheMenu['os_version'])) {
                $cacheMenu['os_version'][] = $this->data['os_version'];
            }

            if ($this->data['app_version'] && !in_array($this->data['app_version'], $cacheMenu['app_version'])) {
                $cacheMenu['app_version'][] = $this->data['app_version'];
            }

            if ($this->data['category'] && !in_array($this->data['category'], $cacheMenu['category'])) {
                $cacheMenu['category'][] = $this->data['category'];
            }

            if ($this->data['platform'] && !in_array($this->data['platform'], $cacheMenu['platform'])) {
                $cacheMenu['platform'][] = $this->data['platform'];
            }

            if ($this->data['country'] && !in_array($this->data['country'], $cacheMenu['country'])) {
                $cacheMenu['country'][] = $this->data['country'];
            }

            if ($this->data['network'] && !in_array($this->data['network'], $cacheMenu['network'])) {
                $cacheMenu['network'][] = $this->data['network'];
            }

            $dataUpdate = [
                'data' => $cacheMenu,
            ];

            $cachePoolRepository->updateCacheByKey($key, $dataUpdate);
            $appInformationRepository->create($this->data);
        } catch (\Throwable $e) {
            Log::error("Job failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
            ]);
        }
    }
}
