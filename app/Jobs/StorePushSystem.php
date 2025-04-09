<?php

namespace App\Jobs;

use App\Enums\Utility;
use App\Repositories\PushSystemCacheRepository;
use App\Repositories\PushSystemRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class StorePushSystem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle(Utility $utility, PushSystemRepository $pushSystemRepository, PushSystemCacheRepository $pushSystemCacheRepository)
    {
        $dataInsert = [
            'token' => $this->data['token'] ?? null,
            'app' => $this->data['app'] ?? null,
            'platform' => $this->data['platform'] ?? null,
            'device' => $this->data['device'] ?? null,
            'country' => $this->data['country'] ?? null,
            'keyword' => $this->data['keyword'] ?? null,
            'shortcode' => $this->data['shortcode'] ?? null,
            'telcoid' => $this->data['telcoid'] ?? null,
            'network' => $this->data['network'] ?? null,
            'permission' => $this->data['permission'] ?? null,
            'created_date' => $utility->getCurrentVNTime('Y-m-d'),
        ];

        try {
            $pushSystemRepository->create($dataInsert);
            $getUserTotal = $pushSystemCacheRepository->getFirstByKey('push_systems_users_total');

            if (empty($getUserTotal)) {
                $data = [
                    'key' => 'push_systems_users_total',
                    'total' => 1,
                ];

                $pushSystemCacheRepository->create($data);
            } else {
                $data = [
                    'total' => $getUserTotal->total + 1,
                ];

                $pushSystemCacheRepository->updateTotalByKey('push_systems_users_total', $data);
            }

            $getDataCountries = $pushSystemCacheRepository->getFirstByKeyLike('push_systems_users_country_' . $this->data['country']);

            if (empty($getDataCountries)) {
                $data = [
                    'key' => 'push_systems_users_country_' . $this->data['country'],
                    'total' => 1,
                ];

                $pushSystemCacheRepository->create($data);
            } else {
                $data = [
                    'total' => $getDataCountries->total + 1,
                ];

                $pushSystemCacheRepository->updateTotalByKey('push_systems_users_country_' . $this->data['country'], $data);
            }
        } catch (\Throwable $e) {
            Log::error("Job failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
            ]);
        }
    }
}
