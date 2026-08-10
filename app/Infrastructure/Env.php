<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Minimal .env loader.
 *
 * Phase 0 scope: parse KEY=VALUE lines from a .env file into the process
 * environment. No external dependency is introduced for this — the format
 * we support is intentionally simple and is a strict subset of what
 * vlucas/phpdotenv supports, so migrating to that package later (if ever
 * needed) is a drop-in change, not a rewrite.
 *
 * Never commit a real .env file. See .env.example for the documented keys.
 */
final class Env
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    public static function load(string $path): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = [];

        if (!is_file($path)) {
            // No .env present (e.g. Phase 0 default install). Fall back to
            // whatever is already in the real environment / .env.example
            // defaults applied by get().
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip matching surrounding quotes.
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            self::$cache[$key] = $value;

            if (getenv($key) === false) {
                putenv("$key=$value");
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $fromEnv = getenv($key);
        if ($fromEnv !== false) {
            return $fromEnv;
        }

        if (self::$cache !== null && array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        return $default;
    }
}
