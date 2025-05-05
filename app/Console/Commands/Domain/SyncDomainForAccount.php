<?php

namespace App\Console\Commands\Domain;

use App\Services\GoDaddyService;
use Illuminate\Console\Command;
use App\Repositories\DomainRepository;
use App\Repositories\ConfigPoolRepository;
use Carbon\Carbon;

class SyncDomainForAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:sync-domain-for-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync domain for account';

    /**
     * Execute the console command.
     */
    public function handle(DomainRepository $domainRepository, ConfigPoolRepository $configPoolRepository, GoDaddyService $goDaddyService)
    {
        $key = 'status_sync_domains_cms';
        $getConfig = $configPoolRepository->getByKey($key);

        if (!$getConfig) {
            $dataCreate = [
                'key' => $key,
                'data' => [
                    'status' => 1,
                    'time_update' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ];
            $configPoolRepository->create($dataCreate);
        } else {
            $dataUpdate = [
                'data' => [
                    'status' => 1,
                    'time_update' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ];

            $configPoolRepository->updateByKey($key, $dataUpdate);
        }

        $getListDomain = $goDaddyService->getListDomain();
        if (array_key_exists('error', $getListDomain)) {
            dump($getListDomain['error']);
            return;
        }

        $domainRepository->deleteByIsLocked(0);

        $listDomain = $getListDomain['data'];
        $count = 0;

        foreach ($listDomain as $domain) {
            $domainsData = [
                'domain' => $domain['domain'],
                'time_expired' => Carbon::parse($domain['expires'])->format('Y-m-d H:i:s'),
                'registrar' => 'Godaddy',
                'is_locked' => $domain['locked'] ?? false,
                'renewable' => $domain['renewable'] ?? false,
                'status' => $domain['status'] ?? 'ACTIVE',
                'name_servers' => json_encode($domain['nameServers']) ?? null,
                'renew_deadline' => isset($domain['renewDeadline']) ? Carbon::parse($domain['renewDeadline'])->format('Y-m-d H:i:s') : null,
                'registrar_created_at' => isset($domain['registrarCreatedAt']) ? Carbon::parse($domain['registrarCreatedAt'])->format('Y-m-d H:i:s') : null,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ];

            $count++;
            $domainRepository->create($domainsData);
        }

        $dataUpdate = [
            'data' => [
                'status' => 0,
                'time_update' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        ];

        $configPoolRepository->updateByKey($key, $dataUpdate);

        dump('Thêm tổng cộng ' . $count . ' domain thành công!');
    }
}
