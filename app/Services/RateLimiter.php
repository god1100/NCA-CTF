<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\Env;
use App\Repositories\AuthAttemptRepository;

/**
 * Database-backed rate limiting for authentication endpoints. No Redis
 * (docs/ctf9.txt §28). Considers both the requesting IP and the
 * identifier being attempted independently, since relying on IP alone is
 * explicitly warned against (docs/ctf5.txt §34, ctf7.txt §3).
 */
final class RateLimiter
{
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(private readonly AuthAttemptRepository $attempts)
    {
        $this->maxAttempts = (int) Env::get('AUTH_RATE_LIMIT_MAX_ATTEMPTS', '5');
        $this->windowSeconds = (int) Env::get('AUTH_RATE_LIMIT_WINDOW_SECONDS', '60');
    }

    /**
     * Returns true if the request should be BLOCKED (limit exceeded).
     */
    public function isBlocked(string $purpose, string $ipHash, ?string $identifierHash): bool
    {
        $byIp = $this->attempts->countFailedSince($purpose, 'ip_hash', $ipHash, $this->windowSeconds);
        if ($byIp >= $this->maxAttempts) {
            return true;
        }

        if ($identifierHash !== null) {
            $byIdentifier = $this->attempts->countFailedSince($purpose, 'identifier_hash', $identifierHash, $this->windowSeconds);
            if ($byIdentifier >= $this->maxAttempts) {
                return true;
            }
        }

        return false;
    }

    public function record(string $purpose, ?string $identifierHash, string $ipHash, bool $successful): void
    {
        $this->attempts->record($purpose, $identifierHash, $ipHash, $successful);
    }
}
