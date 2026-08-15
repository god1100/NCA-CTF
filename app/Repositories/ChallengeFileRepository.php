<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Data access for `challenge_files` (created in Phase 1, unmodified).
 * storage_path is a server-generated relative path under storage/
 * (outside the public webroot) -- never a client-supplied value
 * (docs/ctf4.txt §13, ctf9.txt §17).
 */
final class ChallengeFileRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM challenge_files WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forChallenge(int $challengeId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM challenge_files WHERE challenge_id = :challenge_id ORDER BY created_at ASC');
        $stmt->execute(['challenge_id' => $challengeId]);

        return $stmt->fetchAll();
    }

    public function create(int $challengeId, string $originalName, string $storagePath, ?string $mimeType, int $fileSize, ?string $sha256): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO challenge_files (challenge_id, original_name, storage_path, mime_type, file_size, sha256_checksum)
             VALUES (:challenge_id, :original_name, :storage_path, :mime_type, :file_size, :sha256_checksum)'
        );
        $stmt->execute([
            'challenge_id' => $challengeId,
            'original_name' => $originalName,
            'storage_path' => $storagePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'sha256_checksum' => $sha256,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM challenge_files WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Participant/API-safe shape -- storage_path (a server filesystem
     * location) is never exposed (docs/ctf9.txt Phase 4: "Do not expose
     * filesystem paths to participants").
     */
    public static function toPublicArray(array $file): array
    {
        return [
            'id' => (int) $file['id'],
            'name' => $file['original_name'],
            'size' => (int) $file['file_size'],
            'sha256' => $file['sha256_checksum'],
        ];
    }
}
