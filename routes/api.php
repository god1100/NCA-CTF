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

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\TeamController;
use App\Controllers\TeamInvitationController;
use App\Infrastructure\Router;

return static function (Router $router): void {
    $router->get('/api/v1/health', static function (): void {
        (new HealthController())->index();
    });

    // --- Authentication (Phase 2) -------------------------------------
    $router->post('/api/v1/auth/register', static function (): void {
        (new AuthController())->register();
    });

    $router->post('/api/v1/auth/login', static function (): void {
        (new AuthController())->login();
    });

    $router->post('/api/v1/auth/logout', static function (): void {
        (new AuthController())->logout();
    });

    $router->get('/api/v1/auth/me', static function (): void {
        (new AuthController())->me();
    });

    // --- Teams (Phase 3) ------------------------------------------------
    $router->post('/api/v1/teams', static function (): void {
        (new TeamController())->create();
    });

    $router->get('/api/v1/teams/me', static function (): void {
        (new TeamController())->me();
    });

    $router->get('/api/v1/teams/me/members', static function (): void {
        (new TeamController())->members();
    });

    $router->delete('/api/v1/teams/me/members/{user_id}', static function (array $params): void {
        (new TeamController())->removeMember($params);
    });

    $router->post('/api/v1/teams/me/leave', static function (): void {
        (new TeamController())->leave();
    });

    $router->post('/api/v1/teams/me/transfer-captain', static function (): void {
        (new TeamController())->transferCaptain();
    });

    $router->get('/api/v1/teams/{id}', static function (array $params): void {
        (new TeamController())->show($params);
    });

    // --- Team invitations (Phase 3) --------------------------------------
    $router->post('/api/v1/teams/me/invitations', static function (): void {
        (new TeamInvitationController())->create();
    });

    $router->get('/api/v1/teams/me/invitations', static function (): void {
        (new TeamInvitationController())->list();
    });

    $router->post('/api/v1/team-invitations/{token}/accept', static function (array $params): void {
        (new TeamInvitationController())->accept($params);
    });

    $router->post('/api/v1/team-invitations/{token}/reject', static function (array $params): void {
        (new TeamInvitationController())->reject($params);
    });
};
