<?php

namespace App\Http\Middleware;

use App\Models\CompanyIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCompanyIp
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();

        if (!CompanyIp::isValidCompanyIp($clientIp)) {
            return response()->json([
                'message' => 'Access denied. Please connect to company network to check in/out.'
            ], 403);
        }

        // Add branch name to request for later use
        $request->attributes->set('branch_name', CompanyIp::getBranchName($clientIp));

        return $next($request);
    }
}