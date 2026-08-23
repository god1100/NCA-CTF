<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Infrastructure\Database;
use App\Infrastructure\JsonResponse;
use App\Infrastructure\Session;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\AuthAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\RateLimiter;

final class PasswordController
{
    private AuthService $auth;
    private UserRepository $users;

    public function __construct()
    {
        $pdo = Database::connection();
        $this->users = new UserRepository($pdo);
        
        // Create AuthService instance for the middleware
        $rateLimiter = new RateLimiter(new AuthAttemptRepository($pdo));
        $audit = new AuditLogger(new AuditLogRepository($pdo));
        $this->auth = new AuthService($this->users, $rateLimiter, $audit);
    }

    public function changePassword(): void
    {
        // Use AuthMiddleware with the AuthService instance
        AuthMiddleware::handle($this->auth, function (array $user) {
            CsrfMiddleware::handle(function () use ($user) {
                $input = $this->jsonBody();
                $currentPassword = $input['current_password'] ?? '';
                $newPassword = $input['new_password'] ?? '';
                $confirmPassword = $input['confirm_password'] ?? '';

                // Validate
                if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                    JsonResponse::error('VALIDATION_FAILED', 'All password fields are required.', 422);
                    return;
                }

                if ($newPassword !== $confirmPassword) {
                    JsonResponse::error('VALIDATION_FAILED', 'New passwords do not match.', 422);
                    return;
                }

                if (strlen($newPassword) < 6) {
                    JsonResponse::error('VALIDATION_FAILED', 'Password must be at least 6 characters.', 422);
                    return;
                }

                // Verify current password
                if (!password_verify($currentPassword, $user['password_hash'])) {
                    JsonResponse::error('INVALID_PASSWORD', 'Current password is incorrect.', 401);
                    return;
                }

                // Update password
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $this->users->updatePassword((int) $user['id'], $newHash);

                JsonResponse::success([], 'Password updated successfully.');
            });
        });
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}