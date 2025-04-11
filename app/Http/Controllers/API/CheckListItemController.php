<?php

namespace App\Http\Controllers\API;

use App\Enums\CheckListItem;
use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Repositories\CheckListItemRepository;
use App\Repositories\CheckListRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ChecklistItemRequest;
use Illuminate\Support\Facades\Validator;

class ChecklistItemController extends Controller
{
    
    protected $checkListItemRepository;
    protected $checkListRepository;
    
    public function __construct(
        CheckListItemRepository $checkListItemRepository,
        CheckListRepository $checkListRepository
    )
    {
        $this->checkListItemRepository = $checkListItemRepository;
        $this->checkListRepository = $checkListRepository;
    }
    
    protected function userHasAccessToChecklist(Checklist $checklist): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        $boardId = optional($checklist->card->listBoard)->board_id;
        if (!$boardId) {
            return false;
        }
        return $user->boards()->where('board_id', $boardId)->exists();
    }
    
    public function index($checklistId)
    {
        $checkList = $this->checkListRepository->show($checklistId);
        if (!$checkList) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy checkList',
                'type' => 'checkList_not_found',
            ], 404);
        }
        
        if (!$this->userHasAccessToChecklist($checkList)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền ',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $checkListItem = $checkList->items;
        return response()->json([
            'success' => true,
            'items' => $checkListItem,
            'message' => 'checkListItem',
            'type' => 'checkListItem',
        ],201);
    }
    
    public function store(ChecklistItemRequest $request, $checklistId)
    {
        try {
            $input = $request->except('token');
            $checkList = $this->checkListRepository->show($checklistId);
            if (!$checkList) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy checkList',
                    'type' => 'checkList_not_found',
                ], 404);
            }
            
            if (!$this->userHasAccessToChecklist($checkList)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền ',
                    'type' => 'unauthorized',
                ], 403);
            }
            
            $item = [
                'content' => $input['content'],
                'is_completed' => CheckListItem::NOT_COMPETE,
                'checklist_id' => $checklistId,
            ];
            
            $checkListItem = $this->checkListItemRepository->storeItem($item);
            
            return response()->json([
                'success' => true,
                'message' => 'Tạo item thành công',
                'data' => $checkListItem,
                'type' => 'success_create_item'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo checkList Item',
                'type' => 'error_create_checklistItem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function update(ChecklistItemRequest $request, $checklistId, $itemId)
    {
        try {
        
        $input = $request->except('token');
        $checkList = $this->checkListRepository->show($checklistId);
        if (!$checkList) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy checkList',
                'type' => 'checkList_not_found',
            ], 404);
        }
        
        $item = $this->checkListItemRepository->show($itemId);
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy item',
                'type' => 'item_not_found',
            ], 404);
        }
        if (!$this->userHasAccessToChecklist($checkList)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền ',
                'type' => 'unauthorized',
            ], 403);
        }
        $checkListItem = $this->checkListItemRepository->updateItem($input, $itemId);
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công',
            'type' => 'success_update_item'
        ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi update checkList Item',
                'type' => 'error_update_checklistItem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function toggle(Request $request, $checklistId, $itemId)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'is_completed' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $checkList = $this->checkListRepository->show($checklistId);
        if (!$checkList) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy checkList',
                'type' => 'checkList_not_found',
            ], 404);
        }
    
        $item = $this->checkListItemRepository->show($itemId);
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy item',
                'type' => 'item_not_found',
            ], 404);
        }
        if (!$this->userHasAccessToChecklist($checkList)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền ',
                'type' => 'unauthorized',
            ], 403);
        }
        if ($item->is_completed) {
            $item->is_completed = 0;
        } else {
            $item->is_completed = 1;
        }
        $item->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $item
        ]);
    }
    
    public function destroy($checklistId, $itemId)
    {
        $checkList = $this->checkListRepository->show($checklistId);
        if (!$checkList) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy checkList',
                'type' => 'checkList_not_found',
            ], 404);
        }
    
        $item = $this->checkListItemRepository->show($itemId);
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy item',
                'type' => 'item_not_found',
            ], 404);
        }
        if (!$this->userHasAccessToChecklist($checkList)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền ',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $item = $this->checkListItemRepository->destroy($itemId);
        
        return response()->json([
            'success' => true,
            'message' => 'Xóa item thành công'
        ]);
    }
}
