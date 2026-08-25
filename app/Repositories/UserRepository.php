<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE id = ?'
        );

        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE username = ?'
        );

        $stmt->execute([$username]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE email = ?'
        );

        $stmt->execute([$email]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function findByIdentifier(string $identifier): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE username = ? OR email = ?'
        );

        $stmt->execute([$identifier, $identifier]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function create(
        string $username,
        string $email,
        string $passwordHash,
        ?string $fullName,
        int $roleId
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users
                (username, email, password_hash, full_name, role_id, status, created_at)
             VALUES
                (?, ?, ?, ?, ?, "active", NOW())'
        );

        $stmt->execute([
            $username,
            $email,
            $passwordHash,
            $fullName,
            $roleId
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET last_login_at = NOW()
             WHERE id = ?'
        );

        $stmt->execute([$userId]);
    }

    public function updatePassword(
        int $userId,
        string $passwordHash
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET password_hash = ?
             WHERE id = ?'
        );

        $stmt->execute([
            $passwordHash,
            $userId
        ]);
    }

    public function roleIdByName(string $roleName): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM roles
             WHERE name = ?'
        );

        $stmt->execute([$roleName]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result
            ? (int) $result['id']
            : null;
    }

    public function roleName(int $roleId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT name
             FROM roles
             WHERE id = ?'
        );

        $stmt->execute([$roleId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result
            ? $result['name']
            : null;
    }

    /*
     * ============================================================
     * Participant Management
     * ============================================================
     */

    /**
     * Return only users whose role is "participant".
     *
     * Administrators and other roles are intentionally excluded.
     *
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     total: int
     * }
     */
    public function paginateParticipants(
        int $page,
        int $perPage,
        ?string $search = null,
        ?string $status = null
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $offset = ($page - 1) * $perPage;

        /*
         * Participants are identified by the role name rather
         * than by a hard-coded role ID.
         */
        $where = [
            "r.name = 'participant'"
        ];

        $params = [];

        /*
         * Search username, full name, and email.
         */
        if ($search !== null && trim($search) !== '') {
            $where[] = '(
                u.username LIKE ?
                OR u.full_name LIKE ?
                OR u.email LIKE ?
            )';

            $searchValue = '%' . trim($search) . '%';

            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
        }

        /*
         * Optional status filter.
         */
        if ($status !== null && $status !== '') {
            $where[] = 'u.status = ?';
            $params[] = $status;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        /*
         * Count only participant accounts.
         */
        $countSql = "
            SELECT COUNT(*)
            FROM users u
            INNER JOIN roles r
                ON r.id = u.role_id
            {$whereSql}
        ";

        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);

        $total = (int) $countStmt->fetchColumn();

        /*
         * Fetch only participant accounts.
         */
        $sql = "
            SELECT
                u.id,
                u.username,
                u.email,
                u.full_name,
                u.role_id,
                r.name AS role,
                u.status,
                u.created_at,
                u.last_login_at
            FROM users u
            INNER JOIN roles r
                ON r.id = u.role_id
            {$whereSql}
            ORDER BY u.created_at DESC, u.id DESC
            LIMIT {$perPage}
            OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Find a participant by ID.
     *
     * Returns NULL if the user exists but is not a participant.
     */
    public function findParticipantById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                u.id,
                u.username,
                u.email,
                u.full_name,
                u.role_id,
                r.name AS role,
                u.status,
                u.created_at,
                u.last_login_at
             FROM users u
             INNER JOIN roles r
                ON r.id = u.role_id
             WHERE u.id = ?
               AND r.name = "participant"'
        );

        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Update a participant's account status.
     */
    public function updateStatus(
        int $userId,
        string $status
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET status = ?
             WHERE id = ?
               AND role_id = (
                   SELECT id
                   FROM roles
                   WHERE name = "participant"
               )'
        );

        $stmt->execute([
            $status,
            $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a participant permanently.
     *
     * The role restriction prevents an administrator account
     * from being deleted through the participant API.
     */
    public function deleteById(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM users
             WHERE id = ?
               AND role_id = (
                   SELECT id
                   FROM roles
                   WHERE name = "participant"
               )'
        );

        $stmt->execute([$userId]);

        return $stmt->rowCount() > 0;
    }

    public static function toPublicArray(
        array $user,
        string $roleName
    ): array {
        return [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $roleName,
            'status' => $user['status'],
            'created_at' => $user['created_at'],
            'last_login_at' => $user['last_login_at'] ?? null,
        ];
    }
}