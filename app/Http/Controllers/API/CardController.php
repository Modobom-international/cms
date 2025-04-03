<?php
namespace App\Http\Controllers\API;

use App\Http\Requests\CartRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartUpdateRequest;
use App\Repositories\CardRepository;
use App\Repositories\LabelRepository;
use App\Repositories\ListBoardRepository;
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
    
    public function __construct(
        ListBoardRepository $listBoardRepository,
        UserRepository $userRepository,
        CardRepository $cardRepository,
        LabelRepository $labelRepository
    )
    {
        $this->listBoardRepository = $listBoardRepository;
        $this->userRepository = $userRepository;
        $this->cardRepository = $cardRepository;
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
        
        $cards = $listBoard->cards();
    
        return response()->json([
            'success' => true,
            'cards' => $cards,
            'message' => 'Danh sách cards',
            'type' => 'list_cards',
        ], 403);
    }
    
    // 📌 2️⃣ Tạo Card mới
    public function store(CartRequest $request)
    {
        try{
            $input = $request->except('token');
            $listBoard = $this->listBoardRepository->show($input['list_id']);
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
            
            $maxPosition = $this->cardRepository->maxPosition($input['list_id']);
            $position = is_null($maxPosition) ? 0 : $maxPosition + 1;
            $card = [
                'list_id' => $input['list_id'],
                'title' => $input['title'],
                'position' => $position,
                'description' => $input['description'] ?? "",
                'due_date' => $input['due_date'] ?? "",
           ];
        
            $dataCard = $this->cardRepository->createCard($card);
            return response()->json([
                'success' => true,
                'data' => $dataCard,
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
                'due_date' => $input['due_date'] ?? "",
                'list_id' => $card->id,
            ];
        
            $dataCard = $this->cardRepository->updateCard($card, $id);
        
            return response()->json([
                'success' => true,
                'data' => $dataCard,
                'message' => 'Tạo card thành công',
                'type' => 'success_update_card',
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
    
    public function assignMember(Request $request, $cardId)
    {
    
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        try{
            $card = $this->cardRepository->show($cardId);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }
            
            // Kiểm tra quyền truy cập vào board
            if  (!Auth::user()->boards()->where('board_id', $card->listBoard->board_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa',
                    'type' => 'unauthorized',
                ], 403);
            }
            
            $user = $this->userRepository->find($request->user_id);
            if(!$user)
            {
                return response()->json([
                    'success' => false,
                    'message' => 'User không tồn tại',
                    'type' => 'user_not_found',
                ], 404);
            }
            // Kiểm tra user đã được assign chưa
            if ($card->members()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User đã được assign',
                    'type' => 'user_assigned',
                ], 400);
            }
            
            // Gán user vào card
            $card->members()->attach($user->id);
            
            return response()->json([
                'success' => false,
                'message' => 'Assign member thành công',
                'type' => 'assign_member_success',
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
    
            // Kiểm tra quyền truy cập vào board
            if  (!Auth::user()->boards()->where('board_id', $card->listBoard->board_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa',
                    'type' => 'unauthorized',
                ], 403);
            }
    
            if (!$card->members()->where('user_id', $userId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member không thuộc card này.',
                    'type' => 'user_not_found',
                ], 400);
            }
    
            $card->members()->detach($userId);
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa member khỏi card thành công',
                'type' => 'Member_delete_success',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa member',
                'type' => 'error_delete_member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function addLabel(Request  $request, $cardId)
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
        
            $user = Auth::user();
            // Kiểm tra user có thuộc board chứa card này không
            if (!$card->listBoard || !$card->listBoard->board || !$card->listBoard->boards->users->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền',
                    'type' => 'Unauthorized',
                ], 400);
            }
        
            // Gán label vào card
            $card->labels()->attach($label);
        
            return response()->json([
                'success' => true,
                'message' => 'Tạo label thành công',
                'type' => 'create_label_success',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thêm label vào card',
                'type' => 'error_add_label_to_card',
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
        
            $user = Auth::user();
            // Kiểm tra user có thuộc board chứa card này không
            if (!$card->listBoard || !$card->listBoard->board || !$card->listBoard->boards->users->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền',
                    'type' => 'Unauthorized',
                ], 400);
            }
            
            // Xóa label khỏi card
            $card->labels()->detach($label);
            
            return response()->json([
                'success' => true,
                'message' => ' xóa label thành công',
                'type' => 'delete_label_to_card_success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa label ',
                'type' => 'error_delete_label_to_card',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
