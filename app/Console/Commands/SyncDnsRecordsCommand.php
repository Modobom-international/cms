<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncDnsRecords;
use App\Repositories\DomainRepository;
use App\Repositories\DnsRecordRepository;
use App\Services\CloudFlareService;

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
        CloudFlareService $cloudFlareService
    ) {
        $domain = $this->argument('domain');
        $syncAll = $this->option('all');
        $useQueue = $this->option('queue');
        $forceSync = $this->option('force');
        $showStats = $this->option('stats');

        if (!$domain && !$syncAll) {
            $this->error('Please specify a domain or use --all flag to sync all domains');
            return 1;
        }

        if ($domain && $syncAll) {
            $this->error('Cannot specify both domain and --all flag');
            return 1;
        }

        // Validate domain exists if specified
        if ($domain) {
            $domainModel = $domainRepository->findByDomain($domain);
            if (!$domainModel) {
                $this->error("Domain '{$domain}' not found in database");
                return 1;
            }
        }

        $this->info('Starting DNS records sync...');

        if ($useQueue) {
            // Dispatch job to queue
            SyncDnsRecords::dispatch($domain, $forceSync);
            $this->info('DNS sync job dispatched to queue');
        } else {
            // Run synchronously
            try {
                $job = new SyncDnsRecords($domain, $forceSync);
                $job->handle($cloudFlareService, $domainRepository, $dnsRecordRepository);

                $this->info('DNS records sync completed successfully');
            } catch (\Exception $e) {
                $this->error('DNS sync failed: ' . $e->getMessage());
                return 1;
            }
        }

        // Show statistics if requested
        if ($showStats && !$useQueue) {
            $this->showStatistics($dnsRecordRepository, $domain);
        }

        return 0;
    }

    /**
     * Show DNS records statistics
     */
    private function showStatistics(DnsRecordRepository $dnsRecordRepository, $domain = null)
    {
        $this->newLine();
        $this->info('=== DNS Records Statistics ===');

        $stats = $dnsRecordRepository->getStatistics($domain);

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
