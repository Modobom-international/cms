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

        return response()->json($keys);
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
                'message' => 'Validation failed',
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
            'message' => 'API key created successfully',
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $keyData['key'], // Only shown once
                'key_prefix' => $keyData['key_prefix'],
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ]
        ], 201);
    }

    public function show(ApiKey $apiKey): JsonResponse
    {
        $this->authorize('view', $apiKey);

        return response()->json([
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key_prefix' => $apiKey->key_prefix,
            'last_used_at' => $apiKey->last_used_at,
            'expires_at' => $apiKey->expires_at,
            'is_active' => $apiKey->is_active,
            'created_at' => $apiKey->created_at,
        ]);
    }

    public function update(Request $request, ApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey);

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('api_keys')->where(function ($query) use ($apiKey) {
                    return $query->where('user_id', Auth::id())
                        ->where('id', '!=', $apiKey->id);
                })
            ],
            'is_active' => 'sometimes|required|boolean',
            'expires_at' => 'sometimes|nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey->update($request->only(['name', 'is_active', 'expires_at']));

        return response()->json([
            'message' => 'API key updated successfully',
            'api_key' => $apiKey->fresh()
        ]);
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $this->authorize('delete', $apiKey);

        $apiKey->delete();

        return response()->json(['message' => 'API key deleted successfully']);
    }

    public function regenerate(ApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey);

        $keyData = ApiKey::generateKey();

        $apiKey->update([
            'key_hash' => $keyData['key_hash'],
            'key_prefix' => $keyData['key_prefix'],
        ]);

        return response()->json([
            'message' => 'API key regenerated successfully',
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $keyData['key'], // Only shown once
                'key_prefix' => $keyData['key_prefix'],
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ]
        ]);
    }
}