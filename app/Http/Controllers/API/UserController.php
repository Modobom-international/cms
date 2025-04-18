<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Enums\ActivityAction;
use App\Traits\LogsActivity;

class UserController extends Controller
{
    use LogsActivity;

    protected $userRepository;
    protected $utility;

    public function __construct(
        UserRepository $userRepository,
        Utility $utility
    ) {
        $this->userRepository = $userRepository;
        $this->utility = $utility;
    }
    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Get authenticated user information",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="profile_photo_path", type="string", nullable=true)
     *             ),
     *             @OA\Property(property="message", type="string", example="Thông tin tài khoản user"),
     *             @OA\Property(property="type", type="string", example="data_user_success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function me()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy user',
                'type' => 'user_not_found',
            ], 404);
        }

        $dataUser = $this->userRepository->getUserByID($user->id); //can be remove, because we already get user from auth

        return response()->json([
            'success' => true,
            'data' => $dataUser,
            'message' => 'Thông tin tài khoản user',
            'type' => 'data_user_success',
        ], 200);
    }
    /**
     * @OA\Post(
     *     path="/api/account/me",
     *     summary="Update current user profile",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="publisher"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     example="publisher@gmail.com"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="80/49 street 3"
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     example="0966957813"
     *                 ),
     *                 @OA\Property(
     *                     property="profile_photo_path",
     *                     type="file",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="country",
     *                     type="string",
     *                     example="Vietnam"
     *                 ),
     *                 @OA\Property(
     *                     property="province",
     *                     type="string",
     *                     example="Go Vap"
     *                 ),
     *                 @OA\Property(
     *                     property="district",
     *                     type="string",
     *                     example="33"
     *                 ),
     *                 @OA\Property(
     *                     property="ward",
     *                     type="string",
     *                     example="333"
     *                 ),
     *                 @OA\Property(
     *                     property="postal_code",
     *                     type="string",
     *                     example="700000"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Cập nhập thông tin thành công"),
     *             @OA\Property(property="type", type="string", example="update_user_success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error storing image"
     *     )
     * )
     */
    public function updateCurrentUser(UserRequest $request)
    {
        try {
            $user = Auth::user();
            $input = $request->except(['_token']);
            if ($request->has('email') && $input['email'] !== $user->email) {
                unset($input['email']);
            }
            $user = Auth::user();
            if (isset($input['profile_photo_path'])) {
                $img = $this->utility->saveImageUser($input);
                if ($img) {
                    $path = '/images/user/' . $input['profile_photo_path']->getClientOriginalName();
                    $input['profile_photo_path'] = $path;
                }
            }
            $this->userRepository->update($input, $user->id);

            $this->logActivity(ActivityAction::UPDATE_RECORD, ['filters' => $request->all(), 'user' => $user], 'Thay đổi thông tin user');

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Cập nhập thông tin thành công',
                'type' => 'update_user_success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update user không thành công',
                'type' => 'error_update_user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/change-password",
     *     summary="Change user password",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="current_password", type="string"),
     *             @OA\Property(property="new_password", type="string"),
     *             @OA\Property(property="new_password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Password changed successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Current password incorrect"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function changePassword(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if the current password is correct
        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Mật khẩu không trùng khớp',
                    'type' => 'password_not_match',
                ],
                400
            );
        }

        // Update the password
        $user->password = bcrypt($request->new_password);
        $user->save();

        $this->logActivity(ActivityAction::UPDATE_RECORD, ['filters' => $request->all(), 'user' => $user], 'Thay đổi mật khẩu');

        return response()->json([
            'success' => true,
            'message' => 'Thay đổi mật khẩu thành công',
            'type' => 'password_change_success',
        ], 201);
    }
    /**
     * @OA\Post(
     *     path="/api/forget-password",
     *     summary="Send password reset email",
     *     tags={"User"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reset token sent successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email not found"
     *     )
     * )
     */

    public function updatePassword($id, Request $request)
    {
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy user',
                'type' => 'user_not_found',
            ], 404);
        }
        // Validate dữ liệu nhập vào
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Cập nhật mật khẩu mới
        $user->password = Hash::make($request->password);
        $user->save();

        $this->logActivity(ActivityAction::UPDATE_RECORD, ['filters' => $request->all(), 'user' => $user], 'Thay đổi mật khẩu');

        return response()->json([
            'success' => true,
            'message' => 'Thay đổi mật khẩu thành công cho user',
            'type' => 'change_password_for_user_success',
        ], 201);
    }

    public function index(Request $request)
    {
        try {
            $input = $request->all();
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;
            $team = $request->get('team');
            $search = $request->get('search');
            $filter = [];

            if (isset($team)) {
                $filter['team'] = $team;
            }

            if (isset($search)) {
                $filter['search'] = $search;
            }

            $query = $this->userRepository->getUsersByFilter();
            $data = $this->utility->paginate($query, $pageSize, $page);

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách users');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách user thành công',
                'type' => 'list_user_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách user không thành công',
                'type' => 'list_user_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = $this->userRepository->getUserByID($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy user',
                    'type' => 'user_not_found',
                ], 404);
            }

            $this->logActivity(ActivityAction::SHOW_RECORD, ['filters' => $request->all(), 'user' => $user], 'Xem thông tin user');

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Thông tin user thành công',
                'type' => 'get_user_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin tài khoản không thành công',
                'type' => 'get_user_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update($id)
    {
        try {
            $user = $this->userRepository->getUserByID($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy user',
                    'type' => 'user_not_found',
                ], 404);
            }

            $input = request()->all();
            $this->userRepository->update($input, $user->id);

            $this->logActivity(ActivityAction::SHOW_RECORD, ['filters' => $request->all(), 'user' => $user], 'Thay đổi thông tin user');

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Cập nhập thông tin thành công',
                'type' => 'update_user_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhập thông tin không thành công',
                'type' => 'update_user_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = $this->userRepository->getUserByID($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy user',
                    'type' => 'user_not_found',
                ], 404);
            }

            $this->userRepository->deleteById($id);

            $this->logActivity(ActivityAction::DELETE_RECORD, ['filters' => $request->all(), 'user' => $user], 'Xóa user');

            return response()->json([
                'success' => true,
                'message' => 'Xóa user thành công',
                'type' => 'delete_user_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xóa user không thành công',
                'type' => 'delete_user_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
