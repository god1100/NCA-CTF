<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Small string utilities. Currently just slug generation, needed for
 * Phase 3 team creation (teams.slug is unique and NOT NULL per the
 * Phase 1 schema, but the API only asks the client for a team name).
 */
final class Str
{
    public static function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug === '' ? 'team' : $slug;
    }
}
