<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\CloudFlareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    protected $cloudflareService;

    public function __construct(CloudFlareService $cloudflareService)
    {
        $this->cloudflareService = $cloudflareService;
    }

    /**
     * Display a listing of the sites.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $sites = Site::with(['user'])->latest()->get();
        return response()->json([
            'success' => true,
            'data' => $sites
        ]);
    }

    /**
     * Store a newly created site and create corresponding Cloudflare project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|unique:sites,domain',
            'description' => 'nullable|string',
            'branch' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create Cloudflare Pages project
            $projectName = Str::slug($request->name);
            $branch = $request->branch ?? 'main';

            $cloudflareResult = $this->cloudflareService->createPagesProject($projectName, $branch);

            if (isset($cloudflareResult['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Cloudflare project',
                    'error' => $cloudflareResult['error']
                ], 500);
            }

            // Create site record in database
            $site = Site::create([
                'name' => $request->name,
                'domain' => $request->domain,
                'description' => $request->description,
                'cloudflare_project_name' => $projectName,
                'branch' => $branch,
                'user_id' => auth()->id(),
                'status' => 'active'
            ]);

            // Apply custom domain if provided
            if ($request->domain) {
                // Apply domain to Pages project
                $domainResult = $this->cloudflareService->applyPagesDomain($projectName, $request->domain);
                $site->cloudflare_domain_status = isset($domainResult['error']) ? 'failed' : 'active';

                if (!isset($domainResult['error'])) {
                    // Get project details to get the Pages subdomain
                    $projectDetails = $this->cloudflareService->getPagesProject($projectName);
                    if (!isset($projectDetails['error']) && isset($projectDetails['result']['subdomain'])) {
                        // Set up DNS CNAME record
                        $dnsResult = $this->cloudflareService->setupDomainDNS(
                            $request->domain,
                            $projectDetails['result']['subdomain']
                        );
                        if (isset($dnsResult['error'])) {
                            $site->cloudflare_domain_status = 'dns_failed';
                        }
                    }
                }

                // $site->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Site created successfully',
                'data' => $site
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified site.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $site = Site::with(['user'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $site
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Site not found'
            ], 404);
        }
    }

    /**
     * Update the specified site.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|unique:sites,domain,' . $id,
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $site = Site::findOrFail($id);

            // Update Cloudflare project if name changed
            if ($site->name !== $request->name) {
                $cloudflareResult = $this->cloudflareService->updatePagesProject(
                    $site->cloudflare_project_name,
                    ['name' => Str::slug($request->name)]
                );

                if (isset($cloudflareResult['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to update Cloudflare project',
                        'error' => $cloudflareResult['error']
                    ], 500);
                }

                $site->cloudflare_project_name = Str::slug($request->name);
            }

            // Update domain if changed
            if ($site->domain !== $request->domain) {
                $domainResult = $this->cloudflareService->applyPagesDomain(
                    $site->cloudflare_project_name,
                    $request->domain
                );
                $site->cloudflare_domain_status = isset($domainResult['error']) ? 'failed' : 'active';
            }

            $site->update([
                'name' => $request->name,
                'domain' => $request->domain,
                'description' => $request->description,
                'status' => $request->status ?? $site->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Site updated successfully',
                'data' => $site
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified site.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $site = Site::findOrFail($id);

            // Delete from Cloudflare if project exists
            if ($site->cloudflare_project_name) {
                $this->cloudflareService->deletePagesProject($site->cloudflare_project_name);
            }

            $site->delete();

            return response()->json([
                'success' => true,
                'message' => 'Site deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete site',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}