<?php

namespace App\Console\Commands\PushSystem;

use Illuminate\Console\Command;
use DB;

class CachePushSystemTotalUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push-system:cache-push-system-total-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache push system total user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $countUser = DB::table('push_systems')->get()->count();

        DB::connection('mongodb')
            ->table('push_systems_cache')
            ->insert([
                'key' => 'push_systems_users_total',
                'total' => $countUser,
            ]);

        $getDataCountries = DB::table('push_systems')->select('country', DB::raw('count(*) as count'))
            ->groupBy('country')
            ->get();

        foreach ($getDataCountries as $getCountry) {
            DB::connection('mongodb')
                ->table('push_systems_cache')
                ->insert([
                    'key' => 'push_systems_users_country_' . $getCountry->country,
                    'total' => $getCountry->count,
                ]);
        }
    }
}
