<?php

namespace App\Jobs;

use App\Repositories\AppInformationRepository;
use App\Repositories\CachePoolRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class StoreAppInformation implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected $data;
    protected $appInformationRepository;
    protected $cachePoolRepository;

    /**
     * Create a new job instance.
     */
    public function __construct($data, AppInformationRepository $appInformationRepository, CachePoolRepository $cachePoolRepository)
    {
        $this->data = $data;
        $this->appInformationRepository = $appInformationRepository;
        $this->cachePoolRepository = $cachePoolRepository;
    }

    /**
     * Execute the job.
     */
    public function handle(AppInformationRepository $appInformationRepository, CachePoolRepository $cachePoolRepository): void
    {
        try {
            $key = 'menu_filter_app_information';
            $appInformationRepository->create($this->data);
            $cacheMenu = $cachePoolRepository->getCacheByKey($key);

            foreach ($this->data as $record) {
                if ($record['app_name'] && !in_array($record['app_name'], $cacheMenu['app_name'])) {
                    $cacheMenu['app_name'][] = $record['app_name'];
                }

                if ($record['os_name'] && !in_array($record['os_name'], $cacheMenu['os_name'])) {
                    $cacheMenu['os_name'][] = $record['os_name'];
                }

                if ($record['os_version'] && !in_array($record['os_version'], $cacheMenu['os_version'])) {
                    $cacheMenu['os_version'][] = $record['os_version'];
                }

                if ($record['app_version'] && !in_array($record['app_version'], $cacheMenu['app_version'])) {
                    $cacheMenu['app_version'][] = $record['app_version'];
                }

                if ($record['category'] && !in_array($record['category'], $cacheMenu['category'])) {
                    $cacheMenu['category'][] = $record['category'];
                }

                if ($record['platform'] && !in_array($record['platform'], $cacheMenu['platform'])) {
                    $cacheMenu['platform'][] = $record['platform'];
                }

                if ($record['country'] && !in_array($record['country'], $cacheMenu['country'])) {
                    $cacheMenu['country'][] = $record['country'];
                }
            }

            $dataUpdate = [
                'data' => $cacheMenu,
            ];

            $cachePoolRepository->updateCacheByKey($key, $dataUpdate);
        } catch (\Throwable $e) {
            Log::error("Job failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
            ]);
        }
    }
}
