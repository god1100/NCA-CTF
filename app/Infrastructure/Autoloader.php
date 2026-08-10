<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Minimal PSR-4-style autoloader for the App\ namespace.
 *
 * Phase 0 has zero third-party dependencies, so this small autoloader
 * removes the need for Composer's generated vendor/autoload.php.
 *
 * If Composer is available in a given environment, `composer install`
 * will still work against the psr-4 mapping declared in composer.json
 * and vendor/autoload.php can be used instead — both approaches resolve
 * classes under app/ identically.
 */
final class Autoloader
{
    public static function register(string $baseDir): void
    {
        spl_autoload_register(static function (string $class) use ($baseDir): void {
            $prefix = 'App\\';

            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $path = $baseDir . '/' . str_replace('\\', '/', $relative) . '.php';

            if (is_file($path)) {
                require $path;
            }
        });
    }
}
