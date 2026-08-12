<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Infrastructure\JsonResponse;
use App\Services\AuthService;

/**
 * Requires a valid authenticated session. On success, calls $next with
 * the current user array; on failure, emits 401 and does not call $next.
 *
 * The authenticated user is ALWAYS resolved from the server-side session
 * via AuthService::currentUser() -- never from any request parameter
 * (docs/ctf9.txt §5).
 */
final class AuthMiddleware
{
    public static function handle(AuthService $auth, callable $next): void
    {
        $user = $auth->currentUser();

        if ($user === null) {
            JsonResponse::error('UNAUTHENTICATED', 'Authentication is required for this request.', 401);
            return;
        }

        $next($user);
    }
}
