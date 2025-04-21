<?php

namespace App\Http\Controllers\API;

use App\Enums\DueDate;
use App\Http\Controllers\Controller;
use App\Http\Requests\DueDateRequest;
use App\Jobs\UpdateDueDateStatus;
use App\Models\Card;
use App\Repositories\CardRepository;
use App\Repositories\DueDateRepository;
use App\Repositories\LogActivityUserRepository;
use Illuminate\Support\Facades\Auth;

class DueDateController extends Controller
{
    protected $cardRepository;
    protected $dueDateRepository;
    protected $logActivityUserRepository;
    
    public function __construct(
        LogActivityUserRepository $logActivityUserRepository,
        CardRepository $cardRepository,
        DueDateRepository $dueDateRepository
    )
    {
        $this->logActivityUserRepository = $logActivityUserRepository;
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
    
        $dueDate = $this->dueDateRepository->store($data);
        $this->logActivity($cardId, 'create', $dueDate->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Tạo due date thành công',
            'type' => 'success_create_due_date',
            'data' => $data
        ], 200);
    }
    
    // 3. Cập nhật
    public function update(DueDateRequest $request, $id)
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
        $this->logActivity($card->id, 'update', $dueDate->id);
        
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
        $this->logActivity($card->id, 'delete', $dueDate->id);
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
        $card = $this->cardRepository->show($dueDate->card->id);
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
        $this->logActivity($card->id, $dueDate->is_completed ? '1' : '0', $dueDate->id);
        UpdateDueDateStatus::dispatch($dueDate);
        return response()->json([
            'success' => true,
            'message' => 'dueDate status đã được update.',
            'type' => 'success_update_status_dueDate',
        ],201);
    }
    
    private function logActivity($cardId, $type, $dueDateId)
    {
        $messages = [
            'create' => 'đã thêm due date vào thẻ',
            'update' => 'đã thay đổi due date của thẻ',
            'complete' => 'đã đánh dấu hoàn thành của thẻ',
            'uncomplete' => 'đã bỏ đánh dấu hoàn thành của thẻ',
            'delete' => 'đã xoá due date của thẻ',
        ];
        
        $log = [
            'user_id' => auth()->id(),
            'card_id' => $cardId,
            'target_id' => $dueDateId,
            'target_type' => 'due_date',
            'action' => $type,
            'content' => Auth::user()->name . $messages[$type] ?? '',
        ];
        
        $this->logActivityUserRepository->create($log);
        
    }
}
