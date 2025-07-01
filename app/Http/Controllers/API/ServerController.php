<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServerRequest;
use App\Traits\LogsActivity;
use App\Enums\Utility;
use App\Repositories\ServerRepository;
use App\Repositories\ApiKeyRepository;
use App\Models\ApiKey;

class ServerController extends Controller
{
    use LogsActivity;

    protected $serverRepository;
    protected $apiKeyRepository;
    protected $utility;

    public function __construct(ServerRepository $serverRepository, ApiKeyRepository $apiKeyRepository, Utility $utility)
    {
        $this->serverRepository = $serverRepository;
        $this->apiKeyRepository = $apiKeyRepository;
        $this->utility = $utility;
    }

    public function index(Request $request)
    {
        try {
            $input = $request->all();
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;
            $search = $request->get('search');
            $filter = [];

            if (isset($search)) {
                $filter['search'] = $search;
            }

            $query = $this->serverRepository->getByFilter($filter);
            $data = $this->utility->paginate($query, $pageSize, $page);

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách server');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách server thành công',
                'type' => 'list_server_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách server không thành công',
                'type' => 'list_server_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(ServerRequest $request)
    {
        try {
            $input = $request->all();

            $server = $this->serverRepository->create([
                'name' => $request->name,
                'ip' => $request->ip,
            ]);

            $keyData = ApiKey::generateKey();
            $apiKey = $this->apiKeyRepository->createApiKey([
                'name' => 'Server API Key - ' . $server->name,
                'key_hash' => $keyData['key_hash'],
                'key_prefix' => $keyData['key_prefix'],
                'user_id' => auth()->id(),
                'expires_at' => null,
                'is_active' => true,
            ]);

            $this->apiKeyRepository->attachToServer($apiKey->id, $server->id);

            $this->logActivity(ActivityAction::CREATE_RECORD, ['filters' => $input], 'Thêm server');

            return response()->json([
                'success' => true,
                'data' => [
                    'server' => $server,
                    'api_key' => $keyData['key'], // Only shown once during creation
                    'api_key_prefix' => $keyData['key_prefix'],
                ],
                'message' => 'Tạo server thành công',
                'type' => 'store_server_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo server không thành công',
                'type' => 'store_server_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(ServerRequest $request, $id)
    {
        try {
            $this->serverRepository->update($id, [
                'name' => $request->name,
                'ip' => $request->ip,
            ]);

            $this->logActivity(ActivityAction::UPDATE_RECORD, ['filters' => $input], 'Cập nhật server');

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin server thành công',
                'type' => 'update_server_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thông tin server không thành công',
                'type' => 'update_server_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $server = $this->serverRepository->getByID($id);
            if (!$server) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy server',
                    'type' => 'server_not_found',
                ], 404);
            }

            $this->serverRepository->deleteById($id);

            $this->logActivity(ActivityAction::DELETE_RECORD, ['filters' => $input], 'Xóa server');

            return response()->json([
                'success' => true,
                'message' => 'Xóa server thành công',
                'type' => 'delete_server_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xóa server không thành công',
                'type' => 'delete_server_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listOnly(Request $request)
    {
        try {
            $input = $request->all();
            $servers = $this->serverRepository->listOnly();

            $this->logActivity(ActivityAction::DETAIL_MONITOR_SERVER, ['request' => $input], 'Lấy danh sách server only');

            return response()->json([
                'success' => true,
                'data' => $servers,
                'message' => 'Lấy danh sách server thành công',
                'type' => 'list_server_only_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách server không thành công',
                'type' => 'list_server_only_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function detail($id)
    {
        try {
            $server = $this->serverRepository->getByID($id);
            if (!$server) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy server',
                    'type' => 'server_not_found',
                ], 404);
            }

            $this->logActivity(ActivityAction::DETAIL_MONITOR_SERVER, ['id' => $id], 'Xem chi tiết server');

            return response()->json([
                'success' => true,
                'data' => $server,
                'message' => 'Lấy chi tiết server thành công',
                'type' => 'detail_server_success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy chi tiết server không thành công',
                'type' => 'detail_server_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
