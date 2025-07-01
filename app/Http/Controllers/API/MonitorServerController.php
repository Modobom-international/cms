<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\MonitorServerRepository;
use App\Repositories\ServerRepository;
use App\Repositories\ApiKeyRepository;
use App\Enums\ActivityAction;
use App\Jobs\StoreMonitorServer;
use App\Traits\LogsActivity;
use App\Enums\Utility;

class MonitorServerController extends Controller
{
    use LogsActivity;

    protected $serverRepository;
    protected $monitorServerRepository;
    protected $apiKeyRepository;
    protected $utility;

    public function __construct(MonitorServerRepository $monitorServerRepository, ServerRepository $serverRepository, ApiKeyRepository $apiKeyRepository, Utility $utility)
    {
        $this->serverRepository = $serverRepository;
        $this->monitorServerRepository = $monitorServerRepository;
        $this->apiKeyRepository = $apiKeyRepository;
        $this->utility = $utility;
    }

    public function detail($id)
    {
        try {
            $server = $this->serverRepository->getByID($id);

            if (empty($server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server không tồn tại',
                    'type' => 'server_empty',
                ], 400);
            }

            $data = $this->monitorServerRepository->getByServer($id);

            $this->logActivity(ActivityAction::DETAIL_MONITOR_SERVER, ['id' => $id], 'Kiểm tra chi tiết thông số server');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy thông số server thành công',
                'type' => 'list_monitor_server_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thông số server không thành công',
                'type' => 'list_monitor_server_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $ip = $request->get('ip');
            $apiKey = $request->get('api_key');
            
            $server = $this->serverRepository->getByIp($ip);

            if (empty($server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server không được phép monitor',
                    'type' => 'server_not_allowed_monitor',
                ], 403);
            }

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key là bắt buộc',
                    'type' => 'api_key_required',
                ], 400);
            }

            $isValidApiKey = $this->apiKeyRepository->verifyKeyForServer($apiKey, $server->id);

            if (!$isValidApiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key không hợp lệ hoặc không thuộc về server này',
                    'type' => 'invalid_api_key',
                ], 401);
            }

            // Update last used timestamp
            $apiKeyModel = $this->apiKeyRepository->getByServer($server->id);
            if ($apiKeyModel) {
                $this->apiKeyRepository->updateLastUsed($apiKeyModel->id);
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

            StoreMonitorServer::dispatch($data)->onQueue('store_monitor_server');

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
