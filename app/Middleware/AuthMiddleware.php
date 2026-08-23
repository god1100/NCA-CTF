<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Infrastructure\JsonResponse;
use App\Services\AuthService;

final class AuthMiddleware
{
    public static function handle(AuthService $auth, callable $next): void
    {
        $user = $auth->currentUser();

        if ($user === null) {
            JsonResponse::error('UNAUTHORIZED', 'Authentication required.', 401);
            return;
        }

        $next($user);
    }
}