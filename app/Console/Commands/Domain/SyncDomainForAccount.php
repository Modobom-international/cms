<?php

namespace App\Console\Commands\Domain;

use App\Services\GoDaddyService;
use App\Services\ApplicationLogger;
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
    public function handle(DomainRepository $domainRepository, ConfigPoolRepository $configPoolRepository, GoDaddyService $goDaddyService, ApplicationLogger $logger)
    {
        $key = 'status_sync_domains_cms';

        $logger->logDomain('info', [
            'message' => 'Domain sync process started',
            'command' => $this->signature
        ]);

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
            $logger->logDomain('info', [
                'message' => 'Created sync status config',
                'config_key' => $key
            ]);
        } else {
            $dataUpdate = [
                'data' => [
                    'status' => 1,
                    'time_update' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ];

            $configPoolRepository->updateByKey($key, $dataUpdate);
            $logger->logDomain('info', [
                'message' => 'Updated sync status config to active',
                'config_key' => $key
            ]);
        }

        $logger->logDomain('info', [
            'message' => 'Fetching domain list from GoDaddy API'
        ]);

        $getListDomain = $goDaddyService->getListDomain();
        if (array_key_exists('error', $getListDomain)) {
            $logger->logDomain('error', [
                'message' => 'Failed to fetch domains from GoDaddy API',
                'error' => $getListDomain['error']
            ]);
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
            'total_domains' => count($listDomain),
            'source' => 'GoDaddy API'
        ]);

        foreach ($listDomain as $domain) {
            try {
                // $logger->logDomain('info', [
                //     'message' => 'Processing domain',
                //     'domain' => $domain['domain'],
                //     'expires' => $domain['expires'] ?? null,
                //     'status' => $domain['status'] ?? null
                // ]);

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
                        // $logger->logDomain('info', [
                        //     'message' => 'Domain updated successfully',
                        //     'domain' => $domain['domain'],
                        //     'domain_id' => $existingDomain->id,
                        //     'action' => 'update'
                        // ]);
                    } else {
                        $errorCount++;
                        $error = "Failed to update domain: {$domain['domain']}";
                        $errors[] = $error;
                        $logger->logDomain('error', [
                            'message' => $error,
                            'domain' => $domain['domain'],
                            'domain_id' => $existingDomain->id,
                            'action' => 'update'
                        ]);
                    }
                } else {
                    $domainsData['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                    $result = $domainRepository->create($domainsData);
                    if ($result) {
                        $count++;
                        $logger->logDomain('info', [
                            'message' => 'New domain created successfully',
                            'domain' => $domain['domain'],
                            'domain_id' => $result->id ?? null,
                            'action' => 'create'
                        ]);
                    } else {
                        $errorCount++;
                        $error = "Failed to create domain: {$domain['domain']}";
                        $errors[] = $error;
                        $logger->logDomain('error', [
                            'message' => $error,
                            'domain' => $domain['domain'],
                            'action' => 'create'
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                $error = "Error processing domain {$domain['domain']}: " . $e->getMessage();
                $errors[] = $error;
                $logger->logDomain('error', [
                    'message' => 'Exception occurred while processing domain',
                    'domain' => $domain['domain'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
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
            'message' => 'Updated sync status config to inactive',
            'config_key' => $key
        ]);

        $logger->logDomain('info', [
            'message' => "Kết thúc đồng bộ domain",
            'total_processed' => count($listDomain),
            'new_domains' => $count,
            'updated_domains' => $updateCount,
            'error_count' => $errorCount,
            'success_rate' => $errorCount > 0 ? round((count($listDomain) - $errorCount) / count($listDomain) * 100, 2) . '%' : '100%',
            'duration' => 'completed'
        ]);

        if (!empty($errors)) {
            $logger->logDomain('warning', [
                'message' => 'Domain sync completed with errors',
                'error_details' => $errors
            ]);
        }

        dump("Thêm mới {$count} domain và cập nhật {$updateCount} domain thành công!");
        if ($errorCount > 0) {
            dump("Có {$errorCount} lỗi xảy ra trong quá trình đồng bộ!");
            dump("Chi tiết lỗi:", $errors);
        }
    }
}
