<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Phase 1)
|--------------------------------------------------------------------------
|
| Per docs/architecture/08-api-spec-phase1.md
|
*/

// Login: rate-limited, uses session (web middleware) for Auth::login + cookie
Route::middleware(['web', 'throttle:5,1'])->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Logout: auth required, CSRF required (web middleware)
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
