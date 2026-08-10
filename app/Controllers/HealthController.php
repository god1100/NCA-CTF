<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Infrastructure\JsonResponse;

/**
 * Phase 0 health check.
 *
 * Deliberately does NOT touch the database — Phase 0 has no schema yet
 * (docs/ctf9.txt §31, Phase 1 owns the database). This endpoint only
 * proves that routing, PHP, and the response envelope work end-to-end.
 */
final class HealthController
{
    public function index(): void
    {
        JsonResponse::success([
            'status' => 'ok',
            'phase' => 0,
            'phase_label' => 'Foundation',
            'timestamp' => gmdate('c'),
        ], 'NCA Batch 4 CTF API is running.');
    }
}
