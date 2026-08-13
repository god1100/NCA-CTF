<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Data access for the `team_invitations` table (created in Phase 1,
 * unmodified). Only token_hash is ever stored -- never the plaintext
 * token (docs/ctf4.txt §10, ctf9.txt §13).
 */
final class TeamInvitationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $teamId, ?int $invitedBy, string $invitedEmail, string $tokenHash, string $expiresAt): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO team_invitations (team_id, invited_by, invited_email, token_hash, status, expires_at)
             VALUES (:team_id, :invited_by, :invited_email, :token_hash, :status, :expires_at)'
        );
        $stmt->execute([
            'team_id' => $teamId,
            'invited_by' => $invitedBy,
            'invited_email' => $invitedEmail,
            'token_hash' => $tokenHash,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM team_invitations WHERE token_hash = :token_hash LIMIT 1');
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Row-locking lookup used inside the accept/reject transaction to
     * prevent a token being redeemed twice via a race condition.
     */
    public function lockByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM team_invitations WHERE token_hash = :token_hash LIMIT 1 FOR UPDATE');
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findPendingByTeamAndEmail(int $teamId, string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM team_invitations
             WHERE team_id = :team_id AND invited_email = :email AND status = 'pending'
             LIMIT 1"
        );
        $stmt->execute(['team_id' => $teamId, 'email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingForTeam(int $teamId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, team_id, invited_email, status, expires_at, created_at
             FROM team_invitations
             WHERE team_id = :team_id AND status = 'pending'
             ORDER BY created_at DESC"
        );
        $stmt->execute(['team_id' => $teamId]);

        return $stmt->fetchAll();
    }

    public function markStatus(int $id, string $status, bool $setAcceptedAt = false): void
    {
        if ($setAcceptedAt) {
            $stmt = $this->pdo->prepare('UPDATE team_invitations SET status = :status, accepted_at = NOW() WHERE id = :id');
        } else {
            $stmt = $this->pdo->prepare('UPDATE team_invitations SET status = :status WHERE id = :id');
        }
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Bulk-expires any pending invitation past its expires_at. Called
     * opportunistically before token lookups so status is always
     * current without needing a background cron in Phase 3.
     */
    public function expireOverdue(): int
    {
        $stmt = $this->pdo->prepare(
            "UPDATE team_invitations SET status = 'expired'
             WHERE status = 'pending' AND expires_at < NOW()"
        );
        $stmt->execute();

        return $stmt->rowCount();
    }

    public static function toPublicArray(array $invitation): array
    {
        return [
            'id' => (int) $invitation['id'],
            'team_id' => (int) $invitation['team_id'],
            'invited_email' => $invitation['invited_email'],
            'status' => $invitation['status'],
            'expires_at' => $invitation['expires_at'],
            'created_at' => $invitation['created_at'],
        ];
    }
}
