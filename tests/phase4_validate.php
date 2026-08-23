<?php

declare(strict_types=1);

/**
 * NCA Batch 4 CTF — Phase 4 Challenge System Validation
 *
 * Windows/XAMPP-compatible validation suite.
 *
 * IMPORTANT:
 * This validator does NOT spawn its own PHP server. Self-spawning a
 * PHP development server via proc_open() from inside another PHP
 * process is unreliable on Windows (the port can appear free at
 * selection time and then fail to bind, producing misleading
 * "Failed to listen" errors that look like application bugs but
 * are actually a test-harness process-management problem). This
 * mirrors the fix already applied to phase3_validate.php.
 *
 * Start the PHP development server manually before running this test:
 *
 *   & "C:\xampp\php\php.exe" -S 127.0.0.1:8124 -t public public/index.php
 *
 * Then run:
 *
 *   & "C:\xampp\php\php.exe" tests\phase4_validate.php --database=nca_ctf_test
 *
 * CRITICAL — READ THIS:
 * This validator's --database flag ONLY controls which database THIS
 * SCRIPT resets/migrates/seeds/queries directly. It has NO effect on
 * the already-running HTTP server above, which is a separate process
 * that reads DB_DATABASE purely from its own .env file (or a real
 * shell environment variable) at the moment IT was started. Before
 * starting the server, make sure the project's .env contains:
 *
 *   DB_DATABASE=nca_ctf_test
 *
 * (matching whatever --database value you pass below). If it does
 * not match, this validator will detect the mismatch during its
 * mandatory database identity check and stop immediately with a
 * clear diagnosis, rather than running into dozens of confusing
 * downstream failures.
 *
 * Options:
 *
 *   --port=8124                     Port of the already-running server
 *                                    (default: 8124, same as phase3).
 *   --base-url=http://127.0.0.1:8124  Explicit base URL override. Takes
 *                                    precedence over --port if supplied.
 *   --database=nca_ctf_test         Test database name.
 *
 * This validator:
 *   - Creates/resets a dedicated test database
 *   - Runs migrations and seed data
 *   - Performs a GET / health check against the already-running server
 *     and STOPS immediately with full diagnostics if it does not pass,
 *     instead of running dozens of misleading downstream failures
 *   - Drives the application through real HTTP requests (curl)
 *   - Tests authentication, authorization, CRUD, lifecycle,
 *     visibility, filtering, pagination, files, hints, flags,
 *     IDOR, CSRF and audit logging
 *   - Runs Phase 3 regression validation at the end, against the
 *     same already-running server
 */

$root = dirname(__DIR__);

require $root . '/app/Infrastructure/Autoloader.php';

\App\Infrastructure\Autoloader::register($root . '/app');

use App\Infrastructure\Database;
use App\Infrastructure\Env;
use App\Infrastructure\FileStorage;

Env::load($root . '/.env');

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

$options = getopt('', [
    'database:',
    'port:',
    'base-url:',
]);

/*
 * Guard against accidentally deriving "..._test_test" when the
 * configured DB_DATABASE already points at a dedicated test
 * database (this has bitten local Windows setups before).
 */
$configuredDatabase = Env::get('DB_DATABASE', 'nca_ctf');

$defaultTestDatabase = str_ends_with($configuredDatabase, '_test')
    ? $configuredDatabase
    : $configuredDatabase . '_test';

$testDatabase = $options['database'] ?? $defaultTestDatabase;

$host = '127.0.0.1';

$port = (int) ($options['port'] ?? 8124);

$baseUrl = isset($options['base-url'])
    ? rtrim((string) $options['base-url'], '/')
    : "http://{$host}:{$port}";

$phpBinary = PHP_BINARY;

$tempBase = rtrim(
    getenv('TEMP')
        ?: getenv('TMP')
        ?: sys_get_temp_dir(),
    DIRECTORY_SEPARATOR
);

$tempDir = $tempBase
    . DIRECTORY_SEPARATOR
    . 'nca_ctf_phase4';

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0750, true);
}

$serverLog = $tempDir
    . DIRECTORY_SEPARATOR
    . 'phase4_server.log';

$serverErrorLog = $tempDir
    . DIRECTORY_SEPARATOR
    . 'phase4_server_error.log';

$testFilePath = $tempDir
    . DIRECTORY_SEPARATOR
    . 'phase4_testfile.txt';

$partCookieJar = $tempDir
    . DIRECTORY_SEPARATOR
    . 'phase4_part_cookies.txt';

$adminCookieJar = $tempDir
    . DIRECTORY_SEPARATOR
    . 'phase4_admin_cookies.txt';

$superCookieJar = $tempDir
    . DIRECTORY_SEPARATOR
    . 'phase4_super_cookies.txt';

$failures = [];
$passes = 0;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function check(
    string $label,
    bool $condition,
    array &$failures,
    int &$passes
): void {
    if ($condition) {
        echo "  [PASS] {$label}\n";
        $passes++;
    } else {
        echo "  [FAIL] {$label}\n";
        $failures[] = $label;
    }
}

/**
 * Cross-platform recursive directory removal.
 */
function removeDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $fullPath = $path . DIRECTORY_SEPARATOR . $item;

        if (is_dir($fullPath) && !is_link($fullPath)) {
            removeDirectory($fullPath);
        } else {
            @unlink($fullPath);
        }
    }

    @rmdir($path);
}

/**
 * Delete a temporary file safely.
 */
function removeFile(string $path): void
{
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Run a curl.exe command via proc_open() with an argument array.
 *
 * This is the Windows-reliable transport already proven in
 * phase3_validate.php had used, plus a further reliability fix: it
 * performs HTTP requests using PHP's native cURL extension
 * (curl_init/curl_exec) instead of spawning a curl.exe *child
 * process* via proc_open(). On at least one real Windows/XAMPP
 * machine, curl.exe run directly in a terminal worked fine, but
 * curl.exe spawned as a child process from inside PHP via
 * proc_open() consistently timed out with 0 bytes received (curl
 * exit 28), even though the PHP dev server was confirmed listening
 * and reachable via a normal terminal curl call. That is consistent
 * with security software (AV/EDR) or a proxy environment variable
 * treating child-process-initiated network I/O differently from
 * interactive terminal use. Using ext-curl performs the request from
 * within this same PHP process, with no child process involved,
 * which sidesteps that class of problem entirely. XAMPP ships
 * ext-curl enabled by default.
 */
if (!extension_loaded('curl')) {
    fwrite(
        STDERR,
        "ERROR: The PHP curl extension is not loaded.\n"
        . "XAMPP ships this enabled by default under extension=curl\n"
        . "in php.ini. Enable it and restart, then re-run.\n"
    );

    exit(1);
}

/**
 * Perform a real HTTP request using PHP's native cURL extension.
 */
function httpRequest(
    string $method,
    string $url,
    ?array $jsonBody = null,
    ?string $cookieJar = null,
    array $headers = []
): array {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $requestHeaders = $headers;

    if ($jsonBody !== null) {
        $encoded = json_encode(
            $jsonBody,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($encoded === false) {
            throw new RuntimeException(
                'Could not JSON encode request body.'
            );
        }

        $requestHeaders[] = 'Content-Type: application/json';

        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    if ($requestHeaders !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
    }

    if ($cookieJar !== null) {
        $cookieDirectory = dirname($cookieJar);

        if (!is_dir($cookieDirectory)) {
            mkdir($cookieDirectory, 0777, true);
        }

        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }

    $raw = curl_exec($ch);

    $errorNumber = curl_errno($ch);
    $errorMessage = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    curl_close($ch);

    if ($raw === false) {
        return [
            'status' => 0,
            'body' => [],
            'raw' => '',
            'header' => '',
            'curl_exit_code' => $errorNumber,
            'curl_stderr' => $errorMessage,
        ];
    }

    $headerPart = substr($raw, 0, $headerSize);
    $bodyPart = substr($raw, $headerSize);

    $decoded = json_decode(
        $bodyPart,
        true
    );

    return [
        'status' => $status,
        'body' => is_array($decoded) ? $decoded : [],
        'raw' => $bodyPart,
        'header' => $headerPart,
        'curl_exit_code' => $errorNumber,
        'curl_stderr' => $errorMessage,
    ];
}

/**
 * Upload a file using multipart/form-data via native ext-curl.
 */
function uploadFile(
    string $url,
    string $filePath,
    ?string $cookieJar,
    array $headers
): array {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($filePath),
        ],
    ]);

    if ($headers !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($cookieJar !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }

    $raw = curl_exec($ch);

    $errorNumber = curl_errno($ch);
    $errorMessage = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    curl_close($ch);

    if ($raw === false) {
        return [
            'status' => 0,
            'body' => [],
            'raw' => '',
            'header' => '',
            'curl_exit_code' => $errorNumber,
            'curl_stderr' => $errorMessage,
        ];
    }

    $headerPart = substr($raw, 0, $headerSize);
    $bodyPart = substr($raw, $headerSize);

    $decoded = json_decode(
        $bodyPart,
        true
    );

    return [
        'status' => $status,
        'body' => is_array($decoded) ? $decoded : [],
        'raw' => $bodyPart,
        'header' => $headerPart,
        'curl_exit_code' => $errorNumber,
        'curl_stderr' => $errorMessage,
    ];
}

/**
 * Register a user and login.
 */
function registerAndLogin(
    string $baseUrl,
    string $username,
    string $email,
    string $cookieJar
): array {
    removeFile($cookieJar);

    httpRequest(
        'POST',
        "{$baseUrl}/api/v1/auth/register",
        [
            'username' => $username,
            'email' => $email,
            'password' => 'correcthorse1',
        ]
    );

    $login = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/auth/login",
        [
            'identifier' => $username,
            'password' => 'correcthorse1',
        ],
        $cookieJar
    );

    return [
        $cookieJar,
        $login['body']['data']['csrf_token'] ?? '',
    ];
}

/**
 * Print useful information when an HTTP request unexpectedly fails.
 */
function debugResponse(
    string $label,
    array $response
): void {
    if ($response['status'] >= 200 && $response['status'] < 500) {
        return;
    }

    echo "\n  [DEBUG] {$label}\n";
    echo "  HTTP status: {$response['status']}\n";

    if ($response['raw'] !== '') {
        echo "  Response:\n";
        echo "  " . str_replace(
            "\n",
            "\n  ",
            trim($response['raw'])
        ) . "\n";
    }
}

/*
|--------------------------------------------------------------------------
| Banner
|--------------------------------------------------------------------------
*/

echo "NCA Batch 4 CTF — Phase 4 Challenge System Validation\n";
echo "Target base URL: {$baseUrl}\n";
echo "Target test database: {$testDatabase}\n";
echo "PHP binary: {$phpBinary}\n";
echo "Temporary directory: {$tempDir}\n";
echo str_repeat('=', 55) . "\n\n";

/*
|--------------------------------------------------------------------------
| Mandatory health check
|--------------------------------------------------------------------------
|
| This validator does NOT start its own server. If the server is not
| already running at $baseUrl, EVERY downstream test would fail in a
| way that looks like broken application code but is really just
| "nothing is listening". We refuse to proceed until this passes, and
| print exact diagnostics so the real cause is obvious immediately.
*/

echo "Checking server health at {$baseUrl}/ ...\n";

$healthCheckAttempts = 0;
$healthCheckMaxAttempts = 5;
$healthCheckPassed = false;
$lastHealthResponse = null;

while ($healthCheckAttempts < $healthCheckMaxAttempts) {
    $healthCheckAttempts++;

    $lastHealthResponse = httpRequest('GET', "{$baseUrl}/");

    if (
        $lastHealthResponse['status'] >= 200
        && $lastHealthResponse['status'] < 500
    ) {
        $healthCheckPassed = true;
        break;
    }

    usleep(500000);
}

if (!$healthCheckPassed) {
    fwrite(STDERR, "\n" . str_repeat('=', 55) . "\n");
    fwrite(STDERR, "ERROR: Health check failed. No working server was\n");
    fwrite(STDERR, "found at {$baseUrl}/ after {$healthCheckAttempts} attempt(s).\n\n");
    fwrite(STDERR, "This validator does NOT start its own server.\n");
    fwrite(STDERR, "Start it manually first, in a separate terminal:\n\n");
    fwrite(
        STDERR,
        "  \"" . $phpBinary . "\" -S {$host}:{$port} -t "
        . $root . DIRECTORY_SEPARATOR . "public "
        . $root . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "index.php\n\n"
    );
    fwrite(STDERR, "Diagnostics:\n");
    fwrite(STDERR, "  Base URL:        {$baseUrl}\n");
    fwrite(STDERR, "  Host:            {$host}\n");
    fwrite(STDERR, "  Port:            {$port}\n");
    fwrite(STDERR, "  PHP binary:      {$phpBinary}\n");
    fwrite(STDERR, "  Attempts made:   {$healthCheckAttempts}\n");

    if ($lastHealthResponse !== null) {
        fwrite(STDERR, "  Last HTTP status: {$lastHealthResponse['status']}\n");
        fwrite(STDERR, "  curl exit code:    " . ($lastHealthResponse['curl_exit_code'] ?? 'n/a') . "\n");

        if (!empty($lastHealthResponse['curl_stderr'])) {
            fwrite(STDERR, "  curl stderr:       " . trim($lastHealthResponse['curl_stderr']) . "\n");
        }

        if ($lastHealthResponse['raw'] !== '') {
            fwrite(STDERR, "  Last response body:\n");
            fwrite(STDERR, "    " . trim($lastHealthResponse['raw']) . "\n");
        }
    }

    fwrite(STDERR, str_repeat('=', 55) . "\n");

    exit(1);
}

echo "Server is reachable at {$baseUrl}/ (HTTP {$lastHealthResponse['status']}).\n\n";

/*
|--------------------------------------------------------------------------
| Connect to test database
|--------------------------------------------------------------------------
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

/*
|--------------------------------------------------------------------------
| Reset test database
|--------------------------------------------------------------------------
*/

echo "Resetting test database\n";

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $tables = $pdo
        ->query('SHOW TABLES')
        ->fetchAll(\PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $quotedTable = str_replace(
            '`',
            '``',
            (string) $table
        );

        $pdo->exec(
            "DROP TABLE IF EXISTS `{$quotedTable}`"
        );
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "  Dropped " . count($tables) . " existing table(s)\n\n";
} catch (\Throwable $e) {
    fwrite(
        STDERR,
        "Failed resetting test database: "
        . $e->getMessage()
        . "\n"
    );

    exit(1);
}

/*
|--------------------------------------------------------------------------
| Run migrations
|--------------------------------------------------------------------------
*/

echo "Running migrations + seed against test database\n";

$migrateCommand =
    escapeshellarg($phpBinary)
    . ' '
    . escapeshellarg($root . '/database/migrate.php')
    . ' --database='
    . escapeshellarg($testDatabase)
    . ' 2>&1';

$migrateOutput = [];

exec(
    $migrateCommand,
    $migrateOutput,
    $migrateCode
);

check(
    'Migrations applied',
    $migrateCode === 0,
    $failures,
    $passes
);

if ($migrateCode !== 0) {
    echo "\nMigration output:\n";
    echo implode("\n", $migrateOutput) . "\n";
}

/*
|--------------------------------------------------------------------------
| Seed
|--------------------------------------------------------------------------
*/

putenv("DB_DATABASE={$testDatabase}");

$seedCommand =
    escapeshellarg($phpBinary)
    . ' '
    . escapeshellarg($root . '/database/seed.php')
    . ' 2>&1';

$seedOutput = [];

exec(
    $seedCommand,
    $seedOutput,
    $seedCode
);

check(
    'Seed data applied',
    $seedCode === 0,
    $failures,
    $passes
);

if ($seedCode !== 0) {
    echo "\nSeed output:\n";
    echo implode("\n", $seedOutput) . "\n";
}

/*
 * Do not leave the test database in the current process environment
 * because the regression suite controls its own database.
 */
putenv('DB_DATABASE');

/*
|--------------------------------------------------------------------------
| Prepare upload storage
|--------------------------------------------------------------------------
*/

$uploadTestDir = FileStorage::baseDir($root);

if (is_dir($uploadTestDir)) {
    removeDirectory($uploadTestDir);
}

if (!is_dir($uploadTestDir)) {
    mkdir(
        $uploadTestDir,
        0750,
        true
    );
}

/*
|--------------------------------------------------------------------------
| Server readiness
|--------------------------------------------------------------------------
|
| No server is spawned here. The health check performed earlier already
| confirmed a working server is listening at $baseUrl and using
| public/index.php as its router (otherwise GET / would not have
| returned a 2xx/3xx/4xx response). It is the operator's responsibility
| to point that already-running server's environment at $testDatabase
| (see the docblock at the top of this file), exactly as required by
| phase3_validate.php and phase2_validate.php.
*/

/*
|--------------------------------------------------------------------------
| Mandatory database identity check
|--------------------------------------------------------------------------
|
| CRITICAL: this validator's own $pdo connection (used above to reset,
| migrate and seed) and the externally-running HTTP application's
| database connection are two COMPLETELY SEPARATE PDO connections
| (App\Infrastructure\Database::connectTo() vs ::connection()). The
| HTTP application was started as its own process, in its own
| terminal, and reads DB_DATABASE purely from the .env file (or a
| real shell environment variable) that was present when THAT process
| started — see app/Infrastructure/Env.php + Database.php. It has NO
| knowledge of, and is not affected by, this validator's --database
| flag, its putenv() calls, or anything else this script does.
|
| If the running application's .env does not point DB_DATABASE at
| $testDatabase, every single stateful assertion below (register,
| login, challenge CRUD, files, hints, flags, audit logs) will
| exercise a different, likely stale/unmigrated/unseeded database
| and fail — not because of an application bug, but because of a
| test-environment mismatch. Those failures are indistinguishable
| from real bugs unless we check for this specifically, first.
|
| We prove the connection here by round-tripping through the HTTP
| application itself: register a uniquely-named user through the
| real /api/v1/auth/register endpoint (no application code changes
| required), then look for that exact row using this validator's own
| $pdo, which is explicitly connected to $testDatabase. If it is not
| there, the HTTP application is provably talking to a different
| database, and we stop immediately with an actionable diagnosis
| instead of producing dozens of misleading downstream failures.
*/

echo "Verifying the running application is connected to '{$testDatabase}'...\n";

$dbIdentityMarker = 'nca_dbcheck_' . bin2hex(random_bytes(6));
$dbIdentityEmail = $dbIdentityMarker . '@example.test';

$dbIdentityRegister = httpRequest(
    'POST',
    "{$baseUrl}/api/v1/auth/register",
    [
        'username' => $dbIdentityMarker,
        'email' => $dbIdentityEmail,
        'password' => 'correcthorse1',
    ]
);

if ($dbIdentityRegister['status'] !== 201) {
    fwrite(STDERR, "\n" . str_repeat('=', 55) . "\n");
    fwrite(STDERR, "ERROR: Database identity check could not even register\n");
    fwrite(STDERR, "a throwaway user through the running application.\n\n");
    fwrite(STDERR, "HTTP status: {$dbIdentityRegister['status']}\n");
    fwrite(STDERR, "Response:\n" . trim((string) $dbIdentityRegister['raw']) . "\n");
    fwrite(STDERR, str_repeat('=', 55) . "\n");

    exit(1);
}

$dbIdentityStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM users WHERE username = ?'
);

$dbIdentityStmt->execute([$dbIdentityMarker]);

$dbIdentityFound = (int) $dbIdentityStmt->fetchColumn();

if ($dbIdentityFound !== 1) {
    fwrite(STDERR, "\n" . str_repeat('=', 55) . "\n");
    fwrite(STDERR, "ERROR: Database identity mismatch detected.\n\n");
    fwrite(STDERR, "This validator just registered a user through the running\n");
    fwrite(STDERR, "application at {$baseUrl}, then looked for that exact user\n");
    fwrite(STDERR, "in database '{$testDatabase}' (this validator's own\n");
    fwrite(STDERR, "connection) — and did not find it.\n\n");
    fwrite(STDERR, "This means the running application is NOT connected to\n");
    fwrite(STDERR, "'{$testDatabase}'. It is connected to whatever DB_DATABASE\n");
    fwrite(STDERR, "was set in its OWN .env file (or shell environment) at the\n");
    fwrite(STDERR, "moment IT was started — this validator's --database flag\n");
    fwrite(STDERR, "has no effect on that already-running process.\n\n");
    fwrite(STDERR, "Fix: stop the running server, edit .env in the project\n");
    fwrite(STDERR, "root so that:\n\n");
    fwrite(STDERR, "  DB_DATABASE={$testDatabase}\n\n");
    fwrite(STDERR, "then restart the server and re-run this validator:\n\n");
    fwrite(
        STDERR,
        "  \"" . $phpBinary . "\" -S {$host}:{$port} -t "
        . $root . DIRECTORY_SEPARATOR . "public "
        . $root . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "index.php\n\n"
    );
    fwrite(STDERR, str_repeat('=', 55) . "\n");

    exit(1);
}

echo "Confirmed: the running application is writing to '{$testDatabase}'.\n\n";

/*
|--------------------------------------------------------------------------
| Main Phase 4 tests
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Setup users
    |--------------------------------------------------------------------------
    */

    [$partJar, $partCsrf] = registerAndLogin(
        $baseUrl,
        'p4part',
        'p4part@example.test',
        $partCookieJar
    );

    [$adminJar, $adminCsrf] = registerAndLogin(
        $baseUrl,
        'p4admin',
        'p4admin@example.test',
        $adminCookieJar
    );

    [$superJar, $superCsrf] = registerAndLogin(
        $baseUrl,
        'p4super',
        'p4super@example.test',
        $superCookieJar
    );

    /*
    |--------------------------------------------------------------------------
    | Promote users
    |--------------------------------------------------------------------------
    */

    $challengeAdminRoleId = (int) $pdo
        ->query(
            "SELECT id FROM roles WHERE name = 'challenge_admin'"
        )
        ->fetchColumn();

    $superAdminRoleId = (int) $pdo
        ->query(
            "SELECT id FROM roles WHERE name = 'super_admin'"
        )
        ->fetchColumn();

    $pdo->exec(
        "UPDATE users
         SET role_id = {$challengeAdminRoleId}
         WHERE username = 'p4admin'"
    );

    $pdo->exec(
        "UPDATE users
         SET role_id = {$superAdminRoleId}
         WHERE username = 'p4super'"
    );

    /*
     * Re-login after role promotion so the session is recreated.
     *
     * Registration will fail because the users already exist,
     * but login succeeds and creates the correct session.
     */
    [$adminJar, $adminCsrf] = registerAndLogin(
        $baseUrl,
        'p4admin',
        'p4admin@example.test',
        $adminCookieJar
    );

    [$superJar, $superCsrf] = registerAndLogin(
        $baseUrl,
        'p4super',
        'p4super@example.test',
        $superCookieJar
    );

    /*
    |--------------------------------------------------------------------------
    | Challenge CRUD
    |--------------------------------------------------------------------------
    */

    echo "Challenge CRUD\n";

    $partCreateAttempt = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'Should Fail',
            'category' => 'web',
            'difficulty' => 'easy',
            'points' => 100,
            'deployment_type' => 'HTTP',
        ],
        $partJar,
        [
            "X-CSRF-Token: {$partCsrf}",
        ]
    );

    debugResponse(
        'Participant challenge creation',
        $partCreateAttempt
    );

    check(
        '6. Participant cannot create a challenge',
        $partCreateAttempt['status'] === 403,
        $failures,
        $passes
    );

    $create = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'SQL Injection 101',
            'description' => 'Find the flag',
            'category' => 'web',
            'difficulty' => 'easy',
            'points' => 100,
            'deployment_type' => 'HTTP',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    debugResponse(
        'Challenge admin creation',
        $create
    );

    check(
        '7. Challenge admin can create a challenge',
        $create['status'] === 201
            && ($create['body']['success'] ?? false) === true,
        $failures,
        $passes
    );

    check(
        'New challenge starts as draft',
        ($create['body']['data']['challenge']['status'] ?? '') === 'draft',
        $failures,
        $passes
    );

    $challengeId =
        (int) ($create['body']['data']['challenge']['id'] ?? 0);

    $challengeSlug =
        (string) ($create['body']['data']['challenge']['slug'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Participant cannot modify
    |--------------------------------------------------------------------------
    */

    $partUpdateAttempt = httpRequest(
        'PUT',
        "{$baseUrl}/api/v1/challenges/{$challengeId}",
        [
            'title' => 'Hacked',
            'category' => 'web',
            'difficulty' => 'easy',
            'points' => 1,
            'deployment_type' => 'HTTP',
        ],
        $partJar,
        [
            "X-CSRF-Token: {$partCsrf}",
        ]
    );

    check(
        '5. Participant cannot modify a challenge',
        $partUpdateAttempt['status'] === 403,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Admin edit
    |--------------------------------------------------------------------------
    */

    $edit = httpRequest(
        'PUT',
        "{$baseUrl}/api/v1/challenges/{$challengeId}",
        [
            'title' => 'SQL Injection 101',
            'description' => 'Updated description',
            'category' => 'web',
            'difficulty' => 'medium',
            'points' => 150,
            'deployment_type' => 'HTTP',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '8. Challenge admin can edit a challenge',
        $edit['status'] === 200
            && (
                $edit['body']['data']['challenge']['difficulty']
                ?? ''
            ) === 'medium',
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    echo "\nValidation\n";

    $badCategory = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'Bad Cat',
            'category' => 'not-a-real-category',
            'difficulty' => 'easy',
            'points' => 10,
            'deployment_type' => 'HTTP',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '13. Invalid category rejected',
        $badCategory['status'] === 422,
        $failures,
        $passes
    );

    $badDifficulty = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'Bad Diff',
            'category' => 'web',
            'difficulty' => 'impossible',
            'points' => 10,
            'deployment_type' => 'HTTP',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '14. Invalid difficulty rejected',
        $badDifficulty['status'] === 422,
        $failures,
        $passes
    );

    $badPoints = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'Bad Points',
            'category' => 'web',
            'difficulty' => 'easy',
            'points' => -5,
            'deployment_type' => 'HTTP',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '15. Invalid points rejected',
        $badPoints['status'] === 422,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Slug collision
    |--------------------------------------------------------------------------
    */

    $dupTitle1 = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'Duplicate Title Challenge',
            'category' => 'web',
            'difficulty' => 'easy',
            'points' => 10,
            'deployment_type' => 'HTTP',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    $dupTitle2 = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'Duplicate Title Challenge',
            'category' => 'web',
            'difficulty' => 'easy',
            'points' => 10,
            'deployment_type' => 'HTTP',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    $slug1 =
        $dupTitle1['body']['data']['challenge']['slug'] ?? 'a';

    $slug2 =
        $dupTitle2['body']['data']['challenge']['slug'] ?? 'b';

    check(
        '16. Duplicate slug prevented (auto-deduplicated, both succeed with distinct slugs)',
        $dupTitle1['status'] === 201
            && $dupTitle2['status'] === 201
            && $slug1 !== $slug2,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Lifecycle
    |--------------------------------------------------------------------------
    */

    echo "\nLifecycle\n";

    $publish = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges/{$challengeId}/publish",
        null,
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '9. Challenge admin can publish a challenge',
        $publish['status'] === 200
            && (
                $publish['body']['data']['challenge']['status']
                ?? ''
            ) === 'published',
        $failures,
        $passes
    );

    $pause = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges/{$challengeId}/pause",
        null,
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '10. Challenge admin can pause a challenge',
        $pause['status'] === 200
            && (
                $pause['body']['data']['challenge']['status']
                ?? ''
            ) === 'paused',
        $failures,
        $passes
    );

    /*
     * Republish for participant visibility tests.
     */
    httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges/{$challengeId}/publish",
        null,
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | Archive
    |--------------------------------------------------------------------------
    */

    $draftForArchive = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'To Be Archived',
            'category' => 'pwn',
            'difficulty' => 'hard',
            'points' => 300,
            'deployment_type' => 'TCP',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    $archiveId =
        (int) (
            $draftForArchive['body']['data']['challenge']['id']
            ?? 0
        );

    $archive = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges/{$archiveId}/archive",
        null,
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '11. Challenge admin can archive a challenge',
        $archive['status'] === 200
            && (
                $archive['body']['data']['challenge']['status']
                ?? ''
            ) === 'archived',
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Super admin
    |--------------------------------------------------------------------------
    */

    $superCreate = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'Super Admin Challenge',
            'category' => 'crypto',
            'difficulty' => 'hard',
            'points' => 250,
            'deployment_type' => 'DOWNLOAD',
        ],
        $superJar,
        [
            "X-CSRF-Token: {$superCsrf}",
        ]
    );

    check(
        '12. Super admin can manage challenges',
        $superCreate['status'] === 201,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Participant visibility
    |--------------------------------------------------------------------------
    */

    echo "\nParticipant visibility\n";

    $draftChallenge = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'Still A Draft',
            'category' => 'general',
            'difficulty' => 'easy',
            'points' => 50,
            'deployment_type' => 'DOWNLOAD',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    $draftId =
        (int) (
            $draftChallenge['body']['data']['challenge']['id']
            ?? 0
        );

    $draftSlug =
        (string) (
            $draftChallenge['body']['data']['challenge']['slug']
            ?? ''
        );

    $participantList = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges?per_page=100",
        null,
        $partJar
    );

    $listedIds = array_column(
        $participantList['body']['data']['challenges'] ?? [],
        'id'
    );

    check(
        '1. Participant can list published challenges',
        in_array($challengeId, $listedIds, true),
        $failures,
        $passes
    );

    check(
        '2. Participant cannot see draft challenges in listing',
        !in_array($draftId, $listedIds, true),
        $failures,
        $passes
    );

    $directDraftAccess = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges/{$draftSlug}",
        null,
        $partJar
    );

    check(
        '2b. Participant cannot view draft challenge directly (404, not leaked)',
        $directDraftAccess['status'] === 404,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Testing visibility
    |--------------------------------------------------------------------------
    */

    $pdo->exec(
        "UPDATE challenges
         SET status = 'testing'
         WHERE id = {$draftId}"
    );

    $testingAccess = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges/{$draftSlug}",
        null,
        $partJar
    );

    check(
        '3. Participant cannot see testing challenges',
        $testingAccess['status'] === 404,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Published detail
    |--------------------------------------------------------------------------
    */

    $detail = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges/{$challengeSlug}",
        null,
        $partJar
    );

    check(
        '4. Participant can view a published challenge',
        $detail['status'] === 200
            && (
                $detail['body']['data']['challenge']['title']
                ?? ''
            ) === 'SQL Injection 101',
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Filtering
    |--------------------------------------------------------------------------
    */

    echo "\nFiltering & pagination\n";

    $filtered = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges?category=pwn",
        null,
        $partJar
    );

    $filteredCategories = array_unique(
        array_column(
            $filtered['body']['data']['challenges'] ?? [],
            'category'
        )
    );

    check(
        '17. Challenge filtering by category works',
        $filtered['status'] === 200
            && (
                count($filteredCategories) === 0
                || $filteredCategories === ['Pwn']
            ),
        $failures,
        $passes
    );

    $diffFiltered = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges?difficulty=medium",
        null,
        $partJar
    );

    $diffValues = array_unique(
        array_column(
            $diffFiltered['body']['data']['challenges'] ?? [],
            'difficulty'
        )
    );

    check(
        '17b. Challenge filtering by difficulty works',
        $diffFiltered['status'] === 200
            && (
                count($diffValues) === 0
                || $diffValues === ['medium']
            ),
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    $page1 = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges?per_page=1&page=1",
        null,
        $partJar
    );

    $page2 = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges?per_page=1&page=2",
        null,
        $partJar
    );

    $id1 =
        $page1['body']['data']['challenges'][0]['id']
        ?? null;

    $id2 =
        $page2['body']['data']['challenges'][0]['id']
        ?? null;

    check(
        '18. Pagination works (distinct items per page)',
        $page1['status'] === 200
            && $id1 !== null
            && $id1 !== $id2,
        $failures,
        $passes
    );

    check(
        '18b. Pagination metadata present',
        isset(
            $page1['body']['data']['pagination']['total']
        ),
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Challenge files
    |--------------------------------------------------------------------------
    */

    echo "\nChallenge files\n";

    file_put_contents(
        $testFilePath,
        "hello ctf phase4\n"
    );

    $uploadResult = uploadFile(
        "{$baseUrl}/api/v1/challenges/{$challengeId}/files",
        $testFilePath,
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '19. Challenge files can be registered securely',
        $uploadResult['status'] === 201
            && ($uploadResult['body']['success'] ?? false) === true,
        $failures,
        $passes
    );

    check(
        '19b. storage_path never returned in API response',
        !isset(
            $uploadResult['body']['data']['file']['storage_path']
        ),
        $failures,
        $passes
    );

    $fileId =
        (int) (
            $uploadResult['body']['data']['file']['id']
            ?? 0
        );

    /*
    |--------------------------------------------------------------------------
    | Path traversal
    |--------------------------------------------------------------------------
    */

    $traversalAttempt = FileStorage::resolvedPath(
        $root,
        '../../../../etc/passwd'
    );

    check(
        '20. Files cannot escape the storage directory (FileStorage guard)',
        $traversalAttempt === null,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Authorized file download
    |--------------------------------------------------------------------------
    */

    $download = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenge-files/{$fileId}/download",
        null,
        $partJar
    );

    check(
        '21. Participant can access authorized challenge files',
        $download['status'] === 200
            && str_contains(
                $download['raw'],
                'hello ctf phase4'
            ),
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Hidden challenge file
    |--------------------------------------------------------------------------
    */

    $uploadOnHidden = uploadFile(
        "{$baseUrl}/api/v1/challenges/{$draftId}/files",
        $testFilePath,
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    $hiddenFileId =
        (int) (
            $uploadOnHidden['body']['data']['file']['id']
            ?? 0
        );

    $hiddenDownload = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenge-files/{$hiddenFileId}/download",
        null,
        $partJar
    );

    check(
        '22. Participant cannot access files on a non-visible challenge',
        $hiddenDownload['status'] === 404,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Hints
    |--------------------------------------------------------------------------
    */

    echo "\nHints\n";

    $hintCreate = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges/{$challengeId}/hints",
        [
            'title' => 'Hint 1',
            'content' => 'Try a single quote',
            'point_penalty' => 10,
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '23. Challenge hints can be created by authorized admins',
        $hintCreate['status'] === 201,
        $failures,
        $passes
    );

    $hintId =
        (int) (
            $hintCreate['body']['data']['hint']['id']
            ?? 0
        );

    $participantDetail = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges/{$challengeSlug}",
        null,
        $partJar
    );

    $hintsInDetail =
        $participantDetail['body']['data']['challenge']['hints']
        ?? [];

    $hintContentLeaked = false;

    foreach ($hintsInDetail as $hint) {
        if (array_key_exists('content', $hint)) {
            $hintContentLeaked = true;
        }
    }

    check(
        '24. Participant cannot see unrevealed hint content',
        !$hintContentLeaked,
        $failures,
        $passes
    );

    $reveal = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenge-hints/{$hintId}/reveal",
        null,
        $partJar,
        [
            "X-CSRF-Token: {$partCsrf}",
        ]
    );

    check(
        '25. Hint reveal works',
        $reveal['status'] === 200
            && (
                $reveal['body']['data']['hint']['content']
                ?? ''
            ) === 'Try a single quote',
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Flags
    |--------------------------------------------------------------------------
    */

    echo "\nFlags\n";

    $flagCreate = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges/{$challengeId}/flag",
        [
            'flag' => 'NCA{phase4_test_flag}',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        '26. Flags can be created by authorized admins',
        $flagCreate['status'] === 201
            && ($flagCreate['body']['success'] ?? false) === true,
        $failures,
        $passes
    );

    $allParticipantResponses = json_encode([
        $participantList,
        $detail,
        $participantDetail,
        $download['raw'] ?? '',
    ]);

    check(
        '27. Plaintext flag never returned by participant APIs',
        !str_contains(
            $allParticipantResponses ?: '',
            'phase4_test_flag'
        ),
        $failures,
        $passes
    );

    $allResponsesIncludingAdmin = json_encode([
        $flagCreate,
        $participantList,
        $detail,
    ]);

    check(
        '28. Flag hash never returned to anyone (admin included)',
        !str_contains(
            $allResponsesIncludingAdmin ?: '',
            'flag_hash'
        ),
        $failures,
        $passes
    );

    $partFlagAttempt = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges/{$challengeId}/flag",
        [
            'flag' => 'NCA{should_not_work}',
        ],
        $partJar,
        [
            "X-CSRF-Token: {$partCsrf}",
        ]
    );

    check(
        '29. Participants cannot create/modify flags',
        $partFlagAttempt['status'] === 403,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Flag versioning
    |--------------------------------------------------------------------------
    */

    $flagReplace = httpRequest(
        'PUT',
        "{$baseUrl}/api/v1/challenges/{$challengeId}/flag",
        [
            'flag' => 'NCA{phase4_test_flag_v2}',
        ],
        $adminJar,
        [
            "X-CSRF-Token: {$adminCsrf}",
        ]
    );

    check(
        'Flag replace (versioning) works',
        $flagReplace['status'] === 200,
        $failures,
        $passes
    );

    $flagCountForChallenge = (int) $pdo
        ->query(
            "SELECT COUNT(*)
             FROM flags
             WHERE challenge_id = {$challengeId}"
        )
        ->fetchColumn();

    check(
        'Flag history preserved on replace (old version kept, inactive)',
        $flagCountForChallenge === 2,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | IDOR / Authorization
    |--------------------------------------------------------------------------
    */

    echo "\nIDOR / authorization\n";

    $unauthList = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/challenges"
    );

    check(
        '30. Unauthenticated request rejected (no session)',
        $unauthList['status'] === 401,
        $failures,
        $passes
    );

    $unauthCreate = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges",
        [
            'title' => 'x',
            'category' => 'web',
            'difficulty' => 'easy',
            'points' => 1,
            'deployment_type' => 'HTTP',
        ]
    );

    check(
        '30b. Unauthenticated create rejected',
        $unauthCreate['status'] === 401,
        $failures,
        $passes
    );

    $partDeleteAttempt = httpRequest(
        'DELETE',
        "{$baseUrl}/api/v1/challenges/{$draftId}",
        null,
        $partJar,
        [
            "X-CSRF-Token: {$partCsrf}",
        ]
    );

    check(
        '30c. Participant cannot delete a challenge (IDOR/authz)',
        $partDeleteAttempt['status'] === 403,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $noCsrfPublish = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/challenges/{$archiveId}/publish",
        null,
        $adminJar
    );

    check(
        'CSRF protection enforced on challenge lifecycle actions',
        $noCsrfPublish['status'] === 419,
        $failures,
        $passes
    );

    /*
    |--------------------------------------------------------------------------
    | Audit logging
    |--------------------------------------------------------------------------
    */

    echo "\nAudit logging\n";

    $events = $pdo
        ->query(
            "SELECT action FROM audit_logs"
        )
        ->fetchAll(\PDO::FETCH_COLUMN);

    $requiredEvents = [
        'CHALLENGE_CREATED',
        'CHALLENGE_UPDATED',
        'CHALLENGE_PUBLISHED',
        'CHALLENGE_PAUSED',
        'CHALLENGE_ARCHIVED',
        'CHALLENGE_FILE_ADDED',
        'CHALLENGE_HINT_CREATED',
        'CHALLENGE_FLAG_CREATED',
        'CHALLENGE_FLAG_UPDATED',
    ];

    foreach ($requiredEvents as $event) {
        check(
            "31. Audit event recorded: {$event}",
            in_array($event, $events, true),
            $failures,
            $passes
        );
    }

} catch (\Throwable $e) {

    /*
     * Do not allow an unexpected PHP exception to leave the server
     * running or hide the actual error.
     */

    echo "\n\nUNEXPECTED VALIDATOR ERROR\n";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";

    $failures[] =
        'Unexpected validator exception: '
        . $e->getMessage();

} finally {

    /*
     * Clean temporary test file.
     *
     * Note: no server process to terminate here — this validator
     * never spawns one. The externally-started server (which the
     * operator launched manually per the docblock instructions)
     * is left running.
     */
    removeFile($testFilePath);
}

/*
|--------------------------------------------------------------------------
| Phase 3 regression
|--------------------------------------------------------------------------
*/

echo "\nRegression (Phase 1, 2, 3 unaffected)\n";

$p3Output = [];

$p3Command =
    escapeshellarg($phpBinary)
    . ' '
    . escapeshellarg($root . '/tests/phase3_validate.php')
    . ' --database=' . escapeshellarg($testDatabase)
    . ' --base-url=' . escapeshellarg($baseUrl)
    . ' 2>&1';

exec(
    $p3Command,
    $p3Output,
    $p3Code
);

check(
    '32-33. Existing auth + team functionality still works (Phase 3 suite, which chains Phase 1+2)',
    $p3Code === 0,
    $failures,
    $passes
);

if ($p3Code !== 0) {
    echo "\nPhase 3 regression output:\n";

    echo implode(
        "\n",
        $p3Output
    );

    echo "\n";
}

/*
|--------------------------------------------------------------------------
| Final result
|--------------------------------------------------------------------------
*/

echo "\n" . str_repeat('=', 55) . "\n";

echo "Result: {$passes} passed, "
    . count($failures)
    . " failed\n";

if (count($failures) > 0) {

    echo "\nFailed checks:\n";

    foreach ($failures as $failure) {
        echo "  - {$failure}\n";
    }

    echo "\nServer log:\n";

    if (is_file($serverLog)) {
        $log = trim(
            (string) file_get_contents($serverLog)
        );

        if ($log !== '') {
            $lines = preg_split(
                '/\R/',
                $log
            );

            echo implode(
                "\n",
                array_slice($lines, -40)
            ) . "\n";
        } else {
            echo "  Server log is empty.\n";
        }
    }

    echo "\nServer error log:\n";

    if (is_file($serverErrorLog)) {
        $errorLog = trim(
            (string) file_get_contents($serverErrorLog)
        );

        if ($errorLog !== '') {
            $lines = preg_split(
                '/\R/',
                $errorLog
            );

            echo implode(
                "\n",
                array_slice($lines, -40)
            ) . "\n";
        } else {
            echo "  Server error log is empty.\n";
        }
    }

    exit(1);
}

echo "\nPhase 4 validation: ALL CHECKS PASSED\n";

exit(0);