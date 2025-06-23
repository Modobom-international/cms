<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Enums\Site as SiteStatus;
use App\Repositories\PageRepository;
use App\Repositories\SiteRepository;
use App\Services\CloudFlareService;
use App\Services\ApplicationLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SiteController extends Controller
{
    protected $cloudflareService;
    protected $logger;
    protected $siteRepository;
    protected $pageRepository;

    public function __construct(CloudFlareService $cloudflareService, ApplicationLogger $logger, SiteRepository $siteRepository, PageRepository $pageRepository)
    {
        $this->cloudflareService = $cloudflareService;
        $this->logger = $logger;
        $this->siteRepository = $siteRepository;
        $this->pageRepository = $pageRepository;
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
            'language' => 'nullable|string|size:2',
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
                'status' => 'active',
                'language' => $request->language ?? 'en',
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
            'language' => 'nullable|string|size:2',
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
                'status' => $request->status ?? $site->status,
                'language' => $request->language ?? $site->language
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
                // Remove custom domain first if it exists
                if ($site->domain) {
                    $this->logger->logSite('removing_custom_domain', [
                        'site_id' => $id,
                        'domain' => $site->domain,
                        'project_name' => $site->cloudflare_project_name
                    ]);

                    $domainRemovalResult = $this->cloudflareService->removePagesDomain(
                        $site->cloudflare_project_name,
                        $site->domain
                    );

                    if ($domainRemovalResult['success'] === false) {
                        $this->logger->logSite('domain_removal_failed', [
                            'site_id' => $id,
                            'domain' => $site->domain,
                            'project_name' => $site->cloudflare_project_name,
                            'error' => $domainRemovalResult['errors'] ?? $domainRemovalResult
                        ], 'warning');
                    } else {
                        $this->logger->logSite('domain_removed', [
                            'site_id' => $id,
                            'domain' => $site->domain,
                            'project_name' => $site->cloudflare_project_name
                        ]);
                    }
                }

                // Now delete the Pages project
                $deleteResult = $this->cloudflareService->deletePagesProject($site->cloudflare_project_name);

                if ($deleteResult['success'] === false) {
                    $this->logger->logSite('project_deletion_failed', [
                        'site_id' => $id,
                        'project_name' => $site->cloudflare_project_name,
                        'error' => $deleteResult['errors'] ?? $deleteResult
                    ], 'warning');
                }
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

    /**
     * Update only the language of the specified site.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLanguage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $site = Site::findOrFail($id);

            $site->update([
                'language' => $request->language
            ]);

            $this->logger->logSite('language_updated', [
                'site_id' => $id,
                'old_language' => $site->getOriginal('language'),
                'new_language' => $request->language
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Site language updated successfully',
                'data' => $site
            ]);

        } catch (\Exception $e) {
            $this->logger->logSite('language_update_failed', [
                'site_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to update site language',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activateSite($siteId)
    {
        $this->logger->logSite('activation_started', [
            'site_id' => $siteId
        ]);

        try {
            $site = $this->siteRepository->findWithRelations($siteId);
            if (!$site) {
                $this->logger->logSite('activation_failed', [
                    'site_id' => $siteId,
                    'error' => 'Site not found'
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            // Step 1: Create Cloudflare Pages Project
            $this->logger->logSite('creating_cloudflare_project', [
                'site_id' => $siteId,
                'project_name' => $site->cloudflare_project_name,
                'branch' => $site->branch ?? 'main'
            ]);

            $cloudflareResult = $this->cloudflareService->createPagesProject(
                $site->cloudflare_project_name,
                $site->branch ?? 'main'
            );

            if ($cloudflareResult['success'] === false) {
                $this->logger->logSite('cloudflare_project_creation_failed', [
                    'site_id' => $siteId,
                    'project_name' => $site->cloudflare_project_name,
                    'error' => $cloudflareResult['errors'] ?? $cloudflareResult
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Cloudflare project',
                    'error' => $cloudflareResult['errors'] ?? 'Unknown error'
                ], 500);
            }

            $this->logger->logSite('cloudflare_project_created', [
                'site_id' => $siteId,
                'project_name' => $site->cloudflare_project_name
            ]);

            // Step 2: Apply Pages Domain
            if ($site->domain) {
                $this->logger->logSite('applying_pages_domain', [
                    'site_id' => $siteId,
                    'domain' => $site->domain,
                    'project_name' => $site->cloudflare_project_name
                ]);

                $domainResult = $this->cloudflareService->applyPagesDomain(
                    $site->cloudflare_project_name,
                    $site->domain
                );

                if ($domainResult['success'] === false) {
                    $this->logger->logSite('domain_application_failed', [
                        'site_id' => $siteId,
                        'domain' => $site->domain,
                        'error' => $domainResult['error'] ?? $domainResult
                    ], 'warning');
                } else {
                    $this->logger->logSite('domain_applied_successfully', [
                        'site_id' => $siteId,
                        'domain' => $site->domain
                    ]);

                    // Update site domain status
                    $site->update(['cloudflare_domain_status' => SiteStatus::STATUS_ACTIVE]);
                }

                // Step 3: Setup DNS and Cache Rules
                $projectDetails = $this->cloudflareService->getPagesProject($site->cloudflare_project_name);
                if ($projectDetails['success'] && isset($projectDetails['result']['subdomain'])) {
                    $this->logger->logSite('setting_up_dns', [
                        'site_id' => $siteId,
                        'domain' => $site->domain,
                        'subdomain' => $projectDetails['result']['subdomain']
                    ]);

                    $dnsResult = $this->cloudflareService->setupDomainDNS(
                        $site->domain,
                        $projectDetails['result']['subdomain']
                    );

                    if ($dnsResult['success'] === false) {
                        $this->logger->logSite('dns_setup_failed', [
                            'site_id' => $siteId,
                            'domain' => $site->domain,
                            'error' => $dnsResult['error'] ?? 'Unknown error'
                        ], 'warning');
                    } else {
                        $this->logger->logSite('dns_setup_successful', [
                            'site_id' => $siteId,
                            'domain' => $site->domain
                        ]);
                    }
                }

                // Setup Cache Rules
                $this->logger->logSite('setting_up_cache_rules', [
                    'site_id' => $siteId,
                    'domain' => $site->domain
                ]);

                $cacheResult = $this->cloudflareService->setupCacheRules($site->domain);
                if ($cacheResult['success'] === false) {
                    $this->logger->logSite('cache_rules_setup_failed', [
                        'site_id' => $siteId,
                        'domain' => $site->domain,
                        'error' => $cacheResult['error'] ?? 'Unknown error'
                    ], 'warning');
                } else {
                    $this->logger->logSite('cache_rules_setup_successful', [
                        'site_id' => $siteId,
                        'domain' => $site->domain
                    ]);
                }
            }

            // Step 4: Deploy existing content
            $pages = $this->pageRepository->getBySiteId($siteId);

            $this->logger->logSite('deploying_content', [
                'site_id' => $siteId,
                'project_name' => $site->cloudflare_project_name,
                'pages_count' => $pages->count()
            ]);

            dispatch(new \App\Jobs\DeployExportsJob(
                $site->cloudflare_project_name,
                $site->cloudflare_project_name,
                $site->domain,
                $pages->pluck('slug')->toArray()
            ));

            // Step 5: Update site status to active
            $site->update(['status' => SiteStatus::STATUS_ACTIVE]);

            $this->logger->logSite('activation_completed', [
                'site_id' => $siteId,
                'project_name' => $site->cloudflare_project_name,
                'domain' => $site->domain,
                'status' => SiteStatus::STATUS_ACTIVE
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Site activated and deployed successfully',
                'data' => $site->fresh()
            ]);

        } catch (\Exception $e) {
            $this->logger->logSite('activation_error', [
                'site_id' => $siteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateSite($siteId)
    {
        $this->logger->logSite('deactivation_started', [
            'site_id' => $siteId
        ]);

        try {
            $site = $this->siteRepository->findWithRelations($siteId);
            if (!$site) {
                $this->logger->logSite('deactivation_failed', [
                    'site_id' => $siteId,
                    'error' => 'Site not found'
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            // Step 1: Remove custom domain and delete Cloudflare Pages Project
            if ($site->cloudflare_project_name) {
                // Remove custom domain first if it exists
                if ($site->domain) {
                    $this->logger->logSite('removing_custom_domain', [
                        'site_id' => $siteId,
                        'domain' => $site->domain,
                        'project_name' => $site->cloudflare_project_name
                    ]);

                    $domainRemovalResult = $this->cloudflareService->removePagesDomain(
                        $site->cloudflare_project_name,
                        $site->domain
                    );

                    if ($domainRemovalResult['success'] === false) {
                        $this->logger->logSite('domain_removal_failed', [
                            'site_id' => $siteId,
                            'domain' => $site->domain,
                            'project_name' => $site->cloudflare_project_name,
                            'error' => $domainRemovalResult['errors'] ?? $domainRemovalResult
                        ], 'warning');
                    } else {
                        $this->logger->logSite('domain_removed', [
                            'site_id' => $siteId,
                            'domain' => $site->domain,
                            'project_name' => $site->cloudflare_project_name
                        ]);
                    }
                }

                // Now delete the Cloudflare Pages Project
                $this->logger->logSite('deleting_cloudflare_project', [
                    'site_id' => $siteId,
                    'project_name' => $site->cloudflare_project_name
                ]);

                $deleteResult = $this->cloudflareService->deletePagesProject($site->cloudflare_project_name);

                if ($deleteResult['success'] === false) {
                    $this->logger->logSite('cloudflare_project_deletion_failed', [
                        'site_id' => $siteId,
                        'project_name' => $site->cloudflare_project_name,
                        'error' => $deleteResult['errors'] ?? $deleteResult
                    ], 'warning');
                } else {
                    $this->logger->logSite('cloudflare_project_deleted', [
                        'site_id' => $siteId,
                        'project_name' => $site->cloudflare_project_name
                    ]);
                }
            }

            // Step 2: Update site status to inactive
            $site->update([
                'status' => SiteStatus::STATUS_INACTIVE,
                'cloudflare_domain_status' => SiteStatus::STATUS_INACTIVE
            ]);

            $this->logger->logSite('deactivation_completed', [
                'site_id' => $siteId,
                'project_name' => $site->cloudflare_project_name,
                'domain' => $site->domain,
                'status' => SiteStatus::STATUS_INACTIVE
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Site deactivated successfully',
                'data' => $site->fresh()
            ]);

        } catch (\Exception $e) {
            $this->logger->logSite('deactivation_error', [
                'site_id' => $siteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate site',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}