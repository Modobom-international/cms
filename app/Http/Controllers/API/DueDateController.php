<?php

namespace App\Http\Controllers\API;

use App\Enums\DueDate;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\DueDateRequest;
use App\Jobs\UpdateCardReminderColor;
use App\Jobs\UpdateDueDateStatus;
use App\Models\Card;
use App\Repositories\CardRepository;
use App\Repositories\DueDateRepository;

class DueDateController extends Controller
{
    protected $cardRepository;
    protected $dueDateRepository;
    
    public function __construct(
        CardRepository $cardRepository,
        DueDateRepository $dueDateRepository
    )
    {
        $this->cardRepository = $cardRepository;
        $this->dueDateRepository = $dueDateRepository;
    }
    
    protected function userHasAccessToCard(Card $card): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        $boardId = optional($card->listBoard)->board_id;
        
        if (!$boardId) return false;
        
        return $user->boards()->where('board_id', $boardId)->exists();
    }
    
    // 2. Tạo  mới
    public function store(DueDateRequest $request, $cardId)
    {
        $input = $request->except('token');
        $card = $this->cardRepository->show($cardId);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        
        if (!$this->userHasAccessToCard($card)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tạo ',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $data = [
            'start_date' => $input['start_date'],
            'due_date' => $input['due_date'],
            'card_id' => $card->id,
            'due_reminder' => $input['due_reminder'],
            'is_completed' => DueDate::NOT_COMPETE
        ];
    
        $this->dueDateRepository->store($data);
        
        return response()->json([
            'success' => true,
            'message' => 'Tạo due date thành công',
            'type' => 'success_create_due_date',
            'data' => $data
        ], 200);
    }
    
    // 3. Cập nhật
    public function update(CommentRequest $request, $id)
    {
        $input = $request->except('token');
        $dueDate = $this->dueDateRepository->show($id);
        if (!$dueDate) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy due date',
                'type' => 'due_date_not_found',
            ], 404);
        }
       
        
        $card = $this->cardRepository->show($dueDate->card_id);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        if (!$this->userHasAccessToCard($card)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền update',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $this->dueDateRepository->update($input, $id);
        
        return response()->json([
            'success' => true,
            'message' => 'Due Date đã được cập nhật.',
            'type' => 'success_update_dua_date',
        ], 201);
    }
    
    // 4. Xóa comment
    public function destroy($id)
    {
        $dueDate = $this->dueDateRepository->show($id);
        if (!$dueDate) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy dueDate',
                'type' => 'dueDate_not_found',
            ], 404);
        }
        
        $card = $this->cardRepository->show($dueDate->card);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        if (!$this->userHasAccessToCard($dueDate->card)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền ',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $this->dueDateRepository->destroy($id);
        
        return response()->json([
            'success' => true,
            'message' => 'dueDate đã được xoá.',
            'type' => 'success_delete_dueDate',
        ],201);
    }
    
    public function toggleComplete( $id)
    {
        $dueDate = $this->dueDateRepository->show($id);
        if (!$dueDate) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy dueDate',
                'type' => 'dueDate_not_found',
            ], 404);
        }
        $card = $this->cardRepository->show($dueDate->card);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        if (!$this->userHasAccessToCard($dueDate->card)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền ',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $dueDate->is_completed = !$dueDate->is_completed;
        $dueDate->save();
        
        UpdateDueDateStatus::dispatch($dueDate);
        return response()->json([
            'success' => true,
            'message' => 'dueDate status đã được update.',
            'type' => 'success_update_status_dueDate',
        ],201);
    }
}
