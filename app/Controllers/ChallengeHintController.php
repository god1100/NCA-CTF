<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Infrastructure\Database;
use App\Infrastructure\JsonResponse;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\AuthAttemptRepository;
use App\Repositories\ChallengeHintRepository;
use App\Repositories\ChallengeRepository;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\ChallengeHintService;
use App\Services\RateLimiter;

/**
 * HTTP layer for challenge hints, including participant reveal. Same
 * thin-controller convention as ChallengeController.
 */
final class ChallengeHintController
{
    private const ADMIN_ROLES = ['challenge_admin', 'super_admin'];

    private AuthService $auth;
    private AuditLogger $audit;
    private ChallengeHintService $hintService;

    public function __construct()
    {
        $pdo = Database::connection();
        $users = new UserRepository($pdo);
        $rateLimiter = new RateLimiter(new AuthAttemptRepository($pdo));
        $this->audit = new AuditLogger(new AuditLogRepository($pdo));
        $this->auth = new AuthService($users, $rateLimiter, $this->audit);

        $this->hintService = new ChallengeHintService(
            new ChallengeHintRepository($pdo),
            new ChallengeRepository($pdo),
            $this->audit
        );
    }

    public function create(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params) {
                CsrfMiddleware::handle(function () use ($user, $params) {
                    $challengeId = (int) ($params['id'] ?? 0);
                    $result = $this->hintService->create($user, $challengeId, $this->jsonBody(), $this->clientIp());

                    if (!$result['success']) {
                        $status = ($result['error_code'] ?? null) === 'NOT_FOUND' ? 404 : 422;
                        JsonResponse::error($result['error_code'] ?? 'VALIDATION_FAILED', implode(' ', $result['errors'] ?? ['Could not create hint.']), $status);
                        return;
                    }

                    JsonResponse::success(['hint' => ChallengeHintRepository::toAdminArray($result['hint'])], 'Hint created.', 201);
                });
            });
        });
    }

    public function update(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params) {
                CsrfMiddleware::handle(function () use ($user, $params) {
                    $hintId = (int) ($params['id'] ?? 0);
                    $result = $this->hintService->update($user, $hintId, $this->jsonBody(), $this->clientIp());

                    if (!$result['success']) {
                        $status = ($result['error_code'] ?? null) === 'NOT_FOUND' ? 404 : 422;
                        JsonResponse::error($result['error_code'] ?? 'VALIDATION_FAILED', implode(' ', $result['errors'] ?? ['Could not update hint.']), $status);
                        return;
                    }

                    JsonResponse::success(['hint' => ChallengeHintRepository::toAdminArray($result['hint'])], 'Hint updated.');
                });
            });
        });
    }

    public function remove(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params) {
                CsrfMiddleware::handle(function () use ($user, $params) {
                    $hintId = (int) ($params['id'] ?? 0);
                    $result = $this->hintService->remove($user, $hintId, $this->clientIp());

                    if (!$result['success']) {
                        JsonResponse::error('NOT_FOUND', 'Hint not found.', 404);
                        return;
                    }

                    JsonResponse::success([], 'Hint removed.');
                });
            });
        });
    }

    public function listForChallenge(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            $challengeId = (int) ($params['id'] ?? 0);
            $roleName = $this->auth->roleName((int) $user['role_id']);
            $isPrivileged = in_array($roleName, self::ADMIN_ROLES, true);

            $hints = $this->hintService->listForChallenge($challengeId, !$isPrivileged);

            $items = $isPrivileged
                ? array_map(static fn (array $h) => ChallengeHintRepository::toAdminArray($h), $hints)
                : array_map(static fn (array $h) => ChallengeHintRepository::toUnrevealedArray($h), $hints);

            JsonResponse::success(['hints' => $items]);
        });
    }

    public function reveal(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            CsrfMiddleware::handle(function () use ($user, $params) {
                $hintId = (int) ($params['id'] ?? 0);
                $roleName = $this->auth->roleName((int) $user['role_id']);
                $isPrivileged = in_array($roleName, self::ADMIN_ROLES, true);

                $result = $this->hintService->reveal($user, $hintId, $isPrivileged);

                if (!$result['success']) {
                    JsonResponse::error('NOT_FOUND', 'Hint not found.', 404);
                    return;
                }

                JsonResponse::success(['hint' => ChallengeHintRepository::toRevealedArray($result['hint'])]);
            });
        });
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
