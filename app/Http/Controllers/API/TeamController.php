<?php

namespace App\Http\Controllers\API;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeamRequest;
use App\Traits\LogsActivity;
use App\Repositories\PermissionRepository;
use App\Repositories\TeamRepository;
use Exception;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    use LogsActivity;

    protected $teamRepository;
    protected $permissionRepository;
    protected $utility;

    public function __construct(TeamRepository $teamRepository, PermissionRepository $permissionRepository, Utility $utility)
    {
        $this->teamRepository  = $teamRepository;
        $this->permissionRepository  = $permissionRepository;
        $this->utility  = $utility;
    }

    public function index()
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

            $query = $this->teamRepository->getTeamByFilter($filter);

            foreach ($query as $team) {
                $permissions  = array();
                if (isset($team->permissions)) {
                    $getPermission = $team->permissions;
                    foreach ($getPermission as $permission) {
                        $prefix = $permission->prefix;

                        if (!in_array($prefix, $permissions)) {
                            $permissions[] = $prefix;
                        }
                    }

                    $team->prefix_permissions = implode(',', $permissions);
                }
            }

            $data = $this->utility->paginate($query, $pageSize, $page);

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách team');

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

    public function store(TeamRequest $request)
    {
        try {
            $getPermission = $request->get('permissions');
            $permissions = array();

            foreach ($getPermission as $permission => $tick) {
                $permissions[] = $permission;
            }

            $team = $this->teamRepository->create([
                'name' => $request->name
            ]);

            if (!empty($permissions)) {
                $team->permissions()->sync($permissions);
            }

            return response()->json([
                'success' => true,
                'data' => $request->all(),
                'message' => 'Tạo team thành công',
                'type' => 'store_team_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo team không thành công',
                'type' => 'store_team_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(TeamRequest $request, $id)
    {
        try {
            $getPermission = $request->get('permissions');
            $permissions = array();

            foreach ($getPermission as $permission => $tick) {
                $permissions[] = $permission;
            }

            $this->teamRepository->update($id, [
                'name' => $request->name
            ]);

            $team = $this->teamRepository->findTeam($id);

            if (!empty($permissions)) {
                $team->permissions()->sync($permissions);
            }

            return response()->json([
                'success' => true,
                'data' => $request->all(),
                'message' => 'Lấy thông tin team thành công',
                'type' => 'update_team_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin team không thành công',
                'type' => 'update_team_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->teamRepository->deleteById($id);

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Xóa team thành công',
                'type' => 'delete_team_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xóa team không thành công',
                'type' => 'delete_team_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPermissionByTeam(Request $request)
    {
        try {
            $id = $request->get('id');
            $team = $this->teamRepository->findTeam($id);

            if (isset($team->permissions)) {
                foreach ($team->permissions as $permission) {
                    $prefix = $permission->prefix;
                    $permissions[$prefix][] = $permission->name;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $permission,
                'message' => 'Lấy thông tin permission của team thành công',
                'type' => 'get_permission_team_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin permission của team không thành công',
                'type' => 'get_permission_team_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
