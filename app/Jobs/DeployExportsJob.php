<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CloudFlareService;
use App\Services\ApplicationLogger;

class DeployExportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $projectName;
    protected $directory;
    protected $options;
    protected $domain;
    protected $pageSlugs;
    public $timeout = 600; // 10 minutes timeout
    public $tries = 1; // Only try once as retrying might cause duplicate deployments

    /**
     * Create a new job instance.
     *
     * @param string $projectName
     * @param string $directory
     * @param string $domain
     * @param array $pageSlugs
     * @param array $options
     */
    public function __construct(string $projectName, string $directory, string $domain, array $pageSlugs, array $options = [])
    {
        $this->projectName = $projectName;
        $this->directory = $directory;
        $this->domain = $domain;
        $this->pageSlugs = $pageSlugs;
        $this->options = $options;
        $this->queue = 'deployments'; // Use a dedicated queue for deployments
    }

    /**
     * Execute the job.
     *
     * @param CloudFlareService $cloudflareService
     * @param ApplicationLogger $logger
     * @return void
     */
    public function handle(CloudFlareService $cloudflareService, ApplicationLogger $logger)
    {
        try {
            $result = $cloudflareService->deployExportDirectory(
                $this->projectName,
                $this->directory,
                $this->domain,
                $this->options
            );

            if ($result['success']) {
                $logger->logDeploy('completed', [
                    'project' => $this->projectName,
                    'directory' => $result['directory'],
                    'deployment_url' => $result['deployment_url'] ?? 'N/A'
                ]);

                if (!empty($this->domain)) {
                    foreach ($this->pageSlugs as $pageSlug) {
                        $purgeResult = $cloudflareService->purgeCache($this->domain, $pageSlug);
                        if ($purgeResult['success']) {
                            $logger->logDeploy('cache_purged', [
                                'domain' => $this->domain,
                                'page_slug' => $pageSlug
                            ]);

                            $pathsToWarm = [
                                '/',
                                $pageSlug,
                            ];

                            $warmResults = $cloudflareService->warmCache($this->domain, $pathsToWarm);

                            foreach ($warmResults as $result) {
                                if ($result['success']) {
                                    $logger->logDeploy('cache_warmed', [
                                        'url' => $result['url'],
                                        'status' => $result['status']
                                    ]);
                                } else {
                                    $logger->logDeploy('cache_warm_failed', [
                                        'url' => $result['url'],
                                        'error' => $result['error'] ?? 'Unknown error'
                                    ], 'warning');
                                }
                            }
                        } else {
                            $logger->logDeploy('cache_purge_failed', [
                                'domain' => $this->domain,
                                'message' => $purgeResult['message'] ?? 'Unknown error',
                                'error' => $purgeResult['error'] ?? null
                            ], 'warning');
                        }
                    }
                }
            } else {
                $logger->logDeploy('failed', [
                    'project' => $this->projectName,
                    'directory' => $result['directory'],
                    'output' => $result['output'] ?? 'No output available'
                ], 'error');
            }
        } catch (\Exception $e) {
            $logger->logDeploy('error', [
                'project' => $this->projectName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return [
            'deployment',
            'project:' . $this->projectName,
            'directory:' . ($this->directory ?? 'root')
        ];
    }
}