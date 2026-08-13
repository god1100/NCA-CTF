<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Secure random token generation + one-way hashing, for values that must
 * be verifiable later (by re-hashing a client-supplied token and
 * comparing) but never stored in plaintext -- e.g. team_invitations
 * (docs/ctf4.txt §10, ctf9.txt §13). Distinct from Hash::correlate(),
 * which is for low-stakes correlation (IP addresses), not security
 * tokens: this always uses a fresh cryptographically random value and
 * an unkeyed SHA-256, which is appropriate since the input already has
 * 256 bits of entropy (no dictionary/brute-force risk the way a
 * password hash needs to defend against).
 */
final class Token
{
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
