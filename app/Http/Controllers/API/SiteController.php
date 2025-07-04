<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Enums\Site as SiteStatus;
use App\Repositories\PageRepository;
use App\Repositories\SiteRepository;
use App\Services\CloudFlareService;
use App\Services\ApplicationLogger;
use App\Traits\LogsActivity;
use App\Enums\ActivityAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SiteController extends Controller
{
    use LogsActivity;

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
            'platform' => 'nullable|in:' . implode(',', SiteStatus::getPlatforms()),
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
                'platform' => $request->platform ?? 'google',
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
                                'error' => $cacheResult['error'] ?? 'Unknown error',
                                'cloudflare_errors' => $cacheResult['cloudflare_errors'] ?? null,
                                'cloudflare_messages' => $cacheResult['cloudflare_messages'] ?? null,
                                'details' => $cacheResult['details'] ?? null,
                                'full_response' => $cacheResult
                            ], 'warning');
                        } else {
                            $this->logger->logSite('cache_rules_setup_successful', [
                                'domain' => $request->domain,
                                'response_data' => $cacheResult['data'] ?? null
                            ]);
                        }
                    }
                }
            }

            $createdSite = Site::create($site);

            $this->logActivity(ActivityAction::CREATE_SITE, [
                'site_id' => $createdSite->id,
                'name' => $createdSite->name,
                'domain' => $createdSite->domain,
                'project_name' => $createdSite->cloudflare_project_name,
                'platform' => $createdSite->platform
            ], 'Site created');

            $this->logger->logSite('created', [
                'site_id' => $createdSite->id,
                'name' => $createdSite->name,
                'domain' => $createdSite->domain,
                'project_name' => $createdSite->cloudflare_project_name,
                'platform' => $createdSite->platform
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
            'platform' => 'nullable|in:' . implode(',', SiteStatus::getPlatforms()),
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
                    $this->logger->logSite('cache_rules_setup_failed', [
                        'domain' => $request->domain,
                        'error' => $cacheResult['error'] ?? 'Unknown error',
                        'cloudflare_errors' => $cacheResult['cloudflare_errors'] ?? null,
                        'cloudflare_messages' => $cacheResult['cloudflare_messages'] ?? null,
                        'details' => $cacheResult['details'] ?? null,
                        'full_response' => $cacheResult
                    ], 'warning');
                } else {
                    $this->logger->logSite('cache_rules_setup_successful', [
                        'domain' => $request->domain,
                        'response_data' => $cacheResult['data'] ?? null
                    ]);
                }
            }

            $site->update([
                'name' => $request->name,
                'domain' => $request->domain,
                'description' => $request->description,
                'status' => $request->status ?? $site->status,
                'language' => $request->language ?? $site->language,
                'platform' => $request->platform ?? $site->platform
            ]);

            $this->logActivity(ActivityAction::UPDATE_SITE, [
                'site_id' => $id,
                'site_name' => $site->name,
                'domain' => $site->domain,
                'changes' => $request->all()
            ], 'Site updated');

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
                // Remove cache rules and custom domain first if it exists
                if ($site->domain) {
                    // Remove cache rules first
                    $this->logger->logSite('removing_cache_rules', [
                        'site_id' => $id,
                        'domain' => $site->domain,
                        'project_name' => $site->cloudflare_project_name
                    ]);

                    $cacheRemovalResult = $this->cloudflareService->removeCacheRules($site->domain);
                    if ($cacheRemovalResult['success'] === false) {
                        $this->logger->logSite('cache_rules_removal_failed', [
                            'site_id' => $id,
                            'domain' => $site->domain,
                            'project_name' => $site->cloudflare_project_name,
                            'error' => $cacheRemovalResult['error'] ?? 'Unknown error',
                            'details' => $cacheRemovalResult['details'] ?? null,
                            'full_response' => $cacheRemovalResult
                        ], 'warning');
                    } else {
                        $this->logger->logSite('cache_rules_removed', [
                            'site_id' => $id,
                            'domain' => $site->domain,
                            'project_name' => $site->cloudflare_project_name,
                            'deleted_count' => $cacheRemovalResult['deleted_count'] ?? 0,
                            'message' => $cacheRemovalResult['message'] ?? 'Cache rules removed'
                        ]);
                    }

                    // Remove custom domain
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

            $this->logActivity(ActivityAction::DELETE_SITE, [
                'site_id' => $id,
                'name' => $site->name,
                'domain' => $site->domain,
                'project_name' => $site->cloudflare_project_name,
                'platform' => $site->platform
            ], 'Site deleted');

            $this->logger->logSite('deleted', [
                'site_id' => $id,
                'name' => $site->name,
                'domain' => $site->domain,
                'project_name' => $site->cloudflare_project_name,
                'platform' => $site->platform
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

            $this->logActivity(ActivityAction::UPDATE_SITE_LANGUAGE, [
                'site_id' => $id,
                'old_language' => $site->getOriginal('language'),
                'new_language' => $request->language,
                'site_name' => $site->name
            ], 'Site language updated');

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

    /**
     * Update only the platform of the specified site.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePlatform(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:' . implode(',', SiteStatus::getPlatforms()),
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
                'platform' => $request->platform
            ]);

            $this->logActivity(ActivityAction::UPDATE_SITE_PLATFORM, [
                'site_id' => $id,
                'old_platform' => $site->getOriginal('platform'),
                'new_platform' => $request->platform,
                'site_name' => $site->name
            ], 'Site platform updated');

            $this->logger->logSite('platform_updated', [
                'site_id' => $id,
                'old_platform' => $site->getOriginal('platform'),
                'new_platform' => $request->platform
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Site platform updated successfully',
                'data' => $site
            ]);

        } catch (\Exception $e) {
            $this->logger->logSite('platform_update_failed', [
                'site_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to update site platform',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activateSite($id)
    {
        $this->logger->logSite('activation_started', [
            'site_id' => $id
        ]);

        try {
            $site = $this->siteRepository->findWithRelations($id);
            if (!$site) {
                $this->logger->logSite('activation_failed', [
                    'site_id' => $id,
                    'error' => 'Site not found'
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            if ($site->status === SiteStatus::STATUS_ACTIVE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site is already active'
                ], 400);
            }

            // Step 1: Create Cloudflare Pages Project
            $this->logger->logSite('creating_cloudflare_project', [
                'site_id' => $id,
                'project_name' => $site->cloudflare_project_name,
                'branch' => $site->branch ?? 'main'
            ]);

            $cloudflareResult = $this->cloudflareService->createPagesProject(
                $site->cloudflare_project_name,
                $site->branch ?? 'main'
            );

            if ($cloudflareResult['success'] === false) {
                $this->logger->logSite('cloudflare_project_creation_failed', [
                    'site_id' => $id,
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
                'site_id' => $id,
                'project_name' => $site->cloudflare_project_name
            ]);

            // Step 2: Apply Pages Domain
            if ($site->domain) {
                $this->logger->logSite('applying_pages_domain', [
                    'site_id' => $id,
                    'domain' => $site->domain,
                    'project_name' => $site->cloudflare_project_name
                ]);

                $domainResult = $this->cloudflareService->applyPagesDomain(
                    $site->cloudflare_project_name,
                    $site->domain
                );

                if ($domainResult['success'] === false) {
                    $this->logger->logSite('domain_application_failed', [
                        'site_id' => $id,
                        'domain' => $site->domain,
                        'error' => $domainResult['error'] ?? $domainResult
                    ], 'warning');
                } else {
                    $this->logger->logSite('domain_applied_successfully', [
                        'site_id' => $id,
                        'domain' => $site->domain
                    ]);

                    // Update site domain status
                    $site->update(['cloudflare_domain_status' => SiteStatus::STATUS_ACTIVE]);
                }

                // Step 3: Setup DNS and Cache Rules
                $projectDetails = $this->cloudflareService->getPagesProject($site->cloudflare_project_name);
                if ($projectDetails['success'] && isset($projectDetails['result']['subdomain'])) {
                    $this->logger->logSite('setting_up_dns', [
                        'site_id' => $id,
                        'domain' => $site->domain,
                        'subdomain' => $projectDetails['result']['subdomain']
                    ]);

                    $dnsResult = $this->cloudflareService->setupDomainDNS(
                        $site->domain,
                        $projectDetails['result']['subdomain']
                    );

                    if ($dnsResult['success'] === false) {
                        $this->logger->logSite('dns_setup_failed', [
                            'site_id' => $id,
                            'domain' => $site->domain,
                            'error' => $dnsResult['error'] ?? 'Unknown error'
                        ], 'warning');
                    } else {
                        $this->logger->logSite('dns_setup_successful', [
                            'site_id' => $id,
                            'domain' => $site->domain
                        ]);
                    }
                }

                // Setup Cache Rules
                $this->logger->logSite('setting_up_cache_rules', [
                    'site_id' => $id,
                    'domain' => $site->domain
                ]);

                $cacheResult = $this->cloudflareService->setupCacheRules($site->domain);
                if ($cacheResult['success'] === false) {
                    $this->logger->logSite('cache_rules_setup_failed', [
                        'site_id' => $id,
                        'domain' => $site->domain,
                        'error' => $cacheResult['error'] ?? 'Unknown error',
                        'cloudflare_errors' => $cacheResult['cloudflare_errors'] ?? null,
                        'cloudflare_messages' => $cacheResult['cloudflare_messages'] ?? null,
                        'details' => $cacheResult['details'] ?? null,
                        'full_response' => $cacheResult
                    ], 'warning');
                } else {
                    $this->logger->logSite('cache_rules_setup_successful', [
                        'site_id' => $id,
                        'domain' => $site->domain,
                        'response_data' => $cacheResult['data'] ?? null
                    ]);
                }
            }

            // Step 4: Deploy existing content
            $pages = $this->pageRepository->getBySiteId($id);

            $this->logger->logSite('deploying_content', [
                'site_id' => $id,
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

            $this->logActivity(ActivityAction::ACTIVATE_SITE, [
                'site_id' => $id,
                'project_name' => $site->cloudflare_project_name,
                'domain' => $site->domain,
                'status' => SiteStatus::STATUS_ACTIVE,
                'platform' => $site->platform,
                'site_name' => $site->name
            ], 'Site activated');

            $this->logger->logSite('activation_completed', [
                'site_id' => $id,
                'project_name' => $site->cloudflare_project_name,
                'domain' => $site->domain,
                'status' => SiteStatus::STATUS_ACTIVE,
                'platform' => $site->platform
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Site activated and deployed successfully',
                'data' => $site->fresh()
            ]);

        } catch (\Exception $e) {
            $this->logger->logSite('activation_error', [
                'site_id' => $id,
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

    public function deactivateSite($id)
    {
        $this->logger->logSite('deactivation_started', [
            'site_id' => $id
        ]);

        try {
            $site = $this->siteRepository->findWithRelations($id);
            if (!$site) {
                $this->logger->logSite('deactivation_failed', [
                    'site_id' => $id,
                    'error' => 'Site not found'
                ], 'error');

                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            if ($site->status === SiteStatus::STATUS_INACTIVE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site is already inactive'
                ], 400);
            }

            // Step 1: Remove cache rules, custom domain and delete Cloudflare Pages Project
            if ($site->cloudflare_project_name) {
                // Remove cache rules and custom domain first if it exists
                if ($site->domain) {
                    // Remove cache rules first
                    $this->logger->logSite('removing_cache_rules', [
                        'site_id' => $id,
                        'domain' => $site->domain,
                        'project_name' => $site->cloudflare_project_name
                    ]);

                    $cacheRemovalResult = $this->cloudflareService->removeCacheRules($site->domain);
                    if ($cacheRemovalResult['success'] === false) {
                        $this->logger->logSite('cache_rules_removal_failed', [
                            'site_id' => $id,
                            'domain' => $site->domain,
                            'project_name' => $site->cloudflare_project_name,
                            'error' => $cacheRemovalResult['error'] ?? 'Unknown error',
                            'details' => $cacheRemovalResult['details'] ?? null,
                            'full_response' => $cacheRemovalResult
                        ], 'warning');
                    } else {
                        $this->logger->logSite('cache_rules_removed', [
                            'site_id' => $id,
                            'domain' => $site->domain,
                            'project_name' => $site->cloudflare_project_name,
                            'deleted_count' => $cacheRemovalResult['deleted_count'] ?? 0,
                            'message' => $cacheRemovalResult['message'] ?? 'Cache rules removed'
                        ]);
                    }

                    // Remove custom domain
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

                // Now delete the Cloudflare Pages Project
                $this->logger->logSite('deleting_cloudflare_project', [
                    'site_id' => $id,
                    'project_name' => $site->cloudflare_project_name
                ]);

                $deleteResult = $this->cloudflareService->deletePagesProject($site->cloudflare_project_name);

                if ($deleteResult['success'] === false) {
                    $this->logger->logSite('cloudflare_project_deletion_failed', [
                        'site_id' => $id,
                        'project_name' => $site->cloudflare_project_name,
                        'error' => $deleteResult['errors'] ?? $deleteResult
                    ], 'warning');
                } else {
                    $this->logger->logSite('cloudflare_project_deleted', [
                        'site_id' => $id,
                        'project_name' => $site->cloudflare_project_name
                    ]);
                }
            }

            // Step 2: Update site status to inactive
            $site->update([
                'status' => SiteStatus::STATUS_INACTIVE,
                'cloudflare_domain_status' => SiteStatus::STATUS_INACTIVE
            ]);

            $this->logActivity(ActivityAction::DEACTIVATE_SITE, [
                'site_id' => $id,
                'site_name' => $site->name
            ], 'Deactivated site');

            $this->logger->logSite('deactivation_completed', [
                'site_id' => $id,
                'project_name' => $site->cloudflare_project_name,
                'domain' => $site->domain,
                'status' => SiteStatus::STATUS_INACTIVE,
                'platform' => $site->platform
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Site deactivated successfully',
                'data' => $site->fresh()
            ]);

        } catch (\Exception $e) {
            $this->logger->logSite('deactivation_error', [
                'site_id' => $id,
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