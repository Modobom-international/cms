<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Models\Card;
use App\Repositories\CardRepository;
use App\Repositories\CommentRepository;

class CommentController extends Controller
{
    protected $cardRepository;
    protected $commentRepository;
    
    public function __construct(
        CardRepository $cardRepository,
        CommentRepository $commentRepository
    )
    {
        $this->cardRepository = $cardRepository;
        $this->commentRepository = $commentRepository;
    }
    
    protected function userHasAccessToCard(Card $card): bool
    {
        $user = auth()->user();
        if (!$user) return false;
    
        $boardId = optional($card->listBoard)->board_id;
    
        if (!$boardId) return false;
    
        return $user->boards()->where('board_id', $boardId)->exists();
    }
    
    // 1. Lấy danh sách comment theo card
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
        
        $comments = $this->commentRepository->index($card);
        return response()->json([
            'success' => true,
            'message' => 'Danh sách comments',
            'type' => 'list_comment',
            'data' => $comments
        ], 200);
    }
    
    // 2. Tạo comment mới
    public function store(CommentRequest $request, $cardId)
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
        
        $comment = [
            'user_id' => auth()->id(),
            'content' => $input['content'],
            'card_id' => $card->id
        ];
        
        $this->commentRepository->storeComment($comment);
    
        return response()->json([
            'success' => true,
            'message' => 'Tạo comment thành công',
            'type' => 'success_create_comment',
            'data' => $comment
        ], 200);
    }
    
    // 3. Cập nhật comment
    public function update(CommentRequest $request, $id)
    {
        $input = $request->except('token');
        $comment = $this->commentRepository->show($id);
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy comment',
                'type' => 'comment_not_found',
            ], 404);
        }
    
        $card = $this->cardRepository->show($comment->card_id);
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
        
        if ($comment->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền update vì bạn không tạo comment này',
                'type' => 'unauthorized',
            ], 403);
        }
    
        $commentUpdate = $this->commentRepository->updateComment($input, $id);
    
        return response()->json([
            'success' => true,
            'message' => 'Comment đã được cập nhật.',
            'type' => 'success_update_comment',
            'data' => $commentUpdate
        ], 201);
    }
    
    // 4. Xóa comment
    public function destroy($id)
    {
        $comment = $this->commentRepository->show($id);
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy comment',
                'type' => 'comment_not_found',
            ], 404);
        }
    
        $card = $this->cardRepository->show($comment->card);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        if (!$this->userHasAccessToCard($comment->card)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền update',
                'type' => 'unauthorized',
            ], 403);
        }
    
        if ($comment->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa vì bạn không tạo comment này',
                'type' => 'unauthorized',
            ], 403);
        }
    
        $this->commentRepository->destroy($id);
        
        return response()->json([
            'success' => true,
            'message' => 'comment đã được xoá.',
            'type' => 'success_delete_comment',
        ],201);
    }
    
    // 5. Trả lời comment (reply)
    public function reply(CommentRequest $request, $id)
    {
        $input = $request->except('token');
        $comment = $this->commentRepository->show($id);
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy comment',
                'type' => 'comment_not_found',
            ], 404);
        }
    
        $card = $this->cardRepository->show($comment->card_id);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        // Kiểm tra quyền user trong board chứa card
        if (!$this->userHasAccessToCard($card)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền update',
                'type' => 'unauthorized',
            ], 403);
        }
    
        // Tạo reply
        $reply = [
            'card_id' => $card->id,
            'user_id' => auth()->id(),
            'content' => $input['content'],
            'parent_id' => $comment->id,
        ];
        $this->commentRepository->storeComment($reply);
    
        return response()->json([
            'success' => true,
            'message' => 'Reply đã được thêm.',
            'data' => $reply,
            'type' => 'success_create_reply',
        ],201);
    }
}
