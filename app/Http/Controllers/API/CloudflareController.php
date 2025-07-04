<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\SiteRepository;
use App\Services\CloudFlareService;
use App\Services\ApplicationLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\LogsActivity;
use App\Enums\ActivityAction;

class CloudflareController extends Controller
{
    use LogsActivity;

    protected $cloudflareService;
    protected $siteRepository;
    protected $logger;

    public function __construct(
        CloudFlareService $cloudflareService,
        SiteRepository $siteRepository,
        ApplicationLogger $logger,
        ActivityAction $activityAction
    ) {
        $this->cloudflareService = $cloudflareService;
        $this->siteRepository = $siteRepository;
        $this->logger = $logger;
        $this->activityAction = $activityAction;
    }

    /**
     * Get all Cloudflare Pages projects
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjects()
    {
        // $this->logActivity(ActivityAction::ACCESS_VIEW, [], 'Viewed Cloudflare projects');

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

        $input = $request->all();
        $branch = $request->branch ?? 'main';
        $result = $this->cloudflareService->createPagesProject($request->name, $branch);

        if ($result['success'] ?? false) {
            $this->logActivity(ActivityAction::CREATE_PROJECT_CLOUDFLARE_PAGE, [
                'project_name' => $request->name,
                'branch' => $branch
            ], 'Created Cloudflare project');
        }

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

        $input = $request->all();
        $result = $this->cloudflareService->updatePagesProject($request->name, $request->except(['name']));

        if ($result['success'] ?? false) {
            $this->logActivity(ActivityAction::UPDATE_PROJECT_CLOUDFLARE_PAGE, [
                'project_name' => $request->name
            ], 'Updated Cloudflare project');
        }

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

        $input = $request->all();
        $result = $this->cloudflareService->createDeployment($request->name);

        if ($result['success'] ?? false) {
            $this->logActivity(ActivityAction::CREATE_DEPLOY_CLOUDFLARE_PAGE, [
                'project_name' => $request->name
            ], 'Created Cloudflare deployment');
        }

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

        $input = $request->all();
        $result = $this->cloudflareService->applyPagesDomain($request->project_name, $request->domain);

        if ($result['success'] ?? false) {
            $this->logActivity(ActivityAction::APPLY_PAGE_DOMAIN_CLOUDFLARE_PAGE, [
                'project_name' => $request->project_name,
                'domain' => $request->domain
            ], 'Applied domain to Cloudflare project');
        }

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
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'page_slugs' => 'nullable|array',
            'branch' => 'nullable|string|max:50',
            'commit_message' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            $this->logger->logDeploy('validation_failed', [
                'site_id' => $request->site_id,
                'errors' => $validator->errors()->toArray()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $site = $this->siteRepository->findWithRelations($request->site_id);
        if (!$site) {
            $this->logger->logDeploy('site_not_found', [
                'site_id' => $request->site_id
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Site not found'
            ], 404);
        }

        // Check if site status is active
        if ($site->status !== \App\Enums\Site::STATUS_ACTIVE) {
            $this->logger->logDeploy('site_not_active', [
                'site_id' => $site->id,
                'site_status' => $site->status
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Site is not active. Deployment not allowed.',
                'site_status' => $site->status
            ], 200);
        }

        $projectName = $site->cloudflare_project_name;
        $directory = $site->cloudflare_project_name;
        $deploymentOptions = $this->collectDeploymentOptions($request);

        try {
            $job = new \App\Jobs\DeployExportsJob(
                $projectName,
                $directory,
                $site->domain,
                $request->page_slugs,
                $deploymentOptions
            );

            dispatch($job);

            $this->logger->logDeploy('queued', [
                'site_id' => $site->id,
                'project_name' => $projectName,
                'directory' => $directory,
                'page_slugs' => $request->page_slugs,
                'options' => $deploymentOptions
            ]);

            $this->logActivity(ActivityAction::DEPLOY_EXPORT_CLOUDFLARE_PAGE, [
                'site_id' => $site->id,
                'project_name' => $projectName
            ], 'Deployed to Cloudflare');

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
            $this->logger->logDeploy('queue_failed', [
                'site_id' => $site->id,
                'project_name' => $projectName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

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
        $level = $result['success'] ? 'info' : 'error';

        $response = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        if (isset($result['deployment_url'])) {
            $response['deployment_url'] = $result['deployment_url'];
        }

        if (isset($result['directory'])) {
            $response['directory'] = $result['directory'];
        }

        if (isset($result['elapsed_time'])) {
            $response['elapsed_time'] = round($result['elapsed_time'], 2) . ' seconds';
        }

        $this->logger->logDeploy(
            $result['success'] ? 'completed' : 'failed',
            array_merge($response, [
                'output' => $result['output'] ?? null
            ]),
            $level
        );

        return response()->json($response, $statusCode);
    }
}