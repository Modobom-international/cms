<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncDnsRecords;
use App\Repositories\DomainRepository;
use App\Repositories\DnsRecordRepository;
use App\Services\CloudFlareService;
use App\Services\ApplicationLogger;
use App\Services\ActivityLogger;

class SyncDnsRecordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dns:sync 
                            {domain? : Specific domain to sync DNS records for}
                            {--all : Sync DNS records for all domains}
                            {--queue : Run sync in queue instead of synchronously}
                            {--force : Force sync even if recently synced}
                            {--stats : Show DNS records statistics after sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync DNS records from CloudFlare API to database';

    /**
     * Execute the console command.
     */
    public function handle(
        DomainRepository $domainRepository,
        DnsRecordRepository $dnsRecordRepository,
        CloudFlareService $cloudFlareService,
        ApplicationLogger $logger,
        ActivityLogger $activityLogger
    ) {
        $domain = $this->argument('domain');
        $syncAll = $this->option('all');
        $useQueue = $this->option('queue');
        $forceSync = $this->option('force');
        $showStats = $this->option('stats');

        $logger->logDomain('info', [
            'message' => 'DNS records sync command started',
            'command' => $this->signature,
            'domain' => $domain,
            'sync_all' => $syncAll,
            'use_queue' => $useQueue,
            'force_sync' => $forceSync,
            'show_stats' => $showStats
        ]);

        if (!$domain && !$syncAll) {
            $logger->logDomain('error', [
                'message' => 'DNS sync command failed - no domain specified and --all flag not used'
            ]);
            $this->error('Please specify a domain or use --all flag to sync all domains');
            return 1;
        }

        if ($domain && $syncAll) {
            $logger->logDomain('error', [
                'message' => 'DNS sync command failed - both domain and --all flag specified',
                'domain' => $domain
            ]);
            $this->error('Cannot specify both domain and --all flag');
            return 1;
        }

        // Validate domain exists if specified
        if ($domain) {
            $domainModel = $domainRepository->findByDomain($domain);
            if (!$domainModel) {
                $logger->logDomain('error', [
                    'message' => 'DNS sync command failed - domain not found in database',
                    'domain' => $domain
                ]);
                $this->error("Domain '{$domain}' not found in database");
                return 1;
            }
            $logger->logDomain('info', [
                'message' => 'Domain validated successfully',
                'domain' => $domain,
                'domain_id' => $domainModel->id
            ]);
        }

        $this->info('Starting DNS records sync...');

        if ($useQueue) {
            // Dispatch job to queue
            SyncDnsRecords::dispatch($domain, $forceSync);
            $logger->logDomain('info', [
                'message' => 'DNS sync job dispatched to queue',
                'domain' => $domain,
                'force_sync' => $forceSync
            ]);
            $this->info('DNS sync job dispatched to queue');
        } else {
            // Run synchronously
            try {
                $logger->logDomain('info', [
                    'message' => 'Starting synchronous DNS records sync',
                    'domain' => $domain,
                    'force_sync' => $forceSync
                ]);

                $job = new SyncDnsRecords($domain, $forceSync);
                $job->handle($cloudFlareService, $domainRepository, $dnsRecordRepository, $activityLogger);

                $logger->logDomain('info', [
                    'message' => 'DNS records sync completed successfully',
                    'domain' => $domain,
                    'execution_mode' => 'synchronous'
                ]);

                $this->info('DNS records sync completed successfully');
            } catch (\Exception $e) {
                $logger->logDomain('error', [
                    'message' => 'DNS sync failed with exception',
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'execution_mode' => 'synchronous'
                ]);
                $this->error('DNS sync failed: ' . $e->getMessage());
                return 1;
            }
        }

        // Show statistics if requested
        if ($showStats && !$useQueue) {
            $logger->logDomain('info', [
                'message' => 'Displaying DNS records statistics',
                'domain' => $domain
            ]);
            $this->showStatistics($dnsRecordRepository, $domain, $logger);
        }

        $logger->logDomain('info', [
            'message' => 'DNS records sync command completed',
            'domain' => $domain,
            'execution_mode' => $useQueue ? 'queued' : 'synchronous'
        ]);

        return 0;
    }

    /**
     * Show DNS records statistics
     */
    private function showStatistics(DnsRecordRepository $dnsRecordRepository, $domain = null, ApplicationLogger $logger)
    {
        $this->newLine();
        $this->info('=== DNS Records Statistics ===');

        $stats = $dnsRecordRepository->getStatistics($domain);

        $logger->logDomain('info', [
            'message' => 'DNS records statistics generated',
            'domain' => $domain,
            'total_records' => $stats['total_records'],
            'proxied_count' => $stats['proxied_count'],
            'non_proxied_count' => $stats['non_proxied_count'],
            'by_type' => $stats['by_type'] ?? []
        ]);

        $this->table([
            'Metric',
            'Value'
        ], [
            ['Total Records', $stats['total_records']],
            ['Proxied Records', $stats['proxied_count']],
            ['Non-Proxied Records', $stats['non_proxied_count']],
        ]);

        if (!empty($stats['by_type'])) {
            $this->newLine();
            $this->info('Records by Type:');

            $typeData = [];
            foreach ($stats['by_type'] as $type => $count) {
                $typeData[] = [$type, $count];
            }

            $this->table(['Type', 'Count'], $typeData);
        }
    }
}
