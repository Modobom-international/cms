<?php

namespace App\Console\Commands\LogBehavior;

use App\Enums\LogBehavior;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CacheMenu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log-behavior:cache-menu';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache menu for log behavior';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $countries = DB::connection('mongodb')->table('log_behavior_history')->select('country')->groupBy('country')->get();
        $platforms = DB::connection('mongodb')->table('log_behavior_history')->select('platform')->groupBy('platform')->get();
        $apps = DB::connection('mongodb')->table('log_behavior_history')->select('app')->groupBy('app')->get();
        $networks = DB::connection('mongodb')->table('log_behavior_history')->select('network')->groupBy('network')->get();
        $listCountry = [];
        foreach ($countries as $keyCountry => $country) {
            if (!in_array($country->country, $listCountry)) {
                $listCountry[] = $country->country;
            } else {
                $countries->forget($keyCountry);
            }
        }
        $listPlatform = [];
        foreach ($platforms as $keyPlatform => $platform) {
            if (!in_array($platform->platform, $listPlatform)) {
                $listPlatform[] = $platform->platform;
            } else {
                $platforms->forget($keyPlatform);
            }
        }
        $listApp = [];
        foreach ($apps as $keyApp => $app) {
            if (!in_array($app->app, $listApp)) {
                $listApp[] = $app->app;
            } else {
                $apps->forget($keyApp);
            }
        }
        $listNetwork = [];
        foreach ($networks as $keyNetwork => $network) {
            if (!in_array($network->network, $listNetwork)) {
                $listNetwork[] = $network->network;
            } else {
                $networks->forget($keyNetwork);
            }
        }
        $arrKey = [
            LogBehavior::CACHE_MENU . '_countries' => $listCountry,
            LogBehavior::CACHE_MENU . '_platforms' => $listPlatform,
            LogBehavior::CACHE_MENU . '_networks' => $listNetwork,
            LogBehavior::CACHE_MENU . '_apps' => $listApp
        ];
        foreach ($arrKey as $key => $value) {
            $data = [
                'data' => $value,
                'key' => $key,
            ];
            $getByKey = DB::connection('mongodb')
                ->table('log_behavior_cache')
                ->where('key', $key)
                ->first();
            if (empty($getByKey)) {
                DB::connection('mongodb')
                    ->table('log_behavior_cache')
                    ->insert($data);
            } else {
                DB::connection('mongodb')
                    ->table('log_behavior_cache')
                    ->where('key', $key)
                    ->update($data);
            }
            dump('Cache ' . $key . ' done');
        }
    }
}
