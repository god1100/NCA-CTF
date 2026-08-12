<?php

declare(strict_types=1);

/**
 * NCA Batch 4 Private CTF — Front Controller
 *
 * Phase 0 responsibilities only:
 *   - Bootstrap autoloading and environment configuration
 *   - Route /api/v1/* requests to the minimal API router
 *   - Serve the static landing page for everything else
 *
 * No authentication, database access, or business logic lives here.
 * See docs/ctf9.txt §31 for the full phase roadmap.
 */

$baseDir = dirname(__DIR__);

// --- Autoloading -----------------------------------------------------
// Prefer Composer's autoloader if it has been generated; otherwise fall
// back to the minimal PSR-4 autoloader shipped for Phase 0 (see
// app/Infrastructure/Autoloader.php for rationale).
if (is_file($baseDir . '/vendor/autoload.php')) {
    require $baseDir . '/vendor/autoload.php';
} else {
    require $baseDir . '/app/Infrastructure/Autoloader.php';
    \App\Infrastructure\Autoloader::register($baseDir . '/app');
}

use App\Infrastructure\Env;
use App\Infrastructure\Router;
use App\Infrastructure\Session;

// --- Environment -------------------------------------------------------
Env::load($baseDir . '/.env');
$config = require $baseDir . '/config/app.php';

// --- Baseline security headers -----------------------------------------
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
// Content-Security-Policy is intentionally minimal in Phase 0 since no
// dynamic pages, forms, or third-party assets exist yet. It will be
// tightened/expanded as the frontend grows (docs/ctf5.txt §62).
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'");

// --- Strict error handling ----------------------------------------------
// Debug info (stack traces, paths) must never leak in non-debug mode.
error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', $baseDir . '/storage/logs/app.log');

// --- Request dispatch -----------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (str_starts_with($uri, '/api/')) {
    Session::start();
    $router = new Router();
    $registerRoutes = require $baseDir . '/routes/api.php';
    $registerRoutes($router);
    $router->dispatch($method, $uri);
    exit;
}

// --- Static landing page --------------------------------------------------
// Anything that isn't an /api/ request gets the Phase 0 landing page.
// Login, register, dashboard, and challenge pages are later phases.
require $baseDir . '/resources/views/landing.php';
