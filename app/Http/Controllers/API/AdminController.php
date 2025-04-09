<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function generateToken(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = Str::random(40);
        $user->exclude_token = $token;
        $user->save();

        return response()->json(['exclude_token' => $token]);
    }

    public function verifyToken(Request $request)
    {
        $token = $request->input('token');
        $user = User::where('exclude_token', $token)->first();

        if ($user) {
            return response()->json(['valid' => true]);
        }

        return response()->json(['valid' => false]);
    }
}
