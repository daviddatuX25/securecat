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

// Protected API routes: auth + RBAC + CSRF
Route::middleware(['web', 'auth', 'rbac'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Dashboard stub (T5.3.1) — Admin only; full impl later
    Route::get('/dashboard', fn () => response()->json([
        'pending_applications_count' => 0,
        'upcoming_sessions' => [],
    ]));
});
