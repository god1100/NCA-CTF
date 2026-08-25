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
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\ParticipantService;
use App\Services\RateLimiter;

final class ParticipantController
{
    private const ADMIN_ROLES = [
        'challenge_admin',
        'super_admin',
    ];

    private const MAX_PER_PAGE = 100;

    private AuthService $auth;

    private ParticipantService $participants;

    private AuditLogger $audit;

    public function __construct()
    {
        $pdo = Database::connection();

        $users = new UserRepository($pdo);

        $rateLimiter = new RateLimiter(
            new AuthAttemptRepository($pdo)
        );

        $this->audit = new AuditLogger(
            new AuditLogRepository($pdo)
        );

        $this->auth = new AuthService(
            $users,
            $rateLimiter,
            $this->audit
        );

        $this->participants = new ParticipantService(
            $users,
            $this->audit
        );
    }

    /**
     * GET /api/v1/admin/participants
     *
     * Supported query parameters:
     *
     * page
     * per_page
     * search
     * status
     */
    public function index(): void
    {
        AuthMiddleware::handle(
            $this->auth,
            function (array $user): void {
                RoleMiddleware::handle(
                    $user,
                    self::ADMIN_ROLES,
                    $this->auth,
                    $this->audit,
                    function (array $user): void {
                        $page = max(
                            1,
                            (int) ($_GET['page'] ?? 1)
                        );

                        $perPage = min(
                            self::MAX_PER_PAGE,
                            max(
                                1,
                                (int) ($_GET['per_page'] ?? 20)
                            )
                        );

                        $search = isset($_GET['search'])
                            ? (string) $_GET['search']
                            : null;

                        $status = isset($_GET['status'])
                            ? (string) $_GET['status']
                            : null;

                        $result = $this->participants->list(
                            $page,
                            $perPage,
                            $search,
                            $status
                        );

                        if (!$result['success']) {
                            JsonResponse::error(
                                $result['error_code']
                                    ?? 'REQUEST_FAILED',
                                implode(
                                    ' ',
                                    $result['errors']
                                        ?? ['Unable to load participants.']
                                ),
                                422
                            );

                            return;
                        }

                        JsonResponse::success(
                            [
                                'participants' =>
                                    $result['participants'] ?? [],

                                'pagination' =>
                                    $result['pagination'] ?? [],
                            ],
                            'Participants retrieved.'
                        );
                    }
                );
            }
        );
    }

    /**
     * GET /api/v1/admin/participants/{id}
     */
    public function show(array $params): void
    {
        AuthMiddleware::handle(
            $this->auth,
            function (array $user) use ($params): void {
                RoleMiddleware::handle(
                    $user,
                    self::ADMIN_ROLES,
                    $this->auth,
                    $this->audit,
                    function (array $user) use ($params): void {
                        $id = (int) (
                            $params['id'] ?? 0
                        );

                        $result =
                            $this->participants->show($id);

                        if (!$result['success']) {
                            $status =
                                ($result['error_code'] ?? '') === 'NOT_FOUND'
                                    ? 404
                                    : 422;

                            JsonResponse::error(
                                $result['error_code']
                                    ?? 'REQUEST_FAILED',
                                implode(
                                    ' ',
                                    $result['errors']
                                        ?? ['Unable to retrieve participant.']
                                ),
                                $status
                            );

                            return;
                        }

                        JsonResponse::success(
                            [
                                'participant' =>
                                    $result['participant'] ?? null,
                            ],
                            'Participant retrieved.'
                        );
                    }
                );
            }
        );
    }

    /**
     * PATCH /api/v1/admin/participants/{id}/status
     */
    public function updateStatus(array $params): void
    {
        AuthMiddleware::handle(
            $this->auth,
            function (array $user) use ($params): void {
                RoleMiddleware::handle(
                    $user,
                    self::ADMIN_ROLES,
                    $this->auth,
                    $this->audit,
                    function (array $user) use ($params): void {
                        CsrfMiddleware::handle(
                            function () use ($user, $params): void {
                                $id = (int) (
                                    $params['id'] ?? 0
                                );

                                $body = $this->jsonBody();

                                $status = is_string(
                                    $body['status'] ?? null
                                )
                                    ? $body['status']
                                    : '';

                                $result =
                                    $this->participants->updateStatus(
                                        $user,
                                        $id,
                                        $status,
                                        $this->clientIp()
                                    );

                                if (!$result['success']) {
                                    $errorCode =
                                        $result['error_code']
                                            ?? 'UPDATE_FAILED';

                                    $statusCode = match ($errorCode) {
                                        'NOT_FOUND' => 404,
                                        'INVALID_ID',
                                        'INVALID_STATUS',
                                        'SELF_ACTION_NOT_ALLOWED' => 422,
                                        default => 422,
                                    };

                                    JsonResponse::error(
                                        $errorCode,
                                        implode(
                                            ' ',
                                            $result['errors']
                                                ?? ['Unable to update participant status.']
                                        ),
                                        $statusCode
                                    );

                                    return;
                                }

                                JsonResponse::success(
                                    [
                                        'participant' =>
                                            $result['participant']
                                                ?? null,
                                    ],
                                    'Participant status updated.'
                                );
                            }
                        );
                    }
                );
            }
        );
    }

    /**
     * DELETE /api/v1/admin/participants/{id}
     */
    public function delete(array $params): void
    {
        AuthMiddleware::handle(
            $this->auth,
            function (array $user) use ($params): void {
                RoleMiddleware::handle(
                    $user,
                    self::ADMIN_ROLES,
                    $this->auth,
                    $this->audit,
                    function (array $user) use ($params): void {
                        CsrfMiddleware::handle(
                            function () use ($user, $params): void {
                                $id = (int) (
                                    $params['id'] ?? 0
                                );

                                $result =
                                    $this->participants->delete(
                                        $user,
                                        $id,
                                        $this->clientIp()
                                    );

                                if (!$result['success']) {
                                    $errorCode =
                                        $result['error_code']
                                            ?? 'DELETE_FAILED';

                                    $statusCode = match ($errorCode) {
                                        'NOT_FOUND' => 404,

                                        'ADMIN_DELETE_NOT_ALLOWED',
                                        'SELF_ACTION_NOT_ALLOWED' => 403,

                                        'INVALID_ID' => 422,

                                        default => 409,
                                    };

                                    JsonResponse::error(
                                        $errorCode,
                                        implode(
                                            ' ',
                                            $result['errors']
                                                ?? ['Unable to delete participant.']
                                        ),
                                        $statusCode
                                    );

                                    return;
                                }

                                JsonResponse::success(
                                    [],
                                    'Participant deleted.'
                                );
                            }
                        );
                    }
                );
            }
        );
    }

    /**
     * Decode a JSON request body.
     */
    private function jsonBody(): array
    {
        $raw = file_get_contents(
            'php://input'
        ) ?: '';

        $decoded = json_decode(
            $raw,
            true
        );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    /**
     * Resolve the client's IP address.
     */
    private function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }
}