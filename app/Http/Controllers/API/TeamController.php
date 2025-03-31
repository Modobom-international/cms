<?php

namespace App\Http\Controllers;

use App\Helper\Common;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Team\TeamRepository;
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

        return view('admin.team.index', compact('teams'));
    }

    public function create()
    {
        $getPermission = $this->permissionRepository->getPermissions();
        $permissions = [];
        foreach ($getPermission as $permission) {
            $prefix = $permission->prefix;

            $permissions[$prefix][] = $permission;
        }

        return view('admin.team.create', compact('permissions'));
    }

    public function store(Request $request)
    {
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

        return redirect()->route('team.list')->with('success', __('Thêm phòng ban thành công!'));
    }

    public function edit($id)
    {
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

        return view('admin.team.edit', compact('team', 'permissions', 'team_permissions'));
    }

    public function update(Request $request, $id)
    {
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

        return redirect()->route('team.list')->with('success', __('Cập nhật thông tin phòng ban thành công!'));
    }

    public function destroy($id)
    {
        $this->userRepository->destroy([$id]);

        return redirect()->route('team.list')->with('success', __('Xóa phòng ban thành công!'));
    }

    public function getPermissionByTeam(Request $request)
    {
        $id = $request->get('id');
        $team = $this->teamRepository->findTeam($id);

        if (isset($team->permissions)) {
            foreach ($team->permissions as $permission) {
                $prefix = $permission->prefix;
                $permissions[$prefix][] = $permission->name;
            }
        }

        return response()->json($permissions);
    }
}
