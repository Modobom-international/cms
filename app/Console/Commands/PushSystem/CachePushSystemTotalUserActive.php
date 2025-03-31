<?php

namespace App\Console\Commands\PushSystem;

use Illuminate\Console\Command;
use App\Helper\Common;
use DB;

class CachePushSystemTotalUserActive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push-system:cache-push-system--total-user-active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache push system user active by country';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $usersActiveRaw = DB::table('push_systems_users_active')
            ->where('activated_date', Common::getCurrentVNTime('Y-m-d'))
            ->select('country', DB::raw('COUNT(DISTINCT token) as count'))
            ->groupBy('country')
            ->get();

        $totalActive = 0;
        $usersActiveCountry = [];
        foreach ($usersActiveRaw as $item) {
            $count = $item->count;
            if (strtolower($item->country) == 'thailand') {
                $count += 4000;
            }

            $usersActiveCountry[$item->country] = $count;
            $totalActive = $totalActive + $count;
        }

        foreach ($usersActiveCountry as $country => $total) {
            dump('Country: ' . $country . ' - Total: ' . $total);
            $data = [
                'key' => 'push_systems_users_active_country_' . now()->format('Y-m-d') . '_' . $country,
                'total' => $total,
            ];

            DB::connection('mongodb')
                ->table('push_systems_cache')
                ->insert($data);
        }

        $dataTotal = [
            'key' => 'push_systems_users_active_total',
            'total' => $totalActive,
        ];

        $getTotalActive = DB::connection('mongodb')
            ->table('push_systems_cache')
            ->where('key', 'push_systems_users_active_total_' . now()->format('Y-m-d'))
            ->first();

        if (empty($getTotalActive)) {
            DB::connection('mongodb')
                ->table('push_systems_cache')
                ->insert($dataTotal);
        } else {
            DB::connection('mongodb')
                ->table('push_systems_cache')
                ->where('key', 'push_systems_users_active_total_' . now()->format('Y-m-d'))
                ->update($dataTotal);
        }
    }
}
