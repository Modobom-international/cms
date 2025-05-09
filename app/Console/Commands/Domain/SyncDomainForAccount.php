<?php

namespace App\Console\Commands\Domain;

use App\Services\GoDaddyService;
use App\Services\SiteManagementLogger;
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
    public function handle(DomainRepository $domainRepository, ConfigPoolRepository $configPoolRepository, GoDaddyService $goDaddyService, SiteManagementLogger $logger)
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
            $logger->logDomain('error', ['message' => $getListDomain['error']]);
            dump($getListDomain['error']);
            return;
        }

        $listDomain = $getListDomain['data'];
        $count = 0;
        $updateCount = 0;
        $errorCount = 0;
        $errors = [];

        $logger->logDomain('info', [
            'message' => 'Bắt đầu đồng bộ domain',
            'total_domains' => count($listDomain)
        ]);

        foreach ($listDomain as $domain) {
            try {
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
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                ];

                $existingDomain = $domainRepository->findByDomain($domain['domain']);

                if ($existingDomain) {
                    $result = $domainRepository->update($existingDomain->id, $domainsData);
                    if ($result) {
                        $updateCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Failed to update domain: {$domain['domain']}";
                    }
                } else {
                    $domainsData['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                    $result = $domainRepository->create($domainsData);
                    if ($result) {
                        $count++;
                    } else {
                        $errorCount++;
                        $errors[] = "Failed to create domain: {$domain['domain']}";
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Error processing domain {$domain['domain']}: " . $e->getMessage();
            }
        }

        $dataUpdate = [
            'data' => [
                'status' => 0,
                'time_update' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        ];

        $configPoolRepository->updateByKey($key, $dataUpdate);

        $logger->logDomain('info', [
            'message' => "Kết thúc đồng bộ domain",
            'total_processed' => count($listDomain),
            'new_domains' => $count,
            'updated_domains' => $updateCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ]);

        dump("Thêm mới {$count} domain và cập nhật {$updateCount} domain thành công!");
        if ($errorCount > 0) {
            dump("Có {$errorCount} lỗi xảy ra trong quá trình đồng bộ!");
            dump("Chi tiết lỗi:", $errors);
        }
    }
}
