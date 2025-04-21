<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\DomainRepository;
use App\Traits\LogsActivity;
use App\Enums\Utility;
use App\Enums\ActivityAction;
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
            $domains = $this->domainRepository->getDomainBySearch($search);
            $data = $this->utility->paginate($domains, $pageSize, $page);

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách domain');

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
            $urlPaths = $this->siteRepository->getSlugByDomain($domain);
            $listPath = [];
            $this->logActivity(ActivityAction::GET_LIST_PATH_BY_DOMAIN, [], 'Lấy danh sách url path theo domain');

            foreach ($urlPaths->pages as $record) {
                $listPath[] = $record;
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
}
