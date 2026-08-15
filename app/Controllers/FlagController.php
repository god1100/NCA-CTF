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
use App\Repositories\ChallengeRepository;
use App\Repositories\FlagRepository;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\FlagService;
use App\Services\RateLimiter;

/**
 * HTTP layer for flag MANAGEMENT only -- create/replace/view-metadata.
 * No submission/validation endpoint exists here or anywhere in Phase 4
 * (docs/ctf9.txt Phase 4 scope). Every action requires challenge_admin
 * or super_admin; there is no participant-facing route in this
 * controller at all.
 */
final class FlagController
{
    private const ADMIN_ROLES = ['challenge_admin', 'super_admin'];

    private AuthService $auth;
    private AuditLogger $audit;
    private FlagService $flagService;

    public function __construct()
    {
        $pdo = Database::connection();
        $users = new UserRepository($pdo);
        $rateLimiter = new RateLimiter(new AuthAttemptRepository($pdo));
        $this->audit = new AuditLogger(new AuditLogRepository($pdo));
        $this->auth = new AuthService($users, $rateLimiter, $this->audit);

        $this->flagService = new FlagService(new FlagRepository($pdo), new ChallengeRepository($pdo), $this->audit);
    }

    public function create(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params) {
                CsrfMiddleware::handle(function () use ($user, $params) {
                    $challengeId = (int) ($params['id'] ?? 0);
                    $flag = is_string($this->jsonBody()['flag'] ?? null) ? $this->jsonBody()['flag'] : '';

                    $result = $this->flagService->create($user, $challengeId, $flag, $this->clientIp());
                    $this->respond($result, 'Flag created.', 201);
                });
            });
        });
    }

    public function replace(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params) {
                CsrfMiddleware::handle(function () use ($user, $params) {
                    $challengeId = (int) ($params['id'] ?? 0);
                    $flag = is_string($this->jsonBody()['flag'] ?? null) ? $this->jsonBody()['flag'] : '';

                    $result = $this->flagService->replace($user, $challengeId, $flag, $this->clientIp());
                    $this->respond($result, 'Flag updated.');
                });
            });
        });
    }

    public function show(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function () use ($params) {
                $challengeId = (int) ($params['id'] ?? 0);
                $result = $this->flagService->metadata($challengeId);

                if (!$result['success']) {
                    JsonResponse::error('NOT_FOUND', 'No active flag for this challenge.', 404);
                    return;
                }

                // flag_hash is deliberately excluded by toMetadataArray()
                // even here -- see docs/PHASE4_REPORT.md.
                JsonResponse::success(['flag' => FlagRepository::toMetadataArray($result['flag'])]);
            });
        });
    }

    private function respond(array $result, string $successMessage, int $successStatus = 200): void
    {
        if (!$result['success']) {
            $status = match ($result['error_code'] ?? null) {
                'NOT_FOUND' => 404,
                'FLAG_EXISTS' => 409,
                default => 422,
            };
            JsonResponse::error($result['error_code'] ?? 'VALIDATION_FAILED', implode(' ', $result['errors'] ?? ['Flag operation failed.']), $status);
            return;
        }

        JsonResponse::success(['flag' => FlagRepository::toMetadataArray($result['flag'])], $successMessage, $successStatus);
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
