<?php

namespace App\Http\Controllers\API;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\DomainRepository;
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
    protected $utility;

    public function __construct(DomainRepository $domainRepository, SiteRepository $siteRepository, Utility $utility)
    {
        $this->domainRepository = $domainRepository;
        $this->siteRepository = $siteRepository;
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
            $data = $this->utility->paginate($domains, $pageSize, $page);
            // Add DNS records only for the current page domains if requested
            if ($includeDns && isset($data['data'])) {
                $data['data'] = collect($data['data'])->map(function ($domain) {
                    try {
                        $domain->dns_records = $this->domainRepository->getDnsRecords($domain->domain);
                    } catch (Exception $e) {
                        $domain->dns_records = [];
                        $domain->dns_error = 'Failed to fetch DNS records: ' . $e->getMessage();
                    }
                    return $domain;
                })->toArray();
            }



            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input, 'include_dns' => $includeDns], 'Xem danh sách domain');

            return response()->json([
                'success' => true,
                'data' => $data,
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

    public function getListAvailableDomain(Request $request)
    {
        try {
            $input = $request->all();
            $search = $request->get('search');
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;

            $allDomains = $this->domainRepository->getDomainBySearch($search);
            $usedDomains = $this->siteRepository->getAllSiteDomains();
            $availableDomains = $allDomains->filter(function ($domain) use ($usedDomains) {
                return !in_array($domain->domain, $usedDomains);
            });

            $data = $this->utility->paginate($availableDomains, $pageSize, $page);

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách domain khả dụng');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách domain khả dụng thành công',
                'type' => 'list_available_domain_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách domain khả dụng không thành công',
                'type' => 'list_available_domain_fail',
                'error' => $e->getMessage()
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

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách domain');

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

            // Get DNS records for the domain
            $dnsRecords = $this->domainRepository->getDnsRecords($domain);

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['domain' => $domain], 'Xem DNS records của domain');

            return response()->json([
                'success' => true,
                'data' => $dnsRecords,
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
