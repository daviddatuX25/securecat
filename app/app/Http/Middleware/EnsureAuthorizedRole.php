<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC: ensure authenticated user's role is allowed for this route.
 *
 * Per 08-api-spec-phase1 §2. Uses config/rbac.php for route → roles mapping.
 * Returns 403 if role not allowed. Use '*' in config for "any authenticated".
 */
class EnsureAuthorizedRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRouteKey($request);

        if ($key === null) {
            return $this->forbiddenResponse($request);
        }

        $allowedRoles = $this->getAllowedRoles($key);

        if ($allowedRoles === null) {
            return $next($request);
        }

        if (in_array('*', $allowedRoles, true)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user === null || ! in_array($user->role, $allowedRoles, true)) {
            return $this->forbiddenResponse($request);
        }

        return $next($request);
    }

    private function forbiddenResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'forbidden', 'message' => 'Access denied.'], 403);
        }

        return redirect()->to('/')
            ->with('error', __('You don\'t have access to that page.'));
    }

    private function resolveRouteKey(Request $request): ?string
    {
        $method = $request->method();
        $path = trim($request->path(), '/');
        $isApi = str_starts_with($path, 'api/');

        $routes = $isApi ? config('rbac.api', []) : config('rbac.web', []);
        if (empty($routes)) {
            return null;
        }

        $normalized = $this->normalizePath($path, $isApi);
        $key = "{$method} {$normalized}";

        if (isset($routes[$key])) {
            return $key;
        }

        return $this->matchPattern($key, array_keys($routes));
    }

    private function normalizePath(string $path, bool $isApi): string
    {
        $segments = explode('/', $path);
        foreach ($segments as $i => $segment) {
            if ($segment === '' || ($isApi && $this->looksLikeId($segment))) {
                $segments[$i] = '*';
            }
        }

        return implode('/', $segments);
    }

    private function looksLikeId(string $segment): bool
    {
        return ctype_digit($segment)
            || (strlen($segment) >= 32 && ctype_xdigit(str_replace('-', '', $segment)));
    }

    private function matchPattern(string $key, array $patterns): ?string
    {
        [$reqMethod, $reqPath] = explode(' ', $key, 2);
        $reqSegments = explode('/', $reqPath);

        foreach ($patterns as $pattern) {
            [$patMethod, $patPath] = explode(' ', $pattern, 2);
            if ($patMethod !== $reqMethod) {
                continue;
            }
            $patSegments = explode('/', $patPath);
            if (count($patSegments) !== count($reqSegments)) {
                continue;
            }
            $match = true;
            foreach ($patSegments as $i => $p) {
                if ($p !== '*' && $p !== ($reqSegments[$i] ?? '')) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $pattern;
            }
        }

        return null;
    }

    private function getAllowedRoles(?string $key): ?array
    {
        if ($key === null) {
            return null;
        }

        $path = explode(' ', $key, 2)[1] ?? '';
        $routes = str_starts_with($path, 'api/')
            ? config('rbac.api', [])
            : config('rbac.web', []);

        return $routes[$key] ?? null;
    }
}
