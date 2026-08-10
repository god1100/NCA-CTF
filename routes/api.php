<?php

declare(strict_types=1);

/**
 * API route definitions.
 *
 * Phase 0 scope: a single health-check endpoint only. Business endpoints
 * (auth, teams, challenges, submissions, leaderboard, admin, integrity)
 * are intentionally NOT defined here — see docs/ctf5.txt for the full
 * planned API surface and docs/ctf9.txt §31 for phase sequencing.
 *
 * This file returns a callback that registers routes onto the given
 * App\Infrastructure\Router instance, keeping route wiring separate
 * from the front controller.
 */

use App\Controllers\HealthController;
use App\Infrastructure\Router;

return static function (Router $router): void {
    $router->get('/api/v1/health', static function (): void {
        (new HealthController())->index();
    });
};
