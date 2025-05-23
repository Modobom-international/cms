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
use App\Http\Requests\UpdateCardPositionsRequest;

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
    ) {
        $this->listBoardRepository = $listBoardRepository;
        $this->userRepository = $userRepository;
        $this->cardRepository = $cardRepository;
        $this->logActivityUserRepository = $logActivityUserRepository;
        $this->labelRepository = $labelRepository;
    }

    // 📌 1️⃣ Lấy danh sách Card theo List
    public function index($listId)
    {
        try {
            $listBoard = $this->listBoardRepository->show($listId);
            if (!$listBoard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy listBoard',
                    'type' => 'listBoard_not_found',
                ], 404);
            }

            // Check if user has access to the board
            if (!$this->listBoardRepository->userHasAccess($listBoard->board_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem danh sách card',
                    'type' => 'unauthorized',
                ], 403);
            }

            $cards = $listBoard->cards;
            $cards->load(['labels', "members"]);

            if ($cards->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'List chưa có card nào',
                    'type' => 'list_empty',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách cards thành công',
                'type' => 'get_cards_success',
                'data' => $cards
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách cards',
                'type' => 'error_get_cards',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 📌 2️⃣ Tạo Card mới
    public function store(CartRequest $request, $listId)
    {
        try {
            $input = $request->except('token');
            $listBoard = $this->listBoardRepository->show($listId);
            if (!$listBoard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy listBoard',
                    'type' => 'listBoard_not_found',
                ], 404);
            }
            // Check edit permission for the list
            if (!$this->listBoardRepository->userCanEdit($listBoard->board->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo card trong list này',
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
                'content' => Auth::user()->name . ' đã tạo card từ ' . $listBoard->title ?? '',
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
        try {
            $input = $request->except('token');
            $card = $this->cardRepository->show($id);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }

            // Check edit permission
            if (!$this->cardRepository->userCanEdit($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền chỉnh sửa card này',
                    'type' => 'unauthorized',
                ], 403);
            }

            $cardData = [
                'title' => $input['title'],
                'description' => $input['description'] ?? "",
            ];
            $dataCard = $this->cardRepository->updateCard($cardData, $id);

            return response()->json([
                'success' => true,
                'message' => 'Card được cập nhật thành công',
                'type' => 'update_card_success',
                'data' => $dataCard
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật card',
                'type' => 'error_update_card',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 📌 4️⃣ Xóa Card
    public function destroy($id)
    {
        try {
            $card = $this->cardRepository->show($id);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }

            // Check edit permission
            if (!$this->cardRepository->userCanEdit($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa card này',
                    'type' => 'unauthorized',
                ], 403);
            }

            $this->cardRepository->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'Card được xóa thành công',
                'type' => 'delete_card_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa card',
                'type' => 'error_delete_card',
                'error' => $e->getMessage()
            ], 500);
        }
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

        try {
            $card = $this->cardRepository->show($id);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }

            // Check edit permission
            if (!$this->cardRepository->userCanEdit($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền di chuyển card này',
                    'type' => 'unauthorized',
                ], 403);
            }

            $input = $request->except('token');
            $listBoard = $this->listBoardRepository->show($input['list_id']);
            if (!$listBoard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy listBoard',
                    'type' => 'listBoard_not_found',
                ], 404);
            }

            // Check if target list is in the same board
            if ($listBoard->board_id !== $card->listBoard->board_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể di chuyển card sang board khác',
                    'type' => 'invalid_board',
                ], 400);
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

            // Check view permission
            if (!$this->cardRepository->userHasAccess($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem logs của card này',
                    'type' => 'unauthorized',
                ], 403);
            }

            $logs = $this->logActivityUserRepository->listLog($card);

            return response()->json([
                'success' => true,
                'message' => 'Lấy log thành công',
                'type' => 'get_logs_success',
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy activity logs',
                'type' => 'error_get_logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function join($cardId)
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

            $user = Auth::user();

            if ($card->members()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã là thành viên của card này',
                    'type' => 'already_member',
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
                'content' => "{$user->name} tham gia \"{$card->title}\"",
            ];

            $this->logActivityUserRepository->create($log);

            return response()->json([
                'success' => true,
                'message' => 'Bạn đã tham gia thành công',
                'type' => 'join_card_success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tham gia card',
                'type' => 'error_join_card',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function leave($cardId)
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

            $user = Auth::user();

            if (!$card->members()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không phải thành viên của card này',
                    'type' => 'not_member',
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
                'content' => "{$user->name} rời khỏi \"{$card->title}\"",
            ];
            $this->logActivityUserRepository->create($log);

            return response()->json([
                'success' => true,
                'message' => 'Bạn đã rời card thành công',
                'type' => 'leave_card_success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi rời card',
                'type' => 'error_leave_card',
                'error' => $e->getMessage()
            ], 500);
        }
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

            // Check edit permission
            if (!$this->cardRepository->userCanEdit($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thêm thành viên vào card này',
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

            // Check edit permission
            if (!$this->cardRepository->userCanEdit($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa thành viên khỏi card này',
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
                'content' => Auth::user()->name . ' đã xóa ' . $user->name . ' khỏi card "' . $card->title . '"',
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
        return $this->cardRepository->userHasAccess($card->id);
    }

    public function assignLabel(Request $request, $cardId)
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

            // Check edit permission
            if (!$this->cardRepository->userCanEdit($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền gán nhãn cho card này',
                    'type' => 'unauthorized',
                ], 403);
            }

            $label = $this->labelRepository->show($input['label_id']);
            if (!$label) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy label',
                    'type' => 'label_not_found',
                ], 404);
            }

            // Check if label belongs to the same board
            // if ($label->board_id !== $card->listBoard->board_id) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Label không thuộc board này',
            //         'type' => 'invalid_label',
            //     ], 400);
            // }

            $exists = $card->labels()->where('label_id', $input['label_id'])->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Label đã được gán cho card này',
                    'type' => 'label_exists',
                ], 400);
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
                'content' => Auth::user()->name . ' gán nhãn "' . $label->name . '" vào card "' . $card->title . '"',
            ];

            $this->logActivityUserRepository->create($log);

            return response()->json([
                'success' => true,
                'message' => 'Gán nhãn thành công',
                'type' => 'assign_label_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi gán nhãn cho card',
                'type' => 'error_assign_label',
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

            // Check edit permission
            if (!$this->cardRepository->userCanEdit($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa nhãn khỏi card này',
                    'type' => 'unauthorized',
                ], 403);
            }

            $label = $this->labelRepository->show($labelId);
            if (!$label) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy label',
                    'type' => 'label_not_found',
                ], 404);
            }

            if (!$card->labels()->where('label_id', $labelId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Card chưa được gán nhãn này',
                    'type' => 'label_not_assigned',
                ], 400);
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
                'content' => Auth::user()->name . ' xóa nhãn "' . $label->name . '" khỏi card "' . $card->title . '"',
            ];
            $this->logActivityUserRepository->create($log);

            return response()->json([
                'success' => true,
                'message' => 'Xóa nhãn thành công',
                'type' => 'remove_label_success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa nhãn khỏi card',
                'type' => 'error_remove_label',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update positions for multiple cards at once.
     */
    public function updateCardPositions(UpdateCardPositionsRequest $request)
    {
        try {
            $positions = $request->input('positions');

            // Get the first card to check board access
            $firstCard = $this->cardRepository->show($positions[0]['id']);
            if (!$firstCard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }

            // Check edit permission
            if (!$this->cardRepository->userCanEdit($firstCard->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật vị trí card',
                    'type' => 'Unauthorized',
                ], 403);
            }

            // Verify all cards belong to the same board
            $boardId = $firstCard->listBoard->board_id;
            foreach ($positions as $position) {
                $card = $this->cardRepository->show($position['id']);
                if (!$card || $card->listBoard->board_id !== $boardId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tất cả card phải thuộc cùng một board',
                        'type' => 'invalid_board',
                    ], 400);
                }
            }

            $this->cardRepository->updatePositions($positions);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật vị trí card thành công',
                'type' => 'update_positions_success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật vị trí card',
                'type' => 'error_update_positions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $card = $this->cardRepository->show($id);
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy card',
                    'type' => 'card_not_found',
                ], 404);
            }

            // Check view permission
            if (!$this->cardRepository->userHasAccess($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem card này',
                    'type' => 'unauthorized',
                ], 403);
            }

            // Load the labels and attachments relationships
            $card->load(['labels', 'attachments', "members"]);

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin card thành công',
                'type' => 'get_card_success',
                'data' => $card
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin card',
                'type' => 'error_get_card',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all members of a card
     */
    public function getMembers($cardId)
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

            // Check view permission
            if (!$this->cardRepository->userHasAccess($card->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem thành viên của card này',
                    'type' => 'unauthorized',
                ], 403);
            }

            $members = $card->members;

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách thành viên thành công',
                'type' => 'get_members_success',
                'data' => $members
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách thành viên',
                'type' => 'error_get_members',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
