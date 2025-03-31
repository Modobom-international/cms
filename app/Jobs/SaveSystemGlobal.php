<?php

namespace App\Jobs;

use App\Helper\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SaveSystemGlobal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private  $params;
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
            'app' => $this->params['app'] ?? null,
            'country' => $this->params['country'] ?? null,
            'status' => $this->params['status'] ?? null,
            'token' => $this->params['token'] ?? null,
        ];
        
        if (isset($this->params['status'])) {
            $dataInsert['status'] = boolval($this->params['status']);
        }
        
        try {
            if (!empty($this->params['token'])) {
               $checkExitData =\DB::table('push_system_globals')->where('token', $this->params['token'])->first();
               if(!empty($checkExitData)) {
                   $dataInsert['updated_at'] = Common::getCurrentVNTime();
                   $resultUpdate= \DB::table('push_system_globals')->where('token', $this->params['token'])->update($dataInsert);
                   
                   dump('Update', $resultUpdate);
               
               }
               else {
                   $dataInsert['token'] = $this->params['token'] ;
                   $dataInsert['created_at'] =  Common::getCurrentVNTime();
    
                   $resultInsert = \DB::table('push_system_globals')->insert($dataInsert);
                   dump('Insert', $resultInsert);
               }
            }
            
        } catch (\Exception $ex) {
            dump(Common::getCurrentVNTime().' - Error while save push system global params. params:  '.json_encode($this->params).', '.$ex->getMessage());
        }
        
        dump('Saving push system data: '.json_encode($this->params));
        
    }
}
