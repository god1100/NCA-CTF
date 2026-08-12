<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Writes to the audit_logs table created in Phase 1
 * (database/migrations/0023_create_audit_logs_table.sql). No new audit
 * table is introduced -- Phase 2 only adds writers for authentication
 * events (docs/ctf9.txt §27, Phase 2 requirement #17).
 */
final class AuditLogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = [],
        ?string $ipHash = null
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata, ip_hash)
             VALUES (:user_id, :action, :entity_type, :entity_id, :metadata, :ip_hash)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata === [] ? null : json_encode($metadata),
            'ip_hash' => $ipHash,
        ]);
    }
}
