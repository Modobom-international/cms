<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServerRequest;
use App\Traits\LogsActivity;
use App\Enums\Utility;
use App\Repositories\ServerRepository;

class ServerController extends Controller
{
    use LogsActivity;

    protected $serverRepository;
    protected $utility;

    public function __construct(ServerRepository $serverRepository, Utility $utility)
    {
        $this->serverRepository  = $serverRepository;
        $this->utility  = $utility;
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

            $this->serverRepository->create([
                'name' => $request->name,
                'ip' => $request->ip,
            ]);

            $this->logActivity(ActivityAction::CREATE_RECORD, ['filters' => $input], 'Thêm server');

            return response()->json([
                'success' => true,
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
            $team = $this->serverRepository->getByID($id);
            if (!$team) {
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
}
