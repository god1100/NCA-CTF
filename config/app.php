<?php

declare(strict_types=1);

use App\Infrastructure\Env;

/**
 * Phase 0 application configuration.
 *
 * Only foundation-level settings are defined here. Database, session,
 * Docker, and rate-limiting configuration are reserved for the phases
 * that actually implement those systems (Phase 1, 2, 8, and beyond),
 * per docs/ctf9.txt §31.
 */
return [
    'name' => Env::get('APP_NAME', 'NCA Batch 4 CTF'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => Env::get('APP_URL', 'http://localhost:8000'),
    'phase' => 0,
    'phase_label' => 'Foundation',
];
