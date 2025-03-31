<?php

namespace App\Jobs;

use Exception;
use App\Enums\LogBehavior;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use App\Helper\Common;

class StoreLogBehaviorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    protected $isInstall;
    public function __construct($data, $isInstall)
    {
        $this->data = $data;
        $this->isInstall = $isInstall;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if ($this->isInstall) {
                $keyCacheMenuApp = LogBehavior::CACHE_MENU . '_apps';
                $keyCacheMenuNetwork = LogBehavior::CACHE_MENU . '_networks';
                $isUpdateCache = false;

                $getCache = DB::connection('mongodb')
                    ->table('log_behavior_cache')
                    ->where('key', $keyCacheMenuApp)
                    ->orWhere('key', $keyCacheMenuNetwork)
                    ->get();
                foreach ($getCache as $cache) {
                    if ($cache->key == $keyCacheMenuApp) {
                        if (!in_array($this->data['app'], $cache->data)) {
                            $isUpdateCache = true;
                        }
                    }
                    if ($cache->key == $keyCacheMenuNetwork) {
                        if (!in_array($this->data['network'], $cache->data)) {
                            $isUpdateCache = true;
                        }
                    }
                    if ($isUpdateCache) {
                        $cache->data[] = $this->data['app'];
                        $isUpdateCache = false;
                        $dataUpdateCache = [
                            'data' => $cache->data
                        ];
                        DB::connection('mongodb')
                            ->table('log_behavior_cache')
                            ->where('id', $cache->id)
                            ->update($dataUpdateCache);
                    }
                }

                DB::connection('mongodb')
                    ->table('log_behavior')
                    ->insert($this->data);
                $info = [
                    'uid' => $this->data['uid'],
                    'message' => 'Created log behavior with new id',
                    'data' => json_encode($this->data),
                    'date' => Common::covertDateTimeToMongoBSONDateGMT7(Common::getCurrentVNTime())
                ];
                BehaviorStoreLogJob::dispatch($info)->onQueue('behavior_store_log');
            } else {
                $getInfor = DB::connection('mongodb')
                    ->table('log_behavior')
                    ->where('uid', $this->data['uid'])
                    ->first();
                if (!empty($getInfor)) {
                    $oldBehavior = json_decode($getInfor->behavior, true);
                    $newBehavior = json_decode($this->data['behavior'], true);
                    $mergeBehavior = array_merge($oldBehavior, $newBehavior);
                    $dataUpdate['behavior'] = json_encode($mergeBehavior);
                    DB::connection('mongodb')
                        ->table('log_behavior')
                        ->where('id', $getInfor->id)
                        ->update($dataUpdate);
                    $info = [
                        'uid' => $this->data['uid'],
                        'message' => 'Updated log behavior',
                        'data' => json_encode($this->data),
                        'date' => Common::covertDateTimeToMongoBSONDateGMT7(Common::getCurrentVNTime())
                    ];
                    BehaviorStoreLogJob::dispatch($info)->onQueue('behavior_store_log');
                } else {
                    $getUser = DB::connection('mongodb')
                        ->table('log_behavior_users')
                        ->where('uid', $this->data['uid'])
                        ->first();
                    if (empty($getUser)) {
                        $info = [
                            'uid' => $this->data['uid'],
                            'message' => "Can't create behavior because no id found",
                            'data' => json_encode($this->data),
                            'date' => Common::covertDateTimeToMongoBSONDateGMT7(Common::getCurrentVNTime())
                        ];
                        BehaviorStoreLogJob::dispatch($info)->onQueue('behavior_store_log');
                    } else {
                        $this->data['date_install'] = $getUser->date_install->toDateTime()->format('Y-m-d H:i:s');
                        $this->data['app'] = $getUser->app;
                        $this->data['country'] = $getUser->country;
                        $this->data['platform'] = $getUser->platform;
                        $this->data['network'] = $getUser->network;
                        $this->data['date_install'] = $getUser->date_install;
                        DB::connection('mongodb')
                            ->table('log_behavior')
                            ->insert($this->data);
                        $info = [
                            'uid' => $this->data['uid'],
                            'message' => 'Updated log behavior',
                            'data' => json_encode($this->data),
                            'date' => Common::covertDateTimeToMongoBSONDateGMT7(Common::getCurrentVNTime())
                        ];
                        BehaviorStoreLogJob::dispatch($info)->onQueue('behavior_store_log');
                    }
                }
            }
        } catch (Exception $e) {
            $info = [
                'uid' => $this->data['uid'],
                'message' => 'Store behavior with error ' . $e->getMessage(),
                'data' => '',
                'date' => Common::covertDateTimeToMongoBSONDateGMT7(Common::getCurrentVNTime())
            ];
            BehaviorStoreLogJob::dispatch($info)->onQueue('behavior_store_log');
        }
    }
}
