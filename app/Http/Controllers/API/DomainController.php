<?php

namespace App\Http\Controllers\API;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\DomainRepository;
use App\Repositories\DnsRecordRepository;
use App\Traits\LogsActivity;
use App\Enums\Utility;
use App\Enums\ActivityAction;
use App\Http\Requests\DomainRequest;
use App\Jobs\RefreshDomainInPlatform;
use App\Repositories\SiteRepository;

class DomainController extends Controller
{
    use LogsActivity;

    protected $domainRepository;
    protected $siteRepository;
    protected $dnsRecordRepository;
    protected $utility;

    public function __construct(DomainRepository $domainRepository, SiteRepository $siteRepository, DnsRecordRepository $dnsRecordRepository, Utility $utility)
    {
        $this->domainRepository = $domainRepository;
        $this->siteRepository = $siteRepository;
        $this->dnsRecordRepository = $dnsRecordRepository;
        $this->utility = $utility;
    }

    public function listDomain(Request $request)
    {
        try {
            $input = $request->all();
            $search = $request->get('search');
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;
            $includeDns = $request->get('include_dns') == 'true';

            // Limit DNS requests to prevent timeouts
            // if ($includeDns && $pageSize > 5) {
            //     $pageSize = 10; // Maximum 5 domains when DNS is requested
            // }

            // Get filter parameters
            $filters = [
                'status' => $request->get('status'),
                'is_locked' => $request->get('is_locked'),
                'renewable' => $request->get('renewable'),
                'registrar' => $request->get('registrar'),
                'has_sites' => $request->get('has_sites') == 'true',
                'time_expired' => $request->get('time_expired'),
                'renew_deadline' => $request->get('renew_deadline'),
                'registrar_created_at' => $request->get('registrar_created_at')
            ];

            $domains = $this->domainRepository->getDomainBySearch($search, $filters);

            // Paginate first for better performance
            $paginator = $this->utility->paginate($domains, $pageSize, $page);

            // Convert paginator to array and add DNS records if requested
            $data = $paginator->toArray();

            // Add DNS records only for the current page domains if requested
            if ($includeDns && isset($data['data']) && is_array($data['data'])) {
                $startTime = time();
                $maxTotalTime = 25; // Maximum 25 seconds for all DNS lookups

                $data['data'] = collect($data['data'])->map(function ($domain) use (&$startTime, $maxTotalTime) {
                    // Check if we've exceeded our total time limit
                    if ((time() - $startTime) >= $maxTotalTime) {
                        $domain = (object) $domain;
                        $domain->dns_records = [];
                        $domain->dns_error = 'DNS lookup skipped due to timeout protection';
                        $domain->dns_source = 'timeout_skip';
                        return $domain;
                    }

                    // Convert array back to object for easier property access
                    $domain = (object) $domain;

                    try {
                        // First try to get DNS records from database (faster and more reliable)
                        $storedDnsRecords = $this->dnsRecordRepository->getByDomain($domain->domain);

                        if ($storedDnsRecords->isNotEmpty()) {
                            // Format stored DNS records to match the expected structure
                            $domain->dns_records = $storedDnsRecords->map(function ($record) {
                                return [
                                    'type' => $record->type,
                                    'name' => $record->name,
                                    'content' => $record->content,
                                    'ttl' => $record->ttl,
                                    'proxied' => $record->proxied ?? false,
                                    'comment' => $record->comment ?? '',
                                ];
                            })->toArray();
                            $domain->dns_source = 'database';
                        } else {
                            // Fallback to real-time DNS lookup if no stored records found
                            // Only for essential records to prevent timeout
                            $domain->dns_records = $this->domainRepository->getDnsRecords($domain->domain);
                            $domain->dns_source = 'realtime';
                            $domain->dns_warning = 'DNS records not synced. Consider running DNS sync job for better performance. Only A and CNAME records shown.';
                        }
                    } catch (Exception $e) {
                        $domain->dns_records = [];
                        $domain->dns_error = 'Failed to fetch DNS records: ' . $e->getMessage();
                        $domain->dns_source = 'error';
                    }
                    return $domain;
                })->toArray();
            }

            // $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input, 'include_dns' => $includeDns], 'Xem danh sách domain');

            $responseData = [
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách domain thành công',
                'type' => 'list_domain_success',
                'debug_info' => [
                    'include_dns_requested' => $includeDns,
                    'include_dns_param' => $request->get('include_dns'),
                    'has_data' => isset($data['data']),
                    'data_count' => isset($data['data']) ? count($data['data']) : 0,
                    'data_structure' => array_keys($data),
                    'sample_domain_keys' => isset($data['data'][0]) ? array_keys((array) $data['data'][0]) : [],
                ],
            ];

            // Add warning if page size was reduced due to DNS request
            if ($includeDns && $request->get('pageSize') > 5) {
                $responseData['warning'] = 'Page size was limited to 5 domains when DNS records are requested to prevent timeouts.';
                $responseData['debug_info']['page_size_limited'] = true;
                $responseData['debug_info']['original_page_size'] = $request->get('pageSize');
                $responseData['debug_info']['limited_page_size'] = $pageSize;
            }

            return response()->json($responseData, 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách domain không thành công',
                'type' => 'list_domain_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getListAvailableDomain(Request $request)
    {
        try {
            $input = $request->all();
            $search = $request->get('search');
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;

            // Get filter parameters similar to listDomain method
            $filters = [
                'status' => $request->get('status'),
                'is_locked' => $request->get('is_locked'),
                'renewable' => $request->get('renewable'),
                'registrar' => $request->get('registrar'),
                'time_expired' => $request->get('time_expired'),
                'renew_deadline' => $request->get('renew_deadline'),
                'registrar_created_at' => $request->get('registrar_created_at')
            ];

            // Apply has_sites filter to only get domains without sites
            $filters['has_sites'] = false;

            $allDomains = $this->domainRepository->getDomainBySearch($search, $filters);
            $usedDomains = $this->siteRepository->getAllSiteDomains();

            // Additional filtering in PHP for any edge cases not caught by the database query
            $availableDomains = $allDomains->filter(function ($domain) use ($usedDomains) {
                return !in_array($domain->domain, $usedDomains);
            });

            $data = $this->utility->paginate($availableDomains, $pageSize, $page);

            // $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách domain khả dụng');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách domain khả dụng thành công',
                'type' => 'list_available_domain_success',
                'debug_info' => [
                    'total_domains_found' => $allDomains->count(),
                    'used_domains_count' => count($usedDomains),
                    'used_domains' => $usedDomains,
                    'available_domains_count' => $availableDomains->count(),
                    'search_term' => $search,
                    'page_size' => $pageSize,
                    'current_page' => $page,
                    'filters_applied' => array_filter($filters), // Only show non-null filters
                    'sample_all_domains' => $allDomains->take(3)->pluck('domain')->toArray(),
                    'sample_available_domains' => $availableDomains->take(3)->pluck('domain')->toArray(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách domain khả dụng không thành công',
                'type' => 'list_available_domain_fail',
                'error' => $e->getMessage(),
                'debug_info' => [
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            ], 500);
        }
    }

    public function refreshDomain()
    {
        try {
            RefreshDomainInPlatform::dispatch()->onQueue('refresh_domain');
            $this->logActivity(ActivityAction::REFRESH_LIST_DOMAIN, [], 'Làm mới danh sách domain');

            return response()->json([
                'success' => true,
                'message' => 'Làm mới danh sách domain thành công',
                'type' => 'refresh_domain_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Làm mới danh sách domain không thành công',
                'type' => 'refresh_domain_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listUrlPath(Request $request)
    {
        try {
            $domain = $request->get('domain');
            $getSlug = $this->siteRepository->getSlugByDomain($domain);

            $listPath = [];
            $this->logActivity(ActivityAction::GET_LIST_PATH_BY_DOMAIN, [], 'Lấy danh sách url path theo domain');

            if (isset($getSlug['pages'])) {
                foreach ($getSlug['pages'] as $record) {
                    $listPath[] = $record;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $listPath,
                'message' => 'Lấy danh sách url path thành công',
                'type' => 'list_url_path_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách url path không thành công',
                'type' => 'list_url_path_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(DomainRequest $request)
    {
        try {
            $input = $request->get('domain');

            $this->domainRepository->create($input);

            $this->logActivity(ActivityAction::SHOW_RECORD, ['data' => $input], 'Thêm record vào bảng domains');

            return response()->json([
                'success' => true,
                'data' => $input,
                'message' => 'Lưu tên miền thành công',
                'type' => 'store_domain_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu tên miền không thành công',
                'type' => 'store_domain_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getListDomainForTracking(Request $request)
    {
        try {
            $input = $request->all();
            $search = $request->get('search');
            $user_id = $request->get('user_id');
            $domains = $this->siteRepository->getDomainBySearchAndUserID($search, $user_id);

            // $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách domain');

            return response()->json([
                'success' => true,
                'data' => $domains,
                'message' => 'Lấy danh sách domain thành công',
                'type' => 'list_domain_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách domain không thành công',
                'type' => 'list_domain_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showDnsRecords(Request $request)
    {
        try {
            $domain = $request->get('domain');

            if (!$domain) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain parameter is required',
                    'type' => 'show_dns_records_fail',
                ], 400);
            }

            // First try to get DNS records from database (faster and more reliable)
            $storedDnsRecords = $this->dnsRecordRepository->getByDomain($domain);

            if ($storedDnsRecords->isNotEmpty()) {
                // Format stored DNS records
                $dnsRecords = $storedDnsRecords->map(function ($record) {
                    return [
                        'type' => $record->type,
                        'name' => $record->name,
                        'content' => $record->content,
                        'ttl' => $record->ttl,
                        'proxied' => $record->proxied ?? false,
                        'comment' => $record->comment ?? '',
                        'cloudflare_id' => $record->cloudflare_id ?? '',
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                })->toArray();

                $responseData = [
                    'dns_records' => $dnsRecords,
                    'source' => 'database',
                    'total_records' => count($dnsRecords),
                    'last_synced' => $storedDnsRecords->max('updated_at'),
                ];
            } else {
                // Fallback to real-time DNS lookup if no stored records found
                $startTime = time();
                try {
                    $dnsRecords = $this->domainRepository->getDnsRecords($domain);
                    $responseData = [
                        'dns_records' => $dnsRecords,
                        'source' => 'realtime',
                        'total_records' => count($dnsRecords),
                        'warning' => 'DNS records not synced. Consider running DNS sync job for better performance. Only A and CNAME records shown for timeout prevention.',
                        'execution_time' => time() - $startTime,
                    ];
                } catch (Exception $e) {
                    $responseData = [
                        'dns_records' => [],
                        'source' => 'error',
                        'total_records' => 0,
                        'error' => 'Failed to fetch real-time DNS records: ' . $e->getMessage(),
                        'execution_time' => time() - $startTime,
                    ];
                }
            }

            // $this->logActivity(ActivityAction::ACCESS_VIEW, ['domain' => $domain], 'Xem DNS records của domain');

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Lấy DNS records thành công',
                'type' => 'show_dns_records_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy DNS records không thành công',
                'type' => 'show_dns_records_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
