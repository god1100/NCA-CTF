<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Data access for `challenge_hints` (created in Phase 1, unmodified).
 * There is no per-user/team reveal-tracking table in the existing schema
 * -- Phase 4 does not add one (see docs/PHASE4_REPORT.md "Known
 * Limitations"); hint content is simply gated behind authentication,
 * and usage/penalty bookkeeping is explicitly deferred to the scoring
 * phase per the Phase 4 brief.
 */
final class ChallengeHintRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM challenge_hints WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forChallenge(int $challengeId, bool $activeOnly): array
    {
        if ($activeOnly) {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM challenge_hints WHERE challenge_id = :challenge_id AND status = 'active' ORDER BY sort_order ASC, id ASC"
            );
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM challenge_hints WHERE challenge_id = :challenge_id ORDER BY sort_order ASC, id ASC'
            );
        }
        $stmt->execute(['challenge_id' => $challengeId]);

        return $stmt->fetchAll();
    }

    public function create(int $challengeId, ?string $title, string $content, int $pointPenalty, int $sortOrder): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO challenge_hints (challenge_id, title, content, point_penalty, sort_order, status)
             VALUES (:challenge_id, :title, :content, :point_penalty, :sort_order, :status)'
        );
        $stmt->execute([
            'challenge_id' => $challengeId,
            'title' => $title,
            'content' => $content,
            'point_penalty' => $pointPenalty,
            'sort_order' => $sortOrder,
            'status' => 'active',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, ?string $title, string $content, int $pointPenalty, int $sortOrder): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE challenge_hints SET title = :title, content = :content, point_penalty = :point_penalty, sort_order = :sort_order
             WHERE id = :id'
        );
        $stmt->execute([
            'title' => $title,
            'content' => $content,
            'point_penalty' => $pointPenalty,
            'sort_order' => $sortOrder,
            'id' => $id,
        ]);
    }

    /**
     * Soft-delete via status, consistent with the existing enum and the
     * project-wide "prefer status over physical deletion" principle
     * (docs/ctf4.txt §32).
     */
    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE challenge_hints SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Participant-safe, UNREVEALED shape: content is withheld, only
     * metadata needed to decide whether to reveal is returned
     * (docs/ctf3.txt §22, ctf9.txt Phase 4: "Participants should not
     * receive unrevealed hint content").
     */
    public static function toUnrevealedArray(array $hint): array
    {
        return [
            'id' => (int) $hint['id'],
            'title' => $hint['title'],
            'point_penalty' => (int) $hint['point_penalty'],
        ];
    }

    public static function toRevealedArray(array $hint): array
    {
        return [
            'id' => (int) $hint['id'],
            'title' => $hint['title'],
            'content' => $hint['content'],
            'point_penalty' => (int) $hint['point_penalty'],
        ];
    }

    public static function toAdminArray(array $hint): array
    {
        return [
            'id' => (int) $hint['id'],
            'challenge_id' => (int) $hint['challenge_id'],
            'title' => $hint['title'],
            'content' => $hint['content'],
            'point_penalty' => (int) $hint['point_penalty'],
            'sort_order' => (int) $hint['sort_order'],
            'status' => $hint['status'],
        ];
    }
}
