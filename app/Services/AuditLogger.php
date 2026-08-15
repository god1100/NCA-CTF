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

    // --- Phase 3: team management -----------------------------------
    public const TEAM_CREATED = 'TEAM_CREATED';
    public const TEAM_INVITATION_CREATED = 'TEAM_INVITATION_CREATED';
    public const TEAM_INVITATION_ACCEPTED = 'TEAM_INVITATION_ACCEPTED';
    public const TEAM_INVITATION_REJECTED = 'TEAM_INVITATION_REJECTED';
    public const TEAM_MEMBER_REMOVED = 'TEAM_MEMBER_REMOVED';
    public const TEAM_MEMBER_LEFT = 'TEAM_MEMBER_LEFT';
    public const CAPTAIN_TRANSFERRED = 'CAPTAIN_TRANSFERRED';

    // --- Phase 4: challenge management --------------------------------
    public const CHALLENGE_CREATED = 'CHALLENGE_CREATED';
    public const CHALLENGE_UPDATED = 'CHALLENGE_UPDATED';
    public const CHALLENGE_PUBLISHED = 'CHALLENGE_PUBLISHED';
    public const CHALLENGE_PAUSED = 'CHALLENGE_PAUSED';
    public const CHALLENGE_ARCHIVED = 'CHALLENGE_ARCHIVED';
    public const CHALLENGE_DELETED = 'CHALLENGE_DELETED';
    public const CHALLENGE_FILE_ADDED = 'CHALLENGE_FILE_ADDED';
    public const CHALLENGE_FILE_REMOVED = 'CHALLENGE_FILE_REMOVED';
    public const CHALLENGE_HINT_CREATED = 'CHALLENGE_HINT_CREATED';
    public const CHALLENGE_HINT_UPDATED = 'CHALLENGE_HINT_UPDATED';
    public const CHALLENGE_HINT_REMOVED = 'CHALLENGE_HINT_REMOVED';
    public const CHALLENGE_FLAG_CREATED = 'CHALLENGE_FLAG_CREATED';
    public const CHALLENGE_FLAG_UPDATED = 'CHALLENGE_FLAG_UPDATED';

    public function __construct(private readonly AuditLogRepository $repository)
    {
    }

    /**
     * @param string|null $entityType Defaults to 'user' (matching Phase 2
     *   auth events). Phase 3 team events pass 'team' explicitly.
     * @param int|null $entityId Defaults to $userId when $entityType is
     *   left as the default, preserving Phase 2 call sites unchanged.
     */
    public function log(
        string $action,
        ?int $userId = null,
        array $metadata = [],
        ?string $ipHash = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): void {
        $this->repository->record(
            $userId,
            $action,
            $entityType ?? 'user',
            $entityType === null ? $userId : $entityId,
            $metadata,
            $ipHash
        );
    }
}
