<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Infrastructure\JsonResponse;
use App\Services\AuditLogger;
use App\Services\AuthService;

/**
 * Requires the authenticated user's role to be in an allowed list.
 * Must run AFTER AuthMiddleware (or be given an already-authenticated
 * $user array) -- this middleware does not itself check authentication.
 *
 * Authorization is enforced server-side; hiding a frontend button is
 * never sufficient (docs/ctf5.txt §13, ctf9.txt §5).
 */
final class RoleMiddleware
{
    /**
     * @param string[] $allowedRoles
     */
    public static function handle(
        array $user,
        array $allowedRoles,
        AuthService $auth,
        AuditLogger $audit,
        callable $next
    ): void {
        $roleName = $auth->roleName((int) $user['role_id']);

        if ($roleName === null || !in_array($roleName, $allowedRoles, true)) {
            $audit->log(AuditLogger::AUTHORIZATION_FAILURE, (int) $user['id'], [
                'required_roles' => $allowedRoles,
                'actual_role' => $roleName,
            ]);
            JsonResponse::error('FORBIDDEN', 'You do not have permission to perform this action.', 403);
            return;
        }

        $next($user);
    }
}
