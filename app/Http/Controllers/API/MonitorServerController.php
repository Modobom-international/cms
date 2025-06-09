<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\MonitorServerRepository;
use App\Repositories\ServerRepository;
use App\Enums\ActivityAction;
use App\Traits\LogsActivity;

class MonitorServerController extends Controller
{
    use LogsActivity;

    protected $serverRepository;
    protected $monitorServerRepository;
    protected $utility;

    public function __construct(MonitorServerRepository $monitorServerRepository, ServerRepository $serverRepository, Utility $utility)
    {
        $this->serverRepository = $serverRepository;
        $this->monitorServerRepository = $monitorServerRepository;
        $this->utility = $utility;
    }

    public function detail(Request $request)
    {
        try {
            $input = $request->all();
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;
            $ip = $request->get('ip');

            $server = $this->serverRepository->getByIp($ip);

            if (empty($server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server không tồn tại',
                    'type' => 'server_empty',
                ], 400);
            }

            $query = $this->monitorServerRepository->getByServer($server->id);

            $data = $this->utility->paginate($query, $pageSize, $page);

            $this->logActivity(ActivityAction::DETAIL_MONITOR_SERVER, ['filters' => $input], 'Kiểm tra chi tiết thông số server');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách team thành công',
                'type' => 'list_team_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách team không thành công',
                'type' => 'list_team_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $ip = $request->get('ip');

            $server = $this->serverRepository->getByIp($ip);

            if (empty($server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server không tồn tại',
                    'type' => 'server_empty',
                ], 400);
            }

            $data = [
                'server_id' => $server->id,
                'cpu' => $request->get('cpu'),
                'ram' => $request->get('ram'),
                'disk' => $request->get('disk'),
                'services' => $request->get('services'),
                'logs' => $request->get('logs'),
                'timestamp' => $request->get('timestamp'),
            ];

            $this->monitorServerRepository->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Thêm mới thông số server thành công',
                'type' => 'create_monitor_server_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thêm mới thông số server không thành công',
                'type' => 'create_monitor_server_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
