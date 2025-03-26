<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LimitRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
  
        // Define the key based on the client's IP address
        $key = sprintf("limit_requests:%s", $request->ip());
    
        // Set the request limit and the time frame (e.g., 5 requests per minute)
        $maxAttempts = 5;
        $decayMinutes = 1;
    
        // Get the number of attempts and the time of the last attempt
        $attempts = Cache::get($key, 0);
    
        if ($attempts >= $maxAttempts) {
            return response()->json(['message' => 'Too many requests. Please try again later.'], 429);
        }
    
        // Increment the request count
        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));
    
        return $next($request);
    }
}
