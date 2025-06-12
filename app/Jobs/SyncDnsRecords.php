<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CloudFlareService;
use App\Repositories\DomainRepository;
use App\Repositories\DnsRecordRepository;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
        DnsRecordRepository $dnsRecordRepository
    ) {
        try {
            if ($this->domain) {
                // Sync specific domain
                $this->syncDomainDnsRecords($this->domain, $cloudFlareService, $dnsRecordRepository);
                Log::info("DNS records synced successfully for domain: {$this->domain}");
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
                            $dnsRecordRepository
                        );
                        $totalSynced += $recordCount;

                        Log::info("Synced {$recordCount} DNS records for domain: {$domainModel->domain}");
                    } catch (\Exception $e) {
                        $errors[] = "Failed to sync DNS records for {$domainModel->domain}: " . $e->getMessage();
                        Log::error("DNS sync error for {$domainModel->domain}: " . $e->getMessage());
                    }
                }

                Log::info("DNS records sync completed. Total records synced: {$totalSynced}");

                if (!empty($errors)) {
                    Log::warning("DNS sync completed with errors: " . implode(', ', $errors));
                }
            }
        } catch (\Exception $e) {
            Log::error('DNS records sync failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync DNS records for a specific domain
     *
     * @param string $domain
     * @param CloudFlareService $cloudFlareService
     * @param DnsRecordRepository $dnsRecordRepository
     * @return int Number of records synced
     */
    private function syncDomainDnsRecords(
        $domain,
        CloudFlareService $cloudFlareService,
        DnsRecordRepository $dnsRecordRepository
    ) {
        // Get zone ID for the domain
        $zoneId = $cloudFlareService->getZoneId($domain);

        if (!$zoneId) {
            Log::warning("Zone ID not found for domain: {$domain}");
            return 0;
        }

        // Get DNS records from CloudFlare
        $response = $cloudFlareService->getDnsRecords($zoneId);

        if (!$response || isset($response['error'])) {
            $errorMsg = $response['error'] ?? 'Unknown error getting DNS records';
            throw new \Exception("Failed to get DNS records for {$domain}: {$errorMsg}");
        }

        if (!isset($response['result']) || !is_array($response['result'])) {
            Log::warning("No DNS records found for domain: {$domain}");
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
                Log::error("Failed to sync DNS record {$record['id']} for {$domain}: " . $e->getMessage());
            }
        }

        // Remove obsolete records that are no longer in CloudFlare
        $deletedCount = $dnsRecordRepository->deleteObsoleteRecords($domain, $cloudflareIds);

        if ($deletedCount > 0) {
            Log::info("Removed {$deletedCount} obsolete DNS records for domain: {$domain}");
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
