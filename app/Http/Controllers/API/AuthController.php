<?php

namespace App\Http\Controllers\API;

use App\Enums\Users;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    // Đăng ký user
    public function register(Request $request)
    {
        try {
            // Validate the incoming request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|min:8',
                'type_user' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            $input = $request->all();
            $dataUser = [
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => bcrypt($input['password']),
                'role' => Users::USER,
                'type_user' => $input['type_user'],
            ];

            $user = $this->userRepository->createUser($dataUser);

            $token = $user->createToken('Personal Access Token')->accessToken;
            return response()->json([
                'success' => true,
                'message' => 'Đăng kí thành công',
                'type' => 'register_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đăng kí tài khoản',
                'type' => 'error_register',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Đăng nhập user
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $token = $user->createToken('Personal Access Token')->accessToken;

                $deviceData = [
                    'user_agent' => $request->header('User-Agent'),
                    'platform' => $request->input('platform'),
                    'language' => $request->input('language'),
                    'cookies_enabled' => $request->input('cookies_enabled', true),
                    'screen_width' => $request->input('screen_width'),
                    'screen_height' => $request->input('screen_height'),
                    'timezone' => $request->input('timezone'),
                    'fingerprint' => $request->input('fingerprint'),
                ];

                if (!$user->deviceFingerprints()->where('fingerprint', $deviceData['fingerprint'])->exists()) {
                    $user->deviceFingerprints()->create($deviceData);
                }

                return response()->json([
                    'success' => true,
                    'data' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'message' => 'Bạn đã đăng nhập thành công.',
                    'type' => 'login_success',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Thông tin đăng nhập không đúng',
                'type' => 'email_or_password_incorrect',
            ], 401);
        } catch (\Exception $e) {
            response()->json([
                'success' => false,
                'message' => 'Lỗi khi đăng nhập tài khoản',
                'type' => 'error_login',
            ], 500);
        }
    }

    // Đăng xuất user
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công',
            'type' => 'logout_success',
        ], 200);
    }
}
