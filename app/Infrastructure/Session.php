<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Native PHP session bootstrap and helpers.
 *
 * No Redis, no custom session handler -- native PHP sessions per
 * docs/ctf9.txt §27. Cookie flags follow docs/ctf5.txt §6 and
 * ctf9.txt §6: HttpOnly, Secure (in non-local environments), SameSite.
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $isProduction = Env::get('APP_ENV', 'local') === 'production';
        $lifetime = (int) Env::get('SESSION_LIFETIME_SECONDS', '7200');

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $isProduction,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetime);

        session_name('nca_ctf_session');
        session_start();
        self::$started = true;
    }

    /**
     * Regenerate the session ID while preserving session data. MUST be
     * called immediately after successful login to prevent session
     * fixation (docs/ctf5.txt §10, ctf9.txt §6).
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'],
                ]
            );
        }

        session_destroy();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
}
