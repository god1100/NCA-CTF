<?php

declare(strict_types=1);

/**
 * NCA Batch 4 Private CTF — Front Controller
 */

$baseDir = dirname(__DIR__);

// --- Determine base URL dynamically ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(dirname($scriptName), '/\\');
$baseUrl = $protocol . $host . $basePath;

// Make baseUrl available globally for views AND for the rest of the app
$GLOBALS['baseUrl'] = $baseUrl;
define('BASE_URL', $baseUrl);  // Also define it as a constant

// --- Autoloading -----------------------------------------------------
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
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'");

// --- Strict error handling ----------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', $baseDir . '/storage/logs/app.log');

// --- Request dispatch -----------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// --- Strip base path from URI ---
$basePathClean = rtrim($basePath, '/');
if ($basePathClean !== '' && strpos($uri, $basePathClean) === 0) {
    $uri = substr($uri, strlen($basePathClean));
}
$uri = '/' . ltrim($uri, '/');
$uri = rtrim($uri, '/') ?: '/';

// --- API routes ---
if (str_starts_with($uri, '/api/')) {
    Session::start();
    $router = new Router();
    $registerRoutes = require $baseDir . '/routes/api.php';
    $registerRoutes($router);
    $router->dispatch($method, $uri);
    exit;
}

// --- Static pages ---
switch ($uri) {
    case '/challenges':
        require $baseDir . '/resources/views/challenges.php';
        exit;
    case '/login':
        // Redirect to the actual login.php file
        header('Location: ' . $baseUrl . '/login.php');
        exit;
    case '/register':
        header('Location: ' . $baseUrl . '/register.php');
        exit;
    case '/about':
        header('Location: ' . $baseUrl . '/about.php');
        exit;
    case '/dashboard':
        Session::start();
        $userId = Session::get('auth_user_id');
        if (!$userId) {
            header('Location: ' . $baseUrl . '/login.php');
            exit;
        }
        require $baseDir . '/resources/views/dashboard.php';
        exit;
    default:
        require $baseDir . '/resources/views/landing.php';
        exit;
}