<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Jobs\StoreAppInformation;
use App\Repositories\AppInformationRepository;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Repositories\CachePoolRepository;

class AppInformationController extends Controller
{
    use LogsActivity;

    protected $appInformationRepository;
    protected $cachePoolRepository;
    protected $utility;

    public function __construct(AppInformationRepository $appInformationRepository, CachePoolRepository $cachePoolRepository, Utility $utility)
    {
        $this->appInformationRepository = $appInformationRepository;
        $this->cachePoolRepository = $cachePoolRepository;
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

    public function list(Request $request)
    {
        try {
            $input = $request->all();
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;
            $count_event = [];
            $filters = [
                'from' => $request->get('from'),
                'to' => $request->get('to'),
                'app_name' => $request->get('app_name'),
                'os_name' => $request->get('os_name'),
                'os_version' => $request->get('os_version'),
                'app_version' => $request->get('app_version'),
                'category' => $request->get('category'),
                'platform' => $request->get('platform'),
                'country' => $request->get('country'),
                'event_name' => $request->get('event_name'),
                'network' => $request->get('network'),
            ];

            $query = $this->appInformationRepository->getWithFilter($filters);
            if (!empty($filters['event_name']) && is_array($filters['event_name'])) {
                $grouped = $query->groupBy('event_name')->map(function ($group) {
                    return $group
                        ->groupBy('event_value')
                        ->map(function ($items) {
                            return $items->count();
                        })
                        ->map(function ($count, $value) {
                            return ['event_value' => $value, 'count' => $count];
                        })
                        ->values();
                });

                $count_event = $grouped->map(function ($values, $eventName) {
                    return [
                        'event_name' => $eventName,
                        'values' => $values
                    ];
                })->values()->toArray();
            }
            $groupUser = $query->groupBy('user_id');
            $total_user = count($groupUser);
            $data = [
                'list' => $this->utility->paginate($query, $pageSize, $page),
                'total_user' => $total_user,
                'count_event' => $count_event,
            ];

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

    public function menu()
    {
        try {
            $key = 'menu_filter_app_information';
            $data = $this->cachePoolRepository->getCacheByKey($key);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách menu app information thành công',
                'type' => 'menu_app_information_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách menu app information không thành công',
                'type' => 'menu_app_information_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function detailUserID(Request $request)
    {
        try {
            $user_id = $request->get('user_id');

            $query = $this->appInformationRepository->getByUserID($user_id);
            $data = $query->groupBy('app_name');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách app_name theo user_id thành công',
                'type' => 'detail_app_information_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách app_name theo user_id không thành công',
                'type' => 'detail_app_information_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
