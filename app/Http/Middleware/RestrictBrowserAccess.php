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
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->path(), $this->except)) {
            if ($request->header('Accept') === 'text/html' || !$request->expectsJson()) {
                return response()->view('welcome');
            }
        }

        return $next($request);
    }
}
