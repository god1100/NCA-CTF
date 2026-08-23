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
use App\Controllers\ChallengeController;
use App\Controllers\ChallengeFileController;
use App\Controllers\ChallengeHintController;
use App\Controllers\FlagController;
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

    // --- Challenges (Phase 4) --------------------------------------------
    $router->get('/api/v1/categories', static function (): void {
        (new ChallengeController())->categories();
    });

    $router->get('/api/v1/challenges', static function (): void {
        (new ChallengeController())->index();
    });

    $router->post('/api/v1/challenges', static function (): void {
        (new ChallengeController())->create();
    });

    $router->put('/api/v1/challenges/{id}', static function (array $params): void {
        (new ChallengeController())->update($params);
    });

    $router->delete('/api/v1/challenges/{id}', static function (array $params): void {
        (new ChallengeController())->delete($params);
    });

    $router->post('/api/v1/challenges/{id}/publish', static function (array $params): void {
        (new ChallengeController())->publish($params);
    });

    $router->post('/api/v1/challenges/{id}/pause', static function (array $params): void {
        (new ChallengeController())->pause($params);
    });

    $router->post('/api/v1/challenges/{id}/archive', static function (array $params): void {
        (new ChallengeController())->archive($params);
    });

    // --- Challenge files (Phase 4) ---------------------------------------
    $router->post('/api/v1/challenges/{id}/files', static function (array $params): void {
        (new ChallengeFileController())->upload($params);
    });

    $router->get('/api/v1/challenges/{id}/files', static function (array $params): void {
        (new ChallengeFileController())->listForChallenge($params);
    });

    $router->get('/api/v1/challenge-files/{id}/download', static function (array $params): void {
        (new ChallengeFileController())->download($params);
    });

    $router->delete('/api/v1/challenge-files/{id}', static function (array $params): void {
        (new ChallengeFileController())->delete($params);
    });

    // --- Challenge hints (Phase 4) ----------------------------------------
    $router->post('/api/v1/challenges/{id}/hints', static function (array $params): void {
        (new ChallengeHintController())->create($params);
    });

    $router->get('/api/v1/challenges/{id}/hints', static function (array $params): void {
        (new ChallengeHintController())->listForChallenge($params);
    });

    $router->put('/api/v1/challenge-hints/{id}', static function (array $params): void {
        (new ChallengeHintController())->update($params);
    });

    $router->delete('/api/v1/challenge-hints/{id}', static function (array $params): void {
        (new ChallengeHintController())->remove($params);
    });

    $router->post('/api/v1/challenge-hints/{id}/reveal', static function (array $params): void {
        (new ChallengeHintController())->reveal($params);
    });

    // --- Challenge flags (Phase 4, management only) ------------------------
    $router->post('/api/v1/challenges/{id}/flag', static function (array $params): void {
        (new FlagController())->create($params);
    });

    $router->put('/api/v1/challenges/{id}/flag', static function (array $params): void {
        (new FlagController())->replace($params);
    });

    $router->get('/api/v1/challenges/{id}/flag', static function (array $params): void {
        (new FlagController())->show($params);
    });
    $router->post('/api/v1/auth/change-password', static function (): void {
        (new \App\Controllers\PasswordController())->changePassword();
    });

    // NOTE: GET /api/v1/challenges/{identifier} (single-segment
    // slug-or-id lookup) is registered LAST among GET routes so the more
    // specific multi-segment routes above (e.g. /challenges/{id}/files)
    // are matched first -- see App\Infrastructure\Router::dispatch(),
    // which matches in registration order.
    $router->get('/api/v1/challenges/{identifier}', static function (array $params): void {
        (new ChallengeController())->show($params);
    });
};
