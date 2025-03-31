<?php

namespace App\Jobs;

use App\Helper\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SaveRequestGetSystemSetting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $params;

    /**
     * Create a new job instance.
     *
     * @param $params
     */
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
        $data = [];
        if (!empty($this->params['data'])) {
            $data = $this->params['data'];
        }

        $linkWeb = null;
        $domainWeb = null;
        if (!empty($this->params['link_web'])) {
            $linkWeb = $this->params['link_web'];
            $domainWeb = Common::getDomainFromUrl($linkWeb);
        }

        $kwDTAC = null;
        $kwAIS = null;

        if (!empty($this->params['keyword_dtac'])) {
            if (!empty($this->params['keyword_dtac']['keyword']) && !empty($this->params['keyword_dtac']['shortcode'])) {
                $kwDTAC = $this->params['keyword_dtac']['keyword'].'_'.$this->params['keyword_dtac']['shortcode'];
            }
        }

        if (!empty($this->params['keyword_ais'])) {
            if (!empty($this->params['keyword_ais']['keyword']) && !empty($this->params['keyword_ais']['shortcode'])) {
                $kwAIS = $this->params['keyword_ais']['keyword'].'_'.$this->params['keyword_ais']['shortcode'];
            }
        }

        \DB::table('get_push_system_setting_requests')->insert([
            'ip' => $this->params['ip'] ?? null,
            'user_agent' => $this->params['user_agent'] ?? null,
            'created_at' => $this->params['created_at'] ?? null,
            'created_date' => $this->params['created_date'] ?? null,
            'keyword_dtac' => $kwDTAC,
            'keyword_ais' => $kwAIS,
            'share_web' => $this->params['share_web'] ?? null,
            'link_web' => $linkWeb,
            'domain' => $domainWeb,
            'data' => json_encode($data),
        ]);
    }
}
