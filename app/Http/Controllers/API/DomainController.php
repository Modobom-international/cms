<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\DomainRepository;
use App\Traits\LogsActivity;
use App\Enums\Utility;

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

    public function listDomain()
    {
        try {
            $domains = $this->domainRepository->getAllDomain();
            $response = $this->utility->paginate($domains);

            return response()->json([
                'success' => true,
                'data' => $response,
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

    public function searchDomain(Request $request)
    {
        try {
            $domains = $request->only(['domains']);
            $domain = $this->domainRepository->getDomainBySearch($domains);
            $response = [
                'list_domain' => $this->utility->paginate($domain),
            ];

            if (!empty($domain)) {
                return response()->json([
                    'success' => true,
                    'data' => $response,
                    'message' => 'Tìm kiếm domain thành công',
                    'type' => 'search_domain_success',
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'list_domain' => [],
                    ],
                    'message' => 'Không tìm thấy domain',
                    'type' => 'search_domain_not_found',
                ], 404);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tìm kiếm domain không thành công',
                'type' => 'search_domain_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
