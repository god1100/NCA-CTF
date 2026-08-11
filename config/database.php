<?php

declare(strict_types=1);

use App\Infrastructure\Env;

/**
 * Phase 1 database configuration.
 *
 * Consumed by App\Infrastructure\Database and by the migration/seed
 * tooling in database/. Credentials always come from the environment —
 * never hardcoded here (docs/ctf4.txt §44, ctf9.txt §5).
 */
return [
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', 'nca_ctf'),
    'username' => Env::get('DB_USERNAME', 'nca_ctf_app'),
    'password' => Env::get('DB_PASSWORD', ''),
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    'collation' => Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
];
