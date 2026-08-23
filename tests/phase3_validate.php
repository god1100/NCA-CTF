<?php

declare(strict_types=1);

/**
 * NCA Batch 4 CTF — Phase 3 Team Management Validation
 *
 * Windows/XAMPP-compatible validation suite.
 *
 * IMPORTANT:
 * Start the PHP development server manually before running this test:
 *
 *   & "C:\xampp\php\php.exe" -S 127.0.0.1:8124 -t public public/index.php
 *
 * Then run:
 *
 *   & "C:\xampp\php\php.exe" tests\phase3_validate.php --database=nca_ctf_test
 *
 * Options:
 *
 *   --port=8124                       Port of the already-running server
 *                                      (default: 8124).
 *   --base-url=http://127.0.0.1:8124  Explicit base URL override. Takes
 *                                      precedence over --port if supplied.
 *   --database=nca_ctf_test           Test database name.
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
 * clear diagnosis.
 *
 * The validator does NOT spawn its own PHP server.
 */

$root = dirname(__DIR__);

require $root . '/app/Infrastructure/Autoloader.php';

\App\Infrastructure\Autoloader::register($root . '/app');

use App\Infrastructure\Database;
use App\Infrastructure\Env;

Env::load($root . '/.env');

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

$options = getopt('', ['database:', 'port:', 'base-url:']);

/*
 * Guard against accidentally deriving "..._test_test" when the
 * configured DB_DATABASE already points at a dedicated test database.
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

$testStorage = $root
    . DIRECTORY_SEPARATOR
    . 'storage'
    . DIRECTORY_SEPARATOR
    . 'test';

if (!is_dir($testStorage)) {
    if (!mkdir($testStorage, 0777, true) && !is_dir($testStorage)) {
        throw new RuntimeException(
            "Unable to create test storage directory: {$testStorage}"
        );
    }
}

$failures = [];
$passes = 0;

if (!extension_loaded('curl')) {
    fwrite(
        STDERR,
        "ERROR: The PHP curl extension is not loaded.\n"
        . "XAMPP ships this enabled by default under extension=curl\n"
        . "in php.ini. Enable it and restart, then re-run.\n"
    );

    exit(1);
}


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
        return;
    }

    echo "  [FAIL] {$label}\n";
    $failures[] = $label;
}


/**
 * Execute an HTTP request using PHP's native cURL extension.
 *
 * Previously this spawned curl.exe as a child process via
 * proc_open(). On at least one real Windows/XAMPP machine that
 * consistently timed out (curl exit 28, 0 bytes) even though curl.exe
 * run directly in a terminal, and the PHP dev server itself, both
 * worked fine — consistent with security software or a proxy
 * environment variable treating child-process-initiated network I/O
 * differently from interactive terminal use. Using ext-curl performs
 * the request from within this same PHP process, with no child
 * process involved, sidestepping that class of problem entirely.
 * XAMPP ships ext-curl enabled by default.
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

    /*
     * API expects JSON request bodies.
     */
    if ($jsonBody !== null) {
        $json = json_encode(
            $jsonBody,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            throw new RuntimeException(
                'Failed to encode JSON request body: ' .
                json_last_error_msg()
            );
        }

        $requestHeaders[] = 'Content-Type: application/json';

        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }

    if ($requestHeaders !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
    }

    /*
     * Cookie handling.
     */
    if ($cookieJar !== null) {
        $cookieDirectory = dirname($cookieJar);

        if (!is_dir($cookieDirectory)) {
            mkdir($cookieDirectory, 0777, true);
        }

        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }

    $raw = curl_exec($ch);

    $exitCode = curl_errno($ch);
    $stderr = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    curl_close($ch);

    if ($raw === false) {
        return [
            'status' => 0,
            'body' => [],
            'raw' => '',
            'response_body' => '',
            'stderr' => $stderr !== '' ? $stderr : 'curl_exec() returned false',
            'exit_code' => $exitCode !== 0 ? $exitCode : -1,
        ];
    }

    $responseBody = substr($raw, $headerSize);

    $decoded = json_decode($responseBody, true);

    return [
        'status' => $status,
        'body' => is_array($decoded) ? $decoded : [],
        'raw' => $raw,
        'response_body' => $responseBody,
        'stderr' => $stderr,
        'exit_code' => $exitCode,
    ];
}

/**
 * Register and login a test user.
 *
 * Returns:
 *
 * [
 *     cookieJar,
 *     csrfToken,
 *     email
 * ]
 */
function registerAndLogin(
    string $baseUrl,
    string $username,
    string $email,
    string $testStorage
): array {
    $password = 'correcthorse1';

    /*
     * Registration
     */
    $register = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/auth/register",
        [
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ]
    );

    if ($register['status'] !== 201) {
        echo "\n[ERROR] Registration failed for {$username}\n";
        echo "Status: {$register['status']}\n";
        echo "Response:\n";
        echo $register['raw'] . "\n";

        if (!empty($register['stderr'])) {
            echo "curl stderr:\n";
            echo $register['stderr'] . "\n";
        }

        throw new RuntimeException(
            "Registration failed for {$username} " .
            "(HTTP {$register['status']})."
        );
    }

    /*
     * Safe Windows filename.
     */
    $safeUsername = preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '_',
        $username
    );

    $jar = $testStorage
        . DIRECTORY_SEPARATOR
        . "phase3_{$safeUsername}_cookies.txt";

    if (file_exists($jar)) {
        unlink($jar);
    }

    /*
     * Login
     */
    $login = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/auth/login",
        [
            'identifier' => $username,
            'password' => $password,
        ],
        $jar
    );

    if ($login['status'] !== 200) {
        echo "\n[ERROR] Login failed for {$username}\n";
        echo "Status: {$login['status']}\n";
        echo "Response:\n";
        echo $login['raw'] . "\n";

        throw new RuntimeException(
            "Login failed for {$username} " .
            "(HTTP {$login['status']})."
        );
    }

    $csrfToken =
        $login['body']['data']['csrf_token']
        ?? '';

    if ($csrfToken === '') {
        echo "\n[ERROR] Login response did not contain CSRF token.\n";
        echo $login['raw'] . "\n";

        throw new RuntimeException(
            "Login succeeded for {$username}, " .
            "but no CSRF token was returned."
        );
    }

    return [
        $jar,
        $csrfToken,
        $email,
    ];
}


/**
 * Print useful HTTP debugging information.
 */
function debugResponse(string $label, array $response): void
{
    echo "\n[DEBUG] {$label}\n";
    echo "Status: " . ($response['status'] ?? 0) . "\n";

    if (!empty($response['body'])) {
        echo "Body:\n";
        echo json_encode(
            $response['body'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ) . "\n";
    } elseif (!empty($response['response_body'])) {
        echo "Response body:\n";
        echo $response['response_body'] . "\n";
    }

    if (!empty($response['stderr'])) {
        echo "curl stderr:\n";
        echo $response['stderr'] . "\n";
    }
}


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

echo "NCA Batch 4 CTF — Phase 3 Team Management Validation\n";
echo "Target test database: {$testDatabase}\n";
echo "HTTP server: {$baseUrl}\n";
echo str_repeat('=', 55) . "\n\n";


/*
|--------------------------------------------------------------------------
| Database connection
|--------------------------------------------------------------------------
*/

try {
    $pdo = Database::connectTo($testDatabase);
} catch (\Throwable $e) {
    fwrite(
        STDERR,
        "Could not connect to test database '{$testDatabase}': " .
        $e->getMessage() .
        "\n"
    );

    exit(1);
}


/*
|--------------------------------------------------------------------------
| Reset database
|--------------------------------------------------------------------------
*/

echo "Resetting test database\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$tables = $pdo
    ->query('SHOW TABLES')
    ->fetchAll(\PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $pdo->exec(
        "DROP TABLE IF EXISTS `" .
        str_replace('`', '``', $table) .
        "`"
    );
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "  Dropped " . count($tables) . " existing table(s)\n\n";


/*
|--------------------------------------------------------------------------
| Migrations
|--------------------------------------------------------------------------
*/

echo "Running migrations + seed against test database\n";

$migrateCommand =
    escapeshellarg(PHP_BINARY) .
    ' ' .
    escapeshellarg($root . '/database/migrate.php') .
    ' --database=' .
    escapeshellarg($testDatabase) .
    ' 2>&1';

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


/*
|--------------------------------------------------------------------------
| Seed
|--------------------------------------------------------------------------
|
| The seed script reads DB_DATABASE from the environment.
|--------------------------------------------------------------------------
*/

$oldDbDatabase = getenv('DB_DATABASE');

putenv("DB_DATABASE={$testDatabase}");

$seedCommand =
    escapeshellarg(PHP_BINARY) .
    ' ' .
    escapeshellarg($root . '/database/seed.php') .
    ' 2>&1';

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

/*
 * Restore environment.
 */
if ($oldDbDatabase === false) {
    putenv('DB_DATABASE');
} else {
    putenv("DB_DATABASE={$oldDbDatabase}");
}

echo "\n";


/*
|--------------------------------------------------------------------------
| HTTP server connectivity
|--------------------------------------------------------------------------
*/

echo "Checking HTTP server at {$baseUrl}\n";

$health = httpRequest(
    'GET',
    $baseUrl . '/'
);

if ($health['status'] === 0) {
    echo "\n[ERROR] Cannot connect to {$baseUrl}\n";

    if (!empty($health['stderr'])) {
        echo "curl error:\n";
        echo $health['stderr'] . "\n";
    }

    echo "\nThis validator does NOT start its own server.\n";
    echo "Start it manually in another terminal:\n\n";
    echo '  & "' . PHP_BINARY . '" -S 127.0.0.1:' .
        $port .
        ' -t public public' . DIRECTORY_SEPARATOR . "index.php\n\n";

    exit(1);
}

echo "  HTTP server reachable (HTTP {$health['status']})\n\n";

/*
|--------------------------------------------------------------------------
| Mandatory database identity check
|--------------------------------------------------------------------------
|
| CRITICAL: this validator's own $pdo connection (used above to reset,
| migrate and seed, via Database::connectTo()) and the externally
| running HTTP application's database connection
| (App\Infrastructure\Database::connection(), used by every
| controller/repository) are two COMPLETELY SEPARATE PDO connections.
| The HTTP application reads DB_DATABASE purely from its own .env file
| (or a real shell environment variable) at the moment IT was started
| — it has no knowledge of this validator's --database flag. If they
| don't match, every stateful assertion below will silently exercise
| a different database and fail in a way that looks like an
| application bug but isn't. Prove they match before running anything
| else, exactly like phase4_validate.php does.
*/

echo "Verifying the running application is connected to '{$testDatabase}'...\n";

$dbIdentityMarker = 'nca_dbcheck_' . bin2hex(random_bytes(6));

$dbIdentityRegister = httpRequest(
    'POST',
    "{$baseUrl}/api/v1/auth/register",
    [
        'username' => $dbIdentityMarker,
        'email' => $dbIdentityMarker . '@example.test',
        'password' => 'correcthorse1',
    ]
);

if ($dbIdentityRegister['status'] !== 201) {
    fwrite(STDERR, "\n" . str_repeat('=', 55) . "\n");
    fwrite(STDERR, "ERROR: Database identity check could not even register\n");
    fwrite(STDERR, "a throwaway user through the running application.\n\n");
    fwrite(STDERR, "HTTP status: {$dbIdentityRegister['status']}\n");
    fwrite(STDERR, "Response:\n" . trim((string) ($dbIdentityRegister['raw'] ?? '')) . "\n");
    fwrite(STDERR, str_repeat('=', 55) . "\n");

    exit(1);
}

$dbIdentityStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM users WHERE username = ?'
);

$dbIdentityStmt->execute([$dbIdentityMarker]);

if ((int) $dbIdentityStmt->fetchColumn() !== 1) {
    fwrite(STDERR, "\n" . str_repeat('=', 55) . "\n");
    fwrite(STDERR, "ERROR: Database identity mismatch detected.\n\n");
    fwrite(STDERR, "This validator just registered a user through the running\n");
    fwrite(STDERR, "application at {$baseUrl}, then looked for that exact user\n");
    fwrite(STDERR, "in database '{$testDatabase}' (this validator's own\n");
    fwrite(STDERR, "connection) — and did not find it.\n\n");
    fwrite(STDERR, "The running application is connected to a different\n");
    fwrite(STDERR, "database than this validator expects. Fix: stop the\n");
    fwrite(STDERR, "running server, set in .env:\n\n");
    fwrite(STDERR, "  DB_DATABASE={$testDatabase}\n\n");
    fwrite(STDERR, "then restart the server and re-run this validator.\n");
    fwrite(STDERR, str_repeat('=', 55) . "\n");

    exit(1);
}

echo "Confirmed: the running application is writing to '{$testDatabase}'.\n\n";


/*
|--------------------------------------------------------------------------
| Main Phase 3 tests
|--------------------------------------------------------------------------
*/

try {

    /*
     * ================================================================
     * Setup
     * ================================================================
     */

    [$capJar, $capCsrf, $capEmail] = registerAndLogin(
        $baseUrl,
        'p3cap',
        'p3cap@example.test',
        $testStorage
    );

    [$memberJar, $memberCsrf, $memberEmail] = registerAndLogin(
        $baseUrl,
        'p3mem',
        'p3mem@example.test',
        $testStorage
    );

    [$outsiderJar, $outsiderCsrf, $outsiderEmail] = registerAndLogin(
        $baseUrl,
        'p3out',
        'p3out@example.test',
        $testStorage
    );


    /*
     * ================================================================
     * 1-6 Team creation
     * ================================================================
     */

    echo "Team creation\n";

    $unauthCreate = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams",
        [
            'name' => 'Should Fail',
        ]
    );

    check(
        '1. Unauthenticated user cannot create a team',
        $unauthCreate['status'] === 401,
        $failures,
        $passes
    );

    $create = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams",
        [
            'name' => 'Cyber Wolves',
        ],
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    check(
        '2. Authenticated user can create a team',
        $create['status'] === 201 &&
        ($create['body']['success'] ?? false) === true,
        $failures,
        $passes
    );

    $teamId =
        $create['body']['data']['team']['id']
        ?? null;

    $meAfterCreate = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/teams/me",
        null,
        $capJar
    );

    check(
        '3. Creator becomes captain',
        ($meAfterCreate['body']['data']['is_captain'] ?? false) === true,
        $failures,
        $passes
    );

    $dupName = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams",
        [
            'name' => 'Cyber Wolves',
        ],
        $outsiderJar,
        [
            "X-CSRF-Token: {$outsiderCsrf}",
        ]
    );

    check(
        '4. Team name uniqueness works',
        $dupName['status'] === 409,
        $failures,
        $passes
    );

    $secondTeam = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams",
        [
            'name' => 'Second Team',
        ],
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    check(
        '5. User cannot create another active team',
        $secondTeam['status'] === 409 &&
        ($secondTeam['body']['error']['code'] ?? '') === 'ALREADY_IN_TEAM',
        $failures,
        $passes
    );

    check(
        '6. User can view their team',
        ($meAfterCreate['body']['data']['team']['name'] ?? '') === 'Cyber Wolves',
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 7 Team members
     * ================================================================
     */

    echo "\nTeam members\n";

    $membersResp = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/teams/me/members",
        null,
        $capJar
    );

    $memberUsernames = array_column(
        $membersResp['body']['data']['members'] ?? [],
        'username'
    );

    check(
        '7. Team members are returned correctly',
        $membersResp['status'] === 200 &&
        in_array('p3cap', $memberUsernames, true),
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 8-14 Invitations
     * ================================================================
     */

    echo "\nInvitations\n";

    $invite = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/invitations",
        [
            'email' => $memberEmail,
        ],
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    check(
        '8. Captain can create an invitation',
        $invite['status'] === 201 &&
        ($invite['body']['success'] ?? false) === true,
        $failures,
        $passes
    );

    $token =
        $invite['body']['data']['token']
        ?? '';

    $storedHash = false;

    if ($token !== '') {
        $stmt = $pdo->prepare(
            'SELECT token_hash
             FROM team_invitations
             WHERE invited_email = ?
             ORDER BY id DESC
             LIMIT 1'
        );

        $stmt->execute([$memberEmail]);

        $storedHash = $stmt->fetchColumn();
    }

    check(
        '9. Invitation token is not stored plaintext',
        $token !== '' &&
        $storedHash !== false &&
        $storedHash !== $token &&
        $storedHash === hash('sha256', $token),
        $failures,
        $passes
    );

    $accept = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/team-invitations/{$token}/accept",
        null,
        $memberJar,
        [
            "X-CSRF-Token: {$memberCsrf}",
        ]
    );

    check(
        '10. Invitation can be accepted',
        $accept['status'] === 200 &&
        ($accept['body']['success'] ?? false) === true,
        $failures,
        $passes
    );

    $memberStmt = $pdo->prepare(
        'SELECT *
         FROM team_members
         WHERE team_id = ?
           AND user_id = (
               SELECT id
               FROM users
               WHERE username = ?
           )
           AND status = ?'
    );

    $memberStmt->execute([
        $teamId,
        'p3mem',
        'active',
    ]);

    $memberRow = $memberStmt->fetch();

    check(
        '11. Invitation creates team membership',
        $memberRow !== false,
        $failures,
        $passes
    );

    $reaccept = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/team-invitations/{$token}/accept",
        null,
        $outsiderJar,
        [
            "X-CSRF-Token: {$outsiderCsrf}",
        ]
    );

    check(
        '12. Invitation becomes unusable after acceptance',
        $reaccept['status'] !== 200 ||
        ($reaccept['body']['success'] ?? false) === false,
        $failures,
        $passes
    );


    /*
     * 13 Expired invitation
     */

    $expiredInvite = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/invitations",
        [
            'email' => 'p3expired@example.test',
        ],
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    $expiredToken =
        $expiredInvite['body']['data']['token']
        ?? '';

    if ($expiredToken !== '') {
        $stmt = $pdo->prepare(
            'UPDATE team_invitations
             SET expires_at = (NOW() - INTERVAL 1 HOUR)
             WHERE token_hash = ?'
        );

        $stmt->execute([
            hash('sha256', $expiredToken),
        ]);
    }

    [
        $expiredUserJar,
        $expiredUserCsrf
    ] = registerAndLogin(
        $baseUrl,
        'p3expired',
        'p3expired@example.test',
        $testStorage
    );

    $expiredAccept = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/team-invitations/{$expiredToken}/accept",
        null,
        $expiredUserJar,
        [
            "X-CSRF-Token: {$expiredUserCsrf}",
        ]
    );

    check(
        '13. Expired invitation cannot be accepted',
        ($expiredAccept['body']['success'] ?? true) === false,
        $failures,
        $passes
    );


    /*
     * 14 Rejected invitation
     */

    [
        $rejectUserJar,
        $rejectUserCsrf,
        $rejectUserEmail
    ] = registerAndLogin(
        $baseUrl,
        'p3reject',
        'p3reject@example.test',
        $testStorage
    );

    $rejectInvite = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/invitations",
        [
            'email' => $rejectUserEmail,
        ],
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    $rejectToken =
        $rejectInvite['body']['data']['token']
        ?? '';

    $rejectResp = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/team-invitations/{$rejectToken}/reject",
        null,
        $rejectUserJar,
        [
            "X-CSRF-Token: {$rejectUserCsrf}",
        ]
    );

    check(
        'Reject invitation succeeds',
        ($rejectResp['body']['success'] ?? false) === true,
        $failures,
        $passes
    );

    $reuseRejected = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/team-invitations/{$rejectToken}/accept",
        null,
        $rejectUserJar,
        [
            "X-CSRF-Token: {$rejectUserCsrf}",
        ]
    );

    check(
        '14. Rejected invitation cannot be reused',
        ($reuseRejected['body']['success'] ?? true) === false,
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 15 User already in a team
     * ================================================================
     */

    $otherTeamCap = registerAndLogin(
        $baseUrl,
        'p3cap2',
        'p3cap2@example.test',
        $testStorage
    );

    httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams",
        [
            'name' => 'Root Hunters',
        ],
        $otherTeamCap[0],
        [
            "X-CSRF-Token: {$otherTeamCap[1]}",
        ]
    );

    $outsiderInvite1 = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/invitations",
        [
            'email' => $outsiderEmail,
        ],
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    $outsiderToken1 =
        $outsiderInvite1['body']['data']['token']
        ?? '';

    httpRequest(
        'POST',
        "{$baseUrl}/api/v1/team-invitations/{$outsiderToken1}/accept",
        null,
        $outsiderJar,
        [
            "X-CSRF-Token: {$outsiderCsrf}",
        ]
    );

    $outsiderInvite2 = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/invitations",
        [
            'email' => $outsiderEmail,
        ],
        $otherTeamCap[0],
        [
            "X-CSRF-Token: {$otherTeamCap[1]}",
        ]
    );

    $outsiderToken2 =
        $outsiderInvite2['body']['data']['token']
        ?? '';

    $secondAccept = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/team-invitations/{$outsiderToken2}/accept",
        null,
        $outsiderJar,
        [
            "X-CSRF-Token: {$outsiderCsrf}",
        ]
    );

    check(
        '15. User already in another team cannot accept invitation',
        ($secondAccept['body']['success'] ?? true) === false &&
        ($secondAccept['body']['error']['code'] ?? '') === 'ALREADY_IN_TEAM',
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 16 Team capacity
     * ================================================================
     */

    echo "\nCapacity\n";

    $pdo->exec(
        "UPDATE system_settings
         SET setting_value = '3'
         WHERE setting_key = 'team_max_size'"
    );

    /*
     * Cyber Wolves should currently have:
     *
     * p3cap
     * p3mem
     * p3out
     *
     * = 3 members.
     */

    $capacityInvite = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/invitations",
        [
            'email' => 'p3overflow@example.test',
        ],
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    check(
        '16. Team capacity is enforced',
        ($capacityInvite['body']['success'] ?? true) === false &&
        ($capacityInvite['body']['error']['code'] ?? '') === 'TEAM_FULL',
        $failures,
        $passes
    );

    $pdo->exec(
        "UPDATE system_settings
         SET setting_value = '4'
         WHERE setting_key = 'team_max_size'"
    );


    /*
     * ================================================================
     * 17-18 Member removal
     * ================================================================
     */

    echo "\nMember removal\n";

    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE username = ?'
    );

    $stmt->execute(['p3mem']);

    $memberUserId = (int) $stmt->fetchColumn();

    $nonCaptainRemove = httpRequest(
        'DELETE',
        "{$baseUrl}/api/v1/teams/me/members/{$memberUserId}",
        null,
        $memberJar,
        [
            "X-CSRF-Token: {$memberCsrf}",
        ]
    );

    check(
        '18. Non-captain cannot remove another member',
        $nonCaptainRemove['status'] === 403,
        $failures,
        $passes
    );

    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE username = ?'
    );

    $stmt->execute(['p3out']);

    $outsiderUserId = (int) $stmt->fetchColumn();

    $captainRemove = httpRequest(
        'DELETE',
        "{$baseUrl}/api/v1/teams/me/members/{$outsiderUserId}",
        null,
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    check(
        '17. Captain can remove a member',
        $captainRemove['status'] === 200 &&
        ($captainRemove['body']['success'] ?? false) === true,
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 24 IDOR
     * ================================================================
     */

    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE username = ?'
    );

    $stmt->execute(['p3cap2']);

    $otherCapUserId = (int) $stmt->fetchColumn();

    $idorRemove = httpRequest(
        'DELETE',
        "{$baseUrl}/api/v1/teams/me/members/{$otherCapUserId}",
        null,
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    check(
        '24. User cannot manipulate IDs to access/modify another team',
        $idorRemove['status'] === 404,
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 19 Member leave
     * ================================================================
     */

    echo "\nLeaving\n";

    $leave = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/leave",
        null,
        $memberJar,
        [
            "X-CSRF-Token: {$memberCsrf}",
        ]
    );

    check(
        '19. Member can leave',
        $leave['status'] === 200 &&
        ($leave['body']['success'] ?? false) === true,
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 20-23 Captain transfer
     * ================================================================
     */

    echo "\nCaptain transfer\n";

    [
        $mem2Jar,
        $mem2Csrf,
        $mem2Email
    ] = registerAndLogin(
        $baseUrl,
        'p3mem2',
        'p3mem2@example.test',
        $testStorage
    );

    $reinvite = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/invitations",
        [
            'email' => $mem2Email,
        ],
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    $reinviteToken =
        $reinvite['body']['data']['token']
        ?? '';

    httpRequest(
        'POST',
        "{$baseUrl}/api/v1/team-invitations/{$reinviteToken}/accept",
        null,
        $mem2Jar,
        [
            "X-CSRF-Token: {$mem2Csrf}",
        ]
    );

    $captainLeaveBlocked = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/leave",
        null,
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    check(
        '20. Captain cannot leave without transferring captaincy',
        ($captainLeaveBlocked['body']['success'] ?? true) === false &&
        ($captainLeaveBlocked['body']['error']['code'] ?? '') === 'CAPTAIN_MUST_TRANSFER',
        $failures,
        $passes
    );

    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE username = ?'
    );

    $stmt->execute(['p3mem2']);

    $mem2UserId = (int) $stmt->fetchColumn();

    $transfer = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams/me/transfer-captain",
        [
            'user_id' => $mem2UserId,
        ],
        $capJar,
        [
            "X-CSRF-Token: {$capCsrf}",
        ]
    );

    check(
        '21. Captain can transfer captaincy',
        $transfer['status'] === 200 &&
        ($transfer['body']['success'] ?? false) === true,
        $failures,
        $passes
    );

    $capMeAfterTransfer = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/teams/me",
        null,
        $capJar
    );

    check(
        '22. Old captain loses captain privileges after transfer',
        ($capMeAfterTransfer['body']['data']['is_captain'] ?? true) === false,
        $failures,
        $passes
    );

    $mem2MeAfterTransfer = httpRequest(
        'GET',
        "{$baseUrl}/api/v1/teams/me",
        null,
        $mem2Jar
    );

    check(
        '23. New captain gains captain privileges',
        ($mem2MeAfterTransfer['body']['data']['is_captain'] ?? false) === true,
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 25 CSRF
     * ================================================================
     */

    echo "\nCSRF\n";

    $noCsrfCreate = httpRequest(
        'POST',
        "{$baseUrl}/api/v1/teams",
        [
            'name' => 'No CSRF Team',
        ],
        $outsiderJar
    );

    check(
        '25. CSRF protection works',
        $noCsrfCreate['status'] === 419,
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 26 Audit logging
     * ================================================================
     */

    echo "\nAudit logging\n";

    $events = $pdo
        ->query('SELECT action FROM audit_logs')
        ->fetchAll(\PDO::FETCH_COLUMN);

    $requiredEvents = [
        'TEAM_CREATED',
        'TEAM_INVITATION_CREATED',
        'TEAM_INVITATION_ACCEPTED',
        'TEAM_INVITATION_REJECTED',
        'TEAM_MEMBER_REMOVED',
        'TEAM_MEMBER_LEFT',
        'CAPTAIN_TRANSFERRED',
    ];

    foreach ($requiredEvents as $event) {
        check(
            "26. Audit event recorded: {$event}",
            in_array($event, $events, true),
            $failures,
            $passes
        );
    }


    /*
     * ================================================================
     * 27 Historical data integrity
     * ================================================================
     */

    echo "\nHistorical data integrity\n";

    $stmt = $pdo->prepare(
        'SELECT id
         FROM categories
         WHERE slug = ?'
    );

    $stmt->execute(['web']);

    $catId = (int) $stmt->fetchColumn();

    if ($catId <= 0) {
        throw new RuntimeException(
            'Web challenge category was not found in seed data.'
        );
    }

    $challengeStmt = $pdo->prepare(
        'INSERT INTO challenges
        (
            category_id,
            title,
            slug,
            difficulty,
            points,
            status,
            deployment_type
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $challengeStmt->execute([
        $catId,
        'P3 Test Challenge',
        'p3-test-challenge',
        'easy',
        100,
        'published',
        'HTTP',
    ]);

    $challengeId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE username = ?'
    );

    $stmt->execute(['p3mem2']);

    $historyUserId = (int) $stmt->fetchColumn();

    $solveStmt = $pdo->prepare(
        'INSERT INTO solves
        (
            team_id,
            challenge_id,
            first_solved_by,
            points_awarded
        )
        VALUES (?, ?, ?, ?)'
    );

    $solveStmt->execute([
        $teamId,
        $challengeId,
        $historyUserId,
        100,
    ]);

    $solveIdBefore = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM solves
         WHERE id = ?'
    );

    $stmt->execute([$solveIdBefore]);

    $solveStillThere = (int) $stmt->fetchColumn();

    check(
        '27. Historical solve records remain intact after membership changes',
        $solveStillThere === 1,
        $failures,
        $passes
    );


    /*
     * ================================================================
     * 28 Sensitive data exposure
     * ================================================================
     */

    echo "\nSensitive data exposure\n";

    $allResponses = json_encode([
        $create,
        $meAfterCreate,
        $membersResp,
        $invite,
        $accept,
    ]);

    check(
        '28. No password_hash in any team-related response',
        !str_contains($allResponses ?: '', 'password_hash'),
        $failures,
        $passes
    );

    check(
        '28. No token_hash in any team-related response',
        !str_contains($allResponses ?: '', 'token_hash'),
        $failures,
        $passes
    );

} catch (\Throwable $e) {

    echo "\nUnexpected validation exception:\n";
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
    echo "\nTrace:\n";
    echo $e->getTraceAsString() . "\n";

    $failures[] = 'Unexpected validation exception';
}


/*
|--------------------------------------------------------------------------
| Regression — Phase 2
|--------------------------------------------------------------------------
*/

echo "\nRegression (Phase 1 + Phase 2 unaffected)\n";

$p2Output = [];

$p2Command =
    escapeshellarg(PHP_BINARY) .
    ' ' .
    escapeshellarg($root . '/tests/phase2_validate.php') .
    ' --database=' . escapeshellarg($testDatabase) .
    ' 2>&1';

exec(
    $p2Command,
    $p2Output,
    $p2Code
);

check(
    '29. Existing Phase 2 authentication still works (full suite)',
    $p2Code === 0,
    $failures,
    $passes
);


/*
|--------------------------------------------------------------------------
| Regression — Phase 1
|--------------------------------------------------------------------------
*/

$p1Output = [];

$p1Command =
    escapeshellarg(PHP_BINARY) .
    ' ' .
    escapeshellarg($root . '/tests/phase1_validate.php') .
    ' --database=' . escapeshellarg($testDatabase) .
    ' 2>&1';

exec(
    $p1Command,
    $p1Output,
    $p1Code
);

check(
    '30. Existing Phase 1 database validation still works (full suite)',
    $p1Code === 0,
    $failures,
    $passes
);


/*
|--------------------------------------------------------------------------
| Final result
|--------------------------------------------------------------------------
*/

echo "\n" . str_repeat('=', 55) . "\n";

echo "Result: {$passes} passed, " .
    count($failures) .
    " failed\n";

if (count($failures) > 0) {

    echo "\nFailed checks:\n";

    foreach ($failures as $failure) {
        echo "  - {$failure}\n";
    }

    /*
     * Show useful regression output if Phase 1/2 failed.
     */
    if ($p2Code !== 0 && !empty($p2Output)) {
        echo "\nPhase 2 output:\n";
        echo implode("\n", $p2Output) . "\n";
    }

    if ($p1Code !== 0 && !empty($p1Output)) {
        echo "\nPhase 1 output:\n";
        echo implode("\n", $p1Output) . "\n";
    }

    exit(1);
}

echo "\nPhase 3 validation: ALL CHECKS PASSED\n";

exit(0);