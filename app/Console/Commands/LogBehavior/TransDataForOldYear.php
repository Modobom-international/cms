<?php

namespace App\Console\Commands\LogBehavior;

use DB;
use Exception;
use Illuminate\Console\Command;
use App\Helper\Common;

class TransDataForOldYear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log-behavior:trans-data-for-old-year';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trans data for old year';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $db = DB::connection('mongodb')->getMongoDB();
        $lastYear = date("Y", strtotime("-1 year"));
        $collectionName = 'log_behavior_archive_' . $lastYear;
        $collectionExists = false;
        foreach ($db->listCollections(['filter' => ['name' => $collectionName]]) as $collection) {
            if ($collection->getName() === $collectionName) {
                $collectionExists = true;
                break;
            }
        }
        if ($collectionExists) {
            dump("Collection '$collectionName' already exists.");
        } else {
            try {
                $created = $db->createCollection($collectionName);
                if ($created) {
                    dump("Collection '$collectionName' created successfully.");
                } else {
                    dump("Failed to create collection '$collectionName'.");
                    return;
                }
            } catch (Exception $e) {
                dump("Error creating collection '$collectionName': " . $e->getMessage());
                return;
            }
        }
        $allDates = [];
        $count = 1;
        $startDate = strtotime("$lastYear-01-01");
        $endDate = strtotime("$lastYear-12-31");
        while ($startDate <= $endDate) {
            $allDates[] = date('Y-m-d', $startDate);
            $startDate = strtotime("+1 day", $startDate);
        }
        foreach ($allDates as $selectDate) {
            $dateEstimate1 = $selectDate . ' 00:00:00';
            $dateEstimate2 = $selectDate . ' 23:59:59';
            $fromQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate1);
            $toQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate2);
            $getLogBehavior = DB::connection('mongodb')
                ->table('log_behavior_history')
                ->whereBetween('date', [$fromQuery, $toQuery])
                ->orWhereBetween('date_install', [$fromQuery, $toQuery])
                ->where('behavior', '!=', '')
                ->where('behavior', '!=', null)
                ->get();
            dump('Get ' . count($getLogBehavior) . ' records from log_behavior_history with date ' . $selectDate);
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
                    ->table($collectionName)
                    ->insert($data);
                dump('Insert record with id ' . $logBehavior->uid . ' into ' . $collectionName);
                $status = DB::connection('mongodb')
                    ->table('log_behavior_history')
                    ->where('id', $logBehavior->id)
                    ->delete();
                dump('Delete record ' . $count . ' with status delete ' . $status);
                $count++;
            }
        }
    }
}
