<?php

namespace App\Jobs;

use DB;
use App\Helper\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SaveUserActivePushSystem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $token;
    private $country;
    private $params;

    public function __construct($token, $country)
    {
        $this->token = $token;
        $this->country = $country;
        $this->params = [
            'token' => $token,
            'country' => $country,
        ];
    }

    public function handle()
    {
        DB::table('push_systems_users_active')->insert([
            'token' => $this->token,
            'country' => $this->country,
            'activated_at' => Common::getCurrentVNTime(),
            'activated_date' => Common::getCurrentVNTime('Y-m-d'),
        ]);

        $getPushSystemCacheByCountry =  DB::connection('mongodb')
            ->table('push_systems_cache')
            ->where('key', 'push_systems_users_active_country_' . now()->format('Y-m-d') . '_' . $this->country)
            ->first();

        $getPushSystemCacheTotal =  DB::connection('mongodb')
            ->table('push_systems_cache')
            ->where('key', 'push_systems_users_active_total_' . now()->format('Y-m-d'))
            ->first();

        if (empty($getPushSystemCacheByCountry)) {
            DB::connection('mongodb')
                ->table('push_systems_cache')
                ->insert([
                    'key' => 'push_systems_users_active_country_' . now()->format('Y-m-d') . '_' . $this->country,
                    'total' => 1,
                ]);
        } else {
            DB::connection('mongodb')
                ->table('push_systems_cache')
                ->where('key', 'push_systems_users_active_country_' . now()->format('Y-m-d') . '_' . $this->country)
                ->update([
                    'total' => $getPushSystemCacheByCountry->total + 1,
                ]);
        }

        if (empty($getPushSystemCacheTotal)) {
            DB::connection('mongodb')
                ->table('push_systems_cache')
                ->insert([
                    'key' => 'push_systems_users_active_total_' . now()->format('Y-m-d'),
                    'total' => 1,
                ]);
        } else {
            DB::connection('mongodb')
                ->table('push_systems_cache')
                ->where('key', 'push_systems_users_active_total_' . now()->format('Y-m-d'))
                ->update([
                    'total' => $getPushSystemCacheTotal->total + 1,
                ]);
        }
    }
}
