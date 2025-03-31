<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Http\Requests\ExportPageRequest;
use Illuminate\Http\Request;
use App\Models\Page;
use App\Repositories\PageRepository;
use App\Repositories\PageExportRepository;
use Illuminate\Support\Facades\Validator;
use App\Jobs\ProcessPageExport;

class PageController extends Controller
{
    protected $pageRepository;
    protected $pageExportRepository;

    public function __construct(
        PageRepository $pageRepository,
        PageExportRepository $pageExportRepository
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageExportRepository = $pageExportRepository;
    }

    /**
     * Create a new page
     */
    public function create(PageRequest $request)
    {
        $provider = $request->provider ?? 1;
        $content = $request->content;
        $site_id = $request->site_id;
        $name = $request->name;
        $slug = $request->slug;

        $data = [
            'provider' => $provider,
            'content' => $content,
            'site_id' => $site_id,
            'name' => $name,
            'slug' => $slug,

        ];

        $page = Page::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data' => $page
        ]);
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

        $page = Page::where('slug', $request->slug)->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }

        $page->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully',
            'data' => $page
        ]);
    }

    /**
     * Get a page by its slug
     * 
     * @param string $slug The page slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPage($slug)
    {
        $page = Page::where('slug', $slug)->first();
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

    public function getPages()
    {
        $pages = Page::all();
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
            'html_file' => 'required|file|mimes:html,htm'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the slug from the request
        $slug = $request->input('slug');

        // Store the HTML file with the slug as the filename
        $htmlFile = $request->file('html_file');
        $filename = $slug . '.' . $htmlFile->getClientOriginalExtension();
        $filePath = $htmlFile->storeAs('exports', $filename, 'public');


        // Create the export request
        $exportRequest = $this->pageExportRepository->create([
            'slugs' => $slug,
            'result_path' => $filePath,
            'status' => 'completed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Export process queued',
            'data' => [
                'export_id' => $exportRequest->id,
                'html_path' => $filePath
            ]
        ]);
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