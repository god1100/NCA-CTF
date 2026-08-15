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
use App\Repositories\CategoryRepository;
use App\Repositories\ChallengeFileRepository;
use App\Repositories\ChallengeHintRepository;
use App\Repositories\ChallengeRepository;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\ChallengeService;
use App\Services\RateLimiter;
use PDO;

/**
 * HTTP layer for /api/v1/challenges/* (core CRUD + lifecycle). Same thin-
 * controller convention as TeamController/AuthController -- all rules
 * live in App\Services\ChallengeService.
 */
final class ChallengeController
{
    private const ADMIN_ROLES = ['challenge_admin', 'super_admin'];
    private const PER_PAGE_MAX = 100;

    private AuthService $auth;
    private ChallengeService $challengeService;
    private ChallengeRepository $challenges;
    private CategoryRepository $categories;
    private ChallengeFileRepository $files;
    private ChallengeHintRepository $hints;
    private AuditLogger $audit;

    public function __construct()
    {
        $pdo = Database::connection();
        $users = new UserRepository($pdo);
        $rateLimiter = new RateLimiter(new AuthAttemptRepository($pdo));
        $this->audit = new AuditLogger(new AuditLogRepository($pdo));

        $this->auth = new AuthService($users, $rateLimiter, $this->audit);
        $this->challenges = new ChallengeRepository($pdo);
        $this->categories = new CategoryRepository($pdo);
        $this->files = new ChallengeFileRepository($pdo);
        $this->hints = new ChallengeHintRepository($pdo);
        $this->challengeService = new ChallengeService($this->challenges, $this->categories, $this->audit);
    }

    public function index(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            $isPrivileged = $this->isPrivileged($user);

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = min(self::PER_PAGE_MAX, max(1, (int) ($_GET['per_page'] ?? 20)));

            $filters = [];

            if (isset($_GET['category']) && $_GET['category'] !== '') {
                $category = ctype_digit((string) $_GET['category'])
                    ? $this->categories->findById((int) $_GET['category'])
                    : $this->categories->findBySlug((string) $_GET['category']);
                if ($category === null) {
                    JsonResponse::success(['challenges' => [], 'pagination' => $this->paginationMeta(0, $page, $perPage)]);
                    return;
                }
                $filters['category_id'] = (int) $category['id'];
            }

            if (isset($_GET['difficulty']) && $_GET['difficulty'] !== '') {
                $difficulty = strtolower((string) $_GET['difficulty']);
                if (!in_array($difficulty, ChallengeRepository::VALID_DIFFICULTIES, true)) {
                    JsonResponse::error('INVALID_FILTER', 'Invalid difficulty filter.', 400);
                    return;
                }
                $filters['difficulty'] = $difficulty;
            }

            if ($isPrivileged && isset($_GET['status']) && $_GET['status'] !== '') {
                $status = strtolower((string) $_GET['status']);
                if (!in_array($status, ChallengeRepository::VALID_STATUSES, true)) {
                    JsonResponse::error('INVALID_FILTER', 'Invalid status filter.', 400);
                    return;
                }
                $filters['status'] = $status;
            }

            $result = $this->challenges->paginate($filters, $isPrivileged, $page, $perPage);

            $items = array_map(function (array $c) use ($isPrivileged) {
                $categoryName = $this->challengeService->categoryName((int) $c['category_id']);
                return $isPrivileged
                    ? ChallengeRepository::toAdminArray($c, $categoryName)
                    : ChallengeRepository::toParticipantArray($c, $categoryName);
            }, $result['items']);

            JsonResponse::success([
                'challenges' => $items,
                'pagination' => $this->paginationMeta($result['total'], $page, $perPage),
            ]);
        });
    }

    /**
     * Accepts either a numeric ID or a slug in {identifier}.
     */
    public function show(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            $identifier = $params['identifier'] ?? '';
            $challenge = ctype_digit($identifier)
                ? $this->challenges->findById((int) $identifier)
                : $this->challenges->findBySlug($identifier);

            $isPrivileged = $this->isPrivileged($user);

            if ($challenge === null) {
                JsonResponse::error('NOT_FOUND', 'Challenge not found.', 404);
                return;
            }

            if (!$isPrivileged && !in_array($challenge['status'], ChallengeRepository::PARTICIPANT_VISIBLE_STATUSES, true)) {
                // Same response whether it doesn't exist or is simply
                // not visible yet -- prevents enumerating draft/testing
                // challenges by guessing IDs/slugs.
                JsonResponse::error('NOT_FOUND', 'Challenge not found.', 404);
                return;
            }

            $categoryName = $this->challengeService->categoryName((int) $challenge['category_id']);
            $shape = $isPrivileged
                ? ChallengeRepository::toAdminArray($challenge, $categoryName)
                : ChallengeRepository::toParticipantArray($challenge, $categoryName);

            $challengeId = (int) $challenge['id'];
            $fileRows = $this->files->forChallenge($challengeId);
            $shape['files'] = array_map(static fn (array $f) => ChallengeFileRepository::toPublicArray($f), $fileRows);

            $hintRows = $this->hints->forChallenge($challengeId, !$isPrivileged);
            $shape['hints'] = $isPrivileged
                ? array_map(static fn (array $h) => ChallengeHintRepository::toAdminArray($h), $hintRows)
                : array_map(static fn (array $h) => ChallengeHintRepository::toUnrevealedArray($h), $hintRows);

            JsonResponse::success(['challenge' => $shape]);
        });
    }

    public function create(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) {
                CsrfMiddleware::handle(function () use ($user) {
                    $result = $this->challengeService->create($user, $this->jsonBody(), $this->clientIp());

                    if (!$result['success']) {
                        JsonResponse::error('VALIDATION_FAILED', implode(' ', $result['errors'] ?? []), 422);
                        return;
                    }

                    $categoryName = $this->challengeService->categoryName((int) $result['challenge']['category_id']);
                    JsonResponse::success(
                        ['challenge' => ChallengeRepository::toAdminArray($result['challenge'], $categoryName)],
                        'Challenge created.',
                        201
                    );
                });
            });
        });
    }

    public function update(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params) {
                CsrfMiddleware::handle(function () use ($user, $params) {
                    $id = (int) ($params['id'] ?? 0);
                    $result = $this->challengeService->update($user, $id, $this->jsonBody(), $this->clientIp());

                    if (!$result['success']) {
                        $status = ($result['error_code'] ?? null) === 'NOT_FOUND' ? 404 : 422;
                        JsonResponse::error($result['error_code'] ?? 'VALIDATION_FAILED', implode(' ', $result['errors'] ?? ['Update failed.']), $status);
                        return;
                    }

                    $categoryName = $this->challengeService->categoryName((int) $result['challenge']['category_id']);
                    JsonResponse::success(['challenge' => ChallengeRepository::toAdminArray($result['challenge'], $categoryName)], 'Challenge updated.');
                });
            });
        });
    }

    public function publish(array $params): void
    {
        $this->handleTransition($params, 'publish', 'Challenge published.');
    }

    public function pause(array $params): void
    {
        $this->handleTransition($params, 'pause', 'Challenge paused.');
    }

    public function archive(array $params): void
    {
        $this->handleTransition($params, 'archive', 'Challenge archived.');
    }

    public function delete(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params) {
                CsrfMiddleware::handle(function () use ($user, $params) {
                    $id = (int) ($params['id'] ?? 0);
                    $result = $this->challengeService->delete($user, $id, $this->clientIp());

                    if (!$result['success']) {
                        $status = ($result['error_code'] ?? null) === 'NOT_FOUND' ? 404 : 409;
                        JsonResponse::error($result['error_code'] ?? 'ACTION_FAILED', implode(' ', $result['errors'] ?? ['Delete failed.']), $status);
                        return;
                    }

                    JsonResponse::success([], 'Challenge deleted.');
                });
            });
        });
    }

    private function handleTransition(array $params, string $action, string $successMessage): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params, $action, $successMessage) {
            RoleMiddleware::handle($user, self::ADMIN_ROLES, $this->auth, $this->audit, function (array $user) use ($params, $action, $successMessage) {
                CsrfMiddleware::handle(function () use ($user, $params, $action, $successMessage) {
                    $id = (int) ($params['id'] ?? 0);
                    $result = $this->challengeService->transition($user, $id, $action, $this->clientIp());

                    if (!$result['success']) {
                        $status = ($result['error_code'] ?? null) === 'NOT_FOUND' ? 404 : 409;
                        JsonResponse::error($result['error_code'] ?? 'ACTION_FAILED', implode(' ', $result['errors'] ?? ['Action failed.']), $status);
                        return;
                    }

                    $categoryName = $this->challengeService->categoryName((int) $result['challenge']['category_id']);
                    JsonResponse::success(['challenge' => ChallengeRepository::toAdminArray($result['challenge'], $categoryName)], $successMessage);
                });
            });
        });
    }

    public function categories(): void
    {
        AuthMiddleware::handle($this->auth, function () {
            $rows = $this->categories->allActive();
            $items = array_map(static fn (array $c) => [
                'id' => (int) $c['id'],
                'name' => $c['name'],
                'slug' => $c['slug'],
                'description' => $c['description'],
            ], $rows);

            JsonResponse::success(['categories' => $items]);
        });
    }

    private function isPrivileged(array $user): bool
    {
        $roleName = $this->auth->roleName((int) $user['role_id']);
        return in_array($roleName, self::ADMIN_ROLES, true);
    }

    private function paginationMeta(int $total, int $page, int $perPage): array
    {
        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
        ];
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
