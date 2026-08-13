<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Data access for the `teams` table (created in Phase 1, unmodified).
 * Prepared statements only (docs/ctf9.txt §30).
 */
final class TeamRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM teams WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM teams WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function nameExists(string $name): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM teams WHERE name = :name');
        $stmt->execute(['name' => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function slugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM teams WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Team status is always 'active' at creation -- never client-supplied
     * (docs/ctf9.txt §29 requirement list).
     */
    public function create(string $name, string $slug): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO teams (name, slug, status) VALUES (:name, :slug, :status)'
        );
        $stmt->execute(['name' => $name, 'slug' => $slug, 'status' => 'active']);

        return (int) $this->pdo->lastInsertId();
    }

    public static function toPublicArray(array $team): array
    {
        return [
            'id' => (int) $team['id'],
            'name' => $team['name'],
            'slug' => $team['slug'],
            'status' => $team['status'],
            'created_at' => $team['created_at'],
        ];
    }
}
