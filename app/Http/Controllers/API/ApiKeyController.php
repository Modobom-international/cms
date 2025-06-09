<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller as BaseController;

class ApiKeyController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(): JsonResponse
    {
        $keys = Auth::user()->apiKeys()
            ->select(['id', 'name', 'key_prefix', 'last_used_at', 'expires_at', 'is_active', 'created_at'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $keys,
            'message' => 'Lấy danh sách API key thành công',
            'type' => 'list_api_key_success',
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('api_keys')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                })
            ],
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'type' => 'validation_error',
                'errors' => $validator->errors()
            ], 422);
        }

        $keyData = ApiKey::generateKey();

        $apiKey = Auth::user()->apiKeys()->create([
            'name' => $request->name,
            'key_hash' => $keyData['key_hash'],
            'key_prefix' => $keyData['key_prefix'],
            'expires_at' => $request->expires_at,
            'is_active' => true,
        ]);

        // Return the API key only once during creation
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $keyData['key'], // Only shown once
                'key_prefix' => $keyData['key_prefix'],
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ],
            'message' => 'Tạo API key thành công',
            'type' => 'create_api_key_success',
        ], 201);
    }

    public function show(Request $request): JsonResponse
    {
        $apiKey = Auth::user()->apiKeys()->where('id', $request->id)->first();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key không tồn tại',
                'type' => 'api_key_not_found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'last_used_at' => $apiKey->last_used_at,
                'expires_at' => $apiKey->expires_at,
                'is_active' => $apiKey->is_active,
                'created_at' => $apiKey->created_at,
            ],
            'message' => 'Lấy thông tin API key thành công',
            'type' => 'get_api_key_success',
        ], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $apiKey = Auth::user()->apiKeys()->where('id', $request->id)->first();
        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('api_keys')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                })
            ],
            'is_active' => 'sometimes|required|boolean',
            'expires_at' => 'sometimes|nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'type' => 'validation_error',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey->update($request->only(['name', 'is_active', 'expires_at']));

        return response()->json([
            'success' => true,
            'data' => $apiKey->fresh(),
            'message' => 'Cập nhật API key thành công',
            'type' => 'update_api_key_success',
        ], 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $apiKey = Auth::user()->apiKeys()->where('id', $request->id)->first();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key không tồn tại',
                'type' => 'api_key_not_found',
            ], 404);
        }
        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa API key thành công',
            'type' => 'delete_api_key_success',
        ], 200);
    }

    public function regenerate(Request $request): JsonResponse
    {
        $apiKey = Auth::user()->apiKeys()->where('id', $request->id)->first();

        $keyData = ApiKey::generateKey();

        $apiKey->update([
            'key_hash' => $keyData['key_hash'],
            'key_prefix' => $keyData['key_prefix'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $keyData['key'], // Only shown once
                'key_prefix' => $keyData['key_prefix'],
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ],
            'message' => 'Tạo lại API key thành công',
            'type' => 'regenerate_api_key_success',
        ], 200);
    }
}