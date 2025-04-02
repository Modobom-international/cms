<?php
namespace App\Http\Controllers\API;

use App\Http\Requests\CartRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartUpdateRequest;
use App\Models\ListModel;
use App\Repositories\CardRepository;
use App\Repositories\ListBoardRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CardController extends Controller
{
    protected $listBoardRepository;
    protected $cardRepository;
    
    public function __construct(
        ListBoardRepository $listBoardRepository,
        CardRepository $cardRepository
    )
    {
        $this->listBoardRepository = $listBoardRepository;
        $this->cardRepository = $cardRepository;
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
    
        // Kiểm tra xem user có quyền xóa hay không
        if (!Auth::user()->boards()->where('board_id', $listBoard->board_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa',
                'type' => 'unauthorized',
            ], 403);
        }
        
        $cards = $listBoard->cards();
    
        return response()->json([
            'success' => false,
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
}
