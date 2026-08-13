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
use App\Repositories\TeamMemberRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\RateLimiter;
use App\Services\TeamService;
use PDO;

/**
 * HTTP layer for /api/v1/teams/*. Thin by design -- all business rules
 * live in App\Services\TeamService. Response envelope matches
 * docs/ctf5.txt §4, same as AuthController.
 */
final class TeamController
{
    private PDO $pdo;
    private AuthService $auth;
    private TeamService $teamService;
    private TeamRepository $teams;
    private TeamMemberRepository $members;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $users = new UserRepository($this->pdo);
        $rateLimiter = new RateLimiter(new AuthAttemptRepository($this->pdo));
        $audit = new AuditLogger(new AuditLogRepository($this->pdo));

        $this->auth = new AuthService($users, $rateLimiter, $audit);
        $this->teams = new TeamRepository($this->pdo);
        $this->members = new TeamMemberRepository($this->pdo);

        $this->teamService = new TeamService(
            $this->pdo,
            $this->teams,
            $this->members,
            new SystemSettingRepository($this->pdo),
            $audit
        );
    }

    public function create(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            CsrfMiddleware::handle(function () use ($user) {
                $input = $this->jsonBody();
                $name = is_string($input['name'] ?? null) ? $input['name'] : '';

                $result = $this->teamService->createTeam($user, $name, $this->clientIp());

                if (!$result['success']) {
                    $status = match ($result['error_code'] ?? null) {
                        'ALREADY_IN_TEAM', 'NAME_TAKEN' => 409,
                        default => 422,
                    };
                    JsonResponse::error($result['error_code'] ?? 'VALIDATION_FAILED', implode(' ', $result['errors'] ?? []), $status);
                    return;
                }

                JsonResponse::success(['team' => TeamRepository::toPublicArray($result['team'])], 'Team created.', 201);
            });
        });
    }

    public function me(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            $context = $this->teamService->myTeam($user);

            if ($context === null) {
                JsonResponse::success(['team' => null], 'You are not currently in a team.');
                return;
            }

            JsonResponse::success([
                'team' => TeamRepository::toPublicArray($context['team']),
                'is_captain' => (bool) $context['membership']['is_captain'],
                'joined_at' => $context['membership']['joined_at'],
            ]);
        });
    }

    public function members(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            $result = $this->teamService->listMyTeamMembers($user);

            if (!$result['success']) {
                JsonResponse::error('NO_TEAM', 'You are not currently in a team.', 404);
                return;
            }

            $members = array_map(
                static fn (array $m) => TeamMemberRepository::toPublicArray($m),
                $result['members']
            );

            JsonResponse::success(['members' => $members]);
        });
    }

    public function removeMember(array $params): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) use ($params) {
            CsrfMiddleware::handle(function () use ($user, $params) {
                $targetUserId = (int) ($params['user_id'] ?? 0);

                if ($targetUserId <= 0) {
                    JsonResponse::error('INVALID_REQUEST', 'A valid user_id is required.', 400);
                    return;
                }

                $result = $this->teamService->removeMember($user, $targetUserId, $this->clientIp());
                $this->respondToActionResult($result, 'Member removed.');
            });
        });
    }

    public function leave(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            CsrfMiddleware::handle(function () use ($user) {
                $result = $this->teamService->leaveTeam($user, $this->clientIp());
                $this->respondToActionResult($result, 'You have left the team.');
            });
        });
    }

    public function transferCaptain(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            CsrfMiddleware::handle(function () use ($user) {
                $input = $this->jsonBody();
                $newCaptainUserId = (int) ($input['user_id'] ?? 0);

                if ($newCaptainUserId <= 0) {
                    JsonResponse::error('INVALID_REQUEST', 'A valid user_id is required.', 400);
                    return;
                }

                $result = $this->teamService->transferCaptain($user, $newCaptainUserId, $this->clientIp());
                $this->respondToActionResult($result, 'Captaincy transferred.');
            });
        });
    }

    /**
     * Limited public view of a team by ID -- no member list, no
     * invitation data (docs/ctf9.txt Phase 3: "ensuring private
     * information is not exposed unnecessarily").
     */
    public function show(array $params): void
    {
        AuthMiddleware::handle($this->auth, function () use ($params) {
            $id = (int) ($params['id'] ?? 0);
            $team = $id > 0 ? $this->teams->findById($id) : null;

            if ($team === null) {
                JsonResponse::error('NOT_FOUND', 'Team not found.', 404);
                return;
            }

            JsonResponse::success(['team' => TeamRepository::toPublicArray($team)]);
        });
    }

    private function respondToActionResult(array $result, string $successMessage): void
    {
        if ($result['success']) {
            JsonResponse::success([], $successMessage);
            return;
        }

        $status = match ($result['error_code'] ?? null) {
            'NO_TEAM', 'NOT_FOUND' => 404,
            'FORBIDDEN' => 403,
            'CAPTAIN_MUST_TRANSFER', 'INVALID_TARGET' => 409,
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
