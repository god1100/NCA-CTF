<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\FileStorage;
use App\Infrastructure\Hash;
use App\Repositories\ChallengeFileRepository;
use App\Repositories\ChallengeRepository;

/**
 * Challenge file attachment management: registration (upload), listing,
 * controlled download resolution, and removal. Storage details are
 * delegated to App\Infrastructure\FileStorage -- this class owns the
 * business rules (visibility, size/extension limits, ownership).
 */
final class ChallengeFileService
{
    private int $maxSizeBytes;

    public function __construct(
        private readonly ChallengeFileRepository $files,
        private readonly ChallengeRepository $challenges,
        private readonly AuditLogger $audit,
        private readonly string $projectRoot,
        int $maxSizeMb,
    ) {
        $this->maxSizeBytes = $maxSizeMb * 1024 * 1024;
    }

    /**
     * @return array{success: bool, error_code?: string, errors?: string[], file?: array}
     */
    public function upload(array $actingUser, int $challengeId, array $uploadedFile, string $ip): array
    {
        $challenge = $this->challenges->findById($challengeId);
        if ($challenge === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'errors' => ['No valid file was uploaded.']];
        }

        $tmpPath = $uploadedFile['tmp_name'] ?? '';
        if (!is_uploaded_file($tmpPath)) {
            // Defense in depth: refuse anything that isn't a genuine
            // PHP-managed upload, even though this should be unreachable
            // via the normal $_FILES flow.
            return ['success' => false, 'errors' => ['Invalid upload.']];
        }

        $size = (int) ($uploadedFile['size'] ?? 0);
        if ($size <= 0 || $size > $this->maxSizeBytes) {
            return ['success' => false, 'errors' => ['File size must be between 1 byte and ' . ($this->maxSizeBytes / 1024 / 1024) . ' MB.']];
        }

        $originalName = basename((string) ($uploadedFile['name'] ?? 'file'));
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        if ($extension === '' || !FileStorage::isExtensionAllowed($extension)) {
            return ['success' => false, 'errors' => ['That file type is not permitted.']];
        }

        FileStorage::ensureChallengeDir($this->projectRoot, $challengeId);
        $relativePath = FileStorage::generateRelativePath($challengeId, $extension);
        $absolutePath = FileStorage::baseDir($this->projectRoot) . '/' . $relativePath;

        if (!move_uploaded_file($tmpPath, $absolutePath)) {
            return ['success' => false, 'errors' => ['Could not store the uploaded file.']];
        }

        $sha256 = hash_file('sha256', $absolutePath) ?: null;
        $mimeType = is_string($uploadedFile['type'] ?? null) ? substr($uploadedFile['type'], 0, 100) : null;

        $fileId = $this->files->create($challengeId, $originalName, $relativePath, $mimeType, $size, $sha256);

        $this->audit->log(
            AuditLogger::CHALLENGE_FILE_ADDED,
            (int) $actingUser['id'],
            ['challenge_id' => $challengeId, 'file_id' => $fileId, 'original_name' => $originalName],
            Hash::correlate($ip),
            'challenge',
            $challengeId
        );

        return ['success' => true, 'file' => $this->files->findById($fileId)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForChallenge(int $challengeId): array
    {
        return $this->files->forChallenge($challengeId);
    }

    /**
     * @return array{success: bool, error_code?: string, absolute_path?: string, original_name?: string, mime_type?: ?string}
     */
    public function resolveForDownload(array $user, int $fileId, bool $isPrivileged): array
    {
        $file = $this->files->findById($fileId);
        if ($file === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        $challenge = $this->challenges->findById((int) $file['challenge_id']);
        if ($challenge === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        if (!$isPrivileged && !in_array($challenge['status'], ChallengeRepository::PARTICIPANT_VISIBLE_STATUSES, true)) {
            // Same response as "not found" -- do not confirm the
            // existence of files on unpublished challenges.
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        $absolutePath = FileStorage::resolvedPath($this->projectRoot, $file['storage_path']);
        if ($absolutePath === null || !is_file($absolutePath)) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        return [
            'success' => true,
            'absolute_path' => $absolutePath,
            'original_name' => $file['original_name'],
            'mime_type' => $file['mime_type'],
        ];
    }

    /**
     * @return array{success: bool, error_code?: string}
     */
    public function delete(array $actingUser, int $fileId, string $ip): array
    {
        $file = $this->files->findById($fileId);
        if ($file === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        $absolutePath = FileStorage::resolvedPath($this->projectRoot, $file['storage_path']);
        if ($absolutePath !== null && is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        $this->files->delete($fileId);

        $this->audit->log(
            AuditLogger::CHALLENGE_FILE_REMOVED,
            (int) $actingUser['id'],
            ['challenge_id' => (int) $file['challenge_id'], 'file_id' => $fileId, 'original_name' => $file['original_name']],
            Hash::correlate($ip),
            'challenge',
            (int) $file['challenge_id']
        );

        return ['success' => true];
    }
}
