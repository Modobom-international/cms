<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Jobs\StoreAppInformation;
use App\Repositories\AppInformationRepository;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;

class AppInformationController extends Controller
{
    use LogsActivity;

    protected $appInformationRepository;
    protected $utility;

    public function __construct(AppInformationRepository $appInformationRepository, Utility $utility)
    {
        $this->appInformationRepository = $appInformationRepository;
        $this->utility = $utility;
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            StoreAppInformation::dispatch($data)->onQueue('store_app_information');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu app information thành công!',
                'type' => 'store_app_information_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu app information không thành công!',
                'type' => 'store_app_information_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function list()
    {
        try {
            $input = $request->all();
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;

            $query = $this->appInformationRepository->get();
            $data = $this->utility->paginate($query, $pageSize, $page);

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách app information');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách app information thành công',
                'type' => 'list_app_information_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách app information không thành công',
                'type' => 'list_app_information_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
