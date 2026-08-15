<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Infrastructure\Database;
use App\Infrastructure\Env;
use App\Infrastructure\JsonResponse;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\AuthAttemptRepository;
use App\Repositories\ChallengeFileRepository;
use App\Repositories\ChallengeRepository;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\ChallengeFileService;
use App\Services\RateLimiter;

/**
 * HTTP layer for challenge file attachments. Same thin-controller
 * convention as ChallengeController.
 */
final class ChallengeFileController
{
    private const ADMIN_ROLES = ['challenge_admin', 'super_admin'];

    private AuthService $auth;
    private AuditLogger $audit;
    private ChallengeFileService $fileService;

    public function __construct()
    {
        $pdo = Database::connection();
        $users = new UserRepository($pdo);
        $rateLimiter = new RateLimiter(new AuthAttemptRepository($pdo));
        $this->audit = new AuditLogger(new AuditLogRepository($pdo));
        $this->auth = new AuthService($users, $rateLimiter, $this->audit);

        $projectRoot = dirname(__DIR__, 2);
        $maxSizeMb = (int) Env::get('CHALLENGE_FILE_MAX_SIZE_MB', '50');

        $this->fileService = new ChallengeFileService(
            new ChallengeFileRepository($pdo),
            new ChallengeRepository($pdo),
            $this->audit,
            $projectRoot,
            $maxSizeMb
        );
    }

    public function upload(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params) {
                CsrfMiddleware::handle(function () use ($user, $params) {
                    $challengeId = (int) ($params['id'] ?? 0);
                    $uploaded = $_FILES['file'] ?? null;

                    if (!is_array($uploaded)) {
                        JsonResponse::error('INVALID_REQUEST', 'A file upload named "file" is required.', 400);
                        return;
                    }

                    $result = $this->fileService->upload($user, $challengeId, $uploaded, $this->clientIp());

                    if (!$result['success']) {
                        $status = ($result['error_code'] ?? null) === 'NOT_FOUND' ? 404 : 422;
                        JsonResponse::error($result['error_code'] ?? 'UPLOAD_FAILED', implode(' ', $result['errors'] ?? ['Upload failed.']), $status);
                        return;
                    }

                    JsonResponse::success(['file' => ChallengeFileRepository::toPublicArray($result['file'])], 'File uploaded.', 201);
                });
            });
        });
    }

    public function listForChallenge(array $params): void
    {
        AuthMiddleware::handle($this->auth, function () use ($params) {
            $challengeId = (int) ($params['id'] ?? 0);
            $files = $this->fileService->listForChallenge($challengeId);
            $items = array_map(static fn (array $f) => ChallengeFileRepository::toPublicArray($f), $files);

            JsonResponse::success(['files' => $items]);
        });
    }

    public function download(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            $fileId = (int) ($params['id'] ?? 0);
            $roleName = $this->auth->roleName((int) $user['role_id']);
            $isPrivileged = in_array($roleName, self::ADMIN_ROLES, true);

            $result = $this->fileService->resolveForDownload($user, $fileId, $isPrivileged);

            if (!$result['success']) {
                JsonResponse::error('NOT_FOUND', 'File not found.', 404);
                return;
            }

            // Stream the file directly -- never expose the resolved
            // filesystem path in any response.
            $mimeType = $result['mime_type'] ?: 'application/octet-stream';
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $result['original_name']) . '"');
            header('Content-Length: ' . (string) filesize($result['absolute_path']));
            header('X-Content-Type-Options: nosniff');
            readfile($result['absolute_path']);
        });
    }

    public function delete(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params) {
                CsrfMiddleware::handle(function () use ($user, $params) {
                    $fileId = (int) ($params['id'] ?? 0);
                    $result = $this->fileService->delete($user, $fileId, $this->clientIp());

                    if (!$result['success']) {
                        JsonResponse::error('NOT_FOUND', 'File not found.', 404);
                        return;
                    }

                    JsonResponse::success([], 'File removed.');
                });
            });
        });
    }

    private function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
