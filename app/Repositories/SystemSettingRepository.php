<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Read access to the `system_settings` table (created + seeded in Phase
 * 1). Phase 3 uses this for team_min_size/team_max_size instead of
 * hardcoding them (docs/ctf9.txt §12, Phase 3 requirement).
 */
final class SystemSettingRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getInt(string $key, int $default): int
    {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        if ($value === false || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }
}
