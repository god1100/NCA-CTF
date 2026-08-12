<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\Hash;
use App\Infrastructure\Session;
use App\Infrastructure\Validator;
use App\Repositories\UserRepository;

/**
 * Core authentication logic. This is the ONLY place that decides whether
 * a login/registration succeeds -- controllers stay thin and never
 * re-implement these checks (docs/ctf9.txt §5-6).
 *
 * Never trusts client-supplied role/status/user_id. Registration always
 * assigns the 'participant' role and 'active' status server-side.
 */
final class AuthService
{
    private const SESSION_USER_ID_KEY = 'auth_user_id';

    public function __construct(
        private readonly UserRepository $users,
        private readonly RateLimiter $rateLimiter,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * @return array{success: bool, errors: string[], user?: array}
     */
    public function register(array $input, string $ip): array
    {
        $ipHash = Hash::correlate($ip);

        if ($this->rateLimiter->isBlocked('register', $ipHash, null)) {
            $this->audit->log(AuditLogger::RATE_LIMIT_BLOCKED, null, ['purpose' => 'register'], $ipHash);
            return ['success' => false, 'errors' => ['Too many registration attempts. Please try again later.'], 'rate_limited' => true];
        }

        $username = is_string($input['username'] ?? null) ? trim($input['username']) : '';
        $email = is_string($input['email'] ?? null) ? trim(strtolower($input['email'])) : '';
        $password = $input['password'] ?? null;
        $fullName = isset($input['full_name']) && is_string($input['full_name']) ? trim($input['full_name']) : null;

        $errors = [
            ...Validator::username($username),
            ...Validator::email($email),
            ...Validator::password($password),
            ...Validator::fullName($fullName),
        ];

        if ($errors !== []) {
            $this->rateLimiter->record('register', null, $ipHash, false);
            return ['success' => false, 'errors' => $errors];
        }

        if ($this->users->findByUsername($username) !== null) {
            $this->rateLimiter->record('register', null, $ipHash, false);
            return ['success' => false, 'errors' => ['That username is already taken.']];
        }

        if ($this->users->findByEmail($email) !== null) {
            $this->rateLimiter->record('register', null, $ipHash, false);
            return ['success' => false, 'errors' => ['That email is already registered.']];
        }

        $roleId = $this->users->roleIdByName('participant');
        if ($roleId === null) {
            // Should never happen once Phase 1 seed data is present, but
            // fail loudly rather than silently assigning an arbitrary role.
            return ['success' => false, 'errors' => ['Registration is temporarily unavailable.']];
        }

        $passwordHash = $this->hashPassword($password);
        $userId = $this->users->create($username, $email, $passwordHash, $fullName ?: null, $roleId);

        $this->rateLimiter->record('register', null, $ipHash, true);
        $this->audit->log(AuditLogger::USER_REGISTERED, $userId, ['username' => $username], $ipHash);

        $user = $this->users->findById($userId);

        return ['success' => true, 'errors' => [], 'user' => $user];
    }

    /**
     * @return array{success: bool, error_code?: string, user?: array, rate_limited?: bool}
     */
    public function login(string $identifier, string $password, string $ip): array
    {
        $ipHash = Hash::correlate($ip);
        $identifierHash = Hash::correlate(strtolower(trim($identifier)));

        if ($this->rateLimiter->isBlocked('login', $ipHash, $identifierHash)) {
            $this->audit->log(AuditLogger::RATE_LIMIT_BLOCKED, null, ['purpose' => 'login'], $ipHash);
            return ['success' => false, 'error_code' => 'RATE_LIMITED', 'rate_limited' => true];
        }

        $user = $this->users->findByIdentifier(trim($identifier));

        // Generic failure path shared by "no such user" and "wrong
        // password" so the API never reveals which one occurred
        // (docs/ctf5.txt §10, ctf9.txt requirement #16).
        if ($user === null || !$this->verifyPassword($password, $user['password_hash'])) {
            $this->rateLimiter->record('login', $identifierHash, $ipHash, false);
            $this->audit->log(AuditLogger::LOGIN_FAILED, $user['id'] ?? null, ['identifier' => $identifier], $ipHash);
            return ['success' => false, 'error_code' => 'INVALID_CREDENTIALS'];
        }

        if ($user['status'] !== 'active') {
            $this->rateLimiter->record('login', $identifierHash, $ipHash, false);
            $this->audit->log(AuditLogger::LOGIN_FAILED, $user['id'], ['reason' => 'inactive_account', 'status' => $user['status']], $ipHash);
            // Still a generic message -- do not reveal account status to
            // an unauthenticated caller.
            return ['success' => false, 'error_code' => 'INVALID_CREDENTIALS'];
        }

        $this->rateLimiter->record('login', $identifierHash, $ipHash, true);
        $this->users->updateLastLogin((int) $user['id']);

        // Session fixation prevention: regenerate the session ID on every
        // successful login (docs/ctf5.txt §10, ctf9.txt requirement #10).
        Session::regenerate();
        Session::set(self::SESSION_USER_ID_KEY, (int) $user['id']);

        $this->audit->log(AuditLogger::LOGIN_SUCCESS, (int) $user['id'], [], $ipHash);

        $freshUser = $this->users->findById((int) $user['id']);

        return ['success' => true, 'user' => $freshUser];
    }

    public function logout(?string $ip = null): void
    {
        $userId = $this->currentUserId();
        Session::destroy();

        if ($userId !== null) {
            $ipHash = $ip !== null ? Hash::correlate($ip) : null;
            $this->audit->log(AuditLogger::LOGOUT, $userId, [], $ipHash);
        }
    }

    /**
     * Resolves the current user strictly from the server-side session --
     * never from any client-supplied field (docs/ctf9.txt §5).
     * Re-fetches from the database on every call so a status change
     * (e.g. suspension) takes effect immediately rather than being
     * cached in the session.
     */
    public function currentUser(): ?array
    {
        $userId = $this->currentUserId();
        if ($userId === null) {
            return null;
        }

        $user = $this->users->findById($userId);

        if ($user === null || $user['status'] !== 'active') {
            // Account was removed or deactivated after the session was
            // created -- treat as logged out rather than serving stale data.
            return null;
        }

        return $user;
    }

    public function currentUserId(): ?int
    {
        $id = Session::get(self::SESSION_USER_ID_KEY);
        return is_int($id) ? $id : null;
    }

    public function roleName(int $roleId): ?string
    {
        return $this->users->roleName($roleId);
    }

    private function hashPassword(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }

        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
