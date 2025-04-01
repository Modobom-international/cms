<?php

namespace App\Console\Commands\LogBehavior;

use App\Enums\LogBehavior;
use App\Enums\Utility;
use App\Repositories\LogBehaviorCacheRepository;
use App\Repositories\LogBehaviorRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CacheDataPerDate extends Command
{
    protected $utility;
    protected $logBehaviorRepository;
    protected $logBehaviorCacheRepository;
    protected $signature = 'log-behavior:cache-data-per-date {--replace}';
    protected $description = 'Cache data per date in log behavior';

    public function __construct(Utility $utility, LogBehaviorRepository $logBehaviorRepository, LogBehaviorCacheRepository $logBehaviorCacheRepository)
    {
        $this->utility = $utility;
        $this->logBehaviorRepository = $logBehaviorRepository;
        $this->logBehaviorCacheRepository = $logBehaviorCacheRepository;
        parent::__construct();
    }

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
            $fromQuery = $this->utility->covertDateTimeToMongoBSONDateGMT7($dateEstimate1);
            $toQuery = $this->utility->covertDateTimeToMongoBSONDateGMT7($dateEstimate2);

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

            $keyCache = LogBehavior::CACHE_DATE . '_' . $selectDate;
            $getInfor = $this->logBehaviorCacheRepository->getByKey($keyCache);
            $chunks = str_split(json_encode($getLogBehavior), 10000);

            if ($replace) {
                $this->logBehaviorCacheRepository->deleteByKey($keyCache);

                foreach ($chunks as $key => $chunk) {
                    $data = [
                        'data' => $chunk,
                        'path' => $key,
                        'totalPath' => count($chunks),
                        'key' => $keyCache
                    ];

                    $this->logBehaviorCacheRepository->create($data);
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
                            'key' => $keyCache
                        ];

                        $this->logBehaviorCacheRepository->create($data);
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
