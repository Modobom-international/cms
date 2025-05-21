<?php

namespace App\Http\Controllers\API;

use App\Enums\Workspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceRequest;
use App\Repositories\UserRepository;
use App\Repositories\WorkspaceRepository;
use App\Repositories\WorkspaceUserRepository;
use App\Repositories\BoardUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\Utility;
use Illuminate\Support\Facades\Validator;
use App\Enums\Users;
use App\Enums\Boards;

class WorkspaceController extends Controller
{
    protected $workspaceRepository;
    protected $workspaceUserRepository;
    protected $utility;
    protected $userRepository;
    protected $boardUserRepository;

    public function __construct(
        Utility $utility,
        WorkspaceRepository $workspaceRepository,
        WorkspaceUserRepository $workspaceUserRepository,
        UserRepository $userRepository,
        BoardUserRepository $boardUserRepository
    ) {
        $this->utility = $utility;
        $this->workspaceUserRepository = $workspaceUserRepository;
        $this->workspaceRepository = $workspaceRepository;
        $this->userRepository = $userRepository;
        $this->boardUserRepository = $boardUserRepository;
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
        $workspaceIds = collect();

        // If user is admin, get all workspaces at once
        if ($user->role === Users::ADMIN) {
            $allWorkspaces = $this->workspaceRepository->getAllWorkspaces();
            $workspaceIds = $allWorkspaces->pluck('id');
        } else {
            // Get owned workspaces
            $ownedWorkspaces = $this->workspaceRepository->getWorkspacesByOwnerId($user->id);
            $workspaceIds = $ownedWorkspaces->pluck('id');

            // Get member workspaces
            $memberWorkspaces = $this->workspaceUserRepository->getMembers($user->id);
            $memberWorkspaceIds = $memberWorkspaces->pluck('workspace_id');
            $workspaceIds = $workspaceIds->merge($memberWorkspaceIds);

            // Get public workspaces
            $publicWorkspaces = $this->workspaceRepository->getPublicWorkspaces();
            $workspaceIds = $workspaceIds->merge($publicWorkspaces->pluck('id'))->unique();
        }

        // Fetch all members for the collected workspace IDs in a single query
        $allMembers = $this->workspaceUserRepository->getMembersForWorkspaces($workspaceIds->toArray());
        $membersGrouped = $allMembers->groupBy('workspace_id');

        // Build response for admin
        if ($user->role === Users::ADMIN) {
            foreach ($allWorkspaces as $workspace) {
                $role = null;
                if ($workspace->owner_id === $user->id) {
                    $role = 'owner';
                } else {
                    $memberInfo = $membersGrouped->get($workspace->id, collect())
                        ->where('user_id', $user->id)
                        ->first();
                    $role = $memberInfo ? $memberInfo->role : null;
                }

                $workspaces->push([
                    'workspace' => $workspace,
                    'role' => $role,
                    'is_member' => ($role !== null),
                    'members' => $membersGrouped->get($workspace->id, collect())
                ]);
            }
        } else {
            // Build response for regular users
            // Add owned workspaces
            foreach ($ownedWorkspaces as $workspace) {
                $workspaces->push([
                    'workspace' => $workspace,
                    'role' => 'owner',
                    'is_member' => true,
                    'members' => $membersGrouped->get($workspace->id, collect())
                ]);
            }

            // Add member workspaces
            foreach ($memberWorkspaces as $member) {
                $workspace = $this->workspaceRepository->show($member->workspace_id);
                if ($workspace && !$workspaces->pluck('workspace.id')->contains($workspace->id)) {
                    $workspaces->push([
                        'workspace' => $workspace,
                        'role' => $member->role,
                        'is_member' => true,
                        'members' => $membersGrouped->get($workspace->id, collect())
                    ]);
                }
            }

            // Add public workspaces
            foreach ($publicWorkspaces as $workspace) {
                if (!$workspaces->pluck('workspace.id')->contains($workspace->id)) {
                    $workspaces->push([
                        'workspace' => $workspace,
                        'role' => null,
                        'is_member' => false,
                        'members' => $membersGrouped->get($workspace->id, collect())
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

            // Get all boards in the workspace and add user to each board
            $workspace = $this->workspaceRepository->show($dataWorkspace->id);
            $boards = $workspace->boards;

            foreach ($boards as $board) {
                $dataBoardUser = [
                    'board_id' => $board->id,
                    'user_id' => Auth::user()->id,
                    'role' => Workspace::ROLE_ADMIN === Workspace::ROLE_ADMIN ? Boards::ROLE_ADMIN : Boards::ROLE_MEMBER
                ];
                $this->boardUserRepository->createBoardUser($dataBoardUser);
            }

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
                'role' => 'required|in:' . implode(',', [
                    Workspace::ROLE_ADMIN,
                    Workspace::ROLE_LEADER,
                    Workspace::ROLE_MEMBER
                ])
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check workspace
            $workspace = $this->workspaceRepository->show($workspaceId);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy workspace',
                    'type' => 'workspace_not_found',
                ], 404);
            }

            // Check if current user has permission to add members
            $currentUser = Auth::user();
            $isAdmin = $this->workspaceUserRepository->checkRoleAdmin($currentUser->id, $workspaceId);

            if (!$isAdmin && $workspace->owner_id !== $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ admin workspace mới có quyền thêm thành viên',
                    'type' => 'unauthorized',
                ], 403);
            }

            // Get user to add
            $userToAdd = $this->userRepository->getUserByEmail($request->email);
            if (!$userToAdd) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy user',
                    'type' => 'user_not_found',
                ], 404);
            }

            // Check if user is already in workspace
            $memberExist = $this->workspaceUserRepository->checkMemberExist($userToAdd->id, $workspaceId);
            if ($memberExist) {
                return response()->json([
                    'success' => false,
                    'message' => 'User đã là thành viên của workspace',
                    'type' => 'user_exist',
                ], 400);
            }

            // Only workspace owner can add admins
            if ($request->role === Workspace::ROLE_ADMIN && $workspace->owner_id !== $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ chủ sở hữu workspace mới có quyền thêm admin',
                    'type' => 'unauthorized',
                ], 403);
            }

            $dataWorkspaceUser = [
                'workspace_id' => $workspaceId,
                'user_id' => $userToAdd->id,
                'role' => $request->role
            ];

            $this->workspaceUserRepository->createWorkSpaceUser($dataWorkspaceUser);

            // Get all boards in the workspace and add user to each board
            $workspace = $this->workspaceRepository->show($workspaceId);
            $boards = $workspace->boards;

            foreach ($boards as $board) {
                // Check if user is already a member of the board
                if (!$this->boardUserRepository->checkMemberExist($userToAdd->id, $board->id)) {
                    $dataBoardUser = [
                        'board_id' => $board->id,
                        'user_id' => $userToAdd->id,
                        'role' => $request->role === Workspace::ROLE_ADMIN ? Boards::ROLE_ADMIN : Boards::ROLE_MEMBER
                    ];
                    $this->boardUserRepository->createBoardUser($dataBoardUser);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Thêm thành viên thành công',
                'type' => 'add_workspace_member_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thêm thành viên workspace',
                'type' => 'error_add_member_workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listMembers($workspaceId)
    {
        $members = $this->workspaceUserRepository->getMembersForWorkspaces([$workspaceId]);
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

        try {
            // Get workspace and its boards
            $workspace = $this->workspaceRepository->show($request->workspace_id);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workspace không tồn tại',
                    'type' => 'workspace_not_found',
                ], 404);
            }

            // Remove member from all boards in the workspace
            foreach ($workspace->boards as $board) {
                $this->boardUserRepository->removeMember($board->id, $request->user_id);
            }

            // Remove member from workspace
            $this->workspaceUserRepository->removeMember($request->workspace_id, $request->user_id);

            return response()->json([
                'success' => true,
                'message' => 'Member được xóa thành công khỏi workspace và các boards',
                'type' => 'delete_member_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa member',
                'type' => 'error_remove_member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
