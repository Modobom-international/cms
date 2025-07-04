<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CloudFlareService;
use App\Services\ApplicationLogger;
use App\Repositories\DomainRepository;
use App\Repositories\DnsRecordRepository;
use App\Enums\ActivityAction;

class SyncDnsRecords implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $domain;
    protected $forceSync;

    /**
     * Create a new job instance.
     *
     * @param string|null $domain Specific domain to sync, or null for all domains
     * @param bool $forceSync Whether to force sync even if recently synced
     */
    public function __construct($domain = null, $forceSync = false)
    {
        $this->domain = $domain;
        $this->forceSync = $forceSync;
    }

    /**
     * Execute the job.
     */
    public function handle(
        CloudFlareService $cloudFlareService,
        DomainRepository $domainRepository,
        DnsRecordRepository $dnsRecordRepository,
        ApplicationLogger $applicationLogger
    ) {
        try {
            if ($this->domain) {
                // Sync specific domain
                $this->syncDomainDnsRecords($this->domain, $cloudFlareService, $dnsRecordRepository, $applicationLogger);
                $applicationLogger->logDomain(
                    'dns_sync_completed',
                    ['domain' => $this->domain, 'message' => "DNS records synced successfully for domain: {$this->domain}"]
                );
            } else {
                // Sync all domains
                $domains = $domainRepository->getAllDomain();
                $totalSynced = 0;
                $errors = [];

                foreach ($domains as $domainModel) {
                    try {
                        $recordCount = $this->syncDomainDnsRecords(
                            $domainModel->domain,
                            $cloudFlareService,
                            $dnsRecordRepository,
                            $applicationLogger
                        );
                        $totalSynced += $recordCount;

                        $applicationLogger->logDomain(
                            ActivityAction::DNS_DOMAIN_SYNCED,
                            [
                                'domain' => $domainModel->domain,
                                'record_count' => $recordCount,
                                'message' => "Synced {$recordCount} DNS records for domain: {$domainModel->domain}"
                            ]
                        );
                    } catch (\Exception $e) {
                        $errors[] = "Failed to sync DNS records for {$domainModel->domain}: " . $e->getMessage();
                        $applicationLogger->logDomain(
                            'dns_sync_error',
                            [
                                'domain' => $domainModel->domain,
                                'error' => $e->getMessage(),
                                'message' => "DNS sync error for {$domainModel->domain}: " . $e->getMessage()
                            ],
                            'error'
                        );
                    }
                }

                $applicationLogger->logDomain(
                    'dns_sync_batch_completed',
                    [
                        'total_synced' => $totalSynced,
                        'domains_count' => count($domains),
                        'message' => "DNS records sync completed. Total records synced: {$totalSynced}"
                    ]
                );

                if (!empty($errors)) {
                    $applicationLogger->logDomain(
                        'dns_sync_completed_with_errors',
                        [
                            'errors' => $errors,
                            'error_count' => count($errors),
                            'message' => "DNS sync completed with errors: " . implode(', ', $errors)
                        ],
                        'warning'
                    );
                }
            }
        } catch (\Exception $e) {
            $applicationLogger->logDomain(
                'dns_sync_failed',
                [
                    'error' => $e->getMessage(),
                    'domain' => $this->domain,
                    'message' => 'DNS records sync failed: ' . $e->getMessage()
                ],
                'error'
            );
            throw $e;
        }
    }

    /**
     * Sync DNS records for a specific domain
     *
     * @param string $domain
     * @param CloudFlareService $cloudFlareService
     * @param DnsRecordRepository $dnsRecordRepository
     * @param ApplicationLogger $applicationLogger
     * @return int Number of records synced
     */
    private function syncDomainDnsRecords(
        string $domain,
        CloudFlareService $cloudFlareService,
        DnsRecordRepository $dnsRecordRepository,
        ApplicationLogger $applicationLogger
    ): int {
        // Get zone ID for the domain
        $zoneId = $cloudFlareService->getZoneId($domain);

        if (!$zoneId) {
            $applicationLogger->logDomain(
                'dns_zone_not_found',
                [
                    'domain' => $domain,
                    'message' => "Zone ID not found for domain: {$domain}"
                ],
                'warning'
            );
            return 0;
        }

        // Get DNS records from CloudFlare
        $response = $cloudFlareService->getDnsRecords($zoneId);

        if (!$response || isset($response['error'])) {
            $errorMsg = $response['error'] ?? 'Unknown error getting DNS records';
            throw new \Exception("Failed to get DNS records for {$domain}: {$errorMsg}");
        }

        if (!isset($response['result']) || !is_array($response['result'])) {
            $applicationLogger->logDomain(
                'dns_no_records_found',
                [
                    'domain' => $domain,
                    'zone_id' => $zoneId,
                    'message' => "No DNS records found for domain: {$domain}"
                ],
                'warning'
            );
            return 0;
        }

        $cloudflareIds = [];
        $syncedCount = 0;

        // Process each DNS record
        foreach ($response['result'] as $record) {
            try {
                // Add zone_id to the record data (CloudFlare doesn't include it in record response)
                $record['zone_id'] = $zoneId;

                // Fix domain extraction for root domain records
                $recordDomain = $this->resolveDomainFromRecord($record, $domain);

                // Sync the record with the resolved domain
                $dnsRecordRepository->syncFromCloudflare($record, $recordDomain);
                $cloudflareIds[] = $record['id'];
                $syncedCount++;
            } catch (\Exception $e) {
                $applicationLogger->logDomain(
                    'dns_record_sync_failed',
                    [
                        'domain' => $domain,
                        'record_id' => $record['id'],
                        'error' => $e->getMessage(),
                        'message' => "Failed to sync DNS record {$record['id']} for {$domain}: " . $e->getMessage()
                    ],
                    'error'
                );
            }
        }

        // Remove obsolete records that are no longer in CloudFlare
        $deletedCount = $dnsRecordRepository->deleteObsoleteRecords($domain, $cloudflareIds);

        if ($deletedCount > 0) {
            $applicationLogger->logDomain(
                'dns_obsolete_records_removed',
                [
                    'domain' => $domain,
                    'deleted_count' => $deletedCount,
                    'message' => "Removed {$deletedCount} obsolete DNS records for domain: {$domain}"
                ]
            );
        }

        return $syncedCount;
    }

    /**
     * Resolve the correct domain name from DNS record
     *
     * @param array $record
     * @param string $baseDomain
     * @return string
     */
    private function resolveDomainFromRecord($record, $baseDomain)
    {
        $recordName = $record['name'];

        // If record name is exactly the base domain or "@", it belongs to the base domain
        if ($recordName === $baseDomain || $recordName === '@') {
            return $baseDomain;
        }

        // If record name ends with the base domain, extract the actual domain
        if (str_ends_with($recordName, '.' . $baseDomain)) {
            return $baseDomain;
        }

        // If record name is the exact domain name
        if ($recordName === $baseDomain) {
            return $baseDomain;
        }

        // For subdomain records, return the base domain
        return $baseDomain;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        $tags = ['dns-sync'];

        if ($this->domain) {
            $tags[] = "domain:{$this->domain}";
        }

        return $tags;
    }
}
