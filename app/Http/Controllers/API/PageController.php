<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Services\CloudFlareService;
use Auth;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use App\Repositories\PageRepository;
use App\Repositories\PageExportRepository;
use App\Repositories\SiteRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PageController extends Controller
{
    use LogsActivity;

    protected $pageRepository;
    protected $pageExportRepository;
    protected $siteRepository;
    protected $cloudflareService;

    public function __construct(
        PageRepository $pageRepository,
        PageExportRepository $pageExportRepository,
        SiteRepository $siteRepository,
        CloudFlareService $cloudflareService
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageExportRepository = $pageExportRepository;
        $this->siteRepository = $siteRepository;
        $this->cloudflareService = $cloudflareService;
    }

    /**
     * Create a new page
     */
    public function create(PageRequest $request)
    {
        try {
            $site = $this->siteRepository->findWithRelations($request->site_id);
            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            // Create the page through repository
            $page = $this->pageRepository->create([
                'provider' => Auth::id(),
                'content' => $request->content,
                'site_id' => $site->id,
                'name' => $request->name,
                'slug' => $request->slug,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Page created successfully',
                'data' => $page
            ]);
        } catch (\Exception $e) {
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
        $page = $this->pageRepository->find($pageId);
        if ($page) {
            return response()->json([
                'success' => true,
                'message' => 'Page found',
                'data' => $page
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
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get the site and page through repositories
            $site = $this->siteRepository->findWithRelations($request->site_id);
            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            $page = $this->pageRepository->find($pageId);
            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            if ($page->site_id !== $site->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page does not belong to this site'
                ], 403);
            }

            // Create site-specific export directory structure
            $exportPath = 'exports/' . $site->cloudflare_project_name . '/' . $page->slug;

            // Store the HTML file
            $htmlFile = $request->file('html_file');
            $filename = 'index.' . $htmlFile->getClientOriginalExtension();
            $filePath = $htmlFile->storeAs($exportPath, $filename, 'public');

            // Create the export request through repository
            $exportRequest = $this->pageExportRepository->create([
                'slugs' => $page->slug,
                'result_path' => $filePath,
                'status' => 'completed',
                'site_id' => $site->id
            ]);

            // Create _headers file in the root directory of the site exports
            $rootExportPath = 'exports/' . $site->cloudflare_project_name;
            $headersContent = <<<EOT
/{$page->slug}/index.html
  Cache-Control: public, max-age=31536000, immutable

/{$page->slug}/*.js
  Cache-Control: public, max-age=31536000, immutable

/{$page->slug}/*.css
  Cache-Control: public, max-age=31536000, immutable


EOT;

            // Check if _headers file exists
            $headersFilePath = storage_path('app/public/' . $rootExportPath . '/_headers');
            if (file_exists($headersFilePath)) {
                // Read existing content
                $existingContent = file_get_contents($headersFilePath);

                // Only add new rules if they don't already exist
                if (strpos($existingContent, "/{$page->slug}/index.html") === false) {
                    $headersContent = $existingContent . "\n" . $headersContent;
                } else {
                    $headersContent = $existingContent;
                }
            }

            // Store the _headers file
            Storage::disk('public')->put($rootExportPath . '/_headers', $headersContent);

            // Always update the _redirects file to point root "/" to the current page's slug
            $redirectsContent = "/ /{$page->slug}/ 302\n";
            Storage::disk('public')->put($rootExportPath . '/_redirects', $redirectsContent);



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
            return response()->json([
                'success' => false,
                'message' => 'Failed to export page: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint for the exporter service to get the latest export request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingExports()
    {
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

            // Find the page and its associated site
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
            // Delete the exported files if project exists
            if ($site->cloudflare_project_name) {
                $this->cloudflareService->deletePageFiles(
                    $site->cloudflare_project_name,
                    $page->slug
                );
            }


            // Delete the page from database
            $this->pageRepository->deleteById($pageId);

            // Trigger site redeployment if project exists
            if ($site->cloudflare_project_name) {
                try {
                    // Dispatch the deployment job
                    dispatch(new \App\Jobs\DeployExportsJob(
                        $site->cloudflare_project_name,
                        $site->cloudflare_project_name,
                        $site->domain
                    ));

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
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to queue deployment job',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Page deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete page',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
