<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\Hash;
use App\Infrastructure\Str;
use App\Repositories\SystemSettingRepository;
use App\Repositories\TeamMemberRepository;
use App\Repositories\TeamRepository;
use PDO;

/**
 * Core team management logic. Controllers stay thin; every authorization
 * and business-rule decision (captain-only actions, capacity limits,
 * one-active-team-per-user) lives here, keyed off the authenticated
 * user passed in by the controller -- never off a client-supplied
 * team_id/user_id (docs/ctf9.txt IDOR requirements).
 */
final class TeamService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly TeamRepository $teams,
        private readonly TeamMemberRepository $members,
        private readonly SystemSettingRepository $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * @return array{success: bool, errors?: string[], team?: array, error_code?: string}
     */
    public function createTeam(array $user, string $name, string $ip): array
    {
        $userId = (int) $user['id'];
        $name = trim($name);

        if ($name === '' || strlen($name) > 100) {
            return ['success' => false, 'errors' => ['Team name must be between 1 and 100 characters.']];
        }

        // One active team per user (docs/ctf9.txt team rule #1-2).
        $this->pdo->beginTransaction();
        try {
            $existing = $this->members->lockActiveMembershipForUser($userId);
            if ($existing !== null) {
                $this->pdo->rollBack();
                return ['success' => false, 'error_code' => 'ALREADY_IN_TEAM', 'errors' => ['You already belong to an active team.']];
            }

            if ($this->teams->nameExists($name)) {
                $this->pdo->rollBack();
                return ['success' => false, 'error_code' => 'NAME_TAKEN', 'errors' => ['That team name is already taken.']];
            }

            $slug = $this->uniqueSlugFor($name);

            $teamId = $this->teams->create($name, $slug);
            $this->members->addMember($teamId, $userId, true);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $this->audit->log(
            AuditLogger::TEAM_CREATED,
            $userId,
            ['team_id' => $teamId, 'name' => $name],
            Hash::correlate($ip),
            'team',
            $teamId
        );

        $team = $this->teams->findById($teamId);

        return ['success' => true, 'team' => $team];
    }

    /**
     * Returns the authenticated user's active team + membership context,
     * or null if they have no active team.
     */
    public function myTeam(array $user): ?array
    {
        $membership = $this->members->findActiveMembershipForUser((int) $user['id']);
        if ($membership === null) {
            return null;
        }

        $team = $this->teams->findById((int) $membership['team_id']);
        if ($team === null) {
            return null;
        }

        return ['team' => $team, 'membership' => $membership];
    }

    /**
     * @return array{success: bool, error_code?: string, members?: array}
     */
    public function listMyTeamMembers(array $user): array
    {
        $context = $this->myTeam($user);
        if ($context === null) {
            return ['success' => false, 'error_code' => 'NO_TEAM'];
        }

        $members = $this->members->activeMembersOfTeam((int) $context['team']['id']);

        return ['success' => true, 'members' => $members];
    }

    /**
     * Captain-only. The target user is always resolved by ID *within the
     * acting captain's own team* -- never a client-supplied team_id
     * (prevents the exact IDOR the spec calls out: "captain of Team A
     * removing a member of Team B").
     *
     * @return array{success: bool, error_code?: string, errors?: string[]}
     */
    public function removeMember(array $actingUser, int $targetUserId, string $ip): array
    {
        $context = $this->myTeam($actingUser);
        if ($context === null) {
            return ['success' => false, 'error_code' => 'NO_TEAM'];
        }

        if (!$context['membership']['is_captain']) {
            return ['success' => false, 'error_code' => 'FORBIDDEN', 'errors' => ['Only the team captain can remove members.']];
        }

        $teamId = (int) $context['team']['id'];

        if ($targetUserId === (int) $actingUser['id']) {
            return ['success' => false, 'error_code' => 'INVALID_TARGET', 'errors' => ['Use the leave endpoint to remove yourself.']];
        }

        $target = $this->members->findMembershipInTeam($teamId, $targetUserId);
        if ($target === null || $target['status'] !== 'active') {
            return ['success' => false, 'error_code' => 'NOT_FOUND', 'errors' => ['That user is not an active member of your team.']];
        }

        // Membership row transitions to 'removed', never deleted --
        // submissions/solves reference user_id/team_id directly and are
        // completely unaffected (docs/ctf9.txt team rule #11).
        $this->members->setStatus((int) $target['id'], 'removed');

        $this->audit->log(
            AuditLogger::TEAM_MEMBER_REMOVED,
            (int) $actingUser['id'],
            ['team_id' => $teamId, 'removed_user_id' => $targetUserId],
            Hash::correlate($ip),
            'team',
            $teamId
        );

        return ['success' => true];
    }

    /**
     * @return array{success: bool, error_code?: string, errors?: string[]}
     */
    public function leaveTeam(array $actingUser, string $ip): array
    {
        $context = $this->myTeam($actingUser);
        if ($context === null) {
            return ['success' => false, 'error_code' => 'NO_TEAM'];
        }

        $teamId = (int) $context['team']['id'];
        $membership = $context['membership'];

        if ($membership['is_captain']) {
            // Rule #9: captain cannot leave without transferring first.
            // Exception: if they're the last active member, leaving
            // just empties the team -- there's no one to transfer to.
            $activeCount = count($this->members->activeMembersOfTeam($teamId));
            if ($activeCount > 1) {
                return [
                    'success' => false,
                    'error_code' => 'CAPTAIN_MUST_TRANSFER',
                    'errors' => ['Transfer captaincy to another member before leaving.'],
                ];
            }
        }

        $this->members->setStatus((int) $membership['id'], 'left');

        $this->audit->log(
            AuditLogger::TEAM_MEMBER_LEFT,
            (int) $actingUser['id'],
            ['team_id' => $teamId],
            Hash::correlate($ip),
            'team',
            $teamId
        );

        return ['success' => true];
    }

    /**
     * Captain-only. Transfers captaincy to another active member of the
     * SAME team as the acting captain (resolved server-side, never from
     * a client-supplied team_id).
     *
     * @return array{success: bool, error_code?: string, errors?: string[]}
     */
    public function transferCaptain(array $actingUser, int $newCaptainUserId, string $ip): array
    {
        $context = $this->myTeam($actingUser);
        if ($context === null) {
            return ['success' => false, 'error_code' => 'NO_TEAM'];
        }

        if (!$context['membership']['is_captain']) {
            return ['success' => false, 'error_code' => 'FORBIDDEN', 'errors' => ['Only the team captain can transfer captaincy.']];
        }

        $teamId = (int) $context['team']['id'];

        if ($newCaptainUserId === (int) $actingUser['id']) {
            return ['success' => false, 'error_code' => 'INVALID_TARGET', 'errors' => ['You are already the captain.']];
        }

        $target = $this->members->findMembershipInTeam($teamId, $newCaptainUserId);
        if ($target === null || $target['status'] !== 'active') {
            return ['success' => false, 'error_code' => 'NOT_FOUND', 'errors' => ['That user is not an active member of your team.']];
        }

        $this->pdo->beginTransaction();
        try {
            $this->members->setCaptain((int) $context['membership']['id'], false);
            $this->members->setCaptain((int) $target['id'], true);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $this->audit->log(
            AuditLogger::CAPTAIN_TRANSFERRED,
            (int) $actingUser['id'],
            ['team_id' => $teamId, 'new_captain_user_id' => $newCaptainUserId],
            Hash::correlate($ip),
            'team',
            $teamId
        );

        return ['success' => true];
    }

    public function teamMinSize(): int
    {
        return $this->settings->getInt('team_min_size', 1);
    }

    public function teamMaxSize(): int
    {
        return $this->settings->getInt('team_max_size', 4);
    }

    private function uniqueSlugFor(string $name): string
    {
        $base = Str::slugify($name);
        $slug = $base;
        $suffix = 2;

        while ($this->teams->slugExists($slug)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return substr($slug, 0, 120);
    }
}
