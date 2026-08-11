<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Minimal PDO connection factory.
 *
 * Phase 1 scope: a single lazily-created PDO connection using credentials
 * from the environment. No ORM, no query builder, no ActiveRecord — every
 * later phase talks to the database through prepared statements via this
 * connection (docs/ctf9.txt §2, ctf4.txt §46).
 *
 * The connection uses the application-level DB user (never a root/admin
 * database account) — see docs/ctf4.txt §39 and .env.example.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_DATABASE', 'nca_ctf');
        $username = Env::get('DB_USERNAME', 'nca_ctf_app');
        $password = Env::get('DB_PASSWORD', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
            ]);
        } catch (PDOException $e) {
            // Never leak DSN/credentials or the raw driver exception in
            // production. Callers (health checks, migration runner) decide
            // how much detail to surface based on APP_DEBUG.
            throw new RuntimeException('Database connection failed.', 0, $e);
        }

        return self::$connection;
    }

    /**
     * For tooling (migration runner, seeders, tests) that need to target a
     * database other than the one configured via DB_DATABASE, e.g. a
     * dedicated test database.
     */
    public static function connectTo(string $database): PDO
    {
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $username = Env::get('DB_USERNAME', 'nca_ctf_app');
        $password = Env::get('DB_PASSWORD', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
        ]);
    }
}
