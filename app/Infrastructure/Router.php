<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Minimal request router.
 *
 * Phase 0 scope: exact-path GET/POST/PUT/PATCH/DELETE matching only.
 * This intentionally does not implement route parameters, groups, or
 * middleware stacks — those are added as later phases need them
 * (e.g. Phase 2 auth middleware, Phase 4 `/challenges/{slug}`).
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            $this->notFound();
            return;
        }

        $handler();
    }

    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'The requested resource could not be found.',
            ],
        ], JSON_PRETTY_PRINT);
    }
}
