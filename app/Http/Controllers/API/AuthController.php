<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Validator;

class AuthController extends Controller
{
    // Đăng ký user
   
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|min:8',
            ]);
        
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);
        
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
