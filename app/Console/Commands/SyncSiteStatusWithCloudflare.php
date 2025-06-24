<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Site;
use App\Services\CloudFlareService;
use App\Services\ApplicationLogger;
use App\Enums\Site as SiteStatus;

class SyncSiteStatusWithCloudflare extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'site:sync-cloudflare-status {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync site statuses with current Cloudflare Pages projects';

    protected $cloudflareService;
    protected $logger;

    /**
     * Create a new command instance.
     */
    public function __construct(CloudFlareService $cloudflareService, ApplicationLogger $logger)
    {
        parent::__construct();
        $this->cloudflareService = $cloudflareService;
        $this->logger = $logger;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting site status synchronization with Cloudflare...');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->logger->logSite('sync_started', [
            'dry_run' => $isDryRun
        ]);

        try {
            // Get all Cloudflare Pages projects
            $this->info('Fetching Cloudflare Pages projects...');
            $cloudflareProjects = $this->cloudflareService->getProjects();

            if (!$cloudflareProjects['success']) {
                $errorMessage = $cloudflareProjects['error'] ?? 'Unknown error';
                $httpStatus = $cloudflareProjects['http_status'] ?? 'N/A';
                $cloudflareErrors = $cloudflareProjects['cloudflare_errors'] ?? [];

                $this->error("Failed to fetch Cloudflare projects: {$errorMessage}");
                $this->error("HTTP Status: {$httpStatus}");

                if (!empty($cloudflareErrors)) {
                    $this->error("Cloudflare API Errors:");
                    foreach ($cloudflareErrors as $error) {
                        $this->error("  - Code {$error['code']}: {$error['message']}");
                    }
                }

                if (isset($cloudflareProjects['response_body'])) {
                    $this->error("Response Body: " . substr($cloudflareProjects['response_body'], 0, 500));
                }

                $this->logger->logSite('sync_failed', [
                    'error' => 'Failed to fetch Cloudflare projects',
                    'cloudflare_error' => $cloudflareProjects
                ], 'error');
                return 1;
            }

            $activeProjects = collect($cloudflareProjects['result'] ?? [])
                ->pluck('name')
                ->toArray();

            $resultInfo = $cloudflareProjects['result_info'] ?? [];
            $totalFetched = $resultInfo['total_count'] ?? count($activeProjects);
            $pagesFetched = $resultInfo['pages_fetched'] ?? 1;

            $this->info("Found {$totalFetched} active Cloudflare Pages projects (fetched from {$pagesFetched} pages)");

            $this->logger->logSite('cloudflare_projects_fetched', [
                'total_projects' => $totalFetched,
                'pages_fetched' => $pagesFetched,
                'project_names' => $activeProjects
            ]);

            // Get all sites from database
            $sites = Site::whereNotNull('cloudflare_project_name')->get();
            $this->info('Found ' . $sites->count() . ' sites with Cloudflare project names');

            $updatedCount = 0;
            $errorCount = 0;

            foreach ($sites as $site) {
                try {
                    $projectExists = in_array($site->cloudflare_project_name, $activeProjects);
                    $expectedStatus = $projectExists ? SiteStatus::STATUS_ACTIVE : SiteStatus::STATUS_INACTIVE;

                    if ($site->status !== $expectedStatus) {
                        $this->line("Site ID {$site->id} ({$site->name}): {$site->status} -> {$expectedStatus}");

                        if (!$isDryRun) {
                            $oldStatus = $site->status;
                            $site->update([
                                'status' => $expectedStatus,
                                'cloudflare_domain_status' => $projectExists ? SiteStatus::STATUS_ACTIVE : SiteStatus::STATUS_INACTIVE
                            ]);

                            $this->logger->logSite('status_synced', [
                                'site_id' => $site->id,
                                'site_name' => $site->name,
                                'project_name' => $site->cloudflare_project_name,
                                'old_status' => $oldStatus,
                                'new_status' => $expectedStatus,
                                'project_exists' => $projectExists
                            ]);
                        }

                        $updatedCount++;
                    } else {
                        $this->line("Site ID {$site->id} ({$site->name}): No change needed (already {$site->status})");
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing site ID {$site->id}: " . $e->getMessage());
                    $this->logger->logSite('sync_site_error', [
                        'site_id' => $site->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], 'error');
                    $errorCount++;
                }
            }

            // Check for orphaned projects (projects in Cloudflare but not in database)
            $siteProjectNames = $sites->pluck('cloudflare_project_name')->toArray();
            $orphanedProjects = array_diff($activeProjects, $siteProjectNames);

            if (!empty($orphanedProjects)) {
                $this->warn('Found ' . count($orphanedProjects) . ' orphaned Cloudflare projects:');
                foreach ($orphanedProjects as $orphanedProject) {
                    $this->line("  - {$orphanedProject}");
                }

                $this->logger->logSite('orphaned_projects_found', [
                    'count' => count($orphanedProjects),
                    'projects' => $orphanedProjects
                ], 'warning');
            }

            $this->info("\nSync completed:");
            $this->info("- Sites processed: " . $sites->count());
            $this->info("- Sites " . ($isDryRun ? 'would be ' : '') . "updated: {$updatedCount}");
            $this->info("- Errors: {$errorCount}");
            $this->info("- Orphaned projects: " . count($orphanedProjects));

            $this->logger->logSite('sync_completed', [
                'dry_run' => $isDryRun,
                'sites_processed' => $sites->count(),
                'sites_updated' => $updatedCount,
                'errors' => $errorCount,
                'orphaned_projects' => count($orphanedProjects)
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            $this->logger->logSite('sync_error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');
            return 1;
        }
    }
}