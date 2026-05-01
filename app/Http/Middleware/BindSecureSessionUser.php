<?php

namespace App\Http\Middleware;

use App\Services\Auth\SecureSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class BindSecureSessionUser
{
    public function __construct(private readonly SecureSessionService $secureSessionService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->secureSessionService->extractBearerToken($request);

        if ($token) {
            $session = $this->secureSessionService->resolveSessionFromRequest($request);

            if ($session) {
                Auth::guard()->setUser($session->user);
                $request->attributes->set('secure_session', $session);
                $request->attributes->set('secure_session_token', $token);
            } else {
                $request->attributes->set('secure_session_invalid', true);
            }
        }

        $response = $next($request);

        if ($request->attributes->get('secure_session_invalid')) {
            Cookie::queue($this->secureSessionService->forgetCookie());
        }

        return $response;
    }
}
