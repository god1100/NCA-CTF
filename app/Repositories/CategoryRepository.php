<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Data access for the `categories` table (created + seeded in Phase 1,
 * unmodified). Category data stays database-driven -- Phase 4 never
 * hardcodes category IDs (docs/ctf9.txt §14, Phase 4 scope).
 */
final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allActive(): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC"
        );

        return $stmt->fetchAll();
    }
}
