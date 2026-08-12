<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Infrastructure\Csrf;
use App\Infrastructure\JsonResponse;

/**
 * CSRF protection for state-changing requests made by an authenticated
 * session (docs/ctf5.txt §55, ctf9.txt requirement #12). Expects the
 * token issued via App\Infrastructure\Csrf::token() to be echoed back in
 * the X-CSRF-Token header.
 */
final class CsrfMiddleware
{
    public static function handle(callable $next): void
    {
        $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!Csrf::verify($submitted)) {
            JsonResponse::error('CSRF_TOKEN_INVALID', 'A valid CSRF token is required for this request.', 419);
            return;
        }

        $next();
    }
}
