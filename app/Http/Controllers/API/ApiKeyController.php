<?php

namespace App\Http\Controllers\API;

use App\Models\ApiKey;
use App\Models\Server;
use App\Repositories\ApiKeyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller as BaseController;

class ApiKeyController extends BaseController
{
    protected $apiKeyRepository;

    public function __construct(ApiKeyRepository $apiKeyRepository)
    {
        $this->apiKeyRepository = $apiKeyRepository;
    }

    public function index(): JsonResponse
    {
        $keys = $this->apiKeyRepository->getByUser(Auth::id());

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

        $apiKey = $this->apiKeyRepository->createApiKey([
            'name' => $request->name,
            'key_hash' => $keyData['key_hash'],
            'key_prefix' => $keyData['key_prefix'],
            'user_id' => Auth::id(),
            'expires_at' => $request->expires_at,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $keyData['key'],
                'key_prefix' => $keyData['key_prefix'],
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ],
            'message' => 'Tạo API key thành công',
            'type' => 'create_api_key_success',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $apiKey = $this->apiKeyRepository->getByUserAndId(Auth::id(), $id);

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

    public function update(string $id, Request $request): JsonResponse
    {
        $apiKey = $this->apiKeyRepository->getByUserAndId(Auth::id(), $id);
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key không tồn tại',
                'type' => 'api_key_not_found',
            ], 404);
        }

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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'type' => 'validation_error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updatedApiKey = $this->apiKeyRepository->updateApiKey($id, $request->only(['name', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => $updatedApiKey,
            'message' => 'Cập nhật API key thành công',
            'type' => 'update_api_key_success',
        ], 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $apiKey = $this->apiKeyRepository->getByUserAndId(Auth::id(), $id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key không tồn tại',
                'type' => 'api_key_not_found',
            ], 404);
        }

        $this->apiKeyRepository->deleteApiKey($id);

        return response()->json([
            'success' => true,
            'message' => 'Xóa API key thành công',
            'type' => 'delete_api_key_success',
        ], 200);
    }

    public function regenerate(string $id): JsonResponse
    {
        $apiKey = $this->apiKeyRepository->getByUserAndId(Auth::id(), $id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key không tồn tại',
                'type' => 'api_key_not_found',
            ], 404);
        }

        $keyData = ApiKey::generateKey();

        $this->apiKeyRepository->updateApiKey($id, [
            'key_hash' => $keyData['key_hash'],
            'key_prefix' => $keyData['key_prefix'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $keyData['key'],
                'key_prefix' => $keyData['key_prefix'],
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ],
            'message' => 'Tạo lại API key thành công',
            'type' => 'regenerate_api_key_success',
        ], 200);
    }

    public function getServerApiKey(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ip' => 'required|ip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'type' => 'validation_error',
                'errors' => $validator->errors()
            ], 422);
        }

        $server = Server::where('ip', $request->ip)->first();
        if (!$server) {
            return response()->json([
                'success' => false,
                'message' => 'Server không tồn tại với IP này',
                'type' => 'server_not_found',
            ], 404);
        }

        $apiKey = $this->apiKeyRepository->getByServer($server->id);
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Server chưa có API key',
                'type' => 'server_no_api_key',
            ], 404);
        }

        if (!$apiKey->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'API key không hợp lệ hoặc đã hết hạn',
                'type' => 'api_key_invalid',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'server_ip' => $server->ip,
                'api_key_id' => $apiKey->id,
                'api_key_name' => $apiKey->name,
                'api_key_prefix' => $apiKey->key_prefix,
                'expires_at' => $apiKey->expires_at,
                'is_active' => $apiKey->is_active,
            ],
            'message' => 'Lấy thông tin API key thành công',
            'type' => 'get_server_api_key_success',
        ], 200);
    }
}