<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\Hash;
use App\Repositories\ChallengeHintRepository;
use App\Repositories\ChallengeRepository;

/**
 * Challenge hint management + reveal. There is no per-user/team reveal-
 * tracking table in the existing schema (see docs/PHASE4_REPORT.md
 * "Known Limitations"), so "reveal" in Phase 4 means: an authenticated
 * participant may fetch the content of an active hint on a visible
 * challenge. Usage tracking and point-penalty application against a
 * team's score are explicitly deferred to the scoring phase per the
 * Phase 4 brief ("Do NOT implement final scoring logic in Phase 4").
 */
final class ChallengeHintService
{
    public function __construct(
        private readonly ChallengeHintRepository $hints,
        private readonly ChallengeRepository $challenges,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * @return array{success: bool, errors?: string[], hint?: array}
     */
    public function create(array $actingUser, int $challengeId, array $input, string $ip): array
    {
        $challenge = $this->challenges->findById($challengeId);
        if ($challenge === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        [$title, $content, $penalty, $sortOrder, $errors] = $this->validate($input);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->hints->create($challengeId, $title, $content, $penalty, $sortOrder);

        $this->audit->log(
            AuditLogger::CHALLENGE_HINT_CREATED,
            (int) $actingUser['id'],
            ['challenge_id' => $challengeId, 'hint_id' => $id],
            Hash::correlate($ip),
            'challenge',
            $challengeId
        );

        return ['success' => true, 'hint' => $this->hints->findById($id)];
    }

    /**
     * @return array{success: bool, error_code?: string, errors?: string[], hint?: array}
     */
    public function update(array $actingUser, int $hintId, array $input, string $ip): array
    {
        $hint = $this->hints->findById($hintId);
        if ($hint === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        [$title, $content, $penalty, $sortOrder, $errors] = $this->validate($input);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->hints->update($hintId, $title, $content, $penalty, $sortOrder);

        $this->audit->log(
            AuditLogger::CHALLENGE_HINT_UPDATED,
            (int) $actingUser['id'],
            ['challenge_id' => (int) $hint['challenge_id'], 'hint_id' => $hintId],
            Hash::correlate($ip),
            'challenge',
            (int) $hint['challenge_id']
        );

        return ['success' => true, 'hint' => $this->hints->findById($hintId)];
    }

    /**
     * @return array{success: bool, error_code?: string}
     */
    public function remove(array $actingUser, int $hintId, string $ip): array
    {
        $hint = $this->hints->findById($hintId);
        if ($hint === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        $this->hints->setStatus($hintId, 'inactive');

        $this->audit->log(
            AuditLogger::CHALLENGE_HINT_REMOVED,
            (int) $actingUser['id'],
            ['challenge_id' => (int) $hint['challenge_id'], 'hint_id' => $hintId],
            Hash::correlate($ip),
            'challenge',
            (int) $hint['challenge_id']
        );

        return ['success' => true];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForChallenge(int $challengeId, bool $activeOnly): array
    {
        return $this->hints->forChallenge($challengeId, $activeOnly);
    }

    /**
     * @return array{success: bool, error_code?: string, hint?: array}
     */
    public function reveal(array $user, int $hintId, bool $isPrivileged): array
    {
        $hint = $this->hints->findById($hintId);
        if ($hint === null || $hint['status'] !== 'active') {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        $challenge = $this->challenges->findById((int) $hint['challenge_id']);
        if ($challenge === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        if (!$isPrivileged && !in_array($challenge['status'], ChallengeRepository::PARTICIPANT_VISIBLE_STATUSES, true)) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        return ['success' => true, 'hint' => $hint];
    }

    /**
     * @return array{0: ?string, 1: string, 2: int, 3: int, 4: string[]}
     */
    private function validate(array $input): array
    {
        $errors = [];

        $title = isset($input['title']) && is_string($input['title']) ? trim($input['title']) : null;
        if ($title !== null && strlen($title) > 150) {
            $errors[] = 'Hint title must be 150 characters or fewer.';
        }

        $content = is_string($input['content'] ?? null) ? trim($input['content']) : '';
        if ($content === '') {
            $errors[] = 'Hint content is required.';
        }

        $penaltyRaw = $input['point_penalty'] ?? 0;
        $penalty = is_int($penaltyRaw) ? $penaltyRaw : (is_string($penaltyRaw) && ctype_digit($penaltyRaw) ? (int) $penaltyRaw : -1);
        if ($penalty < 0 || $penalty > 100000) {
            $errors[] = 'Point penalty must be a whole number between 0 and 100000.';
        }

        $sortOrderRaw = $input['sort_order'] ?? 0;
        $sortOrder = is_int($sortOrderRaw) ? $sortOrderRaw : (is_string($sortOrderRaw) && ctype_digit($sortOrderRaw) ? (int) $sortOrderRaw : 0);

        return [$title, $content, max(0, $penalty), $sortOrder, $errors];
    }
}
