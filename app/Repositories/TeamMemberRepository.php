<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Data access for the `team_members` table (created in Phase 1,
 * unmodified). Membership rows are never physically deleted -- status
 * transitions to 'removed'/'left' instead, so historical
 * submissions/solves (which reference team_id/user_id directly, not
 * team_members) are never at risk (docs/ctf4.txt §29, ctf9.txt team rule
 * #11).
 */
final class TeamMemberRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * The active team_members row for a user, if any. A user may only
     * have one 'active' row across all teams at a time -- enforced in
     * App\Services\TeamService with row locking, not by a DB constraint
     * (docs/ctf9.txt team rule #1-2, matching the Phase 1 note that this
     * application-level rule was deferred to a later phase).
     */
    public function findActiveMembershipForUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM team_members WHERE user_id = :user_id AND status = 'active' LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Same as above, but takes a row lock (FOR UPDATE) -- used inside a
     * transaction to prevent a user joining two teams via a race
     * condition (docs/ctf9.txt security requirements: "Prevent race
     * conditions around team capacity ... using transactions and
     * appropriate locking").
     */
    public function lockActiveMembershipForUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM team_members WHERE user_id = :user_id AND status = 'active' LIMIT 1 FOR UPDATE"
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findMembershipInTeam(int $teamId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM team_members WHERE team_id = :team_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['team_id' => $teamId, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeMembersOfTeam(int $teamId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT tm.*, u.username, u.full_name
             FROM team_members tm
             JOIN users u ON u.id = tm.user_id
             WHERE tm.team_id = :team_id AND tm.status = 'active'
             ORDER BY tm.is_captain DESC, tm.joined_at ASC"
        );
        $stmt->execute(['team_id' => $teamId]);

        return $stmt->fetchAll();
    }

    /**
     * Row-locking count used inside a transaction to enforce
     * team_max_size without a race (docs/ctf9.txt security requirements).
     */
    public function lockAndCountActiveMembers(int $teamId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM team_members WHERE team_id = :team_id AND status = 'active' FOR UPDATE"
        );
        $stmt->execute(['team_id' => $teamId]);

        return (int) $stmt->fetchColumn();
    }

    public function addMember(int $teamId, int $userId, bool $isCaptain): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO team_members (team_id, user_id, is_captain, status)
             VALUES (:team_id, :user_id, :is_captain, :status)'
        );
        $stmt->execute([
            'team_id' => $teamId,
            'user_id' => $userId,
            'is_captain' => $isCaptain ? 1 : 0,
            'status' => 'active',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function setStatus(int $teamMemberId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE team_members SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $teamMemberId]);
    }

    public function setCaptain(int $teamMemberId, bool $isCaptain): void
    {
        $stmt = $this->pdo->prepare('UPDATE team_members SET is_captain = :is_captain WHERE id = :id');
        $stmt->execute(['is_captain' => $isCaptain ? 1 : 0, 'id' => $teamMemberId]);
    }

    public static function toPublicArray(array $member): array
    {
        return [
            'user_id' => (int) $member['user_id'],
            'username' => $member['username'] ?? null,
            'full_name' => $member['full_name'] ?? null,
            'is_captain' => (bool) $member['is_captain'],
            'joined_at' => $member['joined_at'],
        ];
    }
}
