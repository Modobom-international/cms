<?php

namespace App\Jobs;

use DB;
use App\Helper\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SavePushSystemData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $params;

    /**
     * Create a new job instance.
     *
     * @param $params
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $dataInsert = [
            'token' => $this->params['token'] ?? null,
            'app' => $this->params['app'] ?? null,
            'platform' => $this->params['platform'] ?? null,
            'device' => $this->params['device'] ?? null,
            'country' => $this->params['country'] ?? null,
            'keyword' => $this->params['keyword'] ?? null,
            'shortcode' => $this->params['shortcode'] ?? null,
            'telcoid' => $this->params['telcoid'] ?? null,
            'network' => $this->params['network'] ?? null,
            'permission' => $this->params['permission'] ?? null,
            'created_at' => Common::getCurrentVNTime(),
            'created_date' => Common::getCurrentVNTime('Y-m-d'),
        ];
        $resultInsert = false;

        try {
            $resultInsert = DB::table('push_systems')->insert($dataInsert);

            $getUserTotal = DB::connection('mongodb')
                ->table('push_systems_cache')
                ->where('key', 'push_systems_users_total')
                ->first();

            if (empty($getUserTotal)) {
                DB::connection('mongodb')
                    ->table('push_systems_cache')
                    ->insert([
                        'key' => 'push_systems_users_total',
                        'total' => 1,
                    ]);
            } else {
                DB::connection('mongodb')
                    ->table('push_systems_cache')
                    ->where('key', 'push_systems_users_total')
                    ->update([
                        'total' => $getUserTotal->total + 1,
                    ]);
            }

            $getDataCountries = DB::connection('mongodb')
                ->table('push_systems_cache')
                ->where('key', 'LIKE', 'push_systems_users_country_%' . $this->params['country'])
                ->first();

            if (empty($getDataCountries)) {
                DB::connection('mongodb')
                    ->table('push_systems_cache')
                    ->insert([
                        'key' => 'push_systems_users_country_' . $this->params['country'],
                        'total' => 1,
                    ]);
            } else {
                DB::connection('mongodb')
                    ->table('push_systems_cache')
                    ->where('key', 'push_systems_users_country_' . $this->params['country'])
                    ->update([
                        'total' => $getPushSystemCacheByCountry->total + 1,
                    ]);
            }
        } catch (\Exception $ex) {
            dump(Common::getCurrentVNTime() . ' - Error while save push system params. params:  ' . json_encode($this->params) . ', ' . $ex->getMessage());
        }

        dump('Saving push system data: ' . json_encode($this->params) . ', result insert: ' . $resultInsert);
    }
}
