<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Team\TeamRepository;
use Exception;
use Illuminate\Http\Request;

class TeamController extends Controller
{
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
            $teams = $this->teamRepository->getTeams();

            foreach ($teams as $team) {
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

            $teams = $this->utility->paginate($teams);

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

    public function create()
    {
        try {
            $getPermission = $this->permissionRepository->getPermissions();
            $permissions = [];
            foreach ($getPermission as $permission) {
                $prefix = $permission->prefix;

                $permissions[$prefix][] = $permission;
            }

            return response()->json([
                'success' => true,
                'data' => $permissions,
                'message' => 'Lấy thông tin team thành công',
                'type' => 'create_view_team_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin team không thành công',
                'type' => 'create_view_team_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'permissions' => 'array',
            ]);

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

    public function edit($id)
    {
        try {
            $team = $this->teamRepository->findTeam($id);
            $getPermission = $this->permissionRepository->getPermissions();
            $permissions = [];
            $team_permissions = [];
            foreach ($getPermission as $permission) {
                $prefix = $permission->prefix;

                $permissions[$prefix][] = $permission;
            }

            foreach ($team->permissions as $permission) {
                $prefix = $permission->prefix;

                $team_permissions[$prefix][] = $permission->name;
            }

            $response = [
                'team' => $team,
                'permissions' => $permissions,
                'team_permissions' => $team_permissions
            ];

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Lấy thông tin team thành công',
                'type' => 'edit_team_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin team không thành công',
                'type' => 'edit_team_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'permissions' => 'array',
            ]);

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
            $this->teamRepository->destroy($id);

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
