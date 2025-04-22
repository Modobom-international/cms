<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttachmentRequest;
use App\Models\Card;
use App\Repositories\AttachmentRepository;
use App\Repositories\CardRepository;
use App\Repositories\LogActivityUserRepository;
use Illuminate\Support\Facades\Auth;

class AttachmentController extends Controller
{
    protected $logActivityUserRepository;
    protected $attachmentRepository;
    protected $cardRepository;
    protected $utility;
    
    public function __construct(
        LogActivityUserRepository $logActivityUserRepository,
        AttachmentRepository $attachmentRepository,
        CardRepository $cardRepository,
        Utility $utility
    )
    {
        $this->logActivityUserRepository = $logActivityUserRepository;
        $this->attachmentRepository = $attachmentRepository;
        $this->cardRepository = $cardRepository;
        $this->utility = $utility;
    }
    protected function userHasAccessToCard(Card $card): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        $boardId = optional($card->listBoard)->board_id;
        
        if (!$boardId) return false;
        
        return $user->boards()->where('board_id', $boardId)->exists();
    }
    
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
        
        if (!$this->userHasAccessToCard($card)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tạo ',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $attachments = $this->attachmentRepository->index($card);
        return response()->json([
            'success' => true,
            'message' => 'Danh sách attachments',
            'type' => 'list_attachments',
            'data' => $attachments
        ], 200);
    }
    
    public function store(AttachmentRequest $request, $cardId)
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
                'message' => 'Bạn không có quyền',
                'type' => 'unauthorized',
            ], 403);
        }
        
        if (isset($input['file_path'])) {
            $img = $this->utility->saveFileAttachment($input);
            if ($img) {
                $path = '/file/attachment/' . $input['file_path']->getClientOriginalName();
                $input['file_path'] = $path;
            }
        }
        
        $attachment = [
            'card_id' => $cardId,
            'user_id' => auth()->id(),
            'file_path' => $path,
            'title' => $input['title'] ?? "",
            'url' => $input['url'] ?? "",
        ];
        
        $this->attachmentRepository->store($attachment);
        
        // Optionally: log activity
        $this->logActivity($cardId, 'create', $attachment->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Tải file lên thành công',
            'attachment' => $attachment,
            'type' => 'success_create_attachment',
        ],201);
    }
    
    public function update(AttachmentRequest $request, $id)
    {
        $input = $request->except('token');
        $attachment = $this->attachmentRepository->show($id);
        if (!$attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy attachment',
                'type' => 'attachment_not_found',
            ], 404);
        }
    
        $card = $this->cardRepository->show($attachment->card_id);
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
                'message' => 'Bạn không có quyền',
                'type' => 'unauthorized',
            ], 403);
        }
        
        if (isset($input['file_path'])) {
            $img = $this->utility->saveFileAttachment($input);
            if ($img) {
                $path = '/file/attachment/' . $input['file_path']->getClientOriginalName();
                $input['file_path'] = $path;
            }
        }
        
        $attachment = [
            'card_id' => $cardId,
            'user_id' => auth()->id(),
            'file_path' => $path,
            'title' => $input['title'] ?? "",
            'url' => $input['url'] ?? "",
        ];
        
        $this->attachmentRepository->update($attachment, $id);
        
        // Optionally: log activity
        $this->logActivity($cardId, 'update', $attachment->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Tải file lên thành công',
            'type' => 'success_update_attachment',
        ],201);
    }
    
    public function destroy($id)
    {
        $attachment = $this->attachmentRepository->show($id);
        if (!$attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy attachment',
                'type' => 'attachment_not_found',
            ], 404);
        }
        
        $card = $this->cardRepository->show($attachment->card_id);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        if (!$this->userHasAccessToCard($card->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền ',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $this->attachmentRepository->destroy($id);
        $this->logActivity($card->id, 'delete', $attachment->id);
        return response()->json([
            'success' => true,
            'message' => 'dueDate đã được xoá.',
            'type' => 'success_delete_attachment',
        ],201);
    }
    private function logActivity($cardId, $type, $attachmentId)
    {
        $messages = [
            'create' => 'đã thêm attachment vào thẻ',
            'update' => 'đã thay đổi attachment của thẻ',
            'delete' => 'đã xoá attachment của thẻ',
        ];
        
        $log = [
            'user_id' => auth()->id(),
            'card_id' => $cardId,
            'target_id' => $attachmentId,
            'target_type' => 'attachment',
            'action' => $type,
            'content' => Auth::user()->name . $messages[$type] ?? '',
        ];
        
        $this->logActivityUserRepository->create($log);
    }
    
}
