<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChecklistRequest;
use App\Repositories\CardRepository;
use App\Repositories\CheckListRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChecklistController extends Controller
{
    protected $cardRepository;
    protected $checkListRepository;
    
    public function __construct(
        CardRepository $cardRepository,
        CheckListRepository $checkListRepository
    )
    {
        $this->cardRepository = $cardRepository;
        $this->checkListRepository = $checkListRepository;
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
    public function store(ChecklistRequest $request, $cardId)
    {
        try{
            $input = $request->except('token');
            $card = $this->cardRepository->show($cardId);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
            // Lấy board_id thông qua quan hệ: card → list → board
            $boardId = optional($card->listBoard)->board_id;
        
            if (!$boardId || !Auth::user()->boards()->where('board_id', $boardId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo checklist trong card này.',
                    'type' => 'unauthorized',
                ], 403);
            }
            
            $checkList = $this->checkListRepository->storeCheckList($input);
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
            // Kiểm tra quyền truy cập vào board
            $user = Auth::user();
            $boardId = optional(optional($checkList->card)->listBoard)->board_id;
        
            if (!$boardId || !$user->boards()->where('board_id', $boardId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền chỉnh sửa checklist này.',
                    'type' => 'unauthorized',
                ], 403);
            }
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
        $card = $checkList->card;
        // Lấy board_id thông qua quan hệ: checklist → card → list → board
        $boardId = optional($card->listBoard)->board_id;
    
        if (!$boardId || !Auth::user()->boards()->where('board_id', $boardId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa checklist này.',
                'type' => 'unauthorized',
            ], 403);
        }
        $checkList = $this->checkListRepository->destroy($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Checklist đã được xoá.',
            'type' => 'success_delete_checkList',
        ],201);
    }
}

