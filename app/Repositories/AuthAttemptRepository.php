<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Data access for the auth_attempts table (Phase 2 addition,
 * database/migrations/0025_create_auth_attempts_table.sql). Used by
 * App\Services\RateLimiter -- see that class for the actual policy.
 */
final class AuthAttemptRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(string $purpose, ?string $identifierHash, string $ipHash, bool $successful): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO auth_attempts (purpose, identifier_hash, ip_hash, successful)
             VALUES (:purpose, :identifier_hash, :ip_hash, :successful)'
        );
        $stmt->execute([
            'purpose' => $purpose,
            'identifier_hash' => $identifierHash,
            'ip_hash' => $ipHash,
            'successful' => $successful ? 1 : 0,
        ]);
    }

    public function countFailedSince(string $purpose, string $column, string $value, int $sinceSeconds): int
    {
        $allowedColumns = ['ip_hash', 'identifier_hash'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new \InvalidArgumentException('Invalid rate-limit column.');
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM auth_attempts
             WHERE purpose = :purpose
               AND {$column} = :value
               AND successful = 0
               AND created_at >= (NOW() - INTERVAL :seconds SECOND)"
        );
        $stmt->bindValue('purpose', $purpose);
        $stmt->bindValue('value', $value);
        $stmt->bindValue('seconds', $sinceSeconds, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
