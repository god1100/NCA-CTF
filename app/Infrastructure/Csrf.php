<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Session-bound CSRF token (synchronizer token pattern).
 *
 * Applied to state-changing requests made by an authenticated session
 * (docs/ctf5.txt §55, ctf9.txt §12 request required functionality #12).
 * The token is issued once per session and must be sent back in the
 * X-CSRF-Token header for protected requests.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::SESSION_KEY)) {
            Session::set(self::SESSION_KEY, bin2hex(random_bytes(32)));
        }

        return Session::get(self::SESSION_KEY);
    }

    public static function verify(?string $submittedToken): bool
    {
        $expected = Session::get(self::SESSION_KEY);

        if (!is_string($expected) || !is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        return hash_equals($expected, $submittedToken);
    }

    public static function rotate(): void
    {
        Session::set(self::SESSION_KEY, bin2hex(random_bytes(32)));
    }
}
