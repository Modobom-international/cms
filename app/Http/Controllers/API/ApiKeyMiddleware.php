<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'API key is required'
            ], 401);
        }

        // First try to find the key by prefix for better performance
        $keyPrefix = substr($apiKey, 0, 8);
        $key = ApiKey::where('key_prefix', $keyPrefix)->first();

        if (!$key || !$key->isValid() || !$key->verifyKey($apiKey)) {
            return response()->json([
                'error' => 'Invalid or expired API key'
            ], 401);
        }

        // Update last used timestamp
        $key->update(['last_used_at' => now()]);

        // Set the authenticated user based on the API key's user
        Auth::login($key->user);

        // Add the API key to the request for use in controllers
        $request->attributes->set('api_key', $key);

        return $next($request);
    }
}
