<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Secure storage for challenge file attachments. Files live under
 * storage/uploads/challenges/ -- entirely outside public/, so nothing
 * here is ever directly web-servable (docs/ctf4.txt §13, ctf9.txt §17).
 *
 * Every stored filename is server-generated (random hex + a validated
 * extension) -- the client-supplied original filename is preserved only
 * as metadata (challenge_files.original_name) for the download's
 * Content-Disposition header, never used to build a filesystem path.
 * This is what makes path traversal structurally impossible on the
 * write side. resolvedPath() adds a defense-in-depth check on the read
 * side too, in case a storage_path value is ever malformed.
 */
final class FileStorage
{
    private const ALLOWED_EXTENSIONS = [
        'zip', 'tar', 'gz', 'bz2', '7z',
        'txt', 'md', 'csv', 'json', 'yaml', 'yml',
        'pdf', 'png', 'jpg', 'jpeg', 'gif',
        'pcap', 'pcapng',
        'py', 'c', 'h', 'cpp', 'java', 'php', 'js', 'sh',
        'bin', 'elf', 'exe', 'so',
    ];

    public static function baseDir(string $projectRoot): string
    {
        return $projectRoot . '/storage/uploads/challenges';
    }

    public static function isExtensionAllowed(string $extension): bool
    {
        return in_array(strtolower($extension), self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Builds a safe, server-controlled relative storage path for a newly
     * uploaded file. Never derived from client input beyond the
     * extension (which is itself validated against an allowlist).
     */
    public static function generateRelativePath(int $challengeId, string $extension): string
    {
        $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', $extension) ?? '');
        $stored = bin2hex(random_bytes(16)) . ($extension !== '' ? ".{$extension}" : '');

        return "{$challengeId}/{$stored}";
    }

    /**
     * Resolves a stored relative path to an absolute filesystem path,
     * refusing to return anything outside the base storage directory --
     * even if storage_path were ever malformed (e.g. containing `../`),
     * this guard stops it (docs/ctf9.txt Phase 4: "Prevent arbitrary
     * path traversal").
     */
    public static function resolvedPath(string $projectRoot, string $relativePath): ?string
    {
        $base = self::baseDir($projectRoot);
        $candidate = $base . '/' . ltrim($relativePath, '/');

        $realBase = realpath($base);
        $realCandidate = realpath($candidate);

        if ($realBase === false || $realCandidate === false) {
            return null;
        }

        if (!str_starts_with($realCandidate, $realBase . DIRECTORY_SEPARATOR) && $realCandidate !== $realBase) {
            return null;
        }

        return $realCandidate;
    }

    public static function ensureChallengeDir(string $projectRoot, int $challengeId): void
    {
        $dir = self::baseDir($projectRoot) . "/{$challengeId}";
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
    }
}
