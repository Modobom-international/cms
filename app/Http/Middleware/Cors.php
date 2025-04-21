<?php

namespace App\Http\Middleware;

use App\Repositories\DomainRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    protected $domainRepository;

    public function __construct(DomainRepository $domainRepository)
    {
        $this->domainRepository = $domainRepository;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('');
        } else {
            $response = $next($request);
        }

        $origin = $request->headers->get('Origin');
        $corsDomain = $this->domainRepository->all()->toArray();

        // if (in_array($origin, $corsDomain)) {
        //     $response->headers->set('Access-Control-Allow-Origin', $origin);
        //     $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        //     $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-XSRF-TOKEN');
        //     $response->headers->set('Access-Control-Allow-Credentials', 'true');
        // }

        if ($request) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-XSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
