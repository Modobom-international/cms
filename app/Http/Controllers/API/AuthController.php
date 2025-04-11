<?php

namespace App\Http\Controllers\API;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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

                try {
                    $token = $user->createToken('auth_token')->plainTextToken;
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error generating access token',
                        'type' => 'token_generation_error',
                        'error' => $e->getMessage()
                    ], 500);
                }

                $deviceData = [
                    'user_agent' => $request->header('User-Agent'),
                    'platform' => $request->input('platform') ?? 'web',
                    'language' => $request->input('language') ?? 'en',
                    'cookies_enabled' => $request->input('cookies_enabled') ?? true,
                    'screen_width' => $request->input('screen_width') ?? 1920,
                    'screen_height' => $request->input('screen_height') ?? 1080,
                    'timezone' => $request->input('timezone') ?? 'Asia/Ho_Chi_Minh',
                    'fingerprint' => $request->input('fingerprint') ?? rand(10000000, 99999999),
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
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đăng nhập tài khoản',
                'type' => 'error_login',
                'error' => $e->getMessage()
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

    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();
            $user->currentAccessToken()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'message' => 'Lấy token thành công',
                'type' => 'get_token_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'token' => null,
                'message' => 'Lấy token không thành công',
                'type' => 'get_token_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
