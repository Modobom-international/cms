<?php

namespace App\Console\Commands\Domain;

use Illuminate\Console\Command;
use App\Repositories\ConfigPoolRepository;
use App\Repositories\DomainRepository;
use App\Repositories\SiteRepository;

class SyncStatusUseByCloudflare extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:sync-status-use-by-cloudflare';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(DomainRepository $domainRepository, ConfigPoolRepository $configPoolRepository, SiteRepository $siteRepository)
    {
        $key = 'status_sync_domains_cms';
        $getConfig = $configPoolRepository->getByKey($key);

        if (!$getConfig) {
            return;
        } else {
            if ($getConfig->data->status == 1) {
                return;
            } else {
                $listDomain = $domainRepository->getAllDomain();

                foreach($listDomain as $domain) {

                }
            }
        }
    }
}
