<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__, 2);

/*
 * Bootstrap autoloading.
 */
if (is_file($baseDir . '/vendor/autoload.php')) {
    require $baseDir . '/vendor/autoload.php';
} else {
    require $baseDir . '/app/Infrastructure/Autoloader.php';
    \App\Infrastructure\Autoloader::register($baseDir . '/app');
}

use App\Infrastructure\Env;
use App\Infrastructure\Session;
use App\Infrastructure\Database;
use App\Repositories\UserRepository;

/*
 * Load environment.
 */
Env::load($baseDir . '/.env');

/*
 * Start the application's native session.
 */
Session::start();

/*
 * Make sure a user is authenticated.
 */
$userId = Session::get('auth_user_id');

if (!is_int($userId)) {
    header('Location: /NCA-CTF/public/login.php');
    exit;
}

/*
 * Fetch the current user from the database.
 */
$pdo = Database::connection();
$userRepository = new UserRepository($pdo);

$user = $userRepository->findById($userId);

/*
 * User no longer exists or is inactive.
 */
if ($user === null || $user['status'] !== 'active') {
    Session::destroy();

    header('Location: /NCA-CTF/public/login.php');
    exit;
}

/*
 * Admin roles:
 *
 * 2 = challenge_admin
 * 3 = super_admin
 */
$adminRoles = [2, 3];

if (!in_array((int) $user['role_id'], $adminRoles, true)) {
    header('Location: /NCA-CTF/public/dashboard.php');
    exit;
}

/*
 * If execution reaches here:
 *
 * - User is authenticated
 * - User exists
 * - User is active
 * - User has an administrator role
 *
 * Continue loading the admin page.
 */