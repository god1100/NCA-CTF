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

// Make baseUrl available globally for views
$GLOBALS['baseUrl'] = $baseUrl;

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
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// --- Strip base path from URI ---
$basePathClean = rtrim($basePath, '/');

// Remove base path first
if ($basePathClean !== '' && strpos($requestUri, $basePathClean) === 0) {
    $requestUri = substr($requestUri, strlen($basePathClean));
}

// Remove query string if present
if (strpos($requestUri, '?') !== false) {
    $requestUri = substr($requestUri, 0, strpos($requestUri, '?'));
}

// Remove /index.php if present
$requestUri = preg_replace('#^/index\.php#', '', $requestUri);

// Parse the URI to get just the path
$uri = parse_url($requestUri, PHP_URL_PATH) ?? '/';
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

// DASHBOARD ROUTE - Check BEFORE the switch
// DEBUG
echo "<!-- DEBUG: URI = " . $uri . " -->";
if ($uri === '/dashboard') {
    Session::start();
    $userId = Session::get('auth_user_id');
    if (!$userId) {
        header('Location: ' . $baseUrl . '/login.php');
        exit;
    }
    require $baseDir . '/resources/views/dashboard.php';
    exit;
}

// Switch for other routes
switch ($uri) {
    case '/challenges':
        require $baseDir . '/resources/views/challenges.php';
        exit;
    case '/login':
        require $baseDir . '/public/login.php';
        exit;
    case '/register':
        require $baseDir . '/public/register.php';
        exit;
    case '/about':
        require $baseDir . '/public/about.php';
        exit;
    default:
        require $baseDir . '/resources/views/landing.php';
        exit;
}