<?php

namespace App\Console\Commands;

use App\Repositories\AppInformationRepository;
use App\Repositories\CachePoolRepository;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Cache;

class CacheMenuAppInformation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-menu-app-information';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache menu app information';

    protected $appInformationRepository;
    protected $cachePoolRepository;

    public function __construct(AppInformationRepository $appInformationRepository, CachePoolRepository $cachePoolRepository)
    {
        $this->appInformationRepository = $appInformationRepository;
        $this->cachePoolRepository = $cachePoolRepository;
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = $this->appInformationRepository->get();
        $listMenu = [
            'app_name' => [],
            'os_name' => [],
            'os_version' => [],
            'app_version' => [],
            'category' => [],
            'platform' => [],
            'country' => [],
            'event_name' => [],
            'network' => []
        ];

        foreach ($data as $record) {
            if (!empty($record->app_name) && !in_array($record->app_name, $listMenu['app_name'])) {
                $listMenu['app_name'][] = $record->app_name;
            }

            if (!empty($record->os_name) && !in_array($record->os_name, $listMenu['os_name'])) {
                $listMenu['os_name'][] = $record->os_name;
            }

            if (!empty($record->os_version) && !in_array($record->os_version, $listMenu['os_version'])) {
                $listMenu['os_version'][] = $record->os_version;
            }

            if (!empty($record->app_version) && !in_array($record->app_version, $listMenu['app_version'])) {
                $listMenu['app_version'][] = $record->app_version;
            }

            if (!empty($record->category) && !in_array($record->category, $listMenu['category'])) {
                $listMenu['category'][] = $record->category;
            }

            if (!empty($record->platform) && !in_array($record->platform, $listMenu['platform'])) {
                $listMenu['platform'][] = $record->platform;
            }

            if (!empty($record->country) && !in_array($record->country, $listMenu['country'])) {
                $listMenu['country'][] = $record->country;
            }

            if (!empty($record->event_name) && !in_array($record->event_name, $listMenu['event_name'])) {
                $listMenu['event_name'][] = $record->event_name;
            }

            if (!empty($record->network) && !in_array($record->network, $listMenu['network'])) {
                $listMenu['network'][] = $record->network;
            }
        }

        $key = 'menu_filter_app_information';
        $cacheMenu = $this->cachePoolRepository->getCacheByKey($key);

        if ($cacheMenu) {
            $dataUpdate = [
                'data' => $listMenu,
            ];

            $this->cachePoolRepository->updateCacheByKey($key, $dataUpdate);
        } else {
            $this->cachePoolRepository->create([
                'key' => $key,
                'data' => $listMenu,
                'description' => 'Menu filter app information',
            ]);
        }

        dump('Cache menu app information successfully!');
    }
}
