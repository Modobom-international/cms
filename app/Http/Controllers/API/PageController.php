<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use Auth;
use Illuminate\Http\Request;
use App\Models\Page;
use App\Repositories\PageRepository;
use App\Repositories\PageExportRepository;
use App\Repositories\SiteRepository;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    protected $pageRepository;
    protected $pageExportRepository;
    protected $siteRepository;

    public function __construct(
        PageRepository $pageRepository,
        PageExportRepository $pageExportRepository,
        SiteRepository $siteRepository
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageExportRepository = $pageExportRepository;
        $this->siteRepository = $siteRepository;
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
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
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
            $page = $this->pageRepository->findBySlug($request->slug);

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
     * Get a page by its slug
     * 
     * @param string $slug The page slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPage($slug)
    {
        $page = $this->pageRepository->findBySlug($slug);
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
     * Create a new page export request and trigger the exporter
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportPage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
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

            $page = $this->pageRepository->findBySlugAndSite($request->slug, $site->id);
            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found for this site'
                ], 404);
            }

            // Create site-specific export directory structure
            $exportPath = 'exports/' . $site->cloudflare_project_name . '/' . $request->slug;

            // Store the HTML file
            $htmlFile = $request->file('html_file');
            $filename = 'index.' . $htmlFile->getClientOriginalExtension();
            $filePath = $htmlFile->storeAs($exportPath, $filename, 'public');

            // Create the export request through repository
            $exportRequest = $this->pageExportRepository->create([
                'slugs' => $request->slug,
                'result_path' => $filePath,
                'status' => 'completed',
                'site_id' => $site->id
            ]);

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
}