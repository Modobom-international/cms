<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\DomainRepository;
use App\Traits\LogsActivity;
use App\Enums\Utility;
use App\Enums\ActivityAction;

class DomainController extends Controller
{
    use LogsActivity;

    protected $domainRepository;
    protected $utility;

    public function __construct(DomainRepository $domainRepository, Utility $utility)
    {
        $this->domainRepository = $domainRepository;
        $this->utility = $utility;
    }

    public function listDomain(Request $request)
    {
        try {
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
}
