<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * HMAC-based hashing for data that must be correlatable but never stored
 * in plaintext -- IP addresses, user agents, rate-limit identifiers
 * (docs/ctf7.txt §21, ctf9.txt §10). Uses APP_SECRET as the HMAC key so
 * hashes aren't reversible via a public rainbow table.
 */
final class Hash
{
    public static function correlate(string $value): string
    {
        $secret = Env::get('APP_SECRET', '');

        if ($secret === '' || $secret === null) {
            // Phase 2 dev fallback only. A production deployment MUST set
            // APP_SECRET -- see .env.example. Never silently fail closed
            // here since that would break rate limiting entirely; instead
            // use a fixed, clearly-labeled fallback so the gap is obvious
            // in code review rather than hidden.
            $secret = 'insecure-dev-secret-set-app-secret-in-env';
        }

        return hash_hmac('sha256', $value, $secret);
    }
}
