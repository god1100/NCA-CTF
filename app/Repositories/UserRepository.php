<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * User data access for Phase 2 authentication. All queries use prepared
 * statements (docs/ctf9.txt §30). No ORM.
 */
final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Looks up by either username or email -- used for login, where the
     * client submits a single "identifier" field (docs/ctf5.txt §9).
     */
    public function findByIdentifier(string $identifier): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE username = :identifier1 OR email = :identifier2 LIMIT 1'
        );
        $stmt->execute(['identifier1' => $identifier, 'identifier2' => $identifier]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function roleIdByName(string $roleName): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $roleName]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function roleName(int $roleId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT name FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $roleId]);
        $name = $stmt->fetchColumn();

        return $name === false ? null : $name;
    }

    /**
     * Creates a new user. Status is always set server-side (default
     * 'active' for Phase 2 -- no email verification pipeline exists yet,
     * per docs/ctf9.txt §6 deferring password reset/email infra). The
     * caller must never pass client-supplied role/status values through
     * unchecked.
     */
    public function create(string $username, string $email, string $passwordHash, ?string $fullName, int $roleId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role_id, status)
             VALUES (:username, :email, :password_hash, :full_name, :role_id, :status)'
        );
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'full_name' => $fullName,
            'role_id' => $roleId,
            'status' => 'active',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    /**
     * Strips sensitive fields before a user record is ever returned in an
     * API response. password_hash must never leave the server
     * (docs/ctf5.txt §9, ctf9.txt requirement list).
     */
    public static function toPublicArray(array $user, ?string $roleName = null): array
    {
        return [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $roleName,
            'status' => $user['status'],
            'created_at' => $user['created_at'],
            'last_login_at' => $user['last_login_at'],
        ];
    }
}
