<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Services\CloudFlareService;
use App\Services\ApplicationLogger;
use Auth;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use App\Enums\ActivityAction;
use App\Repositories\PageRepository;
use App\Repositories\PageExportRepository;
use App\Repositories\SiteRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\TrackingScriptRequest;

class PageController extends Controller
{
    use LogsActivity;

    protected $pageRepository;
    protected $pageExportRepository;
    protected $siteRepository;
    protected $cloudflareService;
    protected $logger;

    public function __construct(
        PageRepository $pageRepository,
        PageExportRepository $pageExportRepository,
        SiteRepository $siteRepository,
        CloudFlareService $cloudflareService,
        ApplicationLogger $logger
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageExportRepository = $pageExportRepository;
        $this->siteRepository = $siteRepository;
        $this->cloudflareService = $cloudflareService;
        $this->logger = $logger;
    }

    /**
     * Create a new page
     */
    public function create(PageRequest $request)
    {
        try {
            $site = $this->siteRepository->findWithRelations($request->site_id);
            if (!$site) {
                $this->logger->logPage('create_failed', [
                    'site_id' => $request->site_id,
                    'error' => 'Site not found'
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            $page = $this->pageRepository->create([
                'provider' => Auth::id(),
                'content' => $request->content,
                'site_id' => $site->id,
                'name' => $request->name,
                'slug' => $request->slug,
            ]);

            $this->logActivity(ActivityAction::CREATE_PAGE, [
                'page_id' => $page->id,
                'name' => $page->name,
                'slug' => $page->slug,
                'site_id' => $site->id
            ], 'Created page');

            $this->logger->logPage('created', [
                'page_id' => $page->id,
                'name' => $page->name,
                'slug' => $page->slug,
                'site_id' => $site->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Page created successfully',
                'data' => $page
            ]);
        } catch (\Exception $e) {
            $this->logger->logPage('create_failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to create page: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing page
     * 
     * @param Request $request
     * @param int $pageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $pageId)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $page = $this->pageRepository->find($pageId);

            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            // Update through repository
            $this->pageRepository->update([
                'content' => $request->content,
            ], $page->id);

            // Fetch the updated page through repository
            $updatedPage = $this->pageRepository->find($page->id);

            $this->logActivity(ActivityAction::UPDATE_PAGE, [
                'page_id' => $pageId,
                'name' => $updatedPage->name
            ], 'Updated page');

            return response()->json([
                'success' => true,
                'message' => 'Page updated successfully',
                'data' => $updatedPage
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update page: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a page by its ID
     * 
     * @param int $pageId The page ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPage($pageId)
    {
        // $this->logActivity(ActivityAction::SHOW_RECORD, ['page_id' => $pageId], 'Viewed page');

        $page = $this->pageRepository->find($pageId);
        $site = $this->siteRepository->findWithRelations($page->site_id);
        if ($page) {
            return response()->json([
                'success' => true,
                'message' => 'Page found',
                'data' => $page,
                'site' => $site
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Page not found'
        ], 404);
    }

    /**
     * Get all pages
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPages()
    {
        // $this->logActivity(ActivityAction::ACCESS_VIEW, [], 'Viewed pages listing');

        $pages = $this->pageRepository->getAllWithRelations();

        return response()->json([
            'success' => true,
            'message' => 'Pages found',
            'data' => $pages
        ], 200);
    }

    /**
     * Get all pages for a specific site
     * 
     * @param int $siteId The site ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPagesBySite($siteId)
    {
        // $this->logActivity(ActivityAction::ACCESS_VIEW, ['site_id' => $siteId], 'Viewed pages for site');

        try {
            // Verify site exists
            $site = $this->siteRepository->findWithRelations($siteId);
            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            $pages = $this->pageRepository->getBySiteId($siteId);

            return response()->json([
                'success' => true,
                'message' => 'Pages found for site',
                'data' => $pages
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new page export request and trigger the exporter
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $pageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportPage(Request $request, $pageId)
    {
        $validator = Validator::make($request->all(), [
            'html_file' => 'required|file|mimes:html,htm',
            'site_id' => 'required|exists:sites,id'
        ]);

        if ($validator->fails()) {
            $this->logger->logExport('validation_failed', [
                'page_id' => $pageId,
                'errors' => $validator->errors()->toArray()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $site = $this->siteRepository->findWithRelations($request->site_id);
            if (!$site) {
                $this->logger->logExport('site_not_found', [
                    'site_id' => $request->site_id,
                    'page_id' => $pageId
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            $page = $this->pageRepository->find($pageId);
            if (!$page) {
                $this->logger->logExport('page_not_found', [
                    'page_id' => $pageId
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            if ($page->site_id !== $site->id) {
                $this->logger->logExport('page_site_mismatch', [
                    'page_id' => $pageId,
                    'page_site_id' => $page->site_id,
                    'requested_site_id' => $site->id
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Page does not belong to this site'
                ], 403);
            }

            $exportPath = 'exports/' . $site->cloudflare_project_name . '/' . $page->slug;
            $htmlFile = $request->file('html_file');
            $filename = 'index.' . $htmlFile->getClientOriginalExtension();

            // Read the HTML content
            $htmlContent = file_get_contents($htmlFile->getRealPath());

            // If page has tracking script, inject it into the head tag
            if ($page->tracking_script) {
                $htmlContent = $this->injectTrackingScript($htmlContent, $page->tracking_script);
            }

            // Store the modified HTML content
            $filePath = $exportPath . '/' . $filename;
            Storage::disk('public')->put($filePath, $htmlContent);

            $exportRequest = $this->pageExportRepository->create([
                'slugs' => $page->slug,
                'result_path' => $filePath,
                'status' => 'completed',
                'site_id' => $site->id
            ]);

            $this->logger->logExport('completed', [
                'export_id' => $exportRequest->id,
                'page_id' => $pageId,
                'site_id' => $site->id,
                'file_path' => $filePath
            ]);

            // Create _headers file
            $rootExportPath = 'exports/' . $site->cloudflare_project_name;
            $headersContent = $this->generateHeadersContent($page->slug);

            $headersFilePath = storage_path('app/public/' . $rootExportPath . '/_headers');
            if (file_exists($headersFilePath)) {
                $existingContent = file_get_contents($headersFilePath);
                if (strpos($existingContent, "/{$page->slug}/index.html") === false) {
                    $headersContent = $existingContent . "\n" . $headersContent;
                } else {
                    $headersContent = $existingContent;
                }
            }

            Storage::disk('public')->put($rootExportPath . '/_headers', $headersContent);

            // Update _redirects file
            $redirectsContent = "/ /{$page->slug}/ 302\n";
            Storage::disk('public')->put($rootExportPath . '/_redirects', $redirectsContent);

            $this->logActivity(ActivityAction::EXPORT_PAGE, [
                'page_id' => $pageId,
                'export_id' => $exportRequest->id,
                'site_id' => $site->id,
                'page_slug' => $page->slug
            ], 'Exported page');

            return response()->json([
                'success' => true,
                'message' => 'Export process completed',
                'data' => [
                    'export_id' => $exportRequest->id,
                    'html_path' => $filePath,
                    'site' => [
                        'id' => $site->id,
                        'name' => $site->name,
                        'cloudflare_project_name' => $site->cloudflare_project_name
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->logExport('failed', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to export page: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Inject or replace tracking script in HTML content
     * 
     * @param string $htmlContent
     * @param string|null $trackingScript
     * @return string
     */
    protected function injectTrackingScript($htmlContent, $trackingScript = null)
    {
        // First, try to remove any existing tracking script
        $pattern = '/<!--\s*TRACKING_SCRIPT_START\s*-->.*?<!--\s*TRACKING_SCRIPT_END\s*-->/s';
        $htmlContent = preg_replace($pattern, '', $htmlContent);

        // If no new script provided, return the cleaned content
        if (empty($trackingScript)) {
            return $htmlContent;
        }

        // Wrap the new script in comments for future identification
        $wrappedScript = "<!-- TRACKING_SCRIPT_START -->\n{$trackingScript}\n<!-- TRACKING_SCRIPT_END -->";

        // Find the closing head tag
        $headEndPos = stripos($htmlContent, '</head>');

        if ($headEndPos !== false) {
            // Insert tracking script before the closing head tag
            return substr_replace($htmlContent, $wrappedScript . "\n", $headEndPos, 0);
        }

        // If no head tag found, try to find the opening body tag
        $bodyStartPos = stripos($htmlContent, '<body');

        if ($bodyStartPos !== false) {
            // Insert tracking script before the body tag
            return substr_replace($htmlContent, $wrappedScript . "\n", $bodyStartPos, 0);
        }

        // If no suitable position found, append to the end
        return $htmlContent . "\n" . $wrappedScript;
    }

    protected function generateHeadersContent($slug)
    {
        return <<<EOT
/{$slug}/index.html
  Cache-Control: public, max-age=31536000, immutable

/{$slug}/*.js
  Cache-Control: public, max-age=31536000, immutable

/{$slug}/*.css
  Cache-Control: public, max-age=31536000, immutable

EOT;
    }

    /**
     * API endpoint for the exporter service to get the latest export request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingExports()
    {
        // $this->logActivity(ActivityAction::ACCESS_VIEW, [], 'Viewed pending exports');

        $latestExport = $this->pageExportRepository->getLatestExport();

        return response()->json([
            'success' => true,
            'message' => 'Latest export retrieved',
            'data' => $latestExport
        ]);
    }

    /**
     * Get the current job status and cancel any running export jobs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelExport()
    {
        // Clear any export records in the database
        $this->pageExportRepository->truncate();

        $this->logActivity(ActivityAction::CANCEL_EXPORT, [], 'Cancelled exports');

        return response()->json([
            'success' => true,
            'message' => 'Export cancelled, any running jobs will complete but future ones are cancelled'
        ]);
    }

    /**
     * Delete a page and its associated files
     *
     * @param int $pageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($pageId)
    {
        try {
            DB::beginTransaction();

            $page = $this->pageRepository->find($pageId);
            if (!$page) {
                $this->logger->logPage('delete_failed', [
                    'page_id' => $pageId,
                    'error' => 'Page not found'
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            $site = $this->siteRepository->findWithRelations($page->site_id);
            if (!$site) {
                $this->logger->logPage('delete_failed', [
                    'page_id' => $pageId,
                    'site_id' => $page->site_id,
                    'error' => 'Associated site not found'
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Associated site not found'
                ], 404);
            }

            if ($site->cloudflare_project_name) {
                $deleteResult = $this->cloudflareService->deletePageFiles(
                    $site->cloudflare_project_name,
                    $page->slug
                );
                if (!$deleteResult) {
                    $this->logger->logPage('cloudflare_delete_failed', [
                        'page_id' => $pageId,
                        'project_name' => $site->cloudflare_project_name,
                        'error' => 'Cloudflare deletion failed'
                    ], 'warning');
                }
            }

            $this->pageRepository->deleteById($pageId);

            if ($site->cloudflare_project_name) {
                try {
                    dispatch(new \App\Jobs\DeployExportsJob(
                        $site->cloudflare_project_name,
                        $site->cloudflare_project_name,
                        $site->domain,
                        [$page->slug]
                    ));

                    $this->logActivity(ActivityAction::DELETE_PAGE, [
                        'page_id' => $pageId,
                        'name' => $page->name,
                        'site_id' => $site->id
                    ], 'Deleted page');

                    $this->logger->logPage('deleted', [
                        'page_id' => $pageId,
                        'site_id' => $site->id,
                        'deployment_queued' => true
                    ]);

                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Page deleted successfully and deployment job has been queued',
                        'job_details' => [
                            'project' => $site->cloudflare_project_name,
                            'directory' => $site->cloudflare_project_name,
                            'queue' => 'deployments'
                        ]
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->logger->logPage('deployment_queue_failed', [
                        'page_id' => $pageId,
                        'site_id' => $site->id,
                        'error' => $e->getMessage()
                    ], 'error');

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to queue deployment job',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            DB::commit();

            $this->logActivity(ActivityAction::DELETE_PAGE, [
                'page_id' => $pageId,
                'name' => $page->name,
                'site_id' => $site->id
            ], 'Deleted page');

            $this->logger->logPage('deleted', [
                'page_id' => $pageId,
                'site_id' => $site->id,
                'deployment_queued' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Page deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->logger->logPage('delete_failed', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete page',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export page using server-side HTML content
     * 
     * @param int $pageId
     */
    protected function exportPageFromServer($pageId)
    {
        try {
            $page = $this->pageRepository->find($pageId);
            if (!$page) {
                return [
                    'success' => false,
                    'message' => 'Page not found'
                ];
            }

            $site = $this->siteRepository->findWithRelations($page->site_id);
            if (!$site) {
                return [
                    'success' => false,
                    'message' => 'Associated site not found'
                ];
            }

            $exportPath = 'exports/' . $site->cloudflare_project_name . '/' . $page->slug;
            $filename = 'index.html';

            // Get the existing HTML content from storage
            $existingFilePath = $exportPath . '/' . $filename;
            if (!Storage::disk('public')->exists($existingFilePath)) {
                return [
                    'success' => false,
                    'message' => 'No existing HTML file found for this page'
                ];
            }

            // Read the existing HTML content
            $htmlContent = Storage::disk('public')->get($existingFilePath);

            // If page has tracking script, inject it into the head tag
            $htmlContent = $this->injectTrackingScript($htmlContent, $page->tracking_script);

            // Store the modified HTML content
            $filePath = $exportPath . '/' . $filename;
            Storage::disk('public')->put($filePath, $htmlContent);

            $exportRequest = $this->pageExportRepository->create([
                'slugs' => $page->slug,
                'result_path' => $filePath,
                'status' => 'completed',
                'site_id' => $site->id
            ]);

            $this->logger->logExport('completed', [
                'export_id' => $exportRequest->id,
                'page_id' => $pageId,
                'site_id' => $site->id,
                'file_path' => $filePath
            ]);

            // Create _headers file
            $rootExportPath = 'exports/' . $site->cloudflare_project_name;
            $headersContent = $this->generateHeadersContent($page->slug);

            $headersFilePath = storage_path('app/public/' . $rootExportPath . '/_headers');
            if (file_exists($headersFilePath)) {
                $existingContent = file_get_contents($headersFilePath);
                if (strpos($existingContent, "/{$page->slug}/index.html") === false) {
                    $headersContent = $existingContent . "\n" . $headersContent;
                } else {
                    $headersContent = $existingContent;
                }
            }

            Storage::disk('public')->put($rootExportPath . '/_headers', $headersContent);

            // Update _redirects file
            $redirectsContent = "/ /{$page->slug}/ 302\n";
            Storage::disk('public')->put($rootExportPath . '/_redirects', $redirectsContent);

            return [
                'success' => true,
                'export_id' => $exportRequest->id,
                'html_path' => $filePath
            ];

        } catch (\Exception $e) {
            $this->logger->logExport('failed', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            throw $e;
        }
    }

    /**
     * Add or update tracking script for a page
     * 
     * @param TrackingScriptRequest $request
     * @param int $pageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTrackingScript(TrackingScriptRequest $request, $pageId)
    {
        try {
            DB::beginTransaction();

            $page = $this->pageRepository->find($pageId);
            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            $site = $this->siteRepository->findWithRelations($page->site_id);
            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Associated site not found'
                ], 404);
            }

            $this->pageRepository->update([
                'tracking_script' => $request->tracking_script
            ], $page->id);

            $updatedPage = $this->pageRepository->find($page->id);

            $this->logger->logPage('tracking_script_updated', [
                'page_id' => $pageId,
                'site_id' => $page->site_id
            ]);

            // Trigger export and deploy
            if ($site->cloudflare_project_name) {
                try {
                    // Export the page first
                    $exportResult = $this->exportPageFromServer($pageId);

                    if (!$exportResult['success']) {
                        throw new \Exception('Failed to export page: ' . ($exportResult['error'] ?? 'Please deploy the page manually before updating the tracking script'));
                    }

                    dispatch(new \App\Jobs\DeployExportsJob(
                        $site->cloudflare_project_name,
                        $site->cloudflare_project_name,
                        $site->domain,
                        [$page->slug]
                    ));

                    $this->logActivity(ActivityAction::UPDATE_TRACKING_SCRIPT, [
                        'page_id' => $pageId,
                        'name' => $page->name,
                        'site_id' => $site->id
                    ], 'Updated tracking script');

                    $this->logger->logPage('deployment_queued', [
                        'page_id' => $pageId,
                        'site_id' => $site->id,
                        'reason' => 'tracking_script_update',
                        'export_id' => $exportResult['export_id']
                    ]);

                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Tracking script updated successfully and deployment job has been queued',
                        'data' => $updatedPage,
                        'job_details' => [
                            'project' => $site->cloudflare_project_name,
                            'directory' => $site->cloudflare_project_name,
                            'queue' => 'deployments',
                            'export_id' => $exportResult['export_id']
                        ]
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->logger->logPage('deployment_queue_failed', [
                        'page_id' => $pageId,
                        'site_id' => $site->id,
                        'error' => $e->getMessage()
                    ], 'error');

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to queue deployment job',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            DB::commit();

            $this->logActivity(ActivityAction::UPDATE_TRACKING_SCRIPT, [
                'page_id' => $pageId,
                'name' => $page->name,
                'site_id' => $page->site_id
            ], 'Updated tracking script');

            return response()->json([
                'success' => true,
                'message' => 'Tracking script updated successfully',
                'data' => $updatedPage
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->logger->logPage('tracking_script_update_failed', [
                'page_id' => $pageId,
                'error' => $e->getMessage()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to update tracking script: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tracking script for a page
     * 
     * @param int $pageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrackingScript($pageId)
    {
        // $this->logActivity(ActivityAction::GET_TRACKING_SCRIPT, ['page_id' => $pageId], 'Viewed tracking script');

        try {
            $page = $this->pageRepository->find($pageId);
            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tracking script retrieved successfully',
                'data' => [
                    'tracking_script' => $page->tracking_script
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->logPage('tracking_script_retrieve_failed', [
                'page_id' => $pageId,
                'error' => $e->getMessage()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking script: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove tracking script from a page
     * 
     * @param int $pageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeTrackingScript($pageId)
    {
        try {
            DB::beginTransaction();

            $page = $this->pageRepository->find($pageId);
            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            $site = $this->siteRepository->findWithRelations($page->site_id);
            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Associated site not found'
                ], 404);
            }

            $this->pageRepository->update([
                'tracking_script' => null
            ], $page->id);

            $this->logger->logPage('tracking_script_removed', [
                'page_id' => $pageId,
                'site_id' => $page->site_id
            ]);

            // Trigger export and deploy
            if ($site->cloudflare_project_name) {
                try {
                    // Export the page first
                    $exportResult = $this->exportPageFromServer($pageId);

                    if (!$exportResult['success']) {
                        throw new \Exception('Failed to export page: ' . ($exportResult['error'] ?? 'Unknown error'));
                    }

                    dispatch(new \App\Jobs\DeployExportsJob(
                        $site->cloudflare_project_name,
                        $site->cloudflare_project_name,
                        $site->domain,
                        [$page->slug]
                    ));

                    $this->logActivity(ActivityAction::REMOVE_TRACKING_SCRIPT, [
                        'page_id' => $pageId,
                        'name' => $page->name,
                        'site_id' => $site->id
                    ], 'Removed tracking script');

                    $this->logger->logPage('deployment_queued', [
                        'page_id' => $pageId,
                        'site_id' => $site->id,
                        'reason' => 'tracking_script_removal',
                        'export_id' => $exportResult['export_id']
                    ]);

                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Tracking script removed successfully and deployment job has been queued',
                        'job_details' => [
                            'project' => $site->cloudflare_project_name,
                            'directory' => $site->cloudflare_project_name,
                            'queue' => 'deployments',
                            'export_id' => $exportResult['export_id']
                        ]
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->logger->logPage('deployment_queue_failed', [
                        'page_id' => $pageId,
                        'site_id' => $site->id,
                        'error' => $e->getMessage()
                    ], 'error');

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to queue deployment job',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            DB::commit();

            $this->logActivity(ActivityAction::REMOVE_TRACKING_SCRIPT, [
                'page_id' => $pageId,
                'name' => $page->name,
                'site_id' => $page->site_id
            ], 'Removed tracking script');

            return response()->json([
                'success' => true,
                'message' => 'Tracking script removed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->logger->logPage('tracking_script_remove_failed', [
                'page_id' => $pageId,
                'error' => $e->getMessage()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove tracking script: ' . $e->getMessage()
            ], 500);
        }
    }

}
