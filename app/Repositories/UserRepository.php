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
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByIdentifier(string $identifier): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$identifier, $identifier]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(string $username, string $email, string $passwordHash, ?string $fullName, int $roleId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, "active", NOW())'
        );
        $stmt->execute([$username, $email, $passwordHash, $fullName, $roleId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $userId]);
    }

    public function roleIdByName(string $roleName): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = ?');
        $stmt->execute([$roleName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int) $result['id'] : null;
    }

    public function roleName(int $roleId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT name FROM roles WHERE id = ?');
        $stmt->execute([$roleId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : null;
    }

    public static function toPublicArray(array $user, string $roleName): array
    {
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