<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireSecureSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !$request->attributes->has('secure_session')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Unauthenticated. A valid active session token is required.',
                ], 401);
            }

            return redirect()->route('account.login')
                ->with('error', 'Your secure session is missing, expired, or revoked. Please login again.');
        }

        return $next($request);
    }
}
