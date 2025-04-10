<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\Domain;

class ExcludeDomainTracking
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $domain = $request->getHost();

        if (in_array($domain, Domain::LIST_EXCLUDE_TRACKING)) {
            return response()->json([
                'message' => 'Domain không nằm trong danh sách được phép tracking.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
