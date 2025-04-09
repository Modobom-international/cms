<?php

namespace App\Jobs;

use App\Enums\Utility;
use App\Repositories\PushSystemCacheRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class StorePushSystemUserActive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle(Utility $utility, PushSystemCacheRepository $pushSystemCacheRepository)
    {
        $dataInsert = [
            'token' => $this->data['token'],
            'country' => $this->data['country'],
            'activated_at' => $utility->getCurrentVNTime(),
            'activated_date' => $utility->getCurrentVNTime('Y-m-d'),
        ];

        $pushSystemCacheRepository->create($dataInsert);
        $getPushSystemCacheByCountry =  $pushSystemCacheRepository->getFirstByKeyLike('push_systems_users_active_country_' . now()->format('Y-m-d') . '_' . $this->data['country']);
        $getPushSystemCacheTotal =  $pushSystemCacheRepository->getFirstByKeyLike('push_systems_users_active_total_' . now()->format('Y-m-d'));

        if (empty($getPushSystemCacheByCountry)) {
            $dataInsert = [
                'key' => 'push_systems_users_active_country_' . now()->format('Y-m-d') . '_' . $this->data['country'],
                'total' => 1,
            ];

            $pushSystemCacheRepository->create($dataInsert);
        } else {
            $keyUpdate = 'push_systems_users_active_country_' . now()->format('Y-m-d') . '_' . $this->data['country'];
            $dataUpdate = [
                'total' => $getPushSystemCacheByCountry->total + 1,
            ];

            $pushSystemCacheRepository->updateTotalByKey($keyUpdate, $dataUpdate);
        }

        if (empty($getPushSystemCacheTotal)) {
            $dataInsert = [
                'key' => 'push_systems_users_active_total_' . now()->format('Y-m-d'),
                'total' => 1,
            ];

            $pushSystemCacheRepository->create($dataInsert);
        } else {
            $keyUpdate = 'push_systems_users_active_total_' . now()->format('Y-m-d');
            $dataUpdate = [
                'total' => $getPushSystemCacheByCountry->total + 1,
            ];

            $pushSystemCacheRepository->updateTotalByKey($keyUpdate, $dataUpdate);
        }
    }
}
