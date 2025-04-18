<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CloudFlareService;
use Illuminate\Support\Facades\Log;

class DeployExportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $projectName;
    protected $directory;
    protected $options;
    protected $domain;
    protected $pageSlug;
    public $timeout = 600; // 10 minutes timeout
    public $tries = 1; // Only try once as retrying might cause duplicate deployments

    /**
     * Create a new job instance.
     *
     * @param string $projectName
     * @param string $directory
     * @param string $domain
     * @param string $pageSlug
     * @param array $options
     */
    public function __construct(string $projectName, string $directory, string $domain, string $pageSlug, array $options = [])
    {
        $this->projectName = $projectName;
        $this->directory = $directory;
        $this->domain = $domain;
        $this->pageSlug = $pageSlug;
        $this->options = $options;
        $this->queue = 'deployments'; // Use a dedicated queue for deployments
    }

    /**
     * Execute the job.
     *
     * @param CloudFlareService $cloudflareService
     * @return void
     */
    public function handle(CloudFlareService $cloudflareService)
    {
        try {
            $result = $cloudflareService->deployExportDirectory(
                $this->projectName,
                $this->directory,
                $this->options
            );

            // Log the deployment result
            if ($result['success']) {
                Log::info('Deployment completed successfully', [
                    'project' => $this->projectName,
                    'directory' => $result['directory'],
                    'deployment_url' => $result['deployment_url'] ?? 'N/A'
                ]);
                // Purge cache after successful deployment
                if (!empty($this->domain)) {
                    $purgeResult = $cloudflareService->purgeCache($this->domain, $this->pageSlug);
                    if ($purgeResult['success']) {
                        Log::info('Cache purged successfully for domain: ' . $this->domain);
                        
                        // Warm Cloudflare cache for key pages
                        $pathsToWarm = [
                            // '/',                         // homepage
                            $this->pageSlug,             // main page
                            // 'about',
                            // 'contact'
                        ];

                        $warmResults = $cloudflareService->warmCache($this->domain, $pathsToWarm);

                        foreach ($warmResults as $result) {
                            if ($result['success']) {
                                Log::info("Cache warmed: {$result['url']} (Status: {$result['status']})");
                            } else {
                                Log::warning("Cache warm failed: {$result['url']}", [
                                    'error' => $result['error'] ?? 'Unknown error'
                                ]);
                            }
                        }
                    } else {
                        Log::warning('Failed to purge cache for domain: ' . $this->domain, [
                            'message' => $purgeResult['message'] ?? 'Unknown error',
                            'error' => $purgeResult['error'] ?? null
                        ]);
                    }
                }
            } else {
                Log::error('Deployment completed with issues', [
                    'project' => $this->projectName,
                    'directory' => $result['directory'],
                    'output' => $result['output'] ?? 'No output available'
                ]);
            }

            // You could also trigger events, notifications, or webhooks here
            // event(new DeploymentCompleted($result));
        } catch (\Exception $e) {
            Log::error('Deployment job failed', [
                'project' => $this->projectName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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