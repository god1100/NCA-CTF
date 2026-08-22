<?php

declare(strict_types=1);

/**
 * NCA Batch 4 CTF — Phase 4 Challenge System Validation
 *
 * Full-stack HTTP validation for Phase 4.
 *
 * This validator:
 *   - Creates/resets a dedicated test database
 *   - Runs migrations and seed data
 *   - Finds a free local TCP port automatically
 *   - Starts its own PHP development server
 *   - Uses public/index.php as the router
 *   - Drives the application through real HTTP requests
 *   - Tests authentication, authorization, CRUD, lifecycle,
 *     visibility, filtering, pagination, files, hints, flags,
 *     IDOR, CSRF and audit logging
 *   - Runs Phase 3 regression validation at the end
 *
 * Run:
 *
 *   php tests/phase4_validate.php
 *
 * Windows:
 *
 *   & "C:\xampp\php\php.exe" tests\phase4_validate.php
 *
 * IMPORTANT:
 *   Do NOT manually run:
 *
 *   php -S 127.0.0.1:8124 -t public
 *
 *   The validator starts and stops its own server.
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
]);

$testDatabase = $options['database'] ?? 'nca_ctf_test';

$requestedPort = isset($options['port'])
    ? (int) $options['port']
    : 0;

$host = '127.0.0.1';

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

$serverProcess = null;
$serverPipes = [];

$port = 0;
$baseUrl = '';

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
 * Find an unused local TCP port.
 */
function findFreePort(string $host = '127.0.0.1'): int
{
    $socket = @stream_socket_server(
        "tcp://{$host}:0",
        $errno,
        $errstr
    );

    if ($socket === false) {
        throw new RuntimeException(
            "Could not find a free TCP port: {$errstr} ({$errno})"
        );
    }

    $name = stream_socket_get_name($socket, false);

    fclose($socket);

    if ($name === false) {
        throw new RuntimeException('Could not determine assigned TCP port.');
    }

    $parts = explode(':', $name);

    $port = (int) end($parts);

    if ($port <= 0) {
        throw new RuntimeException('Invalid dynamically assigned TCP port.');
    }

    return $port;
}

/**
 * Check whether the PHP HTTP server is reachable.
 */
function serverIsReady(
    string $host,
    int $port,
    int $timeoutMilliseconds = 500
): bool {
    $errno = 0;
    $errstr = '';

    $socket = @fsockopen(
        $host,
        $port,
        $errno,
        $errstr,
        $timeoutMilliseconds / 1000
    );

    if ($socket === false) {
        return false;
    }

    fclose($socket);

    return true;
}

/**
 * Wait until the server is actually listening.
 */
function waitForServer(
    string $host,
    int $port,
    int $timeoutSeconds = 10
): bool {
    $deadline = microtime(true) + $timeoutSeconds;

    while (microtime(true) < $deadline) {
        if (serverIsReady($host, $port)) {
            return true;
        }

        usleep(100000);
    }

    return false;
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
 * Perform a real HTTP request using curl.
 */
function httpRequest(
    string $method,
    string $url,
    ?array $jsonBody = null,
    ?string $cookieJar = null,
    array $headers = []
): array {
    $cmd = [
        'curl',
        '--silent',
        '--show-error',
        '--include',
        '--max-time',
        '15',
        '--connect-timeout',
        '5',
        '--request',
        $method,
    ];

    foreach ($headers as $header) {
        $cmd[] = '--header';
        $cmd[] = $header;
    }

    if ($jsonBody !== null) {
        $encoded = json_encode(
            $jsonBody,
            JSON_UNESCAPED_SLASHES
        );

        if ($encoded === false) {
            throw new RuntimeException(
                'Could not JSON encode request body.'
            );
        }

        $cmd[] = '--header';
        $cmd[] = 'Content-Type: application/json';

        $cmd[] = '--data';
        $cmd[] = $encoded;
    }

    if ($cookieJar !== null) {
        $cmd[] = '--cookie-jar';
        $cmd[] = $cookieJar;

        $cmd[] = '--cookie';
        $cmd[] = $cookieJar;
    }

    $cmd[] = $url;

    $escaped = implode(
        ' ',
        array_map('escapeshellarg', $cmd)
    );

    $raw = shell_exec($escaped);

    $raw = $raw ?? '';

    /*
     * curl may include multiple header blocks, for example
     * when redirects happen. We want the final HTTP header.
     */
    $headerPart = '';
    $bodyPart = $raw;

    $separatorPositions = [];

    $offset = 0;

    while (($position = strpos($raw, "\r\n\r\n", $offset)) !== false) {
        $separatorPositions[] = $position;
        $offset = $position + 4;
    }

    if ($separatorPositions !== []) {
        $lastSeparator = end($separatorPositions);

        $headerPart = substr(
            $raw,
            0,
            $lastSeparator
        );

        $bodyPart = substr(
            $raw,
            $lastSeparator + 4
        );
    }

    $status = 0;

    if (
        preg_match(
            '/HTTP\/\d(?:\.\d)?\s+(\d{3})/s',
            $headerPart,
            $matches
        )
    ) {
        $status = (int) $matches[1];
    }

    $decoded = json_decode(
        $bodyPart,
        true
    );

    return [
        'status' => $status,
        'body' => is_array($decoded) ? $decoded : [],
        'raw' => $bodyPart,
        'header' => $headerPart,
    ];
}

/**
 * Upload a file using multipart/form-data.
 */
function uploadFile(
    string $url,
    string $filePath,
    ?string $cookieJar,
    array $headers
): array {
    $cmd = [
        'curl',
        '--silent',
        '--show-error',
        '--include',
        '--max-time',
        '20',
        '--connect-timeout',
        '5',
        '--request',
        'POST',
    ];

    foreach ($headers as $header) {
        $cmd[] = '--header';
        $cmd[] = $header;
    }

    if ($cookieJar !== null) {
        $cmd[] = '--cookie-jar';
        $cmd[] = $cookieJar;

        $cmd[] = '--cookie';
        $cmd[] = $cookieJar;
    }

    $cmd[] = '--form';
    $cmd[] = 'file=@' . $filePath;

    $cmd[] = $url;

    $escaped = implode(
        ' ',
        array_map('escapeshellarg', $cmd)
    );

    $raw = shell_exec($escaped);

    $raw = $raw ?? '';

    $parts = explode(
        "\r\n\r\n",
        $raw,
        2
    );

    $headerPart = $parts[0] ?? '';
    $bodyPart = $parts[1] ?? '';

    $status = 0;

    if (
        preg_match(
            '/HTTP\/\d(?:\.\d)?\s+(\d{3})/',
            $headerPart,
            $matches
        )
    ) {
        $status = (int) $matches[1];
    }

    $decoded = json_decode(
        $bodyPart,
        true
    );

    return [
        'status' => $status,
        'body' => is_array($decoded) ? $decoded : [],
        'raw' => $bodyPart,
        'header' => $headerPart,
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
echo "Target test database: {$testDatabase}\n";
echo "PHP binary: {$phpBinary}\n";
echo "Temporary directory: {$tempDir}\n";
echo str_repeat('=', 55) . "\n\n";

/*
|--------------------------------------------------------------------------
| Verify curl
|--------------------------------------------------------------------------
*/

exec(
    'curl --version',
    $curlOutput,
    $curlCode
);

if ($curlCode !== 0) {
    fwrite(
        STDERR,
        "ERROR: curl is not available on PATH.\n"
    );

    exit(1);
}

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
| Find a free HTTP port
|--------------------------------------------------------------------------
*/

try {
    if ($requestedPort > 0) {
        if (serverIsReady($host, $requestedPort)) {
            echo "Requested port {$requestedPort} is already in use.\n";
            echo "Finding another free port automatically...\n";

            $port = findFreePort($host);
        } else {
            $port = $requestedPort;
        }
    } else {
        $port = findFreePort($host);
    }
} catch (\Throwable $e) {
    fwrite(
        STDERR,
        "Could not select HTTP port: "
        . $e->getMessage()
        . "\n"
    );

    exit(1);
}

$baseUrl = "http://{$host}:{$port}";

echo "Selected free HTTP port: {$port}\n";

/*
|--------------------------------------------------------------------------
| Clean old logs
|--------------------------------------------------------------------------
*/

removeFile($serverLog);
removeFile($serverErrorLog);

/*
|--------------------------------------------------------------------------
| Start isolated PHP development server
|--------------------------------------------------------------------------
*/

echo "Starting test HTTP server on {$baseUrl}\n";

$serverEnv = [
    'DB_HOST' => Env::get(
        'DB_HOST',
        '127.0.0.1'
    ),

    'DB_PORT' => Env::get(
        'DB_PORT',
        '3306'
    ),

    'DB_DATABASE' => $testDatabase,

    'DB_USERNAME' => Env::get(
        'DB_USERNAME',
        'nca_ctf_app'
    ),

    'DB_PASSWORD' => Env::get(
        'DB_PASSWORD',
        ''
    ),

    'DB_CHARSET' => Env::get(
        'DB_CHARSET',
        'utf8mb4'
    ),

    'APP_SECRET' => Env::get(
        'APP_SECRET',
        'test-secret-for-phase4-validation'
    ),

    'APP_ENV' => 'local',

    'AUTH_RATE_LIMIT_MAX_ATTEMPTS' => '1000',

    'AUTH_RATE_LIMIT_WINDOW_SECONDS' => '60',

    'TEAM_INVITATION_TTL_HOURS' => '72',

    'CHALLENGE_FILE_MAX_SIZE_MB' => '50',

    'TEMP' => $tempBase,
    'TMP' => $tempBase,

    'PATH' => getenv('PATH')
        ?: 'C:\\Windows\\System32;C:\\Windows',
];

/*
 * IMPORTANT:
 *
 * The last argument is public/index.php.
 *
 * This makes PHP's built-in server use index.php as the router
 * instead of trying to locate /api/v1/... as a physical file.
 */
$serverCommand = [
    $phpBinary,
    '-S',
    "{$host}:{$port}",
    '-t',
    $root . '/public',
    $root . '/public/index.php',
];

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['file', $serverLog, 'ab'],
    2 => ['file', $serverErrorLog, 'ab'],
];

$serverProcess = proc_open(
    $serverCommand,
    $descriptors,
    $serverPipes,
    $root,
    $serverEnv
);

if (!is_resource($serverProcess)) {
    fwrite(
        STDERR,
        "Failed to start test HTTP server.\n"
    );

    exit(1);
}

/*
|--------------------------------------------------------------------------
| Wait for server startup
|--------------------------------------------------------------------------
*/

if (!waitForServer($host, $port, 10)) {
    echo "\nERROR: Test HTTP server did not start.\n";

    echo "\nServer stdout/stderr log:\n";

    if (is_file($serverLog)) {
        echo file_get_contents($serverLog) . "\n";
    }

    if (is_file($serverErrorLog)) {
        echo file_get_contents($serverErrorLog) . "\n";
    }

    proc_terminate($serverProcess);
    proc_close($serverProcess);

    exit(1);
}

echo "Test HTTP server is ready.\n\n";

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
    |--------------------------------------------------------------------------
    | Stop our own server
    |--------------------------------------------------------------------------
    */

    if (is_resource($serverProcess)) {

        @proc_terminate(
            $serverProcess
        );

        /*
         * Give the process a moment to terminate.
         */
        usleep(250000);

        @proc_close(
            $serverProcess
        );
    }

    /*
     * Close pipes.
     */
    foreach ($serverPipes as $pipe) {
        if (is_resource($pipe)) {
            @fclose($pipe);
        }
    }

    /*
     * Clean temporary test file.
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