<?php

namespace App\Http\Controllers\API;

use App\Enums\Boards;
use App\Enums\Workspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\BoardRequest;
use App\Http\Requests\UpdateBoardRequest;
use App\Repositories\BoardRepository;
use App\Repositories\BoardUserRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkspaceRepository;
use App\Repositories\WorkspaceUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\Utility;
use Illuminate\Support\Facades\Validator;

class BoardController extends Controller
{
    protected $boardRepository;
    protected $boardUserRepository;
    protected $workspaceUserRepository;
    protected $workspaceRepository;
    protected $utility;
    protected $userRepository;

    public function __construct(
        Utility $utility,
        BoardRepository $boardRepository,
        BoardUserRepository $boardUserRepository,
        WorkspaceUserRepository $workspaceUserRepository,
        WorkspaceRepository $workspaceRepository,
        UserRepository $userRepository
    ) {
        $this->utility = $utility;
        $this->workspaceUserRepository = $workspaceUserRepository;
        $this->workspaceRepository = $workspaceRepository;
        $this->boardUserRepository = $boardUserRepository;
        $this->boardRepository = $boardRepository;
        $this->userRepository = $userRepository;
    }

    public function index($workspaceId)
    {
        $workspace = $this->workspaceRepository->show($workspaceId);
        if (!$workspace) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy workspace',
                'type' => 'workspace_not_found',
            ], 404);
        }

        $user = Auth::user();
        // Check if user has access to the workspace
        if ($workspace->visibility === Workspace::WORKSPACE_PRIVATE) {
            $isMember = $this->workspaceUserRepository->checkMemberExist($user->id, $workspaceId);
            if (!$isMember && $workspace->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập',
                    'type' => 'unauthorized',
                ], 403);
            }
        }

        $boards = $this->boardRepository->index($workspace->id);
        $isAdmin = $this->workspaceUserRepository->checkRoleAdmin($user->id, $workspaceId);

        // Add isAdmin to workspace data
        $workspace = $workspace->toArray();
        $workspace['is_admin'] = $isAdmin || $workspace['owner_id'] === $user->id;

        return response()->json([
            'success' => true,
            'boards' => $boards,
            'workspace' => $workspace,
            'message' => 'Danh sách boards',
            'type' => 'list_boards',
        ], 200);
    }

    public function store(BoardRequest $request)
    {
        try {
            $input = $request->except(['_token']);
            $workspace = $this->workspaceRepository->show($input['workspace_id']);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy workspace',
                    'type' => 'workspace_not_found',
                ], 404);
            }

            $user = Auth::user();
            // Check if user is workspace admin or owner
            $isAdmin = $this->workspaceUserRepository->checkRoleAdmin($user->id, $workspace->id);
            if (!$isAdmin && $workspace->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ admin workspace mới có quyền tạo board',
                    'type' => 'unauthorized',
                ], 403);
            }

            $board = [
                'workspace_id' => $input['workspace_id'],
                'name' => $input['name'],
                'description' => $input['description'] ?? '',
                'owner_id' => $user->id,
                // Inherit visibility from workspace
                'visibility' => $workspace->visibility
            ];

            $board = $this->boardRepository->createBoard($board);

            // Make creator an admin of the board
            $dataBoardUser = [
                'board_id' => $board->id,
                'user_id' => $user->id,
                'role' => Boards::ROLE_ADMIN,
            ];

            $this->boardUserRepository->createBoardUser($dataBoardUser);

            return response()->json([
                'success' => true,
                'message' => 'Tạo board thành công',
                'type' => 'create_board_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo board',
                'type' => 'error_create_board',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $board = $this->boardRepository->show($id);
            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy board',
                    'type' => 'board_not_found',
                ], 404);
            }

            // Check workspace access
            $workspace = $this->workspaceRepository->show($board->workspace_id);
            $user = Auth::user();

            // Check if user has access to the workspace
            if ($workspace->visibility === Workspace::WORKSPACE_PRIVATE) {
                $isMember = $this->workspaceUserRepository->checkMemberExist($user->id, $workspace->id);
                if (!$isMember && $workspace->owner_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền truy cập',
                        'type' => 'unauthorized',
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'board' => $board,
                'message' => 'Thông tin board',
                'type' => 'board_information',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin board',
                'type' => 'error_get_board',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateBoardRequest $request, $id)
    {
        try {
            $board = $this->boardRepository->show($id);
            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy board',
                    'type' => 'board_not_found',
                ], 404);
            }

            $user = Auth::user();
            $workspace = $this->workspaceRepository->show($board->workspace_id);

            // Check if user is workspace admin or owner
            $isAdmin = $this->workspaceUserRepository->checkRoleAdmin($user->id, $workspace->id);
            if (!$isAdmin && $workspace->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ admin workspace mới có quyền cập nhật board',
                    'type' => 'unauthorized',
                ], 403);
            }

            $input = $request->except(['_token', 'visibility']); // Remove visibility from updateable fields
            $this->boardRepository->updateBoard($input, $id);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật board thành công',
                'type' => 'update_board_success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật board',
                'type' => 'error_update_board',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $board = $this->boardRepository->show($id);
            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy board',
                    'type' => 'board_not_found',
                ], 404);
            }

            $user = Auth::user();
            $workspace = $this->workspaceRepository->show($board->workspace_id);

            // Check if user is workspace admin or owner
            $isAdmin = $this->workspaceUserRepository->checkRoleAdmin($user->id, $workspace->id);
            if (!$isAdmin && $workspace->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ admin workspace mới có quyền xóa board',
                    'type' => 'unauthorized',
                ], 403);
            }

            $this->boardRepository->destroy($id);
            return response()->json([
                'success' => true,
                'message' => 'Board được xóa thành công',
                'type' => 'delete_board_success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa board',
                'type' => 'error_delete_board',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function joinPublicBoard($boardId)
    {
        try {
            $board = $this->boardRepository->show($boardId);
            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy board',
                    'type' => 'board_not_found',
                ], 404);
            }

            // Check workspace visibility
            $workspace = $this->workspaceRepository->show($board->workspace_id);
            if ($workspace->visibility !== Workspace::WORKSPACE_PUBLIC) {
                return response()->json([
                    'success' => false,
                    'message' => 'Board thuộc workspace private',
                    'type' => 'workspace_is_private',
                ], 403);
            }

            // Check if user is already a member
            $user = Auth::user();
            $memberExist = $this->boardUserRepository->checkMemberExist($user->id, $boardId);
            if ($memberExist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã là thành viên của board',
                    'type' => 'user_exist',
                ], 400);
            }

            $dataBoardsUser = [
                'board_id' => $boardId,
                'user_id' => $user->id,
                'role' => Boards::ROLE_MEMBER,
            ];

            $this->boardUserRepository->createBoardUser($dataBoardsUser);

            return response()->json([
                'success' => true,
                'message' => 'Bạn đã join board thành công',
                'type' => 'user_join_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi join board',
                'type' => 'error_join',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addMember(Request $request, $boardId)
    {
        try {
            //validate
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'role' => 'sometimes|in:admin,member'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            //check workspace
            $board = $this->boardRepository->show($boardId);
            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy board',
                    'type' => 'board_not_found',
                ], 404);
            }
            // Kiểm tra nếu board không tồn tại trong workspace
            $user = Auth::user();
            $checkExistWorkspace = $this->workspaceRepository->checkExist($board->workspace_id);
            if (!$checkExistWorkspace) {
                return response()->json(
                    [
                        'message' => 'Workspace không tồn tại'
                    ],
                    404
                );
            }
            // Chỉ owner hoặc admin mới có quyền mời user
            $isAdmin = $this->workspaceUserRepository->checkRoleAdmin($user->id, $checkExistWorkspace->id);
            if (!$isAdmin && $board->owner_id !== $user->id) {
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
            $memberExist = $this->boardUserRepository->checkMemberExist($user->id, $boardId);
            if ($memberExist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã là 1 member',
                    'type' => 'user_exist',
                ], 400);
            }

            $dataBoardsUser = [
                'board_id' => $boardId,
                'user_id' => $user->id,
                'role' => $request->role ?? Boards::ROLE_MEMBER,
            ];

            $this->boardUserRepository->createBoardUser($dataBoardsUser);

            return response()->json([
                'success' => true,
                'message' => 'Thêm member thành công',
                'type' => 'member_invite_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi add member',
                'type' => 'error_add_member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listMembers($boardId)
    {
        $members = $this->boardUserRepository->getMembers($boardId);
        return response()->json([
            'success' => true,
            'members' => $members,
            'message' => 'Danh sách member',
            'type' => 'list_members',
        ], 201);
    }

    public function removeMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'board_id' => 'required|exists:boards,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->boardUserRepository->removeMember($request->board_id, $request->user_id);
        return response()->json([
            'success' => true,
            'message' => 'Member được xóa thành công',
            'type' => 'delete_member_success',
        ], 201);
    }
}
