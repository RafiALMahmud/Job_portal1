<?php

namespace App\Http\Middleware;

use App\Services\Auth\SecureSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectSessionBearerToken
{
    public function __construct(private readonly SecureSessionService $secureSessionService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->bearerToken()) {
            $token = $request->cookie($this->secureSessionService->cookieName());

            if (is_string($token) && $token !== '') {
                $request->headers->set('Authorization', 'Bearer ' . $token);
                $request->server->set('HTTP_AUTHORIZATION', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
