<?php

namespace App\Jobs;

use App\Enums\Utility;
use App\Repositories\RequestGetSystemSettingRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class StoreRequestGetSystemSetting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(Utility $utility, RequestGetSystemSettingRepository $requestGetSystemSettingRepository): void
    {
        $data = [];
        if (!empty($this->data['data'])) {
            $data = $this->data['data'];
        }

        $linkWeb = null;
        $domainWeb = null;
        if (!empty($this->data['link_web'])) {
            $linkWeb = $this->data['link_web'];
            $domainWeb = $utility->getDomainFromUrl($linkWeb);
        }

        $kwDTAC = null;
        $kwAIS = null;

        if (!empty($this->data['keyword_dtac'])) {
            if (!empty($this->data['keyword_dtac']['keyword']) && !empty($this->data['keyword_dtac']['shortcode'])) {
                $kwDTAC = $this->data['keyword_dtac']['keyword'] . '_' . $this->data['keyword_dtac']['shortcode'];
            }
        }

        if (!empty($this->data['keyword_ais'])) {
            if (!empty($this->data['keyword_ais']['keyword']) && !empty($this->data['keyword_ais']['shortcode'])) {
                $kwAIS = $this->data['keyword_ais']['keyword'] . '_' . $this->data['keyword_ais']['shortcode'];
            }
        }

        $dataInsert = [
            'ip' => $this->data['ip'] ?? null,
            'user_agent' => $this->data['user_agent'] ?? null,
            'created_at' => $this->data['created_at'] ?? null,
            'created_date' => $this->data['created_date'] ?? null,
            'keyword_dtac' => $kwDTAC,
            'keyword_ais' => $kwAIS,
            'share_web' => $this->data['share_web'] ?? null,
            'link_web' => $linkWeb,
            'domain' => $domainWeb,
            'data' => json_encode($data),
        ];

        $requestGetSystemSettingRepository->create($dataInsert);
    }
}
