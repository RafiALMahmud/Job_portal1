<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobCreationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !in_array(Auth::user()->user_type, ['admin', 'employer'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'You are not authorized to create jobs.'], 403);
            }

            return redirect()->route('account.profile')->with('error', 'You are not authorized to create jobs.');
        }

        return $next($request);
    }
} 
