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
    ) {
        $this->logActivityUserRepository = $logActivityUserRepository;
        $this->attachmentRepository = $attachmentRepository;
        $this->cardRepository = $cardRepository;
        $this->utility = $utility;
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
        $attachments = $this->attachmentRepository->index($card->id);
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

        // Validate that either file_path or url is provided
        if (!isset($input['file_path']) && !isset($input['url'])) {
            return response()->json([
                'success' => false,
                'message' => 'Phải cung cấp file hoặc url',
                'type' => 'validation_error',
            ], 422);
        }
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

        $path = null;
        if (isset($input['file_path'])) {

            $input['file_pathname'] = $card->id . '/' . $input['file_path']->getClientOriginalName();
            $img = $this->utility->saveFileAttachment($input);
            if ($img) {
                $path = '/file/attachment/' . $input['file_pathname'];
            }
        }

        $attachment = [
            'card_id' => $cardId,
            'user_id' => auth()->id(),
            'file_path' => $path,
            'title' => $input['title'] ?? "",
            'url' => $input['url'] ?? "hi",
        ];
        $attachment = $this->attachmentRepository->store($attachment);

        // Optionally: log activity
        // $this->logActivity($cardId, 'create', $attachment->id);

        return response()->json([
            'success' => true,
            'message' => 'Tải file lên thành công',
            'attachment' => $attachment,
            'url' => $input['url'] ?? null,
            'type' => 'success_create_attachment',
        ], 201);
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
            'card_id' => $card->id,
            'user_id' => auth()->id(),
            'file_path' => $path,
            'title' => $input['title'] ?? "",
            'url' => $input['url'] ?? null,
        ];

        $this->attachmentRepository->update($attachment, $id);

        // Optionally: log activity
        // $this->logActivity($card->id, 'update', $attachment->id);

        return response()->json([
            'success' => true,
            'message' => 'Tải file lên thành công',
            'type' => 'success_update_attachment',
        ], 201);
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
        // if (!$this->userHasAccessToCard($card->id)) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Bạn không có quyền ',
        //         'type' => 'unauthorized',
        //     ], 403);
        // }
        if (!$this->cardRepository->userCanEdit($card->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xoá attachment',
                'type' => 'unauthorized',
            ], 403);
        }
        // Only attempt to delete file if it exists
        if ($attachment->file_path) {
            try {
                $this->utility->deleteFileAttachment($attachment->file_path);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi xoá file',
                    'type' => 'error_delete_file',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        // Only delete from database if file deletion was successful or if there was no file
        try {
            $this->attachmentRepository->destroy($id);
            // $this->logActivity($card->id, 'delete', $attachment->id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xoá attachment',
                'type' => 'error_delete_attachment',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Attachment đã được xoá.',
            'type' => 'success_delete_attachment',
        ], 201);
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
