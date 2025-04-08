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
        $response = $next($request);

        $corsDomain = $this->domainRepository->all()->toArray();

        if (in_array($request->headers->get('Origin'), $corsDomain)) {
            $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        return $response;
    }
}
