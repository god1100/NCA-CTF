<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;

/**
 * Thin wrapper around AuditLogRepository with named authentication event
 * constants, so callers don't hand-type action strings
 * (docs/ctf9.txt requirement #17; event names follow docs/ctf4.txt §24
 * conventions).
 */
final class AuditLogger
{
    public const USER_REGISTERED = 'USER_REGISTERED';
    public const LOGIN_SUCCESS = 'LOGIN_SUCCESS';
    public const LOGIN_FAILED = 'LOGIN_FAILED';
    public const LOGOUT = 'LOGOUT';
    public const AUTHORIZATION_FAILURE = 'AUTHORIZATION_FAILURE';
    public const RATE_LIMIT_BLOCKED = 'RATE_LIMIT_BLOCKED';

    public function __construct(private readonly AuditLogRepository $repository)
    {
    }

    public function log(string $action, ?int $userId = null, array $metadata = [], ?string $ipHash = null): void
    {
        $this->repository->record($userId, $action, 'user', $userId, $metadata, $ipHash);
    }
}
