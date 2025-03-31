<?php

namespace App\Console\Commands\LogBehavior;

use App\Enums\LogBehavior;
use App\Helper\Common;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CacheDataPerDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log-behavior:cache-data-per-date {--replace}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache data per date in log behavior';
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
        $while = true;
        $replace = $this->option('replace');
        $prevDate = date('Y-m-d', strtotime('-1 day'));
        $selectDate = $prevDate;
        while ($while) {
            if (strtotime($selectDate) == strtotime('2023-10-19')) {
                break;
            }
            $dateEstimate1 = $selectDate . ' 00:00:00';
            $dateEstimate2 = $selectDate . ' 23:59:59';
            $explodeDate = explode('-', $selectDate);
            $fromQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate1);
            $toQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate2);
            if ($replace) {
                if ($explodeDate[0] == date('Y')) {
                    $collection = 'log_behavior_history';
                } else {
                    $collection = 'log_behavior_archive_' . $explodeDate[0];
                }
            } else {
                $collection = 'log_behavior';
            }
            $getLogBehavior = DB::connection('mongodb')
                ->table($collection)
                ->whereBetween('date', [$fromQuery, $toQuery])
                ->where('behavior', '!=', '')
                ->where('behavior', '!=', null)
                ->get();
            $getInfor = DB::connection('mongodb')
                ->table('log_behavior_cache')
                ->where('key', LogBehavior::CACHE_DATE . '_' . $selectDate)
                ->get();
            $chunks = str_split(json_encode($getLogBehavior), 10000);
            if ($replace) {
                DB::connection('mongodb')
                    ->table('log_behavior_cache')
                    ->where('key', LogBehavior::CACHE_DATE . '_' . $selectDate)
                    ->delete();
                foreach ($chunks as $key => $chunk) {
                    $data = [
                        'data' => $chunk,
                        'path' => $key,
                        'totalPath' => count($chunks),
                        'key' => LogBehavior::CACHE_DATE . '_' . $selectDate
                    ];
                    DB::connection('mongodb')
                        ->table('log_behavior_cache')
                        ->insert($data);
                }
                dump('Current date : ' . $selectDate);
                dump('Data synced : ' . count($getLogBehavior));
            } else {
                dump('Current date : ' . $selectDate);
                if (count($getInfor) == 0) {
                    foreach ($chunks as $key => $chunk) {
                        $data = [
                            'data' => $chunk,
                            'path' => $key,
                            'totalPath' => count($chunks),
                            'key' => LogBehavior::CACHE_DATE . '_' . $selectDate
                        ];
                        DB::connection('mongodb')
                            ->table('log_behavior_cache')
                            ->insert($data);
                    }
                    dump('Data synced : ' . count($getLogBehavior));
                } else {
                    $while = false;
                    dump('No need update. End!!!');
                }
            }
            $selectDate = date('Y-m-d', strtotime('-1 day', strtotime($selectDate)));
        }
    }
}
