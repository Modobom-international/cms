<?php

namespace App\Console\Commands\LogBehavior;

use App\Enums\LogBehavior;
use App\Enums\Utility;
use Illuminate\Console\Command;
use App\Helper\Common;
use Illuminate\Support\Facades\DB;

class CacheKeywords extends Command
{
    protected $utility;
    protected $signature = 'log-behavior:cache-keywords';
    protected $description = 'Cache keywords in log behavior';

    public function __construct(Utility $utility)
    {
        $this->utility = $utility;
        parent::__construct();
    }

    public function handle()
    {
        $while = true;
        $prevDate = date('Y-m-d', strtotime('-1 day'));
        $selectDate = $prevDate;
        $listKeyword = [];
        while ($while) {
            if (strtotime($selectDate) == strtotime('2023-10-19')) {
                break;
            }
            
            $dateEstimate1 = $selectDate . ' 00:00:00';
            $dateEstimate2 = $selectDate . ' 23:59:59';
            $fromQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate1);
            $toQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate2);
            $keywords = DB::connection('mongodb')->table('log_behavior_history')->select('behavior')->whereBetween('date', [$fromQuery, $toQuery])->groupBy('uid')->get();
            foreach ($keywords as $key => $value) {
                if (strpos($value->behavior, 'SUB_OK_KwDefault') !== false) {
                    $behavior = json_decode($value->behavior, true);
                    foreach ($behavior as $keyBehavior => $valueBehavior) {
                        if (strpos($keyBehavior, 'SUB_OK_KwDefault') !== false) {
                            if (!in_array($valueBehavior, $listKeyword)) {
                                $listKeyword[] = $valueBehavior;
                            }
                        }
                    }
                }
            }

            $count = count($listKeyword);
            $selectDate = date('Y-m-d', strtotime('-1 day', strtotime($selectDate)));
            dump('Done date ' . $selectDate);
            dump('Count keywords ' . $count);
        }

        $data = [
            'data' => $listKeyword,
            'key' => LogBehavior::CACHE_KEYWORD
        ];

        DB::connection('mongodb')
            ->table('log_behavior_cache')
            ->insert($data);

        dump('Cache keyword done');
    }
}
