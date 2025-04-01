<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CloudflarePagesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CloudflareController extends Controller
{
    protected $cloudflareService;

    public function __construct(CloudflarePagesService $cloudflareService)
    {
        $this->cloudflareService = $cloudflareService;
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
        $result = $this->cloudflareService->createProject($request->name, $branch);

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

        $result = $this->cloudflareService->updateProject($request->name, $request->except(['name']));

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
            'name' => 'required|string',
            'domain' => 'required|string',
        ]);

        $result = $this->cloudflareService->applyDomain($request->name, $request->domain);

        return response()->json($result);
    }

    /**
     * Deploy files to a Cloudflare Pages project
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function deployFiles(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string',
    //         'file' => 'required|file|mimes:zip|max:100000', // Max file size 100MB
    //     ]);

    //     $result = $this->cloudflareService->deployFiles(
    //         $request->name,
    //         $request->file('file')
    //     );

    //     return response()->json($result);
    // }

    /**
     * Deploy files to Cloudflare Pages using Wrangler CLI with the uploaded ZIP file
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deployWithWrangler(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string',
            'file' => 'required|file|mimes:zip|max:100000', // Max file size 100MB
            'branch' => 'nullable|string',
            'commit_hash' => 'nullable|string',
            'commit_message' => 'nullable|string',
        ]);

        $options = [
            'branch' => $request->branch,
            'commit_hash' => $request->commit_hash,
            'commit_message' => $request->commit_message,
        ];

        $result = $this->cloudflareService->deployZipWithWrangler(
            $request->project_name,
            $request->file('file'),
            $options
        );

        return response()->json($result);
    }

    /**
     * Deploy files from a local directory to Cloudflare Pages using Wrangler CLI
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deployDirectoryWithWrangler(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string',
            'directory' => 'required|string',
            'branch' => 'nullable|string',
            'commit_hash' => 'nullable|string',
            'commit_message' => 'nullable|string',
            'wrangler_path' => 'nullable|string', // Allow overriding wrangler path
        ]);

        $options = [
            'branch' => $request->branch,
            'commit_hash' => $request->commit_hash,
            'commit_message' => $request->commit_message,
        ];

        // Allow a custom wrangler path for testing
        if ($request->has('wrangler_path')) {
            $options['wrangler_path'] = $request->wrangler_path;
        }

        // Get the directory path - handle both absolute and relative paths
        $directory = $request->directory;

        // If it's not an absolute path, check if it's relative to public directory
        if (!file_exists($directory) && !str_starts_with($directory, '/') && !preg_match('/^[A-Z]:/i', $directory)) {
            // Try resolving from public path
            $publicDirectory = public_path($directory);
            if (file_exists($publicDirectory)) {
                $directory = $publicDirectory;
            } else {
                // Try resolving from base path
                $baseDirectory = base_path($directory);
                if (file_exists($baseDirectory)) {
                    $directory = $baseDirectory;
                }
            }
        }

        // Validate that the directory exists and is accessible
        if (!file_exists($directory)) {
            return response()->json([
                'success' => false,
                'message' => 'Directory not found or not accessible',
                'path' => $request->directory,
                'resolved_path' => $directory
            ], 400);
        }

        $result = $this->cloudflareService->deployWithWrangler(
            $request->project_name,
            $directory,
            $options
        );

        return response()->json($result);
    }

    /**
     * Test Wrangler setup and connectivity
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testWrangler(Request $request)
    {
        $request->validate([
            'wrangler_path' => 'nullable|string'
        ]);

        $wranglerPath = $request->wrangler_path ?? null;
        $result = $this->cloudflareService->testWranglerSetup($wranglerPath);

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
            'project_name' => 'required|string|max:100',
            'directory' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:50',
            'commit_message' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Collect deployment options
        $deploymentOptions = $this->collectDeploymentOptions($request);

        // Execute deployment through service
        try {
            $result = $this->cloudflareService->deployExportDirectory(
                $request->project_name,
                $request->directory,
                $deploymentOptions
            );

            return $this->formatDeploymentResponse($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deployment failed',
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