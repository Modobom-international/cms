<?php
namespace App\Http\Controllers\API;

use App\Http\Requests\AssignMultipleMembersRequest;
use App\Http\Requests\CartRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartUpdateRequest;
use App\Models\Card;
use App\Models\User;
use App\Repositories\CardRepository;
use App\Repositories\LabelRepository;
use App\Repositories\ListBoardRepository;
use App\Repositories\LogActivityUserRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CardController extends Controller
{
    protected $listBoardRepository;
    protected $cardRepository;
    protected $userRepository;
    protected $labelRepository;
    protected $logActivityUserRepository;
    
    public function __construct(
        ListBoardRepository $listBoardRepository,
        UserRepository $userRepository,
        CardRepository $cardRepository,
        LogActivityUserRepository $logActivityUserRepository,
        LabelRepository $labelRepository
    )
    {
        $this->listBoardRepository = $listBoardRepository;
        $this->userRepository = $userRepository;
        $this->cardRepository = $cardRepository;
        $this->logActivityUserRepository = $logActivityUserRepository;
        $this->labelRepository = $labelRepository;
    }
    
    // 📌 1️⃣ Lấy danh sách Card theo List
    public function index($listId)
    {
        $listBoard = $this->listBoardRepository->show($listId);
    
        if(!$listBoard) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy listBoard',
                'type' => 'listBoard_not_found',
            ], 404);
        }
    
        // Kiểm tra xem user có quyền hay không
        if (!Auth::user()->boards()->where('board_id', $listBoard->board_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $cards = $listBoard->cards;
    
        return response()->json([
            'success' => true,
            'cards' => $cards,
            'message' => 'Danh sách cards',
            'type' => 'list_cards',
        ], 403);
    }
    
    // 📌 2️⃣ Tạo Card mới
    public function store(CartRequest $request, $listId)
    {
        try{
            $input = $request->except('token');
            $listBoard = $this->listBoardRepository->show($listId);
            if(!$listBoard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy listBoard',
                    'type' => 'listBoard_not_found',
                ], 404);
            }
    
            // Kiểm tra quyền truy cập
            if (!Auth::user()->boards()->where('board_id', $listBoard->board_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền ',
                    'type' => 'unauthorized',
                ], 403);
            }
            
            $maxPosition = $this->cardRepository->maxPosition($listBoard->id);
            $position = is_null($maxPosition) ? 0 : $maxPosition + 1;
            $card = [
                'list_id' => $listBoard->id,
                'title' => $input['title'],
                'position' => $position,
                'description' => $input['description'] ?? "",
           ];
            
            $dataCard = $this->cardRepository->createCard($card);
            // Ghi log hoạt động
            $log = [
                'user_id' => Auth::user()->id,
                'card_id' => $dataCard->id,
                'action_type' => 'create',
                'target_type' => 'create card',
                'target_id' => $dataCard->id,
                'content' => Auth::user()->name . ' đã tạo card từ ' .$listBoard->title   ?? '',
            ];
            
            $this->logActivityUserRepository->create($log);
            
            return response()->json([
                'success' => true,
                'data' => $dataCard,
                'log' => $log,
                'message' => 'Tạo card thành công',
                'type' => 'success_create_card',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo card',
                'type' => 'error_create_card',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // 📌 3️⃣ Cập nhật Card
    public function update(CartUpdateRequest $request, $id)
    {
        try{
            $input = $request->except('token');
            $card = $this->cardRepository->show($id);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
            $listBoard = $this->listBoardRepository->show($card['list_id']);
            if(!$listBoard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy listBoard',
                    'type' => 'listBoard_not_found',
                ], 404);
            }
            // Kiểm tra quyền truy cập
            if (!Auth::user()->boards()->where('board_id', $listBoard->board_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa',
                    'type' => 'unauthorized',
                ], 403);
            }
            $maxPosition = $this->cardRepository->maxPosition($listBoard->id);
            $position = is_null($maxPosition) ? 0 : $maxPosition + 1;
        
            $card = [
                'title' => $input['title'],
                'position' => $position,
                'description' => $input['description'] ?? "",
                'list_id' => $listBoard->id,
            ];
            $dataCard = $this->cardRepository->updateCard($card, $id);
    
            // Ghi log hoạt động
            $log = [
                'user_id' => Auth::user()->id,
                'card_id' => $dataCard->id,
                'action_type' => 'update',
                'target_type' => 'Update card',
                'target_id' => $dataCard->id,
                'content' => Auth::user()->name . ' đã cập nhập card từ ' .$listBoard->title   ?? '',
            ];
    
            $this->logActivityUserRepository->create($log);
            
            return response()->json([
                'success' => true,
                'data' => $dataCard,
                'log' => $log,
                'message' => 'update card thành công',
                'type' => 'success_update_card',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi update card',
                'type' => 'error_update_card',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
      // 📌 4️⃣ Xóa Card
    public function destroy($id)
    {
        $card = $this->cardRepository->show($id);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        $this->cardRepository->destroy($id);
        return response()->json([
            'success' => true,
            'message' => 'Card được xóa thành công',
            'type' => 'delete_card_success',
        ], 201);

    }

    // 📌 5️⃣ Di chuyển Card giữa các List
    public function move(Request $request, $id)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'list_id' => 'required',
            'position' => 'required|integer|min:1',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        try{
            $card = $this->cardRepository->show($id);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
            $input = $request->except('token');
            $listBoard = $this->listBoardRepository->show($input['list_id']);
            if(!$listBoard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy listBoard',
                    'type' => 'listBoard_not_found',
                ], 404);
            }
        
            $card = $this->cardRepository->moveCard($id, $input['list_id'], $input['position']);
        
            return response()->json([
                'success' => true,
                'message' => 'Card đã được di chuyển thành công',
                'type' => 'success_move_card',
                'data' => $card
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi di chuyển card',
                'type' => 'error_move_card',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getLogsByCard($cardId)
    {
        try {
            $card = $this->cardRepository->show($cardId);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
            // Kiểm tra user có thuộc board chứa card này không
            if (!$this->userHasAccessToCard($card)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo ',
                    'type' => 'unauthorized',
                ], 403);
            }
            $logs = $this->logActivityUserRepository->listLog($card);
            
            return response()->json([
                'success' => true,
                'message' => 'Lấy log thành công',
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy activity logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    
    public function join($cardId)
    {
        $card = $this->cardRepository->show($cardId);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        
        $user = Auth::user();
        
        if ($card->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã tham gia',
                'type' => 'user_exists',
            ], 400);
        }
        
        // Attach user to card
        $card->members()->attach($user->id);
        
        // Ghi log hoạt động
        $log = [
            'user_id' => $user->id,
            'card_id' => $card->id,
            'action_type' => 'join card',
            'target_type' => 'user Join card',
            'target_id' => $card->id,
            'content' => "{$user->name} tham gia  \"{$card->title}\"",
        ];
        
        $this->logActivityUserRepository->create($log);
        return response()->json([
            'success' => true,
            'message' => 'Bạn đã tham gia thành công',
            'type' => 'join_card_success',
        ], 200);
    }
    
    
    public function leave($cardId)
    {
        $card = $this->cardRepository->show($cardId);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy card',
                'type' => 'card_not_found',
            ], 404);
        }
        $user = Auth::user();
        if (!$card->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã tham gia',
                'type' => 'user_exists',
            ], 400);
        }
        
        $card->members()->detach($user->id);
        
        // Ghi log hoạt động
        $log = [
            'user_id' => $user->id,
            'card_id' => $card->id,
            'action_type' => 'leave card',
            'target_type' => 'User leave card',
            'target_id' => $card->id,
            'content' => "{$user->name} rời khỏi  \"{$card->title}\"",
        ];
        $this->logActivityUserRepository->create($log);
        return response()->json([
            'success' => true,
            'message' => 'Bạn đã rời thành công',
            'type' => 'leave_card_success',
        ], 200);
    }
    
    public function assignMember(AssignMultipleMembersRequest $request, $cardId)
    {
        try {
            $card = $this->cardRepository->show($cardId);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
            // Kiểm tra quyền truy cập vào board chứa card
            $boardId = optional($card->listBoard)->board_id;
            if (!$boardId || !Auth::user()->boards()->where('board_id', $boardId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền hoặc dữ liệu không hợp lệ',
                    'type' => 'unauthorized',
                ], 403);
            }
            
            // Lặp qua danh sách user_id từ request để assign nhiều user
            $assigned = [];
            $skipped = [];
            
            foreach ($request->user_ids as $userId) {
                $user = $this->userRepository->find($userId);
                if (!$user) {
                    $skipped[] = [
                        'user_id' => $userId,
                        'reason' => 'User không tồn tại',
                    ];
                    continue;
                }
                
                $alreadyAssigned = $card->members()->where('user_id', $userId)->exists();
                if ($alreadyAssigned) {
                    $skipped[] = [
                        'user_id' => $userId,
                        'reason' => 'User đã được assign',
                    ];
                    continue;
                }
                $card->members()->attach($userId, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
                //log activity
                $userAssign = User::find($userId);
                $log = [
                    'user_id' => Auth::id(),
                    'card_id' => $card->id,
                    'action_type' => 'assign card',
                    'target_type' => 'Assign user to card',
                    'target_id' => $card->id,
                    'content' => Auth::user()->name . ' đã thêm ' . $userAssign->name . ' vào card "' . $card->title . '"'
                ];
                $this->logActivityUserRepository->create($log);
                $assigned[] = $userId;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Assign member hoàn tất',
                'type' => 'assign_member_success',
                'data' => [
                    'assigned' => $assigned,
                    'skipped' => $skipped,
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi assign member',
                'type' => 'error_assign_member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function removeMember($cardId, $userId)
    {
        try {
            $card = $this->cardRepository->show($cardId);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
            
            // Kiểm tra quyền truy cập vào board chứa card
            $boardId = optional($card->listBoard)->board_id;
            if (!$boardId || !Auth::user()->boards()->where('board_id', $boardId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền hoặc dữ liệu không hợp lệ',
                    'type' => 'unauthorized',
                ], 403);
            }
            
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User không tồn tại',
                    'type' => 'user_not_found',
                ], 404);
            }
            
            // Kiểm tra user đã tham gia card chưa
            if (!$card->members()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User chưa được assign vào card',
                    'type' => 'user_not_assigned',
                ], 400);
            }
            
            // Xoá user khỏi card
            $card->members()->detach($user->id);
    
            // Ghi log
            $log = [
                'user_id' => Auth::id(), // người thực hiện
                'card_id' => $card->id,
                'action_type' => 'remove user',
                'target_type' => 'Remove user from card',
                'target_id' => $card->id,
                'content' => Auth::user()->name . ' đã xóa ' . $user->name . ' khỏi card"' . $card->title . '"',
            ];
            $this->logActivityUserRepository->create($log);
            
            return response()->json([
                'success' => true,
                'message' => 'Xoá member thành công',
                'type' => 'remove_member_success',
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xoá member',
                'type' => 'error_remove_member',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    protected function userHasAccessToCard(Card $card): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        $boardId = optional($card->listBoard)->board_id;
        
        if (!$boardId) return false;
        
        return $user->boards()->where('board_id', $boardId)->exists();
    }
    
    
    public function assignLabel(Request  $request, $cardId)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'label_id' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $input = $request->except('token');
            $card = $this->cardRepository->show($cardId);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
        
            $label = $this->labelRepository->show($input['label_id']);
            if (!$label) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy label',
                    'type' => 'label_not_found',
                ], 404);
            }
            $exists = $card->labels()->where('label_id', $input['label_id'])->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'label đã tồn tại',
                    'type' => 'label_exist',
                ], 400);
            }
        
            // Kiểm tra user có thuộc board chứa card này không
            if (!$this->userHasAccessToCard($card)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo ',
                    'type' => 'unauthorized',
                ], 403);
            }
    
            // Gán label vào card
            $card->labels()->attach($label);
            // Ghi log
            $log = [
                'user_id' => Auth::id(), // người thực hiện
                'card_id' => $card->id,
                'action_type' => 'assign label',
                'target_type' => 'assign label to card',
                'target_id' => $label->id,
                'content' => Auth::user()->name . ' gán nhãn "' . $label->name . '" vào card"' . $card->title . '"',
            ];
    
            $this->logActivityUserRepository->create($log);
            
            return response()->json([
                'success' => true,
                'message' => 'Assign label thành công',
                'type' => 'assign_label_success',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi assign label vào card',
                'type' => 'error_assign_label_to_card',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // Xóa label khỏi card
    public function removeLabel($cardId, $labelId)
    {
        try {
            $card = $this->cardRepository->show($cardId);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
        
            $label = $this->labelRepository->show($labelId);
            if (!$label) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy label',
                    'type' => 'label_not_found',
                ], 404);
            }
            $exists = $card->labels()->where('label_id', $labelId)->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'label đã tồn tại',
                    'type' => 'label_exist',
                ], 400);
            }
    
            // Kiểm tra user có thuộc board chứa card này không
            if (!$this->userHasAccessToCard($card)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo ',
                    'type' => 'unauthorized',
                ], 403);
            }
            
            // Xóa label khỏi card
            $card->labels()->detach($label);
    
            // Ghi log
            $log = [
                'user_id' => Auth::id(), // người thực hiện
                'card_id' => $card->id,
                'action_type' => 'remove label',
                'target_type' => 'remove label from card',
                'target_id' => $label->id,
                'content' => Auth::user()->name . ' xóa gán nhãn "' . $label->name . '" từ card"' . $card->title . '"',
            ];
            $this->logActivityUserRepository->create($log);
            
            return response()->json([
                'success' => true,
                'message' => ' xóa label khỏi card thành công',
                'type' => 'delete_label_to_card_success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa label khỏi card ',
                'type' => 'error_delete_label_to_card',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
