<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\Hash;
use App\Repositories\ChallengeRepository;
use App\Repositories\FlagRepository;

/**
 * Flag MANAGEMENT only -- no submission/validation logic exists here or
 * anywhere yet (that arrives with the scoring phase). Plaintext flags
 * are hashed immediately and never persisted or logged
 * (docs/ctf9.txt Phase 4: flag management scope).
 *
 * Hashing approach: SHA-256 of the normalized flag, matching the
 * approach described in docs/ctf4.txt §11 ("SHA-256(flag)"). This is
 * deliberately NOT password_hash()/Argon2id -- flags are high-entropy
 * server-generated-or-chosen strings compared for exact equality during
 * submission, not low-entropy user secrets needing per-hash salting and
 * deliberate slowness.
 */
final class FlagService
{
    public function __construct(
        private readonly FlagRepository $flags,
        private readonly ChallengeRepository $challenges,
        private readonly AuditLogger $audit,
    ) {
    }

    public static function normalize(string $flag): string
    {
        return trim($flag);
    }

    public static function hash(string $flag): string
    {
        return hash('sha256', self::normalize($flag));
    }

    /**
     * @return array{success: bool, errors?: string[], error_code?: string, flag?: array}
     */
    public function create(array $actingUser, int $challengeId, string $plaintextFlag, string $ip): array
    {
        $challenge = $this->challenges->findById($challengeId);
        if ($challenge === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        $errors = $this->validateFlag($plaintextFlag);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $existing = $this->flags->findActiveForChallenge($challengeId);
        if ($existing !== null) {
            return ['success' => false, 'error_code' => 'FLAG_EXISTS', 'errors' => ['This challenge already has an active flag. Use update to replace it.']];
        }

        $version = $this->flags->highestVersionForChallenge($challengeId) + 1;
        $hash = self::hash($plaintextFlag);
        // $plaintextFlag deliberately goes out of scope here and is
        // never written to a variable that outlives this method, logged,
        // or returned.
        $id = $this->flags->create($challengeId, $hash, 'static', $version);

        $this->audit->log(
            AuditLogger::CHALLENGE_FLAG_CREATED,
            (int) $actingUser['id'],
            ['challenge_id' => $challengeId, 'flag_id' => $id, 'version' => $version], // never the flag itself
            Hash::correlate($ip),
            'challenge',
            $challengeId
        );

        return ['success' => true, 'flag' => $this->flags->findActiveForChallenge($challengeId)];
    }

    /**
     * Replaces the active flag: deactivates the current one and creates
     * a new version, preserving history (docs/ctf4.txt §13 "flags may
     * have multiple historical flag versions but only one active").
     *
     * @return array{success: bool, errors?: string[], error_code?: string, flag?: array}
     */
    public function replace(array $actingUser, int $challengeId, string $plaintextFlag, string $ip): array
    {
        $challenge = $this->challenges->findById($challengeId);
        if ($challenge === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        $errors = $this->validateFlag($plaintextFlag);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $existing = $this->flags->findActiveForChallenge($challengeId);
        if ($existing !== null) {
            $this->flags->deactivate((int) $existing['id']);
        }

        $version = $this->flags->highestVersionForChallenge($challengeId) + 1;
        $hash = self::hash($plaintextFlag);
        $id = $this->flags->create($challengeId, $hash, 'static', $version);

        $this->audit->log(
            AuditLogger::CHALLENGE_FLAG_UPDATED,
            (int) $actingUser['id'],
            ['challenge_id' => $challengeId, 'flag_id' => $id, 'version' => $version],
            Hash::correlate($ip),
            'challenge',
            $challengeId
        );

        return ['success' => true, 'flag' => $this->flags->findActiveForChallenge($challengeId)];
    }

    /**
     * Metadata only -- never the hash (docs/ctf9.txt Phase 4).
     *
     * @return array{success: bool, error_code?: string, flag?: array}
     */
    public function metadata(int $challengeId): array
    {
        $flag = $this->flags->findActiveForChallenge($challengeId);
        if ($flag === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        return ['success' => true, 'flag' => $flag];
    }

    /**
     * @return string[]
     */
    private function validateFlag(string $flag): array
    {
        $flag = self::normalize($flag);

        if ($flag === '' || strlen($flag) > 500) {
            return ['Flag must be between 1 and 500 characters.'];
        }

        return [];
    }
}
