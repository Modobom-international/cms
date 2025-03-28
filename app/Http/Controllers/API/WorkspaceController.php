<?php

namespace App\Http\Controllers\Api;

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
        UserRepository $userRepository)
    {
        $this->utility = $utility;
        $this->workspaceUserRepository = $workspaceUserRepository;
        $this->workspaceRepository = $workspaceRepository;
        $this->userRepository = $userRepository;
    }
    
    /**
     * Lấy danh sách workspace của user.
     */
    public function index()
    {
        $listWorkspace = $this->workspaceRepository->index();
    
        return response()->json([
            'success' => true,
            'workspace' => $listWorkspace,
            'message' => 'Danh sách workspace',
            'type' => 'list_workspace',
        ], 201);
    }
    
    /**
     * Tạo workspace mới.
     */
    public function store(WorkspaceRequest $request)
    {
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
    }
    
    /**
     * Lấy chi tiết workspace theo ID.
     */
    public function show($id)
    {
        $workspace = $this->workspaceRepository->show($id);
        if(!$workspace) {
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
        $workspace = $this->workspaceRepository->show($id);
        if(!$workspace) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy workspace',
                'type' => 'workspace_not_found',
            ], 404);
        }
        
        if ($workspace->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $input = $request->except(['_token']);
        
        $workspace = $this->workspaceRepository->updateWorkspace($input, $id);
    
        return response()->json([
            'success' => true,
            'workspace' => $workspace,
            'message' => 'Cập nhật workspace thành công',
            'type' => 'update_workspace_success',
        ], 201);
    }
    
    /**
     * Xóa workspace.
     */
    public function destroy($id)
    {
        $workspace = $this->workspaceRepository->show($id);
        if(!$workspace) {
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
        
        $this->workspaceRepository->destroy($id);
    
        return response()->json([
            'success' => true,
            'message' => 'Workspace được xóa thành công',
            'type' => 'delete_workspace_success',
        ], 201);
    }
    
    public function joinPublicWorkspace($workspaceId)
    {
        $workspace = $this->workspaceRepository->show($workspaceId);
        if(!$workspace) {
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
        
        $user = Auth::user();
        
        // Kiểm tra user đã có trong workspace chưa
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
    }
    
    public function addMember(Request $request, $workspaceId)
    {
        //validate
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        //check workspace
        $workspace = $this->workspaceRepository->show($workspaceId);
        if(!$workspace) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy workspace',
                'type' => 'workspace_not_found',
            ], 404);
        }
        $user = Auth::user();
        // Chỉ owner hoặc admin mới có quyền mời user
        $isAdmin = $this->workspaceUserRepository->checkRoleAdmin($user->id ,$workspaceId);
    
        if (!$isAdmin && $workspace->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền',
                'type' => 'Unauthorized',
                ], 403);
        }
        
        $user = $this->userRepository->getInfo($request->email);
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
            'message' => 'Thêm workspace user thành công',
            'type' => 'add_workspace_user_success',
        ], 201);
    }
    
    public function listMembers($workspaceId)
    {
        $members = $this->workspaceUserRepository->getMembers($workspaceId);
        return response()->json([
            'success' => true,
            'members' => $members,
            'message' => 'Danh sách workspace',
            'type' => 'list_workspace',
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
