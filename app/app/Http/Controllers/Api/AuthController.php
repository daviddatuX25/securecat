<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Auth API controller.
 *
 * Per docs/architecture/08-api-spec-phase1.md §2.
 */
class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     *
     * Validate credentials, create session, return user + session_token.
     * Rate-limited to 5 attempts per minute (throttle:5,1 on route).
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:1'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ])->status(401);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ])->status(401);
        }

        Auth::login($user, $request->boolean('remember', false));

        $request->session()->regenerate();

        return response()->json([
            'user' => [
                'id' => (string) $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ],
            'session_token' => $request->session()->getId(),
        ]);
    }

    /**
     * POST /api/auth/logout
     *
     * Invalidate session. Auth required (any role). CSRF required.
     * Per 08-api-spec-phase1 §2.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }
}
