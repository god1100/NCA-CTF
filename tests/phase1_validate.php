<?php

declare(strict_types=1);

/**
 * Phase 1 database validation.
 *
 * Runs migrations against a dedicated test database (DB_DATABASE with a
 * "_test" suffix, or override with --database=), seeds it, then performs
 * functional checks against the real schema: constraint enforcement,
 * duplicate-solve protection, first-blood uniqueness, plaintext-secret
 * absence, and referential integrity. This intentionally goes further
 * than a structural check -- it proves the constraints actually work,
 * not just that the tables exist (docs/ctf9.txt Phase 1 §34).
 *
 * Requires a reachable MySQL/MariaDB server with credentials in .env.
 *
 * Run: php tests/phase1_validate.php
 */

$root = dirname(__DIR__);
require $root . '/app/Infrastructure/Autoloader.php';
\App\Infrastructure\Autoloader::register($root . '/app');

use App\Infrastructure\Database;
use App\Infrastructure\Env;

Env::load($root . '/.env');

$options = getopt('', ['database:']);

/*
 * Guard against accidentally deriving "..._test_test" when the
 * configured DB_DATABASE already points at a dedicated test database.
 */
$configuredDatabase = Env::get('DB_DATABASE', 'nca_ctf');

$defaultTestDatabase = str_ends_with($configuredDatabase, '_test')
    ? $configuredDatabase
    : $configuredDatabase . '_test';

$testDatabase = $options['database'] ?? $defaultTestDatabase;

$failures = [];
$passes = 0;

function check(string $label, bool $condition, array &$failures, int &$passes): void
{
    if ($condition) {
        echo "  [PASS] $label\n";
        $passes++;
    } else {
        echo "  [FAIL] $label\n";
        $failures[] = $label;
    }
}

echo "NCA Batch 4 CTF — Phase 1 Database Validation\n";
echo "Target test database: {$testDatabase}\n";
echo str_repeat('=', 50) . "\n\n";

try {
    $pdo = Database::connectTo($testDatabase);
} catch (\Throwable $e) {
    fwrite(STDERR, "Could not connect to test database '{$testDatabase}': " . $e->getMessage() . "\n");
    fwrite(STDERR, "Create it first, e.g.:\n  CREATE DATABASE {$testDatabase} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
    exit(1);
}

echo "Resetting test database (dropping + recreating all tables)\n";
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "  Dropped " . count($tables) . " existing table(s)\n\n";

echo "Running migrations against test database\n";
$migrateOutput = [];
$exitCode = 0;
$phpBinary = PHP_BINARY;

exec(
    escapeshellarg($phpBinary) . ' ' .
    escapeshellarg($root . '/database/migrate.php') . ' --database=' .
    escapeshellarg($testDatabase) . ' 2>&1',
    $migrateOutput,
    $exitCode
);
check('Migration runner exits successfully', $exitCode === 0, $failures, $passes);
if ($exitCode !== 0) {
    echo implode("\n", $migrateOutput) . "\n";
}

echo "\nSchema structure\n";
$expectedTables = [
    'roles', 'users', 'teams', 'team_members', 'team_invitations',
    'categories', 'challenges', 'challenge_files', 'challenge_hints', 'flags',
    'submissions', 'solves', 'first_bloods', 'announcements', 'docker_instances',
    'integrity_events', 'integrity_evidence', 'integrity_alerts', 'risk_scores',
    'investigations', 'disciplinary_actions', 'account_relationships',
    'audit_logs', 'system_settings', 'schema_migrations',
];
$existingTables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
foreach ($expectedTables as $table) {
    check("Table exists: $table", in_array($table, $existingTables, true), $failures, $passes);
}

echo "\nEngine / charset\n";
$engineCheck = $pdo->query("
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND (ENGINE != 'InnoDB' OR TABLE_COLLATION NOT LIKE 'utf8mb4%')
")->fetchColumn();
check('All tables use InnoDB + utf8mb4', (int) $engineCheck === 0, $failures, $passes);

echo "\nPlaintext-secret column absence\n";
$columns = $pdo->query("
    SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
")->fetchAll();
$columnNames = array_map(static fn ($c) => strtolower($c['COLUMN_NAME']), $columns);
check('No column literally named "password"', !in_array('password', $columnNames, true), $failures, $passes);
check('No column literally named "flag"', !in_array('flag', $columnNames, true), $failures, $passes);
check('users.password_hash exists', in_array('password_hash', array_map(static fn ($c) => strtolower($c['COLUMN_NAME']), array_filter($columns, static fn ($c) => $c['TABLE_NAME'] === 'users')), true), $failures, $passes);
check('flags.flag_hash exists', in_array('flag_hash', array_map(static fn ($c) => strtolower($c['COLUMN_NAME']), array_filter($columns, static fn ($c) => $c['TABLE_NAME'] === 'flags')), true), $failures, $passes);

echo "\nSeeding\n";
$seedOutput = [];
$seedExit = 0;

// Use the same PHP executable that is running this validation script.
// This avoids accidentally using another PHP installation from PATH.
$phpBinary = PHP_BINARY;

// Pass the test database through the environment of the child process.
$oldDbDatabase = getenv('DB_DATABASE');

putenv("DB_DATABASE={$testDatabase}");

exec(
    escapeshellarg($phpBinary) . ' ' .
    escapeshellarg($root . '/database/seed.php') . ' 2>&1',
    $seedOutput,
    $seedExit
);

// Restore the original environment value.
if ($oldDbDatabase === false) {
    putenv('DB_DATABASE');
} else {
    putenv("DB_DATABASE={$oldDbDatabase}");
}

check('Seed script exits successfully', $seedExit === 0, $failures, $passes);

if ($seedExit !== 0) {
    echo implode("\n", $seedOutput) . "\n";
}

echo "\nFunctional constraint checks\n";

// Unique username/email
try {
    $roleId = $pdo->query("SELECT id FROM roles WHERE name = 'participant'")->fetchColumn();
    $pdo->prepare('INSERT INTO users (username, email, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?)')
        ->execute(['tester1', 'tester1@example.test', password_hash('irrelevant', PASSWORD_BCRYPT), $roleId, 'active']);
    $dupUsernameRejected = false;
    try {
        $pdo->prepare('INSERT INTO users (username, email, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?)')
            ->execute(['tester1', 'different@example.test', password_hash('irrelevant', PASSWORD_BCRYPT), $roleId, 'active']);
    } catch (\PDOException $e) {
        $dupUsernameRejected = true;
    }
    check('Duplicate username rejected by unique constraint', $dupUsernameRejected, $failures, $passes);
} catch (\Throwable $e) {
    check('Duplicate username rejected by unique constraint', false, $failures, $passes);
    echo "    " . $e->getMessage() . "\n";
}

// Duplicate solve protection
try {
    $userId = $pdo->query("SELECT id FROM users WHERE username = 'tester1'")->fetchColumn();
    $pdo->exec("INSERT INTO teams (name, slug, status) VALUES ('Test Team', 'test-team', 'active')");
    $teamId = (int) $pdo->lastInsertId();
    $categoryId = $pdo->query("SELECT id FROM categories WHERE slug = 'web'")->fetchColumn();
    $pdo->prepare("INSERT INTO challenges (category_id, title, slug, difficulty, points, status, deployment_type) VALUES (?, 'Test Challenge', 'test-challenge', 'easy', 100, 'published', 'HTTP')")
        ->execute([$categoryId]);
    $challengeId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO solves (team_id, challenge_id, first_solved_by, points_awarded) VALUES (?, ?, ?, ?)')
        ->execute([$teamId, $challengeId, $userId, 100]);

    $dupSolveRejected = false;
    try {
        $pdo->prepare('INSERT INTO solves (team_id, challenge_id, first_solved_by, points_awarded) VALUES (?, ?, ?, ?)')
            ->execute([$teamId, $challengeId, $userId, 100]);
    } catch (\PDOException $e) {
        $dupSolveRejected = true;
    }
    check('Duplicate (team_id, challenge_id) solve rejected', $dupSolveRejected, $failures, $passes);

    // First blood uniqueness
    $pdo->prepare('INSERT INTO first_bloods (challenge_id, team_id, user_id, bonus_points) VALUES (?, ?, ?, ?)')
        ->execute([$challengeId, $teamId, $userId, 25]);
    $dupFirstBloodRejected = false;
    try {
        $pdo->prepare('INSERT INTO first_bloods (challenge_id, team_id, user_id, bonus_points) VALUES (?, ?, ?, ?)')
            ->execute([$challengeId, $teamId, $userId, 25]);
    } catch (\PDOException $e) {
        $dupFirstBloodRejected = true;
    }
    check('Duplicate first_blood per challenge rejected', $dupFirstBloodRejected, $failures, $passes);

    // Referential integrity: cannot insert a solve for a nonexistent team
    $fkRejected = false;
    try {
        $pdo->prepare('INSERT INTO solves (team_id, challenge_id, points_awarded) VALUES (?, ?, ?)')
            ->execute([999999, $challengeId, 50]);
    } catch (\PDOException $e) {
        $fkRejected = true;
    }
    check('Foreign key rejects solve for nonexistent team', $fkRejected, $failures, $passes);

    // Historical data protection: cannot delete a team that has submissions/solves
    $restrictHolds = false;
    try {
        $pdo->exec("DELETE FROM teams WHERE id = {$teamId}");
    } catch (\PDOException $e) {
        $restrictHolds = true;
    }
    check('Team with solves cannot be hard-deleted (RESTRICT holds)', $restrictHolds, $failures, $passes);
} catch (\Throwable $e) {
    fwrite(STDERR, "Functional check setup failed: " . $e->getMessage() . "\n");
    $failures[] = 'Functional constraint checks (setup error)';
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Result: $passes passed, " . count($failures) . " failed\n";

if (count($failures) > 0) {
    echo "\nFailed checks:\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
    exit(1);
}

echo "\nPhase 1 validation: ALL CHECKS PASSED\n";
exit(0);
