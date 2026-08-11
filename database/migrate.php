<?php

declare(strict_types=1);

/**
 * Migration runner.
 *
 * Applies every .sql file in database/migrations/ that has not already
 * been recorded in the schema_migrations tracking table, in filename
 * order. Each migration is idempotent (CREATE TABLE IF NOT EXISTS), and
 * already-applied migrations are skipped by tracking table lookup, so
 * running this repeatedly is always safe (docs/ctf9.txt Phase 1 §3).
 *
 * Usage:
 *   php database/migrate.php            # migrate the configured DB_DATABASE
 *   php database/migrate.php --database=nca_ctf_test
 *   php database/migrate.php --status   # show applied/pending without running
 */

$root = dirname(__DIR__);
require $root . '/app/Infrastructure/Autoloader.php';
\App\Infrastructure\Autoloader::register($root . '/app');

use App\Infrastructure\Database;
use App\Infrastructure\Env;

Env::load($root . '/.env');

$options = getopt('', ['database:', 'status']);
$targetDatabase = $options['database'] ?? Env::get('DB_DATABASE', 'nca_ctf');
$statusOnly = array_key_exists('status', $options);

try {
    $pdo = Database::connectTo($targetDatabase);
} catch (\Throwable $e) {
    fwrite(STDERR, "Could not connect to database '{$targetDatabase}': " . $e->getMessage() . "\n");
    exit(1);
}

// Ensure the tracking table exists. This is the one piece of schema not
// represented as a numbered migration file, since it must exist before
// migration tracking can begin.
$pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS schema_migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_schema_migrations_migration (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

$applied = $pdo->query('SELECT migration FROM schema_migrations')
    ->fetchAll(\PDO::FETCH_COLUMN);
$applied = array_flip($applied);

$migrationFiles = glob($root . '/database/migrations/*.sql');
sort($migrationFiles);

if ($migrationFiles === false || count($migrationFiles) === 0) {
    fwrite(STDERR, "No migration files found in database/migrations/\n");
    exit(1);
}

$pending = [];
foreach ($migrationFiles as $file) {
    $name = basename($file);
    if (!isset($applied[$name])) {
        $pending[] = $file;
    }
}

echo "Target database: {$targetDatabase}\n";
echo 'Migrations found: ' . count($migrationFiles) . "\n";
echo 'Already applied: ' . count($applied) . "\n";
echo 'Pending: ' . count($pending) . "\n\n";

if ($statusOnly) {
    foreach ($migrationFiles as $file) {
        $name = basename($file);
        $mark = isset($applied[$name]) ? '[applied]' : '[pending]';
        echo "  {$mark} {$name}\n";
    }
    exit(0);
}

if (count($pending) === 0) {
    echo "Nothing to do -- database is up to date.\n";
    exit(0);
}

foreach ($pending as $file) {
    $name = basename($file);
    $sql = file_get_contents($file);

    if ($sql === false || trim($sql) === '') {
        fwrite(STDERR, "  [SKIP] {$name} (empty or unreadable)\n");
        continue;
    }

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
        $stmt->execute(['migration' => $name]);
        echo "  [OK]   {$name}\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "  [FAIL] {$name}: " . $e->getMessage() . "\n");
        fwrite(STDERR, "\nMigration run aborted. Fix the failing migration before retrying.\n");
        exit(1);
    }
}

echo "\nAll pending migrations applied successfully.\n";
