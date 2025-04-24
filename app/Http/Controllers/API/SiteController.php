<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\CloudFlareService;
use App\Services\SiteManagementLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SiteController extends Controller
{
    protected $cloudflareService;
    protected $logger;

    public function __construct(CloudFlareService $cloudflareService, SiteManagementLogger $logger)
    {
        $this->cloudflareService = $cloudflareService;
        $this->logger = $logger;
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
            $this->logger->logSite('create_failed', [
                'errors' => $validator->errors()->toArray()
            ], 'error');

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $projectName = Str::slug($request->name);
            $branch = $request->branch ?? 'main';

            $cloudflareResult = $this->cloudflareService->createPagesProject($projectName, $branch);
            if ($cloudflareResult['success'] === false) {
                $this->logger->logSite('cloudflare_project_creation_failed', [
                    'project_name' => $projectName,
                    'errors' => $cloudflareResult['errors']
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Cloudflare project',
                    'error' => $cloudflareResult['errors']
                ], 500);
            }

            $site = [
                'name' => $request->name,
                'domain' => $request->domain,
                'description' => $request->description,
                'cloudflare_project_name' => $projectName,
                'branch' => $branch,
                'user_id' => auth()->id(),
                'status' => 'active'
            ];

            if ($request->domain) {
                $domainResult = $this->cloudflareService->applyPagesDomain($projectName, $request->domain);
                $site['cloudflare_domain_status'] = isset($domainResult['error']) ? 'failed' : 'active';

                if ($domainResult['success']) {
                    $projectDetails = $this->cloudflareService->getPagesProject($projectName);
                    if ($projectDetails['success'] && isset($projectDetails['result']['subdomain'])) {
                        $dnsResult = $this->cloudflareService->setupDomainDNS(
                            $request->domain,
                            $projectDetails['result']['subdomain']
                        );

                        if ($dnsResult['success'] === false) {
                            $site['cloudflare_domain_status'] = 'dns_failed';
                            $this->logger->logSite('dns_setup_failed', [
                                'domain' => $request->domain,
                                'project_name' => $projectName
                            ], 'warning');
                        }

                        $cacheResult = $this->cloudflareService->setupCacheRules($request->domain);
                        if ($cacheResult['success'] === false) {
                            $this->logger->logSite('cache_rules_setup_failed', [
                                'domain' => $request->domain,
                                'error' => $cacheResult['error'] ?? 'Unknown error'
                            ], 'warning');
                        }
                    }
                }
            }

            $createdSite = Site::create($site);

            $this->logger->logSite('created', [
                'site_id' => $createdSite->id,
                'name' => $createdSite->name,
                'domain' => $createdSite->domain,
                'project_name' => $createdSite->cloudflare_project_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Site created successfully',
                'data' => $createdSite
            ], 201);

        } catch (\Exception $e) {
            $this->logger->logSite('create_failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

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

                // Update cache rules for new domain
                $cacheResult = $this->cloudflareService->setupCacheRules($request->domain);
                if ($cacheResult['success'] === false) {
                    Log::warning('Failed to set cache rules for domain: ' . $request->domain, $cacheResult);
                }
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

            $this->logger->logSite('deleted', [
                'site_id' => $id,
                'name' => $site->name,
                'domain' => $site->domain,
                'project_name' => $site->cloudflare_project_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Site deleted successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->logSite('delete_failed', [
                'site_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete site',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}