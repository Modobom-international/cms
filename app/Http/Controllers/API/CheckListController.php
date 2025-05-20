<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChecklistRequest;
use App\Models\Card;
use App\Repositories\CardRepository;
use App\Repositories\CheckListRepository;
use App\Repositories\LogActivityUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckListController extends Controller
{
    protected $cardRepository;
    protected $checkListRepository;
    protected $logActivityUserRepository;

    public function __construct(
        CardRepository $cardRepository,
        LogActivityUserRepository $logActivityUserRepository,
        CheckListRepository $checkListRepository
    ) {
        $this->cardRepository = $cardRepository;
        $this->checkListRepository = $checkListRepository;
        $this->logActivityUserRepository = $logActivityUserRepository;

    }

    protected function userHasAccessToCard(Card $card): bool
    {
        $user = auth()->user();
        if (!$user)
            return false;

        $boardId = optional($card->listBoard)->board_id;

        if (!$boardId)
            return false;

        return $user->boards()->where('board_id', $boardId)->exists();
    }

    // Lấy danh sách checklist theo card
    public function index($cardId)
    {
        $card = $this->cardRepository->show($cardId);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }

        $checklists = $this->checkListRepository->index($cardId);

        return response()->json([
            'success' => true,
            'message' => 'Danh sách checklists',
            'type' => 'list_checklists',
            'data' => $checklists
        ], 200);
    }

    // Tạo checklist mới
    public function store(ChecklistRequest $request, $id)
    {
        try {
            $input = $request->except('token');
            $card = $this->cardRepository->show($id);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
            // Kiểm tra user có thuộc board chứa card này không
            // if (!$this->userHasAccessToCard($card)) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Bạn không có quyền tạo ',
            //         'type' => 'unauthorized',
            //     ], 403);
            // }

            if (!$this->cardRepository->userCanEdit($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật vị trí card',
                    'type' => 'Unauthorized',
                ], 403);
            }
            $data = [
                'card_id' => $card->id,
                'title' => $input['title'],
            ];
            $checkList = $this->checkListRepository->storeCheckList($data);
            $log = [
                'user_id' => Auth::user()->id,
                'card_id' => $card->id,
                'action_type' => 'create',
                'target_type' => 'Create checklist',
                'target_id' => $checkList->id,
                'content' => Auth::user()->name . ' tạo checklist vào card ' . $card->title ?? '',
            ];

            $this->logActivityUserRepository->create($log);

            return response()->json([
                'success' => true,
                'message' => 'Checklist được tạo thành công.',
                'type' => 'success_create_checkList',
                'data' => $checkList
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo checkList',
                'type' => 'error_create_check_list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Cập nhật checklist
    public function update(Request $request, $id)
    {
        try {
            $input = $request->except('token');
            $checkList = $this->checkListRepository->show($id);
            if (!$checkList) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy checkList',
                    'type' => 'checkList_not_found',
                ], 404);
            }
            // Kiểm tra user có thuộc board chứa card này không
            if (!$this->userHasAccessToCard($checkList->card)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo ',
                    'type' => 'unauthorized',
                ], 403);
            }

            $checkList = $this->checkListRepository->storeCheckList($input);
            $log = [
                'user_id' => Auth::user()->id,
                'card_id' => $checkList->card,
                'action_type' => 'update',
                'target_type' => 'Update checklist',
                'target_id' => $checkList->id,
                'content' => Auth::user()->name . ' cập nhập checklist vào card ' . $checkList->card->title ?? '',
            ];

            $this->logActivityUserRepository->create($log);

            $updateCheckList = $this->checkListRepository->updateCheckList($input, $id);

            return response()->json([
                'success' => true,
                'message' => 'Checklist đã được cập nhật.',
                'type' => 'success_update_checkList',
                'data' => $updateCheckList
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi update checkList',
                'type' => 'error_update_check_list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Xoá checklist
    public function destroy($id)
    {
        $checkList = $this->checkListRepository->show($id);
        if (!$checkList) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy checkList',
                'type' => 'checkList_not_found',
            ], 404);
        }
        // Kiểm tra user có thuộc board chứa card này không
        if (!$this->userHasAccessToCard($checkList->card)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tạo ',
                'type' => 'unauthorized',
            ], 403);
        }
        $checkList = $this->checkListRepository->destroy($id);

        $log = [
            'user_id' => Auth::user()->id,
            'card_id' => $checkList->card,
            'action_type' => 'Delete',
            'target_type' => 'Delete checklist',
            'target_id' => $checkList->id,
            'content' => Auth::user()->name . ' xóa checklist từ card ' . $checkList->card->title ?? '',
        ];

        $this->logActivityUserRepository->create($log);
        return response()->json([
            'success' => true,
            'message' => 'Checklist đã được xoá.',
            'type' => 'success_delete_checkList',
        ], 201);
    }
}

