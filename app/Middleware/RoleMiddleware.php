<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Infrastructure\JsonResponse;
use App\Services\AuditLogger;
use App\Services\AuthService;

/**
 * Requires the authenticated user's role to be in an allowed list.
 *
 * Must run AFTER AuthMiddleware, or be given an already-authenticated
 * user array.
 *
 * Authorization is always enforced server-side.
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
        /*
         * Safely resolve the user ID.
         *
         * A valid authenticated user should always have a positive ID.
         * If the value is missing or invalid, we still return FORBIDDEN
         * instead of allowing the request or causing an audit FK crash.
         */
        $userId = isset($user['id']) && is_numeric($user['id'])
            ? (int) $user['id']
            : 0;

        /*
         * Resolve the role only when a usable role_id exists.
         */
        $roleId = isset($user['role_id']) && is_numeric($user['role_id'])
            ? (int) $user['role_id']
            : 0;

        $roleName = null;

        if ($roleId > 0) {
            $roleName = $auth->roleName($roleId);
        }

        /*
         * Authorization decision.
         *
         * Both conditions must be satisfied:
         * 1. The user's role must exist.
         * 2. The role must be explicitly allowed.
         */
        $authorized =
            $roleName !== null &&
            in_array($roleName, $allowedRoles, true);

        if (!$authorized) {
            $audit->log(
                AuditLogger::AUTHORIZATION_FAILURE,
                $userId > 0 ? $userId : null,
                [
                    'required_roles' => array_values($allowedRoles),
                    'actual_role' => $roleName,
                ]
            );

            JsonResponse::error(
                'FORBIDDEN',
                'You do not have permission to perform this action.',
                403
            );

            return;
        }

        $next($user);
    }
}