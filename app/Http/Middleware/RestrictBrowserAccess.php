<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictBrowserAccess
{
    protected $except = [
        'api/check-device',
        'api/tracking-event',
        'api/heartbeat',
        'api/collect-ai-training-data',
        'api/create-video-timeline',
        'api/save-html-source',
        'api/push-system',
        'api/get-push-system-config',
        'api/add-user-active-push-system',
        'api/push-system/save-config-links',
        'api/me',
        'api/refresh-token',
        'horizon/*'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        foreach ($this->except as $except) {
            if (str_ends_with($except, '/*')) {
                $prefix = rtrim($except, '/*');
                if (str_starts_with($path, $prefix)) {
                    return $next($request);
                }
            } elseif ($path === $except) {
                return $next($request);
            }
        }

        if ($request->header('Accept') === 'text/html') {
            return response()->json([
                'message' => 'This endpoint requires a JSON request. Please set the Accept header to application/json.',
                'status' => false,
            ], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        } else if (!$request->expectsJson()) {
            return redirect()->route('home');
        } else {
            return $next($request);
        }
    }
}
