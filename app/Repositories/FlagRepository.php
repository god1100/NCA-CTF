<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Data access for the `flags` table (created in Phase 1, unmodified).
 * flag_hash is NEVER returned by any static-array helper here -- not
 * even to admins -- because there is no legitimate reason for the API
 * to echo it back once stored (docs/ctf9.txt Phase 4: never expose
 * flag_hash / internal flag metadata).
 */
final class FlagRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findActiveForChallenge(int $challengeId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM flags WHERE challenge_id = :challenge_id AND status = 'active' ORDER BY version DESC LIMIT 1"
        );
        $stmt->execute(['challenge_id' => $challengeId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function highestVersionForChallenge(int $challengeId): int
    {
        $stmt = $this->pdo->prepare('SELECT MAX(version) FROM flags WHERE challenge_id = :challenge_id');
        $stmt->execute(['challenge_id' => $challengeId]);
        $max = $stmt->fetchColumn();

        return $max !== null ? (int) $max : 0;
    }

    public function create(int $challengeId, string $flagHash, string $flagType, int $version): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO flags (challenge_id, flag_hash, flag_type, version, status)
             VALUES (:challenge_id, :flag_hash, :flag_type, :version, :status)'
        );
        $stmt->execute([
            'challenge_id' => $challengeId,
            'flag_hash' => $flagHash,
            'flag_type' => $flagType,
            'version' => $version,
            'status' => 'active',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE flags SET status = 'inactive' WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE flags SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Metadata only -- flag_hash deliberately excluded, for admins too.
     */
    public static function toMetadataArray(array $flag): array
    {
        return [
            'id' => (int) $flag['id'],
            'challenge_id' => (int) $flag['challenge_id'],
            'flag_type' => $flag['flag_type'],
            'version' => (int) $flag['version'],
            'status' => $flag['status'],
            'created_at' => $flag['created_at'],
            'updated_at' => $flag['updated_at'],
        ];
    }
}
