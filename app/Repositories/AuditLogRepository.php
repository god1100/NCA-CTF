<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use JsonException;

/**
 * Writes authentication, authorization, and security events to audit_logs.
 *
 * Phase 2 uses the audit_logs table created in Phase 1.
 *
 * Important:
 * - user_id is nullable because some events can happen before authentication.
 * - Invalid/non-positive user IDs are normalized to NULL rather than causing
 *   a foreign-key violation.
 * - Metadata is stored as JSON.
 */
final class AuditLogRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * Record an audit event.
     *
     * @param int|null $userId
     * @param string $action
     * @param string|null $entityType
     * @param int|null $entityId
     * @param array<string, mixed> $metadata
     * @param string|null $ipHash
     */
    public function record(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = [],
        ?string $ipHash = null
    ): void {
        /*
         * user_id is a foreign key to users.id.
         *
         * A value of 0 is not a valid user ID and would cause:
         *
         * SQLSTATE[23000]: Integrity constraint violation: 1452
         *
         * Normalize invalid IDs to NULL so unauthenticated or malformed
         * audit events can still be recorded safely.
         */
        if ($userId !== null && $userId <= 0) {
            $userId = null;
        }

        if ($entityId !== null && $entityId <= 0) {
            $entityId = null;
        }

        $metadataJson = null;

        if ($metadata !== []) {
            try {
                $metadataJson = json_encode(
                    $metadata,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            } catch (JsonException $e) {
                /*
                 * Audit logging must never break the main application flow
                 * because metadata contains an unserializable value.
                 *
                 * Store a small fallback object instead.
                 */
                $metadataJson = json_encode([
                    'audit_metadata_error' => 'Unable to encode metadata',
                ]);
            }
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs
                (user_id, action, entity_type, entity_id, metadata, ip_hash)
             VALUES
                (:user_id, :action, :entity_type, :entity_id, :metadata, :ip_hash)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadataJson,
            'ip_hash' => $ipHash,
        ]);
    }
}