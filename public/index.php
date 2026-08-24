<?php

declare(strict_types=1);

/**
 * NCA Batch 4 Private CTF — Front Controller
 */

$baseDir = dirname(__DIR__);

/*
|--------------------------------------------------------------------------
| Determine base URL
|--------------------------------------------------------------------------
*/

$protocol = (
    isset($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] === 'on'
) ? 'https://' : 'http://';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

$basePath = rtrim(
    dirname($scriptName),
    '/\\'
);

$baseUrl = $protocol . $host . $basePath;

/*
 * Make base URL available to views.
 */
$GLOBALS['baseUrl'] = $baseUrl;


/*
|--------------------------------------------------------------------------
| Autoloading
|--------------------------------------------------------------------------
*/

if (is_file($baseDir . '/vendor/autoload.php')) {

    require $baseDir . '/vendor/autoload.php';

} else {

    require $baseDir . '/app/Infrastructure/Autoloader.php';

    \App\Infrastructure\Autoloader::register(
        $baseDir . '/app'
    );
}


use App\Infrastructure\Env;
use App\Infrastructure\Router;
use App\Infrastructure\Session;


/*
|--------------------------------------------------------------------------
| Environment
|--------------------------------------------------------------------------
*/

Env::load($baseDir . '/.env');

$config = require $baseDir . '/config/app.php';


/*
|--------------------------------------------------------------------------
| Security Headers
|--------------------------------------------------------------------------
*/

header('X-Content-Type-Options: nosniff');

header(
    'Referrer-Policy: strict-origin-when-cross-origin'
);

header('X-Frame-Options: DENY');

header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
    "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
    "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
    "img-src 'self' data:;"
);


/*
|--------------------------------------------------------------------------
| Error Handling
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);

ini_set(
    'display_errors',
    $config['debug'] ? '1' : '0'
);

ini_set('log_errors', '1');

ini_set(
    'error_log',
    $baseDir . '/storage/logs/app.log'
);


/*
|--------------------------------------------------------------------------
| Request Information
|--------------------------------------------------------------------------
*/

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

/*
 * Remove query string.
 */
$requestPath = parse_url(
    $requestUri,
    PHP_URL_PATH
) ?? '/';


/*
|--------------------------------------------------------------------------
| Determine public directory
|--------------------------------------------------------------------------
*/

$publicPath = str_replace(
    '\\',
    '/',
    dirname(
        $_SERVER['SCRIPT_NAME'] ?? '/index.php'
    )
);

$publicPath = rtrim(
    $publicPath,
    '/'
);


/*
|--------------------------------------------------------------------------
| Remove public path
|--------------------------------------------------------------------------
*/

if (
    $publicPath !== '' &&
    str_starts_with($requestPath, $publicPath)
) {

    $requestPath = substr(
        $requestPath,
        strlen($publicPath)
    );
}


/*
|--------------------------------------------------------------------------
| Handle direct /index.php/... requests
|--------------------------------------------------------------------------
*/

$requestPath = preg_replace(
    '#^/index\.php#',
    '',
    $requestPath
);


/*
|--------------------------------------------------------------------------
| Normalize URI
|--------------------------------------------------------------------------
*/

$uri = '/' . ltrim(
    $requestPath ?: '/',
    '/'
);

$uri = rtrim(
    $uri,
    '/'
) ?: '/';


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

if (str_starts_with($uri, '/api/')) {

    Session::start();

    $router = new Router();

    $registerRoutes = require $baseDir . '/routes/api.php';

    $registerRoutes($router);

    $router->dispatch(
        $method,
        $uri
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
*/

switch ($uri) {

    case '/challenges':

        require $baseDir .
            '/resources/views/challenges.php';

        exit;


    case '/login':

        require $baseDir .
            '/public/login.php';

        exit;


    case '/register':

        require $baseDir .
            '/public/register.php';

        exit;


    case '/about':

        require $baseDir .
            '/public/about.php';

        exit;


    case '/dashboard':

        require $baseDir .
            '/public/dashboard.php';

        exit;


    default:

        require $baseDir .
            '/resources/views/landing.php';

        exit;
}