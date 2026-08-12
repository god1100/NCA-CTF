<?php

declare(strict_types=1);

/**
 * Phase 2 authentication validation.
 *
 * Boots a real PHP dev server against a dedicated test database, then
 * drives it over real HTTP with curl (cookies, headers, status codes)
 * so the checks exercise the actual request/response path -- not just
 * the service classes in isolation. Role-based authorization is checked
 * as a direct unit test of RoleMiddleware since Phase 2 does not add any
 * role-restricted business endpoint yet (those arrive in later phases).
 *
 * Requires: reachable MySQL/MariaDB, the `curl` binary on PATH.
 *
 * Run: php tests/phase2_validate.php
 */

$root = dirname(__DIR__);
require $root . '/app/Infrastructure/Autoloader.php';
\App\Infrastructure\Autoloader::register($root . '/app');

use App\Infrastructure\Database;
use App\Infrastructure\Env;

Env::load($root . '/.env');

$options = getopt('', ['database:']);
$testDatabase = $options['database'] ?? (Env::get('DB_DATABASE', 'nca_ctf') . '_test');
$port = 8123;
$baseUrl = "http://127.0.0.1:{$port}";

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

echo "NCA Batch 4 CTF — Phase 2 Authentication Validation\n";
echo "Target test database: {$testDatabase}\n";
echo str_repeat('=', 55) . "\n\n";

// --- Reset test database ---------------------------------------------------
try {
    $pdo = Database::connectTo($testDatabase);
} catch (\Throwable $e) {
    fwrite(STDERR, "Could not connect to test database '{$testDatabase}': " . $e->getMessage() . "\n");
    exit(1);
}

echo "Resetting test database\n";
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "  Dropped " . count($tables) . " existing table(s)\n\n";

echo "Running migrations + seed against test database\n";
exec('php ' . escapeshellarg($root . '/database/migrate.php') . ' --database=' . escapeshellarg($testDatabase) . ' 2>&1', $out, $code);
check('Migrations applied', $code === 0, $failures, $passes);
putenv("DB_DATABASE={$testDatabase}");
$seedOut = [];
exec('php ' . escapeshellarg($root . '/database/seed.php') . ' 2>&1', $seedOut, $seedCode);
check('Seed data applied', $seedCode === 0, $failures, $passes);
echo "\n";

// --- Boot a dev server bound to the test database ---------------------------
echo "Starting test HTTP server on {$baseUrl}\n";
$envForServer = [
    'DB_HOST' => Env::get('DB_HOST', '127.0.0.1'),
    'DB_PORT' => Env::get('DB_PORT', '3306'),
    'DB_DATABASE' => $testDatabase,
    'DB_USERNAME' => Env::get('DB_USERNAME', 'nca_ctf_app'),
    'DB_PASSWORD' => Env::get('DB_PASSWORD', ''),
    'DB_CHARSET' => Env::get('DB_CHARSET', 'utf8mb4'),
    'APP_SECRET' => Env::get('APP_SECRET', 'test-secret-for-phase2-validation'),
    'APP_ENV' => 'local',
    'AUTH_RATE_LIMIT_MAX_ATTEMPTS' => '5',
    'AUTH_RATE_LIMIT_WINDOW_SECONDS' => '60',
    'PATH' => getenv('PATH') ?: '/usr/bin:/bin',
];

$descriptors = [1 => ['file', '/tmp/phase2_server.log', 'w'], 2 => ['file', '/tmp/phase2_server.log', 'w']];
$process = proc_open(
    ['php', '-S', "127.0.0.1:{$port}", '-t', $root . '/public'],
    $descriptors,
    $pipes,
    $root,
    $envForServer
);

if (!is_resource($process)) {
    fwrite(STDERR, "Failed to start test server.\n");
    exit(1);
}

usleep(700000); // let the server boot

/**
 * Minimal curl-based HTTP client with cookie-jar support.
 */
function httpRequest(string $method, string $url, ?array $jsonBody = null, ?string $cookieJar = null, array $headers = []): array
{
    $cmd = ['curl', '-s', '-i', '-X', $method];

    foreach ($headers as $h) {
        $cmd[] = '-H';
        $cmd[] = $h;
    }

    if ($jsonBody !== null) {
        $cmd[] = '-H';
        $cmd[] = 'Content-Type: application/json';
        $cmd[] = '-d';
        $cmd[] = json_encode($jsonBody);
    }

    if ($cookieJar !== null) {
        $cmd[] = '-c';
        $cmd[] = $cookieJar;
        $cmd[] = '-b';
        $cmd[] = $cookieJar;
    }

    $cmd[] = $url;

    $escaped = implode(' ', array_map('escapeshellarg', $cmd));
    $raw = shell_exec($escaped);

    [$headerPart, $bodyPart] = array_pad(explode("\r\n\r\n", $raw ?? '', 2), 2, '');
    preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $headerPart, $m);
    $status = isset($m[1]) ? (int) $m[1] : 0;
    $decoded = json_decode($bodyPart, true);

    return ['status' => $status, 'body' => is_array($decoded) ? $decoded : [], 'raw' => $bodyPart];
}

try {
    // --- Registration --------------------------------------------------
    echo "Registration\n";
    $r = httpRequest('POST', "$baseUrl/api/v1/auth/register", [
        'username' => 'phase2_alice',
        'email' => 'phase2_alice@example.test',
        'password' => 'correcthorse1',
        'full_name' => 'Alice Phase Two',
    ]);
    check('Registration succeeds (201)', $r['status'] === 201 && ($r['body']['success'] ?? false) === true, $failures, $passes);
    check('Registered user has no password_hash in response', !isset($r['body']['data']['user']['password_hash']), $failures, $passes);
    check('Registered user role is participant', ($r['body']['data']['user']['role'] ?? null) === 'participant', $failures, $passes);

    $dup = httpRequest('POST', "$baseUrl/api/v1/auth/register", [
        'username' => 'phase2_alice',
        'email' => 'different@example.test',
        'password' => 'correcthorse1',
    ]);
    check('Duplicate username rejected', $dup['status'] === 422 && $dup['body']['success'] === false, $failures, $passes);

    $dupEmail = httpRequest('POST', "$baseUrl/api/v1/auth/register", [
        'username' => 'someone_else',
        'email' => 'phase2_alice@example.test',
        'password' => 'correcthorse1',
    ]);
    check('Duplicate email rejected', $dupEmail['status'] === 422 && $dupEmail['body']['success'] === false, $failures, $passes);

    $badInput = httpRequest('POST', "$baseUrl/api/v1/auth/register", [
        'username' => 'ab',
        'email' => 'not-an-email',
        'password' => 'short',
    ]);
    check('Invalid input rejected (short username/bad email/weak password)', $badInput['status'] === 422, $failures, $passes);

    // --- Plaintext password never stored ---------------------------------
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE username = ?');
    $stmt->execute(['phase2_alice']);
    $storedHash = $stmt->fetchColumn();
    check('Password is hashed (stored value != plaintext)', $storedHash !== 'correcthorse1' && str_starts_with((string) $storedHash, '$'), $failures, $passes);
    check(
        'Password hash uses Argon2id (or bcrypt fallback)',
        str_starts_with((string) $storedHash, '$argon2id$') || str_starts_with((string) $storedHash, '$2y$'),
        $failures,
        $passes
    );

    echo "\nLogin\n";
    $cookieJar = '/tmp/phase2_cookies.txt';
    @unlink($cookieJar);

    $badLogin = httpRequest('POST', "$baseUrl/api/v1/auth/login", ['identifier' => 'phase2_alice', 'password' => 'wrong-password'], $cookieJar);
    check('Login fails with incorrect password (401)', $badLogin['status'] === 401 && $badLogin['body']['success'] === false, $failures, $passes);

    $sessionCookieBefore = is_file($cookieJar) ? file_get_contents($cookieJar) : '';
    preg_match('/nca_ctf_session\s+([A-Za-z0-9,]+)/', $sessionCookieBefore, $mBefore);

    $goodLogin = httpRequest('POST', "$baseUrl/api/v1/auth/login", ['identifier' => 'phase2_alice', 'password' => 'correcthorse1'], $cookieJar);
    check('Login succeeds with correct credentials (200)', $goodLogin['status'] === 200 && $goodLogin['body']['success'] === true, $failures, $passes);
    check('Login response has no password_hash', !isset($goodLogin['body']['data']['user']['password_hash']), $failures, $passes);
    check('Login response includes a csrf_token', !empty($goodLogin['body']['data']['csrf_token']), $failures, $passes);

    $sessionCookieAfter = is_file($cookieJar) ? file_get_contents($cookieJar) : '';
    preg_match('/nca_ctf_session\s+([A-Za-z0-9,]+)/', $sessionCookieAfter, $mAfter);
    check('Session ID changed after successful login (fixation prevention)', ($mBefore[1] ?? 'a') !== ($mAfter[1] ?? 'b'), $failures, $passes);
    check('Session cookie is set after login', $sessionCookieAfter !== '', $failures, $passes);

    $csrfToken = $goodLogin['body']['data']['csrf_token'] ?? '';

    // --- /auth/me ----------------------------------------------------------
    echo "\n/auth/me\n";
    $unauth = httpRequest('GET', "$baseUrl/api/v1/auth/me");
    check('/auth/me requires authentication (401 without session)', $unauth['status'] === 401, $failures, $passes);

    $me = httpRequest('GET', "$baseUrl/api/v1/auth/me", null, $cookieJar);
    check('/auth/me returns authenticated user with valid session', $me['status'] === 200 && ($me['body']['data']['user']['username'] ?? null) === 'phase2_alice', $failures, $passes);
    check('/auth/me response has no password_hash', !isset($me['body']['data']['user']['password_hash']), $failures, $passes);

    // --- Inactive account cannot log in -------------------------------------
    echo "\nAccount status enforcement\n";
    $pdo->prepare("INSERT INTO roles (name, description) VALUES ('__unused__', '') ON DUPLICATE KEY UPDATE name = name")->execute();
    $roleId = $pdo->query("SELECT id FROM roles WHERE name = 'participant'")->fetchColumn();
    $suspendedHash = password_hash('correcthorse1', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id, status) VALUES ('phase2_suspended', 'suspended@example.test', ?, ?, 'suspended')")
        ->execute([$suspendedHash, $roleId]);

    $suspendedLogin = httpRequest('POST', "$baseUrl/api/v1/auth/login", ['identifier' => 'phase2_suspended', 'password' => 'correcthorse1']);
    check('Suspended account cannot log in', $suspendedLogin['status'] === 401, $failures, $passes);

    // --- CSRF ----------------------------------------------------------------
    echo "\nCSRF protection\n";
    $logoutNoCsrf = httpRequest('POST', "$baseUrl/api/v1/auth/logout", null, $cookieJar);
    check('Logout without CSRF token is rejected (419)', $logoutNoCsrf['status'] === 419, $failures, $passes);

    $logoutBadCsrf = httpRequest('POST', "$baseUrl/api/v1/auth/logout", null, $cookieJar, ['X-CSRF-Token: not-the-real-token']);
    check('Logout with wrong CSRF token is rejected (419)', $logoutBadCsrf['status'] === 419, $failures, $passes);

    // --- Logout with correct CSRF ---------------------------------------------
    echo "\nLogout\n";
    $logoutOk = httpRequest('POST', "$baseUrl/api/v1/auth/logout", null, $cookieJar, ["X-CSRF-Token: {$csrfToken}"]);
    check('Logout with correct CSRF token succeeds (200)', $logoutOk['status'] === 200 && $logoutOk['body']['success'] === true, $failures, $passes);

    $meAfterLogout = httpRequest('GET', "$baseUrl/api/v1/auth/me", null, $cookieJar);
    check('/auth/me fails after logout (session destroyed)', $meAfterLogout['status'] === 401, $failures, $passes);

    // --- Rate limiting -----------------------------------------------------
    echo "\nRate limiting\n";
    $rateLimitTriggered = false;
    for ($i = 0; $i < 7; $i++) {
        $attempt = httpRequest('POST', "$baseUrl/api/v1/auth/login", ['identifier' => 'phase2_alice', 'password' => 'still-wrong']);
        if ($attempt['status'] === 429) {
            $rateLimitTriggered = true;
            break;
        }
    }
    check('Repeated failed logins eventually trigger rate limiting (429)', $rateLimitTriggered, $failures, $passes);

    // --- Audit logging ---------------------------------------------------------
    echo "\nAudit logging\n";
    $events = $pdo->query("SELECT action FROM audit_logs")->fetchAll(\PDO::FETCH_COLUMN);
    check('USER_REGISTERED event recorded', in_array('USER_REGISTERED', $events, true), $failures, $passes);
    check('LOGIN_SUCCESS event recorded', in_array('LOGIN_SUCCESS', $events, true), $failures, $passes);
    check('LOGIN_FAILED event recorded', in_array('LOGIN_FAILED', $events, true), $failures, $passes);
    check('LOGOUT event recorded', in_array('LOGOUT', $events, true), $failures, $passes);
    check('RATE_LIMIT_BLOCKED event recorded', in_array('RATE_LIMIT_BLOCKED', $events, true), $failures, $passes);

    // --- Role-based authorization middleware (direct unit test) -----------------
    // No role-restricted business endpoint exists yet in Phase 2 (those
    // arrive in later phases), so RoleMiddleware is exercised directly.
    echo "\nRole-based authorization middleware (unit-level)\n";

    $participantUserId = (int) $pdo->query("SELECT id FROM users WHERE username = 'phase2_alice'")->fetchColumn();
    $participantUser = ['id' => $participantUserId, 'role_id' => (int) $roleId];
    $adminAuditRepo = new \App\Repositories\AuditLogRepository($pdo);
    $adminAudit = new \App\Services\AuditLogger($adminAuditRepo);
    $usersRepo = new \App\Repositories\UserRepository($pdo);
    $rateLimiterForUnit = new \App\Services\RateLimiter(new \App\Repositories\AuthAttemptRepository($pdo));
    $authServiceForUnit = new \App\Services\AuthService($usersRepo, $rateLimiterForUnit, $adminAudit);

    $nextCalled = false;
    ob_start();
    $prevErrorReporting = error_reporting(E_ALL & ~E_WARNING);
    \App\Middleware\RoleMiddleware::handle(
        $participantUser,
        ['super_admin'],
        $authServiceForUnit,
        $adminAudit,
        function () use (&$nextCalled) { $nextCalled = true; }
    );
    $roleOutput = ob_get_clean();
    error_reporting($prevErrorReporting);
    check('Unauthorized role access is rejected (participant blocked from super_admin-only action)', !$nextCalled && str_contains($roleOutput, 'FORBIDDEN'), $failures, $passes);

    $superAdminRoleId = $pdo->query("SELECT id FROM roles WHERE name = 'super_admin'")->fetchColumn();
    $adminUserId = (int) $pdo->query("SELECT id FROM users WHERE username = 'phase2_suspended'")->fetchColumn();
    $adminUser = ['id' => $adminUserId, 'role_id' => (int) $superAdminRoleId];
    $nextCalledAdmin = false;
    ob_start();
    \App\Middleware\RoleMiddleware::handle(
        $adminUser,
        ['super_admin'],
        $authServiceForUnit,
        $adminAudit,
        function () use (&$nextCalledAdmin) { $nextCalledAdmin = true; }
    );
    ob_get_clean();
    check('Matching role is allowed through', $nextCalledAdmin, $failures, $passes);

    $authzFailureLogged = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'AUTHORIZATION_FAILURE'")->fetchColumn();
    check('AUTHORIZATION_FAILURE event recorded for the blocked attempt', (int) $authzFailureLogged >= 1, $failures, $passes);
} finally {
    proc_terminate($process);
    proc_close($process);
}

echo "\n" . str_repeat('=', 55) . "\n";
echo "Result: $passes passed, " . count($failures) . " failed\n";

if (count($failures) > 0) {
    echo "\nFailed checks:\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
    echo "\nServer log (last 40 lines):\n";
    $log = @file_get_contents('/tmp/phase2_server.log');
    if ($log) {
        $lines = explode("\n", trim($log));
        echo implode("\n", array_slice($lines, -40)) . "\n";
    }
    exit(1);
}

echo "\nPhase 2 validation: ALL CHECKS PASSED\n";
exit(0);
