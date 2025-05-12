<?php

namespace App\Http\Controllers\API;

use App\Enums\Workspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\UpdateListRequest;
use App\Models\ListBoard;
use App\Repositories\UserRepository;
use App\Repositories\BoardRepository;
use App\Repositories\ListBoardRepository;
use App\Repositories\WorkspaceUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\Utility;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Traits\LogsActivity;
use App\Http\Requests\UpdateListPositionsRequest;

class ListBoardController extends Controller
{
    use LogsActivity;

    protected $boardRepository;
    protected $workspaceUserRepository;
    protected $utility;
    protected $userRepository;
    protected $listBoardRepository;

    public function __construct(
        Utility $utility,
        BoardRepository $boardRepository,
        ListBoardRepository $listBoardRepository,
        WorkspaceUserRepository $workspaceUserRepository,
        UserRepository $userRepository
    ) {
        $this->utility = $utility;
        $this->listBoardRepository = $listBoardRepository;
        $this->workspaceUserRepository = $workspaceUserRepository;
        $this->boardRepository = $boardRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Lấy danh sách List của board.
     */
    public function index($boardId)
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
            $checkRoleUser = $this->listBoardRepository->userHasAccess($boardId);
            if (!$checkRoleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập board này',
                    'type' => 'Unauthorized'
                ], 403);
            }
            $lists = $this->listBoardRepository->getListsByBoard($boardId);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách list thành công',
                'data' => $lists
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'Lỗi khi lấy danh sách list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo workspace mới.
     */
    public function store(ListRequest $request)
    {
        try {
            $input = $input = $request->except(['_token']);
            $board = $this->boardRepository->show($input['board_id']);
            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy board',
                    'type' => 'board_not_found',
                ], 404);
            }
            // Kiểm tra quyền truy cập
            if (!$this->boardRepository->userHasAccess($board->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thêm list vào board này',
                    'type' => 'Unauthorized',
                ], 403);
            }

            // Xác định position nếu không có
            $maxPosition = $this->listBoardRepository->maxPosition($board->id);
            $position = is_null($maxPosition) ? 1 : $maxPosition + 1;

            // Tạo list mới
            $dataList = [
                'board_id' => $board->id,
                'title' => $input['title'],
                'position' => $position
            ];

            $this->listBoardRepository->createListBoard($dataList);

            return response()->json([
                'success' => true,
                'data' => $dataList,
                'message' => 'Tạo list thành công',
                'type' => 'create_list_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo list',
                'type' => 'error_create_list',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    /**
     * Lấy chi tiết workspace theo ID.
     */
    public function show($id)
    {
        $listBoard = $this->listBoardRepository->show($id);
        if (!$listBoard) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy listBoard',
                'type' => 'listBoard_not_found',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'workspace' => $listBoard,
            'message' => 'Thông tin listBoard',
            'type' => 'listBoard_information',
        ], 201);
    }

    /**
     * Cập nhật list.
     */
    public function update(UpdateListRequest $request, $id)
    {
        try {
            $input = $request->except(['_token']);
            $listBoard = $this->listBoardRepository->show($id);
            if (!$listBoard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy listBoard',
                    'type' => 'listBoard_not_found',
                ], 404);
            }

            $board = $this->boardRepository->show($listBoard->board_id);

            // Kiểm tra quyền truy cập
            if (!$this->boardRepository->userHasAccess($board->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thêm list vào board này',
                    'type' => 'Unauthorized',
                ], 403);
            }

            // Xác định position nếu không có
            // Nếu có thay đổi vị trí, cập nhật lại vị trí
            if (isset($input['position'])) {
                $this->updateListPosition($listBoard, $input['position']);
            }
            // Tạo list mới
            $dataList = [
                'board_id' => $board->id,
                'title' => $input['title'] ?? $listBoard->title,
                'position' => $input['position'] ?? $listBoard->position
            ];

            $this->listBoardRepository->updateListBoard($dataList, $id);

            return response()->json([
                'success' => true,
                'data' => $dataList,
                'message' => 'Cập nhập list thành công',
                'type' => 'update_list_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhập list',
                'type' => 'error_update_list',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    // Hàm cập nhật vị trí list
    private function updateListPosition($list, $newPosition)
    {
        if ($newPosition < 1) {
            throw ValidationException::withMessages(['position' => 'Vị trí không hợp lệ']);
        }

        $maxPosition = ListBoard::max('position');
        if ($newPosition > $maxPosition) {
            throw ValidationException::withMessages(['position' => 'Vị trí vượt quá giới hạn']);
        }

        // Nếu di chuyển lên hoặc xuống, cập nhật lại vị trí của các list khác
        if ($newPosition < $list->position) {
            ListBoard::whereBetween('position', [$newPosition, $list->position - 1])
                ->increment('position');
        } else {
            ListBoard::whereBetween('position', [$list->position + 1, $newPosition])
                ->decrement('position');
        }

        $list->update(['position' => $newPosition]);
    }

    /**
     * Xóa workspace.
     */
    public function destroy($id)
    {
        try {
            $listBoard = $this->listBoardRepository->show($id);
            if (!$listBoard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy listBoard',
                    'type' => 'listBoard_not_found',
                ], 404);
            }

            // Kiểm tra xem user có quyền xóa hay không
            if (!Auth::user()->boards()->where('board_id', $listBoard->board_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa',
                    'type' => 'unauthorized',
                ], 403);
            }

            $this->listBoardRepository->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'listBoard được xóa thành công',
                'type' => 'delete_listBoard_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa listBoard',
                'type' => 'error_delete_listBoard',
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
            $user = Auth::user();
            // Chỉ owner hoặc admin mới có quyền mời user
            $isAdmin = $this->workspaceUserRepository->checkRoleAdmin($user->id, $workspaceId);

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

    /**
     * Update positions for multiple lists at once.
     */
    public function updatePositions(UpdateListPositionsRequest $request)
    {
        try {
            $positions = $request->input('positions');

            // Get the first list to check board access
            $firstList = $this->listBoardRepository->show($positions[0]['id']);
            if (!$firstList) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy list',
                    'type' => 'list_not_found',
                ], 404);
            }

            // Check if user has access to the board
            if (!$this->boardRepository->userHasAccess($firstList->board_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật vị trí list',
                    'type' => 'Unauthorized',
                ], 403);
            }

            // Verify all lists belong to the same board
            foreach ($positions as $position) {
                $list = $this->listBoardRepository->show($position['id']);
                if ($list->board_id !== $firstList->board_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tất cả list phải thuộc cùng một board',
                        'type' => 'invalid_board',
                    ], 400);
                }
            }

            $this->listBoardRepository->updatePositions($positions);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật vị trí list thành công',
                'type' => 'update_positions_success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật vị trí list',
                'type' => 'error_update_positions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
