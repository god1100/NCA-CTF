<?php

declare(strict_types=1);

/**
 * NCA Batch 4 CTF — Phase 2 Authentication Validation
 *
 * Boots a real PHP development server against a dedicated test database,
 * then drives it over real HTTP with curl.
 *
 * Windows/XAMPP compatible:
 * - Uses PHP_BINARY instead of literal "php".
 * - Uses sys_get_temp_dir() instead of /tmp.
 * - Preserves the complete parent process environment.
 * - Overrides only the variables required by the test server.
 *
 * Run:
 *   php tests/phase2_validate.php
 */

$root = dirname(__DIR__);

require $root . '/app/Infrastructure/Autoloader.php';

\App\Infrastructure\Autoloader::register($root . '/app');

use App\Infrastructure\Database;
use App\Infrastructure\Env;

Env::load($root . '/.env');

/*
 * --------------------------------------------------------------------------
 * Configuration
 * --------------------------------------------------------------------------
 */

$options = getopt('', ['database:']);

$testDatabase = $options['database']
    ?? (Env::get('DB_DATABASE', 'nca_ctf') . '_test');

$port = 8123;

$baseUrl = "http://127.0.0.1:{$port}";

/*
 * IMPORTANT:
 *
 * PHP_BINARY gives us the exact PHP executable currently running this
 * validator.
 *
 * On the user's XAMPP setup this should be:
 *
 *   C:\xampp\php\php.exe
 *
 * This avoids accidentally using another PHP installation.
 */
$phpBinary = PHP_BINARY;

/*
 * --------------------------------------------------------------------------
 * Windows/Linux compatible temporary files
 * --------------------------------------------------------------------------
 */

$serverLog = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'phase2_server.log';

$cookieJar = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'phase2_cookies.txt';

$failures = [];
$passes = 0;

/*
 * --------------------------------------------------------------------------
 * Test helper
 * --------------------------------------------------------------------------
 */

function check(
    string $label,
    bool $condition,
    array &$failures,
    int &$passes
): void {
    if ($condition) {
        echo "  [PASS] $label\n";
        $passes++;
    } else {
        echo "  [FAIL] $label\n";
        $failures[] = $label;
    }
}

/*
 * --------------------------------------------------------------------------
 * Header
 * --------------------------------------------------------------------------
 */

echo "NCA Batch 4 CTF — Phase 2 Authentication Validation\n";
echo "Target test database: {$testDatabase}\n";
echo str_repeat('=', 55) . "\n\n";

/*
 * --------------------------------------------------------------------------
 * Reset test database
 * --------------------------------------------------------------------------
 */

try {
    $pdo = Database::connectTo($testDatabase);
} catch (\Throwable $e) {
    fwrite(
        STDERR,
        "Could not connect to test database '{$testDatabase}': "
        . $e->getMessage()
        . "\n"
    );

    exit(1);
}

echo "Resetting test database\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$tables = $pdo
    ->query('SHOW TABLES')
    ->fetchAll(\PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "  Dropped " . count($tables) . " existing table(s)\n\n";

/*
 * --------------------------------------------------------------------------
 * Run migrations
 * --------------------------------------------------------------------------
 */

echo "Running migrations + seed against test database\n";

$migrateOutput = [];
$migrateExit = 0;

exec(
    escapeshellarg($phpBinary)
    . ' '
    . escapeshellarg($root . '/database/migrate.php')
    . ' --database='
    . escapeshellarg($testDatabase)
    . ' 2>&1',
    $migrateOutput,
    $migrateExit
);

check(
    'Migrations applied',
    $migrateExit === 0,
    $failures,
    $passes
);

if ($migrateExit !== 0) {
    echo implode("\n", $migrateOutput) . "\n";
}

/*
 * --------------------------------------------------------------------------
 * Run seed
 * --------------------------------------------------------------------------
 */

putenv("DB_DATABASE={$testDatabase}");

$seedOutput = [];
$seedExit = 0;

exec(
    escapeshellarg($phpBinary)
    . ' '
    . escapeshellarg($root . '/database/seed.php')
    . ' 2>&1',
    $seedOutput,
    $seedExit
);

check(
    'Seed data applied',
    $seedExit === 0,
    $failures,
    $passes
);

if ($seedExit !== 0) {
    echo implode("\n", $seedOutput) . "\n";
}

echo "\n";

/*
 * --------------------------------------------------------------------------
 * Boot development server
 * --------------------------------------------------------------------------
 */

echo "Starting test HTTP server on {$baseUrl}\n";

/*
 * IMPORTANT WINDOWS FIX:
 *
 * Do NOT construct a tiny environment array.
 *
 * getenv() returns the complete existing environment. We preserve it and
 * override only the database/application variables required by the test
 * server.
 */

$envForServer = getenv();

if (!is_array($envForServer)) {
    $envForServer = [];
}

$envForServer['DB_HOST'] = Env::get(
    'DB_HOST',
    '127.0.0.1'
);

$envForServer['DB_PORT'] = Env::get(
    'DB_PORT',
    '3306'
);

$envForServer['DB_DATABASE'] = $testDatabase;

$envForServer['DB_USERNAME'] = Env::get(
    'DB_USERNAME',
    'nca_ctf_app'
);

$envForServer['DB_PASSWORD'] = Env::get(
    'DB_PASSWORD',
    ''
);

$envForServer['DB_CHARSET'] = Env::get(
    'DB_CHARSET',
    'utf8mb4'
);

$envForServer['APP_SECRET'] = Env::get(
    'APP_SECRET',
    'test-secret-for-phase2-validation'
);

$envForServer['APP_ENV'] = 'local';

$envForServer['AUTH_RATE_LIMIT_MAX_ATTEMPTS'] = '5';

$envForServer['AUTH_RATE_LIMIT_WINDOW_SECONDS'] = '60';

/*
 * Make sure PATH exists.
 *
 * This is mainly useful on Windows when PHP is launched from PowerShell.
 */
if (
    !isset($envForServer['PATH'])
    || trim((string) $envForServer['PATH']) === ''
) {
    $envForServer['PATH'] = dirname($phpBinary);
}

/*
 * --------------------------------------------------------------------------
 * Clean old test files
 * --------------------------------------------------------------------------
 */

@unlink($serverLog);
@unlink($cookieJar);

/*
 * --------------------------------------------------------------------------
 * Process descriptors
 * --------------------------------------------------------------------------
 */

$descriptors = [
    1 => ['file', $serverLog, 'w'],
    2 => ['file', $serverLog, 'a'],
];

/*
 * --------------------------------------------------------------------------
 * Start PHP development server
 * --------------------------------------------------------------------------
 */

$process = proc_open(
    [
        $phpBinary,
        '-S',
        "127.0.0.1:{$port}",
        '-t',
        $root . DIRECTORY_SEPARATOR . 'public',
    ],
    $descriptors,
    $pipes,
    $root,
    $envForServer
);

if (!is_resource($process)) {
    fwrite(
        STDERR,
        "Failed to start test server.\n"
    );

    if (is_file($serverLog)) {
        $log = file_get_contents($serverLog);

        if ($log !== false && trim($log) !== '') {
            fwrite(
                STDERR,
                "\nServer startup log:\n"
                . $log
                . "\n"
            );
        }
    }

    exit(1);
}

/*
 * Give the PHP development server time to boot.
 */
usleep(700000);

/*
 * --------------------------------------------------------------------------
 * Minimal curl HTTP client
 * --------------------------------------------------------------------------
 */

/*
 * --------------------------------------------------------------------------
 * Minimal curl HTTP client
 * --------------------------------------------------------------------------
 */
function httpRequest(
    string $method,
    string $url,
    ?array $jsonBody = null,
    ?string $cookieJar = null,
    array $headers = []
): array {
    /*
     * ----------------------------------------------------------------------
     * Cross-platform curl HTTP client
     * ----------------------------------------------------------------------
     *
     * IMPORTANT:
     *
     * Do NOT use shell_exec() + escapeshellarg() here.
     *
     * On Windows, passing JSON through:
     *
     *   PHP -> shell_exec -> cmd.exe -> curl.exe
     *
     * can alter quoting and cause curl to send malformed JSON.
     *
     * proc_open() with an argument array lets PHP launch curl directly
     * without manually constructing a shell command.
     */

    $curlBinary = PHP_OS_FAMILY === 'Windows'
        ? 'curl.exe'
        : 'curl';

    $cmd = [
        $curlBinary,
        '--silent',
        '--show-error',
        '--include',
        '--request',
        strtoupper($method),
        '--max-time',
        '15',
    ];

    /*
     * ----------------------------------------------------------------------
     * HTTP headers
     * ----------------------------------------------------------------------
     */

    foreach ($headers as $header) {
        $cmd[] = '--header';
        $cmd[] = $header;
    }

    /*
     * ----------------------------------------------------------------------
     * JSON request body
     * ----------------------------------------------------------------------
     */

    if ($jsonBody !== null) {
        $encoded = json_encode(
            $jsonBody,
            JSON_UNESCAPED_SLASHES
        );

        if ($encoded === false) {
            return [
                'status' => 0,
                'body' => [],
                'raw' => '',
            ];
        }

        $cmd[] = '--header';
        $cmd[] = 'Content-Type: application/json';

        $cmd[] = '--data-binary';
        $cmd[] = $encoded;
    }

    /*
     * ----------------------------------------------------------------------
     * Cookie handling
     * ----------------------------------------------------------------------
     *
     * -c writes received cookies.
     * -b sends existing cookies.
     */

    if ($cookieJar !== null) {
        $cmd[] = '--cookie-jar';
        $cmd[] = $cookieJar;

        $cmd[] = '--cookie';
        $cmd[] = $cookieJar;
    }

    /*
     * URL must be the final argument.
     */

    $cmd[] = $url;

    /*
     * ----------------------------------------------------------------------
     * Process descriptors
     * ----------------------------------------------------------------------
     */

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(
        $cmd,
        $descriptors,
        $pipes
    );

    if (!is_resource($process)) {
        return [
            'status' => 0,
            'body' => [],
            'raw' => '',
        ];
    }

    /*
     * curl does not need stdin.
     */

    fclose($pipes[0]);

    /*
     * Capture stdout and stderr.
     */

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    /*
     * If curl itself failed, return the diagnostic information.
     */

    if ($stdout === false) {
        $stdout = '';
    }

    if ($stderr === false) {
        $stderr = '';
    }

    $raw = (string) $stdout;

    /*
     * ----------------------------------------------------------------------
     * Parse HTTP response
     * ----------------------------------------------------------------------
     */

    $headerPart = '';
    $bodyPart = $raw;

    /*
     * curl --include produces:
     *
     * HTTP/1.1 200 OK
     * Header: value
     *
     * {"json":"body"}
     */

    $parts = preg_split(
        "/\r\n\r\n/",
        $raw,
        2
    );

    if (is_array($parts) && count($parts) === 2) {
        $headerPart = $parts[0];
        $bodyPart = $parts[1];
    }

    /*
     * ----------------------------------------------------------------------
     * Determine HTTP status
     * ----------------------------------------------------------------------
     */

    $status = 0;

    preg_match_all(
        '/HTTP\/(?:1\.[01]|2)\s+(\d{3})/m',
        $headerPart,
        $matches
    );

    if (!empty($matches[1])) {
        $status = (int) end($matches[1]);
    }

    /*
     * ----------------------------------------------------------------------
     * Decode JSON response
     * ----------------------------------------------------------------------
     */

    $decoded = json_decode(
        trim($bodyPart),
        true
    );

    /*
     * ----------------------------------------------------------------------
     * Debug information
     * ----------------------------------------------------------------------
     *
     * If curl fails completely, include stderr in the raw response so
     * the validator gives us something useful to diagnose.
     */

    if ($status === 0 && $exitCode !== 0) {
        $bodyPart = trim(
            $bodyPart
            . "\n"
            . $stderr
        );
    }

    return [
        'status' => $status,
        'body' => is_array($decoded)
            ? $decoded
            : [],
        'raw' => $bodyPart,
    ];
}

// ===============


try {
    /*
     * ----------------------------------------------------------------------
     * Registration
     * ----------------------------------------------------------------------
     */

    echo "Registration\n";

    $r = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/register",
        [
            'username' => 'phase2_alice',
            'email' => 'phase2_alice@example.test',
            'password' => 'correcthorse1',
            'full_name' => 'Alice Phase Two',
        ]
    );

    check(
        'Registration succeeds (201)',
        $r['status'] === 201
        && ($r['body']['success'] ?? false) === true,
        $failures,
        $passes
    );

    check(
        'Registered user has no password_hash in response',
        !isset($r['body']['data']['user']['password_hash']),
        $failures,
        $passes
    );

    check(
        'Registered user role is participant',
        ($r['body']['data']['user']['role'] ?? null)
            === 'participant',
        $failures,
        $passes
    );

    /*
     * Duplicate username
     */

    $dup = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/register",
        [
            'username' => 'phase2_alice',
            'email' => 'different@example.test',
            'password' => 'correcthorse1',
        ]
    );

    check(
        'Duplicate username rejected',
        $dup['status'] === 422
        && ($dup['body']['success'] ?? null) === false,
        $failures,
        $passes
    );

    /*
     * Duplicate email
     */

    $dupEmail = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/register",
        [
            'username' => 'someone_else',
            'email' => 'phase2_alice@example.test',
            'password' => 'correcthorse1',
        ]
    );

    check(
        'Duplicate email rejected',
        $dupEmail['status'] === 422
        && ($dupEmail['body']['success'] ?? null) === false,
        $failures,
        $passes
    );

    /*
     * Invalid registration input
     */

    $badInput = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/register",
        [
            'username' => 'ab',
            'email' => 'not-an-email',
            'password' => 'short',
        ]
    );

    check(
        'Invalid input rejected (short username/bad email/weak password)',
        $badInput['status'] === 422,
        $failures,
        $passes
    );

    /*
     * ----------------------------------------------------------------------
     * Plaintext password never stored
     * ----------------------------------------------------------------------
     */

    $stmt = $pdo->prepare(
        'SELECT password_hash FROM users WHERE username = ?'
    );

    $stmt->execute([
        'phase2_alice',
    ]);

    $storedHash = $stmt->fetchColumn();

    check(
        'Password is hashed (stored value != plaintext)',
        $storedHash !== 'correcthorse1'
        && str_starts_with(
            (string) $storedHash,
            '$'
        ),
        $failures,
        $passes
    );

    check(
        'Password hash uses Argon2id (or bcrypt fallback)',
        str_starts_with(
            (string) $storedHash,
            '$argon2id$'
        )
        || str_starts_with(
            (string) $storedHash,
            '$2y$'
        ),
        $failures,
        $passes
    );

    /*
     * ----------------------------------------------------------------------
     * Login
     * ----------------------------------------------------------------------
     */

    echo "\nLogin\n";

    $cookieJar = sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'phase2_cookies.txt';

    @unlink($cookieJar);

    /*
     * Wrong password
     */

    $badLogin = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/login",
        [
            'identifier' => 'phase2_alice',
            'password' => 'wrong-password',
        ],
        $cookieJar
    );

    check(
        'Login fails with incorrect password (401)',
        $badLogin['status'] === 401
        && ($badLogin['body']['success'] ?? null) === false,
        $failures,
        $passes
    );

    /*
     * Capture session before successful login.
     */

    $sessionCookieBefore = is_file($cookieJar)
        ? (string) file_get_contents($cookieJar)
        : '';

    preg_match(
        '/nca_ctf_session\s+([A-Za-z0-9,]+)/',
        $sessionCookieBefore,
        $mBefore
    );

    /*
     * Correct password
     */

    $goodLogin = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/login",
        [
            'identifier' => 'phase2_alice',
            'password' => 'correcthorse1',
        ],
        $cookieJar
    );

    check(
        'Login succeeds with correct credentials (200)',
        $goodLogin['status'] === 200
        && ($goodLogin['body']['success'] ?? null) === true,
        $failures,
        $passes
    );

    check(
        'Login response has no password_hash',
        !isset($goodLogin['body']['data']['user']['password_hash']),
        $failures,
        $passes
    );

    check(
        'Login response includes a csrf_token',
        !empty($goodLogin['body']['data']['csrf_token']),
        $failures,
        $passes
    );

    /*
     * Session fixation prevention
     */

    $sessionCookieAfter = is_file($cookieJar)
        ? (string) file_get_contents($cookieJar)
        : '';

    preg_match(
        '/nca_ctf_session\s+([A-Za-z0-9,]+)/',
        $sessionCookieAfter,
        $mAfter
    );

    check(
        'Session ID changed after successful login (fixation prevention)',
        ($mBefore[1] ?? 'a') !== ($mAfter[1] ?? 'b'),
        $failures,
        $passes
    );

    check(
        'Session cookie is set after login',
        $sessionCookieAfter !== '',
        $failures,
        $passes
    );

    $csrfToken =
        $goodLogin['body']['data']['csrf_token']
        ?? '';

    /*
     * ----------------------------------------------------------------------
     * /auth/me
     * ----------------------------------------------------------------------
     */

    echo "\n/auth/me\n";

    $unauth = httpRequest(
        'GET',
        "$baseUrl/api/v1/auth/me"
    );

    check(
        '/auth/me requires authentication (401 without session)',
        $unauth['status'] === 401,
        $failures,
        $passes
    );

    $me = httpRequest(
        'GET',
        "$baseUrl/api/v1/auth/me",
        null,
        $cookieJar
    );

    check(
        '/auth/me returns authenticated user with valid session',
        $me['status'] === 200
        && ($me['body']['data']['user']['username'] ?? null)
            === 'phase2_alice',
        $failures,
        $passes
    );

    check(
        '/auth/me response has no password_hash',
        !isset($me['body']['data']['user']['password_hash']),
        $failures,
        $passes
    );

    /*
     * ----------------------------------------------------------------------
     * Account status enforcement
     * ----------------------------------------------------------------------
     */

    echo "\nAccount status enforcement\n";

    $pdo
        ->prepare(
            "INSERT INTO roles (name, description)
             VALUES ('__unused__', '')
             ON DUPLICATE KEY UPDATE name = name"
        )
        ->execute();

    $roleId = $pdo
        ->query(
            "SELECT id FROM roles WHERE name = 'participant'"
        )
        ->fetchColumn();

    $suspendedHash = password_hash(
        'correcthorse1',
        PASSWORD_BCRYPT
    );

    $pdo
        ->prepare(
            "INSERT INTO users
                (username, email, password_hash, role_id, status)
             VALUES
                ('phase2_suspended',
                 'suspended@example.test',
                 ?,
                 ?,
                 'suspended')"
        )
        ->execute([
            $suspendedHash,
            $roleId,
        ]);

    $suspendedLogin = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/login",
        [
            'identifier' => 'phase2_suspended',
            'password' => 'correcthorse1',
        ]
    );

    check(
        'Suspended account cannot log in',
        $suspendedLogin['status'] === 401,
        $failures,
        $passes
    );

    /*
     * ----------------------------------------------------------------------
     * CSRF
     * ----------------------------------------------------------------------
     */

    echo "\nCSRF protection\n";

    $logoutNoCsrf = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/logout",
        null,
        $cookieJar
    );

    check(
        'Logout without CSRF token is rejected (419)',
        $logoutNoCsrf['status'] === 419,
        $failures,
        $passes
    );

    $logoutBadCsrf = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/logout",
        null,
        $cookieJar,
        [
            'X-CSRF-Token: not-the-real-token',
        ]
    );

    check(
        'Logout with wrong CSRF token is rejected (419)',
        $logoutBadCsrf['status'] === 419,
        $failures,
        $passes
    );

    /*
     * ----------------------------------------------------------------------
     * Logout
     * ----------------------------------------------------------------------
     */

    echo "\nLogout\n";

    $logoutOk = httpRequest(
        'POST',
        "$baseUrl/api/v1/auth/logout",
        null,
        $cookieJar,
        [
            "X-CSRF-Token: {$csrfToken}",
        ]
    );

    check(
        'Logout with correct CSRF token succeeds (200)',
        $logoutOk['status'] === 200
        && ($logoutOk['body']['success'] ?? null) === true,
        $failures,
        $passes
    );

    $meAfterLogout = httpRequest(
        'GET',
        "$baseUrl/api/v1/auth/me",
        null,
        $cookieJar
    );

    check(
        '/auth/me fails after logout (session destroyed)',
        $meAfterLogout['status'] === 401,
        $failures,
        $passes
    );

    /*
     * ----------------------------------------------------------------------
     * Rate limiting
     * ----------------------------------------------------------------------
     */

    echo "\nRate limiting\n";

    $rateLimitTriggered = false;

    for ($i = 0; $i < 7; $i++) {
        $attempt = httpRequest(
            'POST',
            "$baseUrl/api/v1/auth/login",
            [
                'identifier' => 'phase2_alice',
                'password' => 'still-wrong',
            ]
        );

        if ($attempt['status'] === 429) {
            $rateLimitTriggered = true;
            break;
        }
    }

    check(
        'Repeated failed logins eventually trigger rate limiting (429)',
        $rateLimitTriggered,
        $failures,
        $passes
    );

    /*
     * ----------------------------------------------------------------------
     * Audit logging
     * ----------------------------------------------------------------------
     */

    echo "\nAudit logging\n";

    $events = $pdo
        ->query(
            "SELECT action FROM audit_logs"
        )
        ->fetchAll(
            \PDO::FETCH_COLUMN
        );

    check(
        'USER_REGISTERED event recorded',
        in_array(
            'USER_REGISTERED',
            $events,
            true
        ),
        $failures,
        $passes
    );

    check(
        'LOGIN_SUCCESS event recorded',
        in_array(
            'LOGIN_SUCCESS',
            $events,
            true
        ),
        $failures,
        $passes
    );

    check(
        'LOGIN_FAILED event recorded',
        in_array(
            'LOGIN_FAILED',
            $events,
            true
        ),
        $failures,
        $passes
    );

    check(
        'LOGOUT event recorded',
        in_array(
            'LOGOUT',
            $events,
            true
        ),
        $failures,
        $passes
    );

    check(
        'RATE_LIMIT_BLOCKED event recorded',
        in_array(
            'RATE_LIMIT_BLOCKED',
            $events,
            true
        ),
        $failures,
        $passes
    );

    /*
     * ----------------------------------------------------------------------
     * Role-based authorization middleware
     * ----------------------------------------------------------------------
     *
     * Phase 2 does not yet have a role-restricted business endpoint,
     * so RoleMiddleware is exercised directly as a unit test.
     */

    echo "\nRole-based authorization middleware (unit-level)\n";

    $participantUserId = (int) $pdo
        ->query(
            "SELECT id
             FROM users
             WHERE username = 'phase2_alice'"
        )
        ->fetchColumn();

    $participantUser = [
        'id' => $participantUserId,
        'role_id' => (int) $roleId,
    ];

    $adminAuditRepo =
        new \App\Repositories\AuditLogRepository($pdo);

    $adminAudit =
        new \App\Services\AuditLogger($adminAuditRepo);

    $usersRepo =
        new \App\Repositories\UserRepository($pdo);

    $rateLimiterForUnit =
        new \App\Services\RateLimiter(
            new \App\Repositories\AuthAttemptRepository($pdo)
        );

    $authServiceForUnit =
        new \App\Services\AuthService(
            $usersRepo,
            $rateLimiterForUnit,
            $adminAudit
        );

    /*
     * Participant should be blocked from super_admin-only access.
     */

    $nextCalled = false;

    ob_start();

    $prevErrorReporting = error_reporting(
        E_ALL & ~E_WARNING
    );

    \App\Middleware\RoleMiddleware::handle(
        $participantUser,
        ['super_admin'],
        $authServiceForUnit,
        $adminAudit,
        function () use (&$nextCalled) {
            $nextCalled = true;
        }
    );

    $roleOutput = ob_get_clean();

    error_reporting($prevErrorReporting);

    check(
        'Unauthorized role access is rejected (participant blocked from super_admin-only action)',
        !$nextCalled
        && str_contains(
            $roleOutput,
            'FORBIDDEN'
        ),
        $failures,
        $passes
    );

    /*
     * Matching role should be allowed.
     *
     * The existing Phase 2 test reuses the suspended account's user row
     * and gives it the super_admin role for direct middleware testing.
     */

    $superAdminRoleId = $pdo
        ->query(
            "SELECT id
             FROM roles
             WHERE name = 'super_admin'"
        )
        ->fetchColumn();

    $adminUserId = (int) $pdo
        ->query(
            "SELECT id
             FROM users
             WHERE username = 'phase2_suspended'"
        )
        ->fetchColumn();

    $adminUser = [
        'id' => $adminUserId,
        'role_id' => (int) $superAdminRoleId,
    ];

    $nextCalledAdmin = false;

    ob_start();

    \App\Middleware\RoleMiddleware::handle(
        $adminUser,
        ['super_admin'],
        $authServiceForUnit,
        $adminAudit,
        function () use (&$nextCalledAdmin) {
            $nextCalledAdmin = true;
        }
    );

    ob_get_clean();

    check(
        'Matching role is allowed through',
        $nextCalledAdmin,
        $failures,
        $passes
    );

    $authzFailureLogged = $pdo
        ->query(
            "SELECT COUNT(*)
             FROM audit_logs
             WHERE action = 'AUTHORIZATION_FAILURE'"
        )
        ->fetchColumn();

    check(
        'AUTHORIZATION_FAILURE event recorded for the blocked attempt',
        (int) $authzFailureLogged >= 1,
        $failures,
        $passes
    );
} catch (\Throwable $e) {
    /*
     * If a test crashes unexpectedly, print the exception instead of
     * silently terminating.
     */
    fwrite(
        STDERR,
        "\nUnexpected validation exception:\n"
        . get_class($e)
        . ': '
        . $e->getMessage()
        . "\n\n"
        . $e->getTraceAsString()
        . "\n"
    );

    $failures[] = 'Unexpected validation exception';
} finally {
    /*
     * ----------------------------------------------------------------------
     * Stop test server
     * ----------------------------------------------------------------------
     */

    if (isset($process) && is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
}

/*
 * --------------------------------------------------------------------------
 * Final result
 * --------------------------------------------------------------------------
 */

echo "\n" . str_repeat('=', 55) . "\n";

echo "Result: $passes passed, "
    . count($failures)
    . " failed\n";

if (count($failures) > 0) {
    echo "\nFailed checks:\n";

    foreach ($failures as $failure) {
        echo "  - $failure\n";
    }

    echo "\nServer log (last 40 lines):\n";

    $log = @file_get_contents($serverLog);

    if ($log) {
        $lines = explode(
            "\n",
            trim($log)
        );

        echo implode(
            "\n",
            array_slice($lines, -40)
        ) . "\n";
    } else {
        echo "  No server log available.\n";
    }

    exit(1);
}

echo "\nPhase 2 validation: ALL CHECKS PASSED\n";

exit(0);