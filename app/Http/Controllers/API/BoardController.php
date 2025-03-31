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

        // Nếu workspace là private, kiểm tra quyền truy cập
        if ($workspace->visibility === Workspace::WORKSPACE_PRIVATE && $workspace->owner_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập',
                'type' => 'unauthorized',
            ], 403);
        }
        $boards = $this->boardRepository->index($workspace->id);
        return response()->json([
            'success' => true,
            'boards' => $boards,
            'message' => 'Danh sách boards',
            'type' => 'list_boards',
        ], 201);
    }

    public function store(BoardRequest $request)
    {
        $input = $request->except(['_token']);
        $workspace = $this->workspaceRepository->show($request->workspace_id);
        if (!$workspace) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy workspace',
                'type' => 'workspace_not_found',
            ], 404);
        }
        // Kiểm tra user có thuộc workspace không
        if ($workspace->owner_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc workspace này',
                'type' => 'user_not_exist',
            ], 403);
        }

        $board = [
            'workspace_id' => $request->workspace_id,
            'name' => $request->name,
            'visibility' => $request->visibility ?? 'private',
            'owner_id' => Auth::id(),
        ];

        $board = $this->boardRepository->createBoard($board);
        // Gán user tạo board làm admin

        $dataBoardUser = [
            'board_id' => $board->id,
            'user_id' => Auth::user()->id,
            'role' => Boards::ROLE_ADMIN,
        ];

        $this->boardUserRepository->createBoardUser($dataBoardUser);

        return response()->json([
            'success' => true,
            'message' => 'Tạo board thành công',
            'type' => 'create_board_success',
        ], 201);
    }

    public function show($id)
    {
        $board = $this->boardRepository->show($id);
        if (!$board) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy board',
                'type' => 'board_not_found',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'board' => $board,
            'message' => 'Thông tin board',
            'type' => 'board_information',
        ], 201);
    }
    // Cập nhật Board
    public function update(UpdateBoardRequest $request, $id)
    {
        $board = $this->boardRepository->show($id);

        if (!$board) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy board',
                'type' => 'board_not_found',
            ], 404);
        }
        if ($board->owner_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập',
                'type' => 'unauthorized',
            ], 403);
        }
        $input = $request->except(['_token']);

        $this->boardRepository->updateBoard($input, $id);

        return response()->json([
            'success' => true,
            'board' => $board,
            'message' => 'Cập nhật board thành công',
            'type' => 'update_board_success',
        ], 201);
    }

    public function destroy($id)
    {
        $board = $this->boardRepository->show($id);
        if (!$board) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy board',
                'type' => 'board_not_found',
            ], 404);
        }

        if ($board->owner_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa',
                'type' => 'unauthorized',
            ], 403);
        }

        $this->boardRepository->destroy($id);
        return response()->json([
            'success' => true,
            'message' => 'Board được xóa thành công',
            'type' => 'delete_board_success',
        ], 201);
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

            if ($board->visibility !== Boards::BOARD_PUBLIC) {
                return response()->json([
                    'success' => false,
                    'message' => 'Board là private',
                    'type' => 'board_is_private',
                ], 403);
            }

            $user = Auth::user();

            // Kiểm tra user đã có trong workspace chưa
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
                'role' =>  Boards::ROLE_VIEWER,
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
            $user = Auth::user();

            // Kiểm tra nếu board không tồn tại trong workspace
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
                'role' => $request->role ?? Boards::ROLE_VIEWER,
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
