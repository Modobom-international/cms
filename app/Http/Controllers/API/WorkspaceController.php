<?php

namespace App\Http\Controllers\API;

use App\Enums\Workspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceRequest;
use App\Repositories\UserRepository;
use App\Repositories\WorkspaceRepository;
use App\Repositories\WorkspaceUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\Utility;
use Illuminate\Support\Facades\Validator;
use App\Enums\Users;

class WorkspaceController extends Controller
{
    protected $workspaceRepository;
    protected $workspaceUserRepository;
    protected $utility;
    protected $userRepository;

    public function __construct(
        Utility $utility,
        WorkspaceRepository $workspaceRepository,
        WorkspaceUserRepository $workspaceUserRepository,
        UserRepository $userRepository
    ) {
        $this->utility = $utility;
        $this->workspaceUserRepository = $workspaceUserRepository;
        $this->workspaceRepository = $workspaceRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Get list of workspaces based on authorization rules:
     * 1. If user role is admin, return all workspaces
     * 2. If user role is user, return only:
     *    - Public workspaces
     *    - Private workspaces they own
     *    - Private workspaces where they are members
     */
    public function index()
    {
        $user = Auth::user();
        $workspaces = collect();

        // If user is admin, return all workspaces with role information
        if ($user->role === Users::ADMIN) {
            $allWorkspaces = $this->workspaceRepository->getAllWorkspaces();
            foreach ($allWorkspaces as $workspace) {
                $role = null;
                if ($workspace->owner_id === $user->id) {
                    $role = 'owner';
                } else {
                    $memberInfo = $this->workspaceUserRepository->getMemberRole($user->id, $workspace->id);
                    $role = $memberInfo ? $memberInfo->role : null;
                }

                $workspaces->push([
                    'workspace' => $workspace,
                    'role' => $role,
                    'is_member' => ($role !== null)
                ]);
            }
        }
        // If user is regular user, return only accessible workspaces
        else {
            // Get workspaces owned by the user
            $ownedWorkspaces = $this->workspaceRepository->getWorkspacesByOwnerId($user->id);
            foreach ($ownedWorkspaces as $workspace) {
                $workspaces->push([
                    'workspace' => $workspace,
                    'role' => 'owner',
                    'is_member' => true
                ]);
            }

            // Get workspaces where user is a member
            $memberWorkspaces = $this->workspaceUserRepository->getMembers($user->id);
            foreach ($memberWorkspaces as $member) {
                if (!in_array($member->workspace_id, $ownedWorkspaces->pluck('id')->toArray())) {
                    $workspaces->push([
                        'workspace' => $member->workspace,
                        'role' => $member->role,
                        'is_member' => true
                    ]);
                }
            }

            // Get public workspaces (excluding already added ones)
            $publicWorkspaces = $this->workspaceRepository->getPublicWorkspaces();
            foreach ($publicWorkspaces as $workspace) {
                if (!in_array($workspace->id, $workspaces->pluck('workspace.id')->toArray())) {
                    $workspaces->push([
                        'workspace' => $workspace,
                        'role' => null,
                        'is_member' => false
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'workspaces' => $workspaces,
            'message' => 'Danh sách workspace',
            'type' => 'list_workspaces',
        ], 200);
    }

    /**
     * Tạo workspace mới.
     */
    public function store(WorkspaceRequest $request)
    {
        try {
            $input = $request->except(['_token']);
            $workspace = [
                'name' => $input['name'],
                'description' => $input['description'],
                'owner_id' => Auth::user()->id,
                'visibility' => $input['visibility'],
            ];
            $dataWorkspace = $this->workspaceRepository->createWorkspace($workspace);

            // Gán user tạo workspace làm admin
            $dataWorkspaceUser = [
                'workspace_id' => $dataWorkspace->id,
                'user_id' => Auth::user()->id,
                'role' => Workspace::ROLE_ADMIN,
            ];

            $this->workspaceUserRepository->createWorkSpaceUser($dataWorkspaceUser);

            return response()->json([
                'success' => true,
                'message' => 'Tạo workspace thành công',
                'type' => 'create_workspace_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo workspace',
                'type' => 'error_create_workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết workspace theo ID.
     */
    public function show($id)
    {
        $workspace = $this->workspaceRepository->show($id);
        if (!$workspace) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy workspace',
                'type' => 'workspace_not_found',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'workspace' => $workspace,
            'message' => 'Thông tin workspace',
            'type' => 'workspace_information',
        ], 201);
    }

    /**
     * Cập nhật workspace.
     */
    public function update(WorkspaceRequest $request, $id)
    {
        try {
            $workspace = $this->workspaceRepository->show($id);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy workspace',
                    'type' => 'workspace_not_found',
                ], 404);
            }

            if ($workspace->owner_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập',
                    'type' => 'unauthorized',
                ], 403);
            }
            $input = $request->except(['_token']);

            $workspace = $this->workspaceRepository->updateWorkspace($input, $id);

            return response()->json([
                'success' => true,
                'workspace' => $workspace,
                'message' => 'Cập nhật workspace thành công',
                'type' => 'update_workspace_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật workspace',
                'type' => 'error_create_workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa workspace.
     */
    public function destroy($id)
    {
        try {
            $workspace = $this->workspaceRepository->show($id);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy workspace',
                    'type' => 'workspace_not_found',
                ], 404);
            }

            if ($workspace->owner_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa',
                    'type' => 'unauthorized',
                ], 403);
            }

            $this->workspaceRepository->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'Workspace được xóa thành công',
                'type' => 'delete_workspace_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa workspace',
                'type' => 'error_delete_workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function joinPublicWorkspace($workspaceId)
    {
        try {
            $workspace = $this->workspaceRepository->show($workspaceId);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy workspace',
                    'type' => 'workspace_not_found',
                ], 404);
            }

            if ($workspace->visibility !== Workspace::WORKSPACE_PUBLIC) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workspace là private',
                    'type' => 'workspace_is_private',
                ], 403);
            }

            // Kiểm tra user đã có trong workspace chưa
            $user = Auth::user();
            $memberExist = $this->workspaceUserRepository->checkMemberExist($user->id, $workspaceId);
            if ($memberExist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã là 1 member',
                    'type' => 'user_exist',
                ], 400);
            }

            $dataWorkspaceUser = [
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'role' => $request->role ?? Workspace::ROLE_MEMBER,
            ];

            $this->workspaceUserRepository->createWorkSpaceUser($dataWorkspaceUser);

            return response()->json([
                'success' => true,
                'message' => 'Bạn đã join workspace thành công',
                'type' => 'user_join_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi join workspace',
                'type' => 'error_join_workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addMember(Request $request, $workspaceId)
    {
        try {
            //validate
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            //check workspace
            $workspace = $this->workspaceRepository->show($workspaceId);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy workspace',
                    'type' => 'workspace_not_found',
                ], 404);
            }

            // Chỉ owner hoặc admin mới có quyền mời user
            $user = Auth::user();
            $isAdmin = $this->workspaceUserRepository->checkRoleAdmin($user->id, $workspaceId);

            if (!$isAdmin && $workspace->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền',
                    'type' => 'Unauthorized',
                ], 403);
            }

            $user = $this->userRepository->getUserByEmail($request->email);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy user',
                    'type' => 'user_not_found',
                ], 404);
            }
            // Kiểm tra nếu user đã trong workspace
            $memberExist = $this->workspaceUserRepository->checkMemberExist($user->id, $workspaceId);
            if ($memberExist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã là 1 member',
                    'type' => 'user_exist',
                ], 400);
            }

            $dataWorkspaceUser = [
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'role' => $request->role ?? Workspace::ROLE_MEMBER,
            ];

            $this->workspaceUserRepository->createWorkSpaceUser($dataWorkspaceUser);

            return response()->json([
                'success' => true,
                'message' => 'Thêm member thành công',
                'type' => 'add_workspace_member_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi add member workspace',
                'type' => 'error_add_member_workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listMembers($workspaceId)
    {
        $members = $this->workspaceUserRepository->getMembers($workspaceId);
        return response()->json([
            'success' => true,
            'members' => $members,
            'message' => 'Danh sách members',
            'type' => 'list_members',
        ], 201);
    }

    public function removeMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workspace_id' => 'required|exists:workspaces,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->workspaceUserRepository->removeMember($request->workspace_id, $request->user_id);
        return response()->json([
            'success' => true,
            'message' => 'Member được xóa thành công',
            'type' => 'delete_member_success',
        ], 201);
    }
}
