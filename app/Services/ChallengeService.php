<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\Hash;
use App\Infrastructure\Str;
use App\Repositories\CategoryRepository;
use App\Repositories\ChallengeRepository;

/**
 * Core challenge management logic. Controllers stay thin; every
 * validation and authorization decision lives here. Status is NEVER
 * settable through the generic update path -- only through the explicit
 * publish/pause/archive/delete actions, each with its own valid-
 * transition check (docs/ctf9.txt Phase 4: "Enforce valid state
 * transitions server-side").
 */
final class ChallengeService
{
    public function __construct(
        private readonly ChallengeRepository $challenges,
        private readonly CategoryRepository $categories,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * @return array{success: bool, errors?: string[], challenge?: array}
     */
    public function create(array $actingUser, array $input, string $ip): array
    {
        $validated = $this->validateInput($input);
        if ($validated['errors'] !== []) {
            return ['success' => false, 'errors' => $validated['errors']];
        }

        $slug = $this->uniqueSlugFor($validated['title']);

        $id = $this->challenges->create([
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'],
            'difficulty' => $validated['difficulty'],
            'points' => $validated['points'],
            'deployment_type' => $validated['deployment_type'],
            'author_id' => (int) $actingUser['id'],
        ]);

        $this->audit->log(
            AuditLogger::CHALLENGE_CREATED,
            (int) $actingUser['id'],
            ['challenge_id' => $id, 'title' => $validated['title']],
            Hash::correlate($ip),
            'challenge',
            $id
        );

        return ['success' => true, 'challenge' => $this->challenges->findById($id)];
    }

    /**
     * @return array{success: bool, errors?: string[], error_code?: string, challenge?: array}
     */
    public function update(array $actingUser, int $id, array $input, string $ip): array
    {
        $challenge = $this->challenges->findById($id);
        if ($challenge === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        $validated = $this->validateInput($input);
        if ($validated['errors'] !== []) {
            return ['success' => false, 'errors' => $validated['errors']];
        }

        $this->challenges->updateContent($id, [
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'difficulty' => $validated['difficulty'],
            'points' => $validated['points'],
            'deployment_type' => $validated['deployment_type'],
        ]);

        $this->audit->log(
            AuditLogger::CHALLENGE_UPDATED,
            (int) $actingUser['id'],
            ['challenge_id' => $id],
            Hash::correlate($ip),
            'challenge',
            $id
        );

        return ['success' => true, 'challenge' => $this->challenges->findById($id)];
    }

    /**
     * @return array{success: bool, error_code?: string, errors?: string[], challenge?: array}
     */
    public function transition(array $actingUser, int $id, string $action, string $ip): array
    {
        $challenge = $this->challenges->findById($id);
        if ($challenge === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        $current = $challenge['status'];

        $transitions = [
            'publish' => [
                'from' => ['draft', 'testing', 'paused'],
                'to' => 'published',
                'event' => AuditLogger::CHALLENGE_PUBLISHED,
            ],
            'pause' => [
                'from' => ['published', 'running'],
                'to' => 'paused',
                'event' => AuditLogger::CHALLENGE_PAUSED,
            ],
            'archive' => [
                'from' => ['draft', 'testing', 'published', 'running', 'paused'],
                'to' => 'archived',
                'event' => AuditLogger::CHALLENGE_ARCHIVED,
            ],
        ];

        if (!isset($transitions[$action])) {
            return ['success' => false, 'error_code' => 'INVALID_ACTION', 'errors' => ['Unknown lifecycle action.']];
        }

        $rule = $transitions[$action];
        if (!in_array($current, $rule['from'], true)) {
            return [
                'success' => false,
                'error_code' => 'INVALID_TRANSITION',
                'errors' => ["Cannot {$action} a challenge in status '{$current}'."],
            ];
        }

        $this->challenges->setStatus($id, $rule['to'], $action === 'publish' && $challenge['published_at'] === null);

        $this->audit->log(
            $rule['event'],
            (int) $actingUser['id'],
            ['challenge_id' => $id, 'from' => $current, 'to' => $rule['to']],
            Hash::correlate($ip),
            'challenge',
            $id
        );

        return ['success' => true, 'challenge' => $this->challenges->findById($id)];
    }

    /**
     * Hard delete -- only permitted while a challenge has never left
     * draft/testing (i.e. never published). Once live, use archive
     * instead; this preserves historical data per the project-wide
     * "preserve historical records" principle (docs/ctf4.txt §30).
     *
     * @return array{success: bool, error_code?: string, errors?: string[]}
     */
    public function delete(array $actingUser, int $id, string $ip): array
    {
        $challenge = $this->challenges->findById($id);
        if ($challenge === null) {
            return ['success' => false, 'error_code' => 'NOT_FOUND'];
        }

        if (!in_array($challenge['status'], ['draft', 'testing'], true)) {
            return [
                'success' => false,
                'error_code' => 'INVALID_TRANSITION',
                'errors' => ['Only draft or testing challenges can be deleted. Archive published challenges instead.'],
            ];
        }

        $this->challenges->delete($id);

        $this->audit->log(
            AuditLogger::CHALLENGE_DELETED,
            (int) $actingUser['id'],
            ['challenge_id' => $id, 'title' => $challenge['title']],
            Hash::correlate($ip),
            'challenge',
            $id
        );

        return ['success' => true];
    }

    public function categoryName(int $categoryId): ?string
    {
        $category = $this->categories->findById($categoryId);

        return $category['name'] ?? null;
    }

    /**
     * @return array{title: string, description: ?string, category_id: int, difficulty: string, points: int, deployment_type: string, errors: string[]}
     */
    private function validateInput(array $input): array
    {
        $errors = [];

        $title = is_string($input['title'] ?? null) ? trim($input['title']) : '';
        if ($title === '' || strlen($title) > 150) {
            $errors[] = 'Title must be between 1 and 150 characters.';
        }

        $description = isset($input['description']) && is_string($input['description']) ? trim($input['description']) : null;

        $categoryId = 0;
        $categoryInput = $input['category_id'] ?? $input['category'] ?? null;
        if (is_int($categoryInput) || (is_string($categoryInput) && ctype_digit($categoryInput))) {
            $category = $this->categories->findById((int) $categoryInput);
        } elseif (is_string($categoryInput)) {
            $category = $this->categories->findBySlug($categoryInput);
        } else {
            $category = null;
        }
        if ($category === null || $category['status'] !== 'active') {
            $errors[] = 'A valid category is required.';
        } else {
            $categoryId = (int) $category['id'];
        }

        $difficulty = is_string($input['difficulty'] ?? null) ? strtolower($input['difficulty']) : '';
        if (!in_array($difficulty, ChallengeRepository::VALID_DIFFICULTIES, true)) {
            $errors[] = 'Difficulty must be one of: ' . implode(', ', ChallengeRepository::VALID_DIFFICULTIES) . '.';
        }

        $pointsRaw = $input['points'] ?? null;
        $points = is_int($pointsRaw) ? $pointsRaw : (is_string($pointsRaw) && ctype_digit($pointsRaw) ? (int) $pointsRaw : -1);
        if ($points < 0 || $points > 100000) {
            $errors[] = 'Points must be a whole number between 0 and 100000.';
        }

        $deploymentType = is_string($input['deployment_type'] ?? null) ? strtoupper($input['deployment_type']) : '';
        if (!in_array($deploymentType, ChallengeRepository::VALID_DEPLOYMENT_TYPES, true)) {
            $errors[] = 'Deployment type must be one of: ' . implode(', ', ChallengeRepository::VALID_DEPLOYMENT_TYPES) . '.';
        }

        return [
            'title' => $title,
            'description' => $description,
            'category_id' => $categoryId,
            'difficulty' => $difficulty,
            'points' => max(0, $points),
            'deployment_type' => $deploymentType,
            'errors' => $errors,
        ];
    }

    private function uniqueSlugFor(string $title): string
    {
        $base = Str::slugify($title);
        $slug = $base;
        $suffix = 2;

        while ($this->challenges->slugExists($slug)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return substr($slug, 0, 170);
    }
}
