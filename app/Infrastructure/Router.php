<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Request router.
 *
 * Phase 0 introduced exact-path GET/POST matching only. Phase 3 extends
 * this — exactly as anticipated in the original class comment — to
 * support DELETE/PUT/PATCH and `{param}` path segments (e.g.
 * `/teams/me/members/{user_id}`), needed for team member and invitation
 * endpoints. Exact-path routes registered by earlier phases (e.g.
 * `/api/v1/auth/login`) continue to work unchanged: a literal path is
 * just a pattern with zero parameters.
 *
 * Still intentionally minimal: no route groups, no global middleware
 * stack. Middleware composition continues to happen inside each route's
 * closure, as established in Phase 2 (AuthMiddleware::handle(...)).
 */
final class Router
{
    /**
     * @var array<string, list<array{pattern: string, paramNames: list<string>, handler: callable}>>
     */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        [$pattern, $paramNames] = self::compile($path);
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'paramNames' => $paramNames,
            'handler' => $handler,
        ];
    }

    /**
     * Converts a path like `/api/v1/teams/me/members/{user_id}` into a
     * regex and the ordered list of parameter names it captures.
     *
     * @return array{0: string, 1: list<string>}
     */
    private static function compile(string $path): array
    {
        $paramNames = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $m) use (&$paramNames): string {
                $paramNames[] = $m[1];
                // Path segments only -- no slashes inside a captured param.
                return '([^/]+)';
            },
            $path
        );

        return ['#^' . $regex . '$#', $paramNames];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) === 1) {
                $params = [];
                foreach ($route['paramNames'] as $i => $name) {
                    $params[$name] = urldecode($matches[$i + 1]);
                }

                ($route['handler'])($params);
                return;
            }
        }

        $this->notFound();
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
