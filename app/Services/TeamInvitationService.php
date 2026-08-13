<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\Env;
use App\Infrastructure\Hash;
use App\Infrastructure\Token;
use App\Infrastructure\Validator;
use App\Repositories\TeamInvitationRepository;
use App\Repositories\TeamMemberRepository;
use PDO;

/**
 * Team invitation lifecycle: create, list, accept, reject. Uses the
 * existing team_invitations table from Phase 1 unmodified -- only a
 * secure hash of the invitation token is ever stored
 * (docs/ctf4.txt §10, ctf9.txt §13).
 *
 * Acceptance/rejection require the *authenticated* recipient's email to
 * match team_invitations.invited_email -- this is how Phase 3 ties an
 * invitation to its intended recipient without needing a new
 * invited_user_id column (the Phase 1 schema is not modified).
 */
final class TeamInvitationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly TeamInvitationRepository $invitations,
        private readonly TeamMemberRepository $members,
        private readonly TeamService $teamService,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Captain-only. @return array{success: bool, error_code?: string, errors?: string[], token?: string, invitation?: array}
     */
    public function createInvitation(array $actingUser, string $email, string $ip): array
    {
        $context = $this->teamService->myTeam($actingUser);
        if ($context === null) {
            return ['success' => false, 'error_code' => 'NO_TEAM'];
        }

        if (!$context['membership']['is_captain']) {
            return ['success' => false, 'error_code' => 'FORBIDDEN', 'errors' => ['Only the team captain can invite members.']];
        }

        $email = trim(strtolower($email));
        $errors = Validator::email($email);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($email === strtolower((string) $actingUser['email'])) {
            return ['success' => false, 'error_code' => 'INVALID_TARGET', 'errors' => ['You cannot invite yourself.']];
        }

        $teamId = (int) $context['team']['id'];

        $this->invitations->expireOverdue();

        if ($this->invitations->findPendingByTeamAndEmail($teamId, $email) !== null) {
            return ['success' => false, 'error_code' => 'INVITATION_EXISTS', 'errors' => ['A pending invitation already exists for that email.']];
        }

        $activeCount = count($this->members->activeMembersOfTeam($teamId));
        if ($activeCount >= $this->teamService->teamMaxSize()) {
            return ['success' => false, 'error_code' => 'TEAM_FULL', 'errors' => ['Your team is already at its maximum size.']];
        }

        $token = Token::generate();
        $tokenHash = Token::hash($token);
        $ttlHours = (int) Env::get('TEAM_INVITATION_TTL_HOURS', '72');
        $expiresAt = gmdate('Y-m-d H:i:s', time() + ($ttlHours * 3600));

        $invitationId = $this->invitations->create($teamId, (int) $actingUser['id'], $email, $tokenHash, $expiresAt);

        $this->audit->log(
            AuditLogger::TEAM_INVITATION_CREATED,
            (int) $actingUser['id'],
            ['team_id' => $teamId, 'invitation_id' => $invitationId], // never log the token itself
            Hash::correlate($ip),
            'team',
            $teamId
        );

        $invitation = $this->invitations->findByTokenHash($tokenHash);

        return [
            'success' => true,
            // The plaintext token is returned exactly once, here, and
            // never again -- there is no email delivery in Phase 3
            // (docs/ctf9.txt Phase 3 scope), so this response IS the
            // delivery mechanism for now.
            'token' => $token,
            'invitation' => $invitation,
        ];
    }

    /**
     * Captain-only. @return array{success: bool, error_code?: string, invitations?: array}
     */
    public function listMyTeamInvitations(array $actingUser): array
    {
        $context = $this->teamService->myTeam($actingUser);
        if ($context === null) {
            return ['success' => false, 'error_code' => 'NO_TEAM'];
        }

        if (!$context['membership']['is_captain']) {
            return ['success' => false, 'error_code' => 'FORBIDDEN'];
        }

        $this->invitations->expireOverdue();

        return ['success' => true, 'invitations' => $this->invitations->pendingForTeam((int) $context['team']['id'])];
    }

    /**
     * @return array{success: bool, error_code?: string, errors?: string[]}
     */
    public function acceptInvitation(array $user, string $token, string $ip): array
    {
        $this->invitations->expireOverdue();
        $tokenHash = Token::hash($token);

        $this->pdo->beginTransaction();
        try {
            $invitation = $this->invitations->lockByTokenHash($tokenHash);

            if ($invitation === null || $invitation['status'] !== 'pending') {
                $this->pdo->rollBack();
                // Deliberately generic -- avoid confirming token existence
                // (docs/ctf9.txt Phase 3: "avoid unnecessarily revealing
                // whether a token exists").
                return ['success' => false, 'error_code' => 'INVALID_INVITATION', 'errors' => ['This invitation is no longer valid.']];
            }

            if (strtotime((string) $invitation['expires_at']) < time()) {
                $this->invitations->markStatus((int) $invitation['id'], 'expired');
                $this->pdo->commit();
                return ['success' => false, 'error_code' => 'INVALID_INVITATION', 'errors' => ['This invitation is no longer valid.']];
            }

            if (strtolower((string) $invitation['invited_email']) !== strtolower((string) $user['email'])) {
                $this->pdo->rollBack();
                return ['success' => false, 'error_code' => 'INVALID_INVITATION', 'errors' => ['This invitation is no longer valid.']];
            }

            $existingMembership = $this->members->lockActiveMembershipForUser((int) $user['id']);
            if ($existingMembership !== null) {
                $this->pdo->rollBack();
                return ['success' => false, 'error_code' => 'ALREADY_IN_TEAM', 'errors' => ['You already belong to an active team.']];
            }

            $teamId = (int) $invitation['team_id'];
            $activeCount = $this->members->lockAndCountActiveMembers($teamId);
            if ($activeCount >= $this->teamService->teamMaxSize()) {
                $this->pdo->rollBack();
                return ['success' => false, 'error_code' => 'TEAM_FULL', 'errors' => ['That team is already at its maximum size.']];
            }

            $this->members->addMember($teamId, (int) $user['id'], false);
            $this->invitations->markStatus((int) $invitation['id'], 'accepted', true);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $this->audit->log(
            AuditLogger::TEAM_INVITATION_ACCEPTED,
            (int) $user['id'],
            ['team_id' => $teamId, 'invitation_id' => (int) $invitation['id']],
            Hash::correlate($ip),
            'team',
            $teamId
        );

        return ['success' => true];
    }

    /**
     * @return array{success: bool, error_code?: string, errors?: string[]}
     */
    public function rejectInvitation(array $user, string $token, string $ip): array
    {
        $this->invitations->expireOverdue();
        $tokenHash = Token::hash($token);

        $invitation = $this->invitations->findByTokenHash($tokenHash);

        if ($invitation === null || $invitation['status'] !== 'pending') {
            return ['success' => false, 'error_code' => 'INVALID_INVITATION', 'errors' => ['This invitation is no longer valid.']];
        }

        if (strtolower((string) $invitation['invited_email']) !== strtolower((string) $user['email'])) {
            return ['success' => false, 'error_code' => 'INVALID_INVITATION', 'errors' => ['This invitation is no longer valid.']];
        }

        $this->invitations->markStatus((int) $invitation['id'], 'declined');

        $this->audit->log(
            AuditLogger::TEAM_INVITATION_REJECTED,
            (int) $user['id'],
            ['team_id' => (int) $invitation['team_id'], 'invitation_id' => (int) $invitation['id']],
            Hash::correlate($ip),
            'team',
            (int) $invitation['team_id']
        );

        return ['success' => true];
    }
}
