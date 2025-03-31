<?php

namespace App\Console\Commands\LogBehavior;

use Illuminate\Console\Command;
use App\Helper\Common;
use Illuminate\Support\Facades\DB;

class SyncOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log-behavior:sync-old-data';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync old data to history table and user table';
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
        $prevDate = date('Y-m-d', strtotime('-1 day'));
        $selectDate = $prevDate;
        $count = 1;
        while ($while) {
            if (strtotime($selectDate) == strtotime('2023-10-19')) {
                break;
            }
            $dateEstimate1 = $selectDate . ' 00:00:00';
            $dateEstimate2 = $selectDate . ' 23:59:59';
            $fromQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate1);
            $toQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate2);
            $getLogBehavior = \DB::connection('mongodb')
                ->table('log_behavior')
                ->whereBetween('date', [$fromQuery, $toQuery])
                ->where('behavior', '!=', '')
                ->where('behavior', '!=', null)
                ->get();
            foreach ($getLogBehavior as $logBehavior) {
                $data = [
                    'uid' => $logBehavior->uid,
                    'app' => $logBehavior->app,
                    'platform' => $logBehavior->platform,
                    'network' => $logBehavior->network,
                    'country' => $logBehavior->country,
                    'behavior' => $logBehavior->behavior,
                    'date' => $logBehavior->date,
                ];
                DB::connection('mongodb')
                    ->table('log_behavior_history')
                    ->insert($data);
                dump('Insert record with id ' . $logBehavior->uid . ' into log_behavior_history');
                $getUsers = DB::connection('mongodb')
                    ->table('log_behavior_users')
                    ->where('uid', $logBehavior->uid)
                    ->first();
                if (empty($getUsers)) {
                    $data = [
                        'uid' => $logBehavior->uid,
                        'app' => $logBehavior->app,
                        'platform' => $logBehavior->platform,
                        'network' => $logBehavior->network,
                        'country' => $logBehavior->country,
                        'date_install' => $logBehavior->date
                    ];
                    DB::connection('mongodb')
                        ->table('log_behavior_users')
                        ->insert($data);
                    dump('Insert record with id ' . $logBehavior->uid . ' into log_behavior_users');
                } else {
                    dump('Users is exist!!!');
                }
                $status = DB::connection('mongodb')
                    ->table('log_behavior')
                    ->where('id', $logBehavior->id)
                    ->delete();
                dump('Delete record ' . $count . ' with status delete ' . $status);
                $count++;
            }
            $selectDate = date('Y-m-d', strtotime('-1 day', strtotime($selectDate)));
        }
    }
}
