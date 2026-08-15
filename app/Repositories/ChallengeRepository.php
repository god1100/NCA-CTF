<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Data access for the `challenges` table (created in Phase 1,
 * unmodified). Only fields that actually exist in the Phase 1 migration
 * are used here -- no `challenge_type`, `is_featured`, or `archived_at`
 * columns exist, so this class never references them even though
 * earlier planning docs (ctf.txt/ctf2.txt) mentioned them
 * (docs/ctf9.txt Phase 4 instruction: "inspect the actual schema first").
 */
final class ChallengeRepository
{
    public const PARTICIPANT_VISIBLE_STATUSES = ['published', 'running'];
    public const VALID_STATUSES = ['draft', 'testing', 'published', 'running', 'paused', 'archived'];
    public const VALID_DIFFICULTIES = ['easy', 'medium', 'hard', 'insane'];
    public const VALID_DEPLOYMENT_TYPES = ['DOWNLOAD', 'HTTP', 'TCP'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM challenges WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM challenges WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function slugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM challenges WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param array{category_id?: int, difficulty?: string, status?: string} $filters
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function paginate(array $filters, bool $includeAllStatuses, int $page, int $perPage): array
    {
        $where = [];
        $params = [];

        if ($includeAllStatuses) {
            if (isset($filters['status'])) {
                $where[] = 'status = :status';
                $params['status'] = $filters['status'];
            }
        } else {
            $placeholders = [];
            foreach (self::PARTICIPANT_VISIBLE_STATUSES as $i => $status) {
                $key = "vstatus{$i}";
                $placeholders[] = ":{$key}";
                $params[$key] = $status;
            }
            $where[] = 'status IN (' . implode(',', $placeholders) . ')';
        }

        if (isset($filters['category_id'])) {
            $where[] = 'category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }

        if (isset($filters['difficulty'])) {
            $where[] = 'difficulty = :difficulty';
            $params['difficulty'] = $filters['difficulty'];
        }

        $whereSql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM challenges {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare(
            "SELECT * FROM challenges {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO challenges
                (category_id, title, slug, description, difficulty, points, status, deployment_type, author_id)
             VALUES
                (:category_id, :title, :slug, :description, :difficulty, :points, :status, :deployment_type, :author_id)'
        );
        $stmt->execute([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'difficulty' => $data['difficulty'],
            'points' => $data['points'],
            'status' => 'draft', // always starts as draft server-side
            'deployment_type' => $data['deployment_type'],
            'author_id' => $data['author_id'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Content-only update. Status is never modified here -- lifecycle
     * transitions go through setStatus() with their own valid-transition
     * checks (docs/ctf9.txt Phase 4: "Enforce valid state transitions
     * server-side").
     */
    public function updateContent(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE challenges SET
                category_id = :category_id,
                title = :title,
                description = :description,
                difficulty = :difficulty,
                points = :points,
                deployment_type = :deployment_type
             WHERE id = :id'
        );
        $stmt->execute([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'difficulty' => $data['difficulty'],
            'points' => $data['points'],
            'deployment_type' => $data['deployment_type'],
            'id' => $id,
        ]);
    }

    public function setStatus(int $id, string $status, bool $setPublishedAt = false): void
    {
        if ($setPublishedAt) {
            $stmt = $this->pdo->prepare('UPDATE challenges SET status = :status, published_at = NOW() WHERE id = :id');
        } else {
            $stmt = $this->pdo->prepare('UPDATE challenges SET status = :status WHERE id = :id');
        }
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Hard delete. Only ever called for challenges that have never been
     * published (see ChallengeService) -- once a challenge has gone live
     * it is archived, never deleted, per the "preserve historical data"
     * principle carried from Phase 1.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM challenges WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Participant-safe shape: no flag data (that table is entirely
     * separate and never joined here), no internal deployment secrets.
     */
    public static function toParticipantArray(array $challenge, ?string $categoryName, bool $solved = false): array
    {
        return [
            'id' => (int) $challenge['id'],
            'title' => $challenge['title'],
            'slug' => $challenge['slug'],
            'description' => $challenge['description'],
            'category' => $categoryName,
            'difficulty' => $challenge['difficulty'],
            'points' => (int) $challenge['points'],
            'status' => $challenge['status'],
            'deployment_type' => $challenge['deployment_type'],
            'solved' => $solved,
        ];
    }

    /**
     * Admin shape: adds status/lifecycle/author bookkeeping fields, but
     * still never includes anything from the flags table.
     */
    public static function toAdminArray(array $challenge, ?string $categoryName): array
    {
        return [
            'id' => (int) $challenge['id'],
            'title' => $challenge['title'],
            'slug' => $challenge['slug'],
            'description' => $challenge['description'],
            'category' => $categoryName,
            'category_id' => (int) $challenge['category_id'],
            'difficulty' => $challenge['difficulty'],
            'points' => (int) $challenge['points'],
            'status' => $challenge['status'],
            'deployment_type' => $challenge['deployment_type'],
            'author_id' => $challenge['author_id'] !== null ? (int) $challenge['author_id'] : null,
            'created_at' => $challenge['created_at'],
            'updated_at' => $challenge['updated_at'],
            'published_at' => $challenge['published_at'],
        ];
    }
}
