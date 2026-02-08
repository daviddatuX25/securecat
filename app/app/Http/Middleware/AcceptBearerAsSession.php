<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allow API clients to send session ID via Authorization: Bearer <session_token>.
 *
 * Per 08-api-spec-phase1: Auth via "Session cookie or Authorization: Bearer <token>".
 * If Bearer token is present, inject it as the session cookie so StartSession loads it.
 */
class AcceptBearerAsSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->header('Authorization');

        if ($auth && str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
            if ($token !== '') {
                $request->cookies->set(
                    config('session.cookie', 'laravel_session'),
                    $token
                );
            }
        }

        return $next($request);
    }
}
