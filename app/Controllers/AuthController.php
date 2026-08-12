<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Infrastructure\Csrf;
use App\Infrastructure\Database;
use App\Infrastructure\JsonResponse;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\AuthAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\RateLimiter;

/**
 * HTTP layer for /api/v1/auth/*. Thin by design -- all real logic lives
 * in App\Services\AuthService. Response envelope matches docs/ctf5.txt §4.
 */
final class AuthController
{
    private AuthService $auth;

    public function __construct()
    {
        $pdo = Database::connection();
        $users = new UserRepository($pdo);
        $rateLimiter = new RateLimiter(new AuthAttemptRepository($pdo));
        $audit = new AuditLogger(new AuditLogRepository($pdo));

        $this->auth = new AuthService($users, $rateLimiter, $audit);
    }

    public function register(): void
    {
        $input = $this->jsonBody();
        $ip = $this->clientIp();

        $result = $this->auth->register($input, $ip);

        if (!$result['success']) {
            $status = ($result['rate_limited'] ?? false) ? 429 : 422;
            JsonResponse::error('REGISTRATION_FAILED', implode(' ', $result['errors']), $status);
            return;
        }

        $roleName = $this->auth->roleName((int) $result['user']['role_id']);

        JsonResponse::success(
            ['user' => UserRepository::toPublicArray($result['user'], $roleName)],
            'Registration successful.',
            201
        );
    }

    public function login(): void
    {
        $input = $this->jsonBody();
        $identifier = is_string($input['identifier'] ?? null) ? $input['identifier'] : '';
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $ip = $this->clientIp();

        if ($identifier === '' || $password === '') {
            JsonResponse::error('INVALID_REQUEST', 'Identifier and password are required.', 400);
            return;
        }

        $result = $this->auth->login($identifier, $password, $ip);

        if (!$result['success']) {
            if (($result['rate_limited'] ?? false) === true) {
                JsonResponse::error('RATE_LIMITED', 'Too many login attempts. Please try again later.', 429);
                return;
            }

            // Deliberately generic -- never reveal whether the identifier
            // exists (docs/ctf9.txt requirement #16).
            JsonResponse::error('INVALID_CREDENTIALS', 'Invalid username/email or password.', 401);
            return;
        }

        $roleName = $this->auth->roleName((int) $result['user']['role_id']);

        JsonResponse::success([
            'user' => UserRepository::toPublicArray($result['user'], $roleName),
            'csrf_token' => Csrf::token(),
        ], 'Login successful.');
    }

    public function logout(): void
    {
        AuthMiddleware::handle($this->auth, function () {
            CsrfMiddleware::handle(function () {
                $this->auth->logout($this->clientIp());
                JsonResponse::success([], 'Logged out successfully.');
            });
        });
    }

    public function me(): void
    {
        AuthMiddleware::handle($this->auth, function (array $user) {
            $roleName = $this->auth->roleName((int) $user['role_id']);
            JsonResponse::success([
                'user' => UserRepository::toPublicArray($user, $roleName),
                'csrf_token' => Csrf::token(),
            ]);
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
