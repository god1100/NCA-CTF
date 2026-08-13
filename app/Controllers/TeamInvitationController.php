<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Infrastructure\Database;
use App\Infrastructure\JsonResponse;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\AuthAttemptRepository;
use App\Repositories\SystemSettingRepository;
use App\Repositories\TeamInvitationRepository;
use App\Repositories\TeamMemberRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\RateLimiter;
use App\Services\TeamInvitationService;
use App\Services\TeamService;
use PDO;

/**
 * HTTP layer for team invitation endpoints. Same conventions as
 * TeamController/AuthController.
 */
final class TeamInvitationController
{
    private AuthService $auth;
    private TeamInvitationService $invitationService;

    public function __construct()
    {
        $pdo = Database::connection();
        $users = new UserRepository($pdo);
        $rateLimiter = new RateLimiter(new AuthAttemptRepository($pdo));
        $audit = new AuditLogger(new AuditLogRepository($pdo));

        $this->auth = new AuthService($users, $rateLimiter, $audit);

        $members = new TeamMemberRepository($pdo);
        $teamService = new TeamService($pdo, new TeamRepository($pdo), $members, new SystemSettingRepository($pdo), $audit);
        $this->invitationService = new TeamInvitationService($pdo, new TeamInvitationRepository($pdo), $members, $teamService, $audit);
    }

    public function create(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            CsrfMiddleware::handle(function () use ($user) {
                $input = $this->jsonBody();
                $email = is_string($input['email'] ?? null) ? $input['email'] : '';

                $result = $this->invitationService->createInvitation($user, $email, $this->clientIp());

                if (!$result['success']) {
                    $status = match ($result['error_code'] ?? null) {
                        'NO_TEAM' => 404,
                        'FORBIDDEN' => 403,
                        'INVITATION_EXISTS', 'TEAM_FULL', 'INVALID_TARGET' => 409,
                        default => 422,
                    };
                    JsonResponse::error($result['error_code'] ?? 'VALIDATION_FAILED', implode(' ', $result['errors'] ?? ['Could not create invitation.']), $status);
                    return;
                }

                JsonResponse::success([
                    'invitation' => TeamInvitationRepository::toPublicArray($result['invitation']),
                    // Plaintext token, returned exactly once -- see
                    // TeamInvitationService::createInvitation() for why
                    // (no email delivery implemented in Phase 3).
                    'token' => $result['token'],
                ], 'Invitation created.', 201);
            });
        });
    }

    public function list(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            $result = $this->invitationService->listMyTeamInvitations($user);

            if (!$result['success']) {
                $status = ($result['error_code'] ?? null) === 'FORBIDDEN' ? 403 : 404;
                JsonResponse::error($result['error_code'] ?? 'NO_TEAM', 'You are not currently a team captain.', $status);
                return;
            }

            $invitations = array_map(
                static fn (array $i) => TeamInvitationRepository::toPublicArray($i),
                $result['invitations']
            );

            JsonResponse::success(['invitations' => $invitations]);
        });
    }

    public function accept(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            CsrfMiddleware::handle(function () use ($user, $params) {
                $token = $params['token'] ?? '';
                $result = $this->invitationService->acceptInvitation($user, $token, $this->clientIp());
                $this->respond($result, 'You have joined the team.');
            });
        });
    }

    public function reject(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            CsrfMiddleware::handle(function () use ($user, $params) {
                $token = $params['token'] ?? '';
                $result = $this->invitationService->rejectInvitation($user, $token, $this->clientIp());
                $this->respond($result, 'Invitation declined.');
            });
        });
    }

    private function respond(array $result, string $successMessage): void
    {
        if ($result['success']) {
            JsonResponse::success([], $successMessage);
            return;
        }

        $status = match ($result['error_code'] ?? null) {
            'ALREADY_IN_TEAM', 'TEAM_FULL' => 409,
            default => 422,
        };

        JsonResponse::error($result['error_code'] ?? 'ACTION_FAILED', implode(' ', $result['errors'] ?? ['Action could not be completed.']), $status);
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
