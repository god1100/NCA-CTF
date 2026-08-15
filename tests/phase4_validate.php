<?php

declare(strict_types=1);

/**
 * Phase 4 challenge system validation.
 *
 * Same approach as tests/phase2_validate.php and phase3_validate.php:
 * boots a real PHP dev server against a dedicated test database and
 * drives it over real HTTP with curl. Ends by running
 * tests/phase3_validate.php as a subprocess, which itself chains
 * phase1/phase2, so a single Phase 4 run proves the whole stack still
 * works (#32-33).
 *
 * Requires: reachable MySQL/MariaDB, the `curl` binary on PATH.
 *
 * Run: php tests/phase4_validate.php
 */

$root = dirname(__DIR__);
require $root . '/app/Infrastructure/Autoloader.php';
\App\Infrastructure\Autoloader::register($root . '/app');

use App\Infrastructure\Database;
use App\Infrastructure\Env;
use App\Infrastructure\FileStorage;

Env::load($root . '/.env');

$options = getopt('', ['database:']);
$testDatabase = $options['database'] ?? (Env::get('DB_DATABASE', 'nca_ctf') . '_test');
$port = 8125;
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

    return ['status' => $status, 'body' => is_array($decoded) ? $decoded : [], 'raw' => $bodyPart, 'header' => $headerPart];
}

function uploadFile(string $url, string $filePath, ?string $cookieJar, array $headers): array
{
    $cmd = ['curl', '-s', '-i', '-X', 'POST'];
    foreach ($headers as $h) {
        $cmd[] = '-H';
        $cmd[] = $h;
    }
    if ($cookieJar !== null) {
        $cmd[] = '-c';
        $cmd[] = $cookieJar;
        $cmd[] = '-b';
        $cmd[] = $cookieJar;
    }
    $cmd[] = '-F';
    $cmd[] = 'file=@' . $filePath;
    $cmd[] = $url;

    $escaped = implode(' ', array_map('escapeshellarg', $cmd));
    $raw = shell_exec($escaped);

    [$headerPart, $bodyPart] = array_pad(explode("\r\n\r\n", $raw ?? '', 2), 2, '');
    preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $headerPart, $m);
    $status = isset($m[1]) ? (int) $m[1] : 0;
    $decoded = json_decode($bodyPart, true);

    return ['status' => $status, 'body' => is_array($decoded) ? $decoded : []];
}

function registerAndLogin(string $baseUrl, string $username, string $email): array
{
    httpRequest('POST', "$baseUrl/api/v1/auth/register", [
        'username' => $username,
        'email' => $email,
        'password' => 'correcthorse1',
    ]);
    $jar = "/tmp/phase4_{$username}_cookies.txt";
    @unlink($jar);
    $login = httpRequest('POST', "$baseUrl/api/v1/auth/login", ['identifier' => $username, 'password' => 'correcthorse1'], $jar);

    return [$jar, $login['body']['data']['csrf_token'] ?? ''];
}

echo "NCA Batch 4 CTF — Phase 4 Challenge System Validation\n";
echo "Target test database: {$testDatabase}\n";
echo str_repeat('=', 55) . "\n\n";

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
putenv('DB_DATABASE'); // clear override before regression subprocess calls later
echo "\n";

// Clean any leftover test upload dir from a prior run.
$uploadTestDir = FileStorage::baseDir($root);
if (is_dir($uploadTestDir)) {
    exec('rm -rf ' . escapeshellarg($uploadTestDir));
}
mkdir($uploadTestDir, 0750, true);

echo "Starting test HTTP server on {$baseUrl}\n";
$envForServer = [
    'DB_HOST' => Env::get('DB_HOST', '127.0.0.1'),
    'DB_PORT' => Env::get('DB_PORT', '3306'),
    'DB_DATABASE' => $testDatabase,
    'DB_USERNAME' => Env::get('DB_USERNAME', 'nca_ctf_app'),
    'DB_PASSWORD' => Env::get('DB_PASSWORD', ''),
    'DB_CHARSET' => Env::get('DB_CHARSET', 'utf8mb4'),
    'APP_SECRET' => Env::get('APP_SECRET', 'test-secret-for-phase4-validation'),
    'APP_ENV' => 'local',
    'AUTH_RATE_LIMIT_MAX_ATTEMPTS' => '1000',
    'AUTH_RATE_LIMIT_WINDOW_SECONDS' => '60',
    'TEAM_INVITATION_TTL_HOURS' => '72',
    'CHALLENGE_FILE_MAX_SIZE_MB' => '50',
    'PATH' => getenv('PATH') ?: '/usr/bin:/bin',
];

$descriptors = [1 => ['file', '/tmp/phase4_server.log', 'w'], 2 => ['file', '/tmp/phase4_server.log', 'w']];
$process = proc_open(['php', '-S', "127.0.0.1:{$port}", '-t', $root . '/public'], $descriptors, $pipes, $root, $envForServer);

if (!is_resource($process)) {
    fwrite(STDERR, "Failed to start test server.\n");
    exit(1);
}
usleep(700000);

try {
    // === Setup: participant, challenge_admin, super_admin ================
    [$partJar, $partCsrf] = registerAndLogin($baseUrl, 'p4part', 'p4part@example.test');
    [$adminJar, $adminCsrf] = registerAndLogin($baseUrl, 'p4admin', 'p4admin@example.test');
    [$superJar, $superCsrf] = registerAndLogin($baseUrl, 'p4super', 'p4super@example.test');

    $challengeAdminRoleId = (int) $pdo->query("SELECT id FROM roles WHERE name = 'challenge_admin'")->fetchColumn();
    $superAdminRoleId = (int) $pdo->query("SELECT id FROM roles WHERE name = 'super_admin'")->fetchColumn();
    $pdo->exec("UPDATE users SET role_id = {$challengeAdminRoleId} WHERE username = 'p4admin'");
    $pdo->exec("UPDATE users SET role_id = {$superAdminRoleId} WHERE username = 'p4super'");
    // Re-login so the session reflects the promoted role.
    [$adminJar, $adminCsrf] = registerAndLogin($baseUrl, 'p4admin', 'p4admin@example.test');
    [$superJar, $superCsrf] = registerAndLogin($baseUrl, 'p4super', 'p4super@example.test');

    // === 6-7: participant cannot create, admin can ==========================
    echo "Challenge CRUD\n";
    $partCreateAttempt = httpRequest('POST', "$baseUrl/api/v1/challenges", [
        'title' => 'Should Fail', 'category' => 'web', 'difficulty' => 'easy', 'points' => 100, 'deployment_type' => 'HTTP',
    ], $partJar, ["X-CSRF-Token: $partCsrf"]);
    check('6. Participant cannot create a challenge', $partCreateAttempt['status'] === 403, $failures, $passes);

    $create = httpRequest('POST', "$baseUrl/api/v1/challenges", [
        'title' => 'SQL Injection 101', 'description' => 'Find the flag', 'category' => 'web', 'difficulty' => 'easy', 'points' => 100, 'deployment_type' => 'HTTP',
    ], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('7. Challenge admin can create a challenge', $create['status'] === 201 && $create['body']['success'] === true, $failures, $passes);
    check('New challenge starts as draft', ($create['body']['data']['challenge']['status'] ?? '') === 'draft', $failures, $passes);
    $challengeId = $create['body']['data']['challenge']['id'] ?? 0;
    $challengeSlug = $create['body']['data']['challenge']['slug'] ?? '';

    // === 5: participant cannot modify ========================================
    $partUpdateAttempt = httpRequest('PUT', "$baseUrl/api/v1/challenges/{$challengeId}", ['title' => 'Hacked', 'category' => 'web', 'difficulty' => 'easy', 'points' => 1, 'deployment_type' => 'HTTP'], $partJar, ["X-CSRF-Token: $partCsrf"]);
    check('5. Participant cannot modify a challenge', $partUpdateAttempt['status'] === 403, $failures, $passes);

    // === 8: admin can edit ====================================================
    $edit = httpRequest('PUT', "$baseUrl/api/v1/challenges/{$challengeId}", [
        'title' => 'SQL Injection 101', 'description' => 'Updated description', 'category' => 'web', 'difficulty' => 'medium', 'points' => 150, 'deployment_type' => 'HTTP',
    ], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('8. Challenge admin can edit a challenge', $edit['status'] === 200 && ($edit['body']['data']['challenge']['difficulty'] ?? '') === 'medium', $failures, $passes);

    // === 13-15: validation ====================================================
    echo "\nValidation\n";
    $badCategory = httpRequest('POST', "$baseUrl/api/v1/challenges", ['title' => 'Bad Cat', 'category' => 'not-a-real-category', 'difficulty' => 'easy', 'points' => 10, 'deployment_type' => 'HTTP'], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('13. Invalid category rejected', $badCategory['status'] === 422, $failures, $passes);

    $badDifficulty = httpRequest('POST', "$baseUrl/api/v1/challenges", ['title' => 'Bad Diff', 'category' => 'web', 'difficulty' => 'impossible', 'points' => 10, 'deployment_type' => 'HTTP'], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('14. Invalid difficulty rejected', $badDifficulty['status'] === 422, $failures, $passes);

    $badPoints = httpRequest('POST', "$baseUrl/api/v1/challenges", ['title' => 'Bad Points', 'category' => 'web', 'difficulty' => 'easy', 'points' => -5, 'deployment_type' => 'HTTP'], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('15. Invalid points rejected', $badPoints['status'] === 422, $failures, $passes);

    // === 16: slug collision prevention ========================================
    $dupTitle1 = httpRequest('POST', "$baseUrl/api/v1/challenges", ['title' => 'Duplicate Title Challenge', 'category' => 'web', 'difficulty' => 'easy', 'points' => 10, 'deployment_type' => 'HTTP'], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    $dupTitle2 = httpRequest('POST', "$baseUrl/api/v1/challenges", ['title' => 'Duplicate Title Challenge', 'category' => 'web', 'difficulty' => 'easy', 'points' => 10, 'deployment_type' => 'HTTP'], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    $slug1 = $dupTitle1['body']['data']['challenge']['slug'] ?? 'a';
    $slug2 = $dupTitle2['body']['data']['challenge']['slug'] ?? 'b';
    check('16. Duplicate slug prevented (auto-deduplicated, both succeed with distinct slugs)', $dupTitle1['status'] === 201 && $dupTitle2['status'] === 201 && $slug1 !== $slug2, $failures, $passes);

    // === 9-11: lifecycle ======================================================
    echo "\nLifecycle\n";
    $publish = httpRequest('POST', "$baseUrl/api/v1/challenges/{$challengeId}/publish", null, $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('9. Challenge admin can publish a challenge', $publish['status'] === 200 && ($publish['body']['data']['challenge']['status'] ?? '') === 'published', $failures, $passes);

    $pause = httpRequest('POST', "$baseUrl/api/v1/challenges/{$challengeId}/pause", null, $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('10. Challenge admin can pause a challenge', $pause['status'] === 200 && ($pause['body']['data']['challenge']['status'] ?? '') === 'paused', $failures, $passes);

    // re-publish so participant-visibility tests have a live challenge
    httpRequest('POST', "$baseUrl/api/v1/challenges/{$challengeId}/publish", null, $adminJar, ["X-CSRF-Token: $adminCsrf"]);

    $draftForArchive = httpRequest('POST', "$baseUrl/api/v1/challenges", ['title' => 'To Be Archived', 'category' => 'pwn', 'difficulty' => 'hard', 'points' => 300, 'deployment_type' => 'TCP'], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    $archiveId = $draftForArchive['body']['data']['challenge']['id'] ?? 0;
    $archive = httpRequest('POST', "$baseUrl/api/v1/challenges/{$archiveId}/archive", null, $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('11. Challenge admin can archive a challenge', $archive['status'] === 200 && ($archive['body']['data']['challenge']['status'] ?? '') === 'archived', $failures, $passes);

    // === 12: super admin can manage ===========================================
    $superCreate = httpRequest('POST', "$baseUrl/api/v1/challenges", ['title' => 'Super Admin Challenge', 'category' => 'crypto', 'difficulty' => 'hard', 'points' => 250, 'deployment_type' => 'DOWNLOAD'], $superJar, ["X-CSRF-Token: $superCsrf"]);
    check('12. Super admin can manage challenges', $superCreate['status'] === 201, $failures, $passes);

    // === draft/testing visibility (#1-3) ======================================
    echo "\nParticipant visibility\n";
    $draftChallenge = httpRequest('POST', "$baseUrl/api/v1/challenges", ['title' => 'Still A Draft', 'category' => 'general', 'difficulty' => 'easy', 'points' => 50, 'deployment_type' => 'DOWNLOAD'], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    $draftId = $draftChallenge['body']['data']['challenge']['id'] ?? 0;
    $draftSlug = $draftChallenge['body']['data']['challenge']['slug'] ?? '';

    $participantList = httpRequest('GET', "$baseUrl/api/v1/challenges?per_page=100", null, $partJar);
    $listedIds = array_column($participantList['body']['data']['challenges'] ?? [], 'id');
    check('1. Participant can list published challenges', in_array($challengeId, $listedIds, true), $failures, $passes);
    check('2. Participant cannot see draft challenges in listing', !in_array($draftId, $listedIds, true), $failures, $passes);

    $directDraftAccess = httpRequest('GET', "$baseUrl/api/v1/challenges/{$draftSlug}", null, $partJar);
    check('2b. Participant cannot view draft challenge directly (404, not leaked)', $directDraftAccess['status'] === 404, $failures, $passes);

    // Move a challenge to 'testing' to check that state too.
    $pdo->exec("UPDATE challenges SET status = 'testing' WHERE id = {$draftId}");
    $testingAccess = httpRequest('GET', "$baseUrl/api/v1/challenges/{$draftSlug}", null, $partJar);
    check('3. Participant cannot see testing challenges', $testingAccess['status'] === 404, $failures, $passes);

    $detail = httpRequest('GET', "$baseUrl/api/v1/challenges/{$challengeSlug}", null, $partJar);
    check('4. Participant can view a published challenge', $detail['status'] === 200 && ($detail['body']['data']['challenge']['title'] ?? '') === 'SQL Injection 101', $failures, $passes);

    // === 17: filtering =========================================================
    echo "\nFiltering & pagination\n";
    $filtered = httpRequest('GET', "$baseUrl/api/v1/challenges?category=pwn", null, $partJar);
    $filteredCategories = array_unique(array_column($filtered['body']['data']['challenges'] ?? [], 'category'));
    check('17. Challenge filtering by category works', $filtered['status'] === 200 && (count($filteredCategories) === 0 || $filteredCategories === ['Pwn']), $failures, $passes);

    $diffFiltered = httpRequest('GET', "$baseUrl/api/v1/challenges?difficulty=medium", null, $partJar);
    $diffValues = array_unique(array_column($diffFiltered['body']['data']['challenges'] ?? [], 'difficulty'));
    check('17b. Challenge filtering by difficulty works', $diffFiltered['status'] === 200 && (count($diffValues) === 0 || $diffValues === ['medium']), $failures, $passes);

    // === 18: pagination ========================================================
    $page1 = httpRequest('GET', "$baseUrl/api/v1/challenges?per_page=1&page=1", null, $partJar);
    $page2 = httpRequest('GET', "$baseUrl/api/v1/challenges?per_page=1&page=2", null, $partJar);
    $id1 = $page1['body']['data']['challenges'][0]['id'] ?? null;
    $id2 = $page2['body']['data']['challenges'][0]['id'] ?? null;
    check('18. Pagination works (distinct items per page)', $page1['status'] === 200 && $id1 !== null && $id1 !== $id2, $failures, $passes);
    check('18b. Pagination metadata present', isset($page1['body']['data']['pagination']['total']), $failures, $passes);

    // === 19-22: files ==========================================================
    echo "\nChallenge files\n";
    $testFilePath = '/tmp/phase4_testfile.txt';
    file_put_contents($testFilePath, "hello ctf phase4\n");

    $uploadResult = uploadFile("$baseUrl/api/v1/challenges/{$challengeId}/files", $testFilePath, $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('19. Challenge files can be registered securely', $uploadResult['status'] === 201 && $uploadResult['body']['success'] === true, $failures, $passes);
    check('19b. storage_path never returned in API response', !isset($uploadResult['body']['data']['file']['storage_path']), $failures, $passes);
    $fileId = $uploadResult['body']['data']['file']['id'] ?? 0;

    // 20: path traversal defense, tested directly against FileStorage
    $traversalAttempt = FileStorage::resolvedPath($root, '../../../../etc/passwd');
    check('20. Files cannot escape the storage directory (FileStorage guard)', $traversalAttempt === null, $failures, $passes);

    $download = httpRequest('GET', "$baseUrl/api/v1/challenge-files/{$fileId}/download", null, $partJar);
    check('21. Participant can access authorized challenge files', $download['status'] === 200 && str_contains($download['raw'], 'hello ctf phase4'), $failures, $passes);

    // File on a non-visible (testing) challenge must not be downloadable by a participant.
    $uploadOnHidden = uploadFile("$baseUrl/api/v1/challenges/{$draftId}/files", $testFilePath, $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    $hiddenFileId = $uploadOnHidden['body']['data']['file']['id'] ?? 0;
    $hiddenDownload = httpRequest('GET', "$baseUrl/api/v1/challenge-files/{$hiddenFileId}/download", null, $partJar);
    check('22. Participant cannot access files on a non-visible challenge', $hiddenDownload['status'] === 404, $failures, $passes);

    // === 23-25: hints ==========================================================
    echo "\nHints\n";
    $hintCreate = httpRequest('POST', "$baseUrl/api/v1/challenges/{$challengeId}/hints", ['title' => 'Hint 1', 'content' => 'Try a single quote', 'point_penalty' => 10], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('23. Challenge hints can be created by authorized admins', $hintCreate['status'] === 201, $failures, $passes);
    $hintId = $hintCreate['body']['data']['hint']['id'] ?? 0;

    $participantDetail = httpRequest('GET', "$baseUrl/api/v1/challenges/{$challengeSlug}", null, $partJar);
    $hintsInDetail = $participantDetail['body']['data']['challenge']['hints'] ?? [];
    $hintContentLeaked = false;
    foreach ($hintsInDetail as $h) {
        if (array_key_exists('content', $h)) {
            $hintContentLeaked = true;
        }
    }
    check('24. Participant cannot see unrevealed hint content', !$hintContentLeaked, $failures, $passes);

    $reveal = httpRequest('POST', "$baseUrl/api/v1/challenge-hints/{$hintId}/reveal", null, $partJar, ["X-CSRF-Token: $partCsrf"]);
    check('25. Hint reveal works', $reveal['status'] === 200 && ($reveal['body']['data']['hint']['content'] ?? '') === 'Try a single quote', $failures, $passes);

    // === 26-29: flags ===========================================================
    echo "\nFlags\n";
    $flagCreate = httpRequest('POST', "$baseUrl/api/v1/challenges/{$challengeId}/flag", ['flag' => 'NCA{phase4_test_flag}'], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('26. Flags can be created by authorized admins', $flagCreate['status'] === 201 && $flagCreate['body']['success'] === true, $failures, $passes);

    $allParticipantResponses = json_encode([$participantList, $detail, $participantDetail, $download['raw'] ?? '']);
    check('27. Plaintext flag never returned by participant APIs', !str_contains($allParticipantResponses, 'phase4_test_flag'), $failures, $passes);

    $allResponsesIncludingAdmin = json_encode([$flagCreate, $participantList, $detail]);
    check('28. Flag hash never returned to anyone (admin included)', !str_contains($allResponsesIncludingAdmin, 'flag_hash'), $failures, $passes);

    $partFlagAttempt = httpRequest('POST', "$baseUrl/api/v1/challenges/{$challengeId}/flag", ['flag' => 'NCA{should_not_work}'], $partJar, ["X-CSRF-Token: $partCsrf"]);
    check('29. Participants cannot create/modify flags', $partFlagAttempt['status'] === 403, $failures, $passes);

    $flagReplace = httpRequest('PUT', "$baseUrl/api/v1/challenges/{$challengeId}/flag", ['flag' => 'NCA{phase4_test_flag_v2}'], $adminJar, ["X-CSRF-Token: $adminCsrf"]);
    check('Flag replace (versioning) works', $flagReplace['status'] === 200, $failures, $passes);
    $flagCountForChallenge = (int) $pdo->query("SELECT COUNT(*) FROM flags WHERE challenge_id = {$challengeId}")->fetchColumn();
    check('Flag history preserved on replace (old version kept, inactive)', $flagCountForChallenge === 2, $failures, $passes);

    // === 30: IDOR ================================================================
    echo "\nIDOR / authorization\n";
    $unauthList = httpRequest('GET', "$baseUrl/api/v1/challenges");
    check('30. Unauthenticated request rejected (no session)', $unauthList['status'] === 401, $failures, $passes);

    $unauthCreate = httpRequest('POST', "$baseUrl/api/v1/challenges", ['title' => 'x', 'category' => 'web', 'difficulty' => 'easy', 'points' => 1, 'deployment_type' => 'HTTP']);
    check('30b. Unauthenticated create rejected', $unauthCreate['status'] === 401, $failures, $passes);

    $partDeleteAttempt = httpRequest('DELETE', "$baseUrl/api/v1/challenges/{$draftId}", null, $partJar, ["X-CSRF-Token: $partCsrf"]);
    check('30c. Participant cannot delete a challenge (IDOR/authz)', $partDeleteAttempt['status'] === 403, $failures, $passes);

    // CSRF check on a state-changing admin action
    $noCsrfPublish = httpRequest('POST', "$baseUrl/api/v1/challenges/{$archiveId}/publish", null, $adminJar);
    check('CSRF protection enforced on challenge lifecycle actions', $noCsrfPublish['status'] === 419, $failures, $passes);

    // === 31: audit logging =======================================================
    echo "\nAudit logging\n";
    $events = $pdo->query("SELECT action FROM audit_logs")->fetchAll(\PDO::FETCH_COLUMN);
    $requiredEvents = ['CHALLENGE_CREATED', 'CHALLENGE_UPDATED', 'CHALLENGE_PUBLISHED', 'CHALLENGE_PAUSED', 'CHALLENGE_ARCHIVED', 'CHALLENGE_FILE_ADDED', 'CHALLENGE_HINT_CREATED', 'CHALLENGE_FLAG_CREATED', 'CHALLENGE_FLAG_UPDATED'];
    foreach ($requiredEvents as $evt) {
        check("31. Audit event recorded: $evt", in_array($evt, $events, true), $failures, $passes);
    }

    @unlink($testFilePath);
} finally {
    proc_terminate($process);
    proc_close($process);
}

// === 32-33: regression (chains phase1+phase2 via phase3) ========================
echo "\nRegression (Phase 1, 2, 3 unaffected)\n";
$p3Out = [];
exec('php ' . escapeshellarg($root . '/tests/phase3_validate.php') . ' 2>&1', $p3Out, $p3Code);
check('32-33. Existing auth + team functionality still works (Phase 3 suite, which chains Phase 1+2)', $p3Code === 0, $failures, $passes);

echo "\n" . str_repeat('=', 55) . "\n";
echo "Result: $passes passed, " . count($failures) . " failed\n";

if (count($failures) > 0) {
    echo "\nFailed checks:\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
    echo "\nServer log (last 40 lines):\n";
    $log = @file_get_contents('/tmp/phase4_server.log');
    if ($log) {
        $lines = explode("\n", trim($log));
        echo implode("\n", array_slice($lines, -40)) . "\n";
    }
    exit(1);
}

echo "\nPhase 4 validation: ALL CHECKS PASSED\n";
exit(0);
