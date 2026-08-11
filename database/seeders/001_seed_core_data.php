<?php

declare(strict_types=1);

/**
 * Core Phase 1 seed data: global roles, challenge categories, system
 * settings defaults, and an OPTIONAL development-only admin account.
 *
 * Idempotent: safe to run multiple times (INSERT ... ON DUPLICATE KEY
 * UPDATE / IGNORE throughout).
 *
 * No real credentials, flags, or production secrets are ever seeded here
 * (docs/ctf9.txt Phase 1 §33).
 *
 * Usage:
 *   php database/seed.php
 */

$root = dirname(__DIR__, 2);
require $root . '/app/Infrastructure/Autoloader.php';
\App\Infrastructure\Autoloader::register($root . '/app');

use App\Infrastructure\Database;
use App\Infrastructure\Env;

Env::load($root . '/.env');

/** @var \PDO $pdo */
$pdo = Database::connectTo(Env::get('DB_DATABASE', 'nca_ctf'));

// --- Roles ---------------------------------------------------------------
// Exactly the three global roles from docs/ctf9.txt §5. team_captain is
// intentionally NOT seeded here -- it is a team_members.is_captain flag,
// not a global role.
$roles = [
    ['participant', 'Standard competitor account.'],
    ['challenge_admin', 'Can create and manage challenges.'],
    ['super_admin', 'Full competition and disciplinary authority.'],
];

$stmt = $pdo->prepare(
    'INSERT INTO roles (name, description) VALUES (:name, :description)
     ON DUPLICATE KEY UPDATE description = VALUES(description)'
);
foreach ($roles as [$name, $description]) {
    $stmt->execute(['name' => $name, 'description' => $description]);
}
echo '  [OK] Seeded roles: ' . implode(', ', array_column($roles, 0)) . "\n";

// --- Categories ------------------------------------------------------------
$categories = [
    ['Web', 'web', 'Web application security challenges.', 1],
    ['Pwn', 'pwn', 'Binary exploitation challenges.', 2],
    ['Crypto', 'crypto', 'Cryptography challenges.', 3],
    ['General', 'general', 'Forensics, OSINT, and miscellaneous challenges.', 4],
];

$stmt = $pdo->prepare(
    'INSERT INTO categories (name, slug, description, sort_order) VALUES (:name, :slug, :description, :sort_order)
     ON DUPLICATE KEY UPDATE description = VALUES(description), sort_order = VALUES(sort_order)'
);
foreach ($categories as [$name, $slug, $description, $sortOrder]) {
    $stmt->execute(['name' => $name, 'slug' => $slug, 'description' => $description, 'sort_order' => $sortOrder]);
}
echo '  [OK] Seeded categories: ' . implode(', ', array_column($categories, 0)) . "\n";

// --- System settings defaults ---------------------------------------------
// Values only -- never hardcoded into application logic (docs/ctf9.txt §12).
$settings = [
    ['team_min_size', '1', 'integer', 'Minimum team size.'],
    ['team_max_size', '4', 'integer', 'Maximum team size.'],
    ['competition_status', 'UPCOMING', 'string', 'One of UPCOMING, REGISTRATION_OPEN, LIVE, PAUSED, FINISHED, ARCHIVED.'],
    ['hint_system_enabled', 'true', 'boolean', 'Whether challenge hints are available.'],
];

$stmt = $pdo->prepare(
    'INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
     VALUES (:setting_key, :setting_value, :setting_type, :description)
     ON DUPLICATE KEY UPDATE description = VALUES(description)'
);
foreach ($settings as [$key, $value, $type, $description]) {
    $stmt->execute([
        'setting_key' => $key,
        'setting_value' => $value,
        'setting_type' => $type,
        'description' => $description,
    ]);
}
echo '  [OK] Seeded system_settings: ' . implode(', ', array_column($settings, 0)) . "\n";

// --- Optional development-only admin account --------------------------------
// Only created if ALL THREE env vars are explicitly set. No hardcoded
// password ever exists in this file (docs/ctf9.txt Phase 1 §33).
$devUsername = Env::get('DEV_SEED_ADMIN_USERNAME');
$devEmail = Env::get('DEV_SEED_ADMIN_EMAIL');
$devPassword = Env::get('DEV_SEED_ADMIN_PASSWORD');

if ($devUsername && $devEmail && $devPassword) {
    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name');
    $roleStmt->execute(['name' => 'super_admin']);
    $roleId = $roleStmt->fetchColumn();

    if ($roleId === false) {
        fwrite(STDERR, "  [WARN] super_admin role not found -- skipping dev admin seed.\n");
    } else {
        $passwordHash = password_hash($devPassword, PASSWORD_ARGON2ID) ?: password_hash($devPassword, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role_id, status)
             VALUES (:username, :email, :password_hash, :role_id, :status)
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
        );
        $stmt->execute([
            'username' => $devUsername,
            'email' => $devEmail,
            'password_hash' => $passwordHash,
            'role_id' => $roleId,
            'status' => 'active',
        ]);
        echo "  [OK] Seeded development admin account: {$devUsername} (DEV ONLY -- never do this in production)\n";
    }
} else {
    echo "  [SKIP] No dev admin seeded (DEV_SEED_ADMIN_* not fully set in .env).\n";
}

echo "\nSeeding complete.\n";
