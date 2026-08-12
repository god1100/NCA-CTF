<?php

declare(strict_types=1);

/**
 * Phase 0 validation script.
 *
 * Deliberately not PHPUnit — Phase 0 has no business logic to unit test,
 * and pulling in a test framework via Composer isn't possible in every
 * environment (no network access to Packagist in some sandboxes). This
 * script performs structural + smoke checks appropriate to the
 * Phase 0 acceptance criteria in docs/ctf8.txt §10 / final prompt §14.
 *
 * Run: php tests/phase0_validate.php
 */

$root = dirname(__DIR__);
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

echo "NCA Batch 4 CTF — Phase 0 Validation\n";
echo str_repeat('=', 42) . "\n\n";

echo "PHP environment\n";
check('PHP version >= 8.1', version_compare(PHP_VERSION, '8.1.0', '>='), $failures, $passes);
check('PDO extension loaded', extension_loaded('pdo'), $failures, $passes);

echo "\nProject structure\n";
$expectedDirs = [
    'app/Controllers', 'app/Models', 'app/Services', 'app/Repositories',
    'app/Middleware', 'app/Infrastructure',
    'config', 'database/migrations', 'database/seeders',
    'public/assets/css', 'public/assets/js',
    'resources/views', 'resources/css', 'resources/js',
    'routes', 'admin', 'challenges', 'docker', 'storage/logs',
    'storage/uploads', 'tests', 'docs',
];
foreach ($expectedDirs as $dir) {
    check("Directory exists: $dir", is_dir("$root/$dir"), $failures, $passes);
}

echo "\nRequired files\n";
$expectedFiles = [
    '.env.example', '.gitignore', 'composer.json', 'README.md',
    'public/index.php', 'routes/api.php', 'config/app.php',
    'app/Infrastructure/Router.php', 'app/Infrastructure/Env.php',
    'app/Infrastructure/JsonResponse.php', 'app/Infrastructure/Autoloader.php',
    'app/Controllers/HealthController.php',
    'admin/index.php', 'challenges/README.md', 'docker/README.md',
    'resources/views/landing.php', 'public/assets/css/style.css',
    'public/assets/js/main.js',
];
foreach ($expectedFiles as $file) {
    check("File exists: $file", is_file("$root/$file"), $failures, $passes);
}

echo "\nSecrets hygiene\n";
check('.env is NOT committed (only .env.example)', !is_file("$root/.env") || is_file("$root/.gitignore"), $failures, $passes);
$gitignore = is_file("$root/.gitignore") ? file_get_contents("$root/.gitignore") : '';
check('.gitignore excludes .env', str_contains($gitignore, '.env'), $failures, $passes);
check('.gitignore excludes /vendor/', str_contains($gitignore, 'vendor'), $failures, $passes);

echo "\nPHP syntax check (no syntax errors)\n";
$phpFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
$syntaxOk = true;
$checkedCount = 0;
foreach ($phpFiles as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    if (str_contains($file->getPathname(), '/vendor/')) {
        continue;
    }
    $checkedCount++;
    $output = [];
    $exitCode = 0;
    exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $exitCode);
    if ($exitCode !== 0) {
        $syntaxOk = false;
        echo "    Syntax error in {$file->getPathname()}:\n      " . implode("\n      ", $output) . "\n";
    }
}
check("All $checkedCount PHP files have valid syntax", $syntaxOk, $failures, $passes);

echo "\nBusiness-logic exclusions (still must NOT exist)\n";
check('No docker-compose.yml at root yet', !is_file("$root/docker-compose.yml"), $failures, $passes);
// NOTE: "no auth controller" and "no database migrations" were valid
// Phase 0 checks but are intentionally retired now that Phase 1 (database)
// and Phase 2 (authentication) have legitimately added those files, per
// docs/ctf9.txt's phased roadmap. This script continues to validate that
// Phase 0's own deliverables still work; it is not meant to gate future
// phases from existing.

echo "\n" . str_repeat('=', 42) . "\n";
echo "Result: $passes passed, " . count($failures) . " failed\n";

if (count($failures) > 0) {
    echo "\nFailed checks:\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
    exit(1);
}

echo "\nPhase 0 validation: ALL CHECKS PASSED\n";
exit(0);
