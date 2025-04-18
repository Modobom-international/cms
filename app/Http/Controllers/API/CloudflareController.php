<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\SiteRepository;
use App\Services\CloudFlareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\LogsActivity;

class CloudflareController extends Controller
{
    use LogsActivity;
    
    protected $cloudflareService;
    protected $siteRepository;

    public function __construct(CloudFlareService $cloudflareService, SiteRepository $siteRepository)
    {
        $this->cloudflareService = $cloudflareService;
        $this->siteRepository = $siteRepository;
    }

    /**
     * Get all Cloudflare Pages projects
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjects()
    {
        $result = $this->cloudflareService->getProjects();

        return response()->json($result);
    }

    /**
     * Create a new Cloudflare Pages project
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createProject(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'branch' => 'nullable|string',
        ]);

        $branch = $request->branch ?? 'main';
        $result = $this->cloudflareService->createPagesProject($request->name, $branch);

        return response()->json($result);
    }

    /**
     * Update an existing Cloudflare Pages project
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProject(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $result = $this->cloudflareService->updatePagesProject($request->name, $request->except(['name']));

        return response()->json($result);
    }

    /**
     * Create a new deployment for a Cloudflare Pages project
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDeployment(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $result = $this->cloudflareService->createDeployment($request->name);

        return response()->json($result);
    }

    /**
     * Apply a custom domain to a Cloudflare Pages project
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyDomain(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string',
            'domain' => 'required|string',
        ]);

        $result = $this->cloudflareService->applyPagesDomain($request->project_name, $request->domain);

        return response()->json($result);
    }

    /**
     * Deploy static files from the exports directory using Wrangler CLI
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deployExports(Request $request)
    {
        // Validate required input parameters
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'page_slug' => 'nullable|string|max:50',
            'branch' => 'nullable|string|max:50',
            'commit_message' => 'nullable|string|max:200',
        ]);
        $site = $this->siteRepository->findWithRelations($request->site_id);
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Site not found'
            ], 404);
        }

        $projectName = $site->cloudflare_project_name;
        $directory = $site->cloudflare_project_name;


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Collect deployment options
        $deploymentOptions = $this->collectDeploymentOptions($request);

        try {
            // Dispatch the deployment job
            $job = new \App\Jobs\DeployExportsJob(
                $projectName,
                $directory,
                $site->domain,
                $request->page_slug,
                $deploymentOptions
            );

            dispatch($job);

            return response()->json([
                'success' => true,
                'message' => 'Deployment job has been queued successfully',
                'job_details' => [
                    'project' => $projectName,
                    'directory' => $directory,
                    'queue' => 'deployments'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue deployment job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Collect deployment options from request
     *
     * @param Request $request
     * @return array
     */
    private function collectDeploymentOptions(Request $request)
    {
        return array_filter([
            'branch' => $request->branch,
            'commit_message' => $request->commit_message,
        ]);
    }

    /**
     * Format deployment response
     *
     * @param array $result
     * @return \Illuminate\Http\JsonResponse
     */
    private function formatDeploymentResponse($result)
    {
        $statusCode = $result['success'] ? 200 : 400;

        $response = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        // Add optional fields if they exist
        if (isset($result['deployment_url'])) {
            $response['deployment_url'] = $result['deployment_url'];
        }

        if (isset($result['directory'])) {
            $response['directory'] = $result['directory'];
        }

        if (isset($result['elapsed_time'])) {
            $response['elapsed_time'] = round($result['elapsed_time'], 2) . ' seconds';
        }

        // Add full output only for debugging or if deployment failed
        if (!$result['success'] && isset($result['output'])) {
            $response['output'] = $result['output'];
        }

        return response()->json($response, $statusCode);
    }
}