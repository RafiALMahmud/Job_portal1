<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('secureSession')->get('/user', function (Request $request) {
    return response()->json([
        'id' => Auth::id(),
        'name' => Auth::user()?->name,
        'email' => Auth::user()?->email,
        'user_type' => Auth::user()?->user_type,
        'session_expires_at' => optional($request->attributes->get('secure_session'))->expires_at?->toIso8601String(),
    ]);
});
