<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM users WHERE username = :u AND is_active = TRUE LIMIT 1',
        );
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return list<array<string, mixed>> */
    public static function all(bool $includeInactive = false): array
    {
        $sql = 'SELECT id, username, real_name, display_alias, email, role, is_active, created_at
                FROM users';
        if (!$includeInactive) {
            $sql .= ' WHERE is_active = TRUE';
        }
        $sql .= ' ORDER BY id';
        return Database::pdo()->query($sql)->fetchAll();
    }

    /**
     * @return list<array{id: int, display_alias: string}>
     */
    public static function listForCompose(int $excludeId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, display_alias FROM users
             WHERE is_active = TRUE AND id <> :me
             ORDER BY display_alias',
        );
        $stmt->execute([':me' => $excludeId]);
        return $stmt->fetchAll();
    }

    public static function create(
        string $username,
        string $realName,
        string $displayAlias,
        string $email,
        string $plainPassword,
        string $role = 'user',
    ): int {
        $hash = password_hash($plainPassword, PASSWORD_ARGON2ID);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (username, real_name, display_alias, email, password_hash, role)
             VALUES (:u, :rn, :da, :em, :ph, :r) RETURNING id',
        );
        $stmt->execute([
            ':u'  => $username,
            ':rn' => $realName,
            ':da' => $displayAlias,
            ':em' => $email,
            ':ph' => $hash,
            ':r'  => $role,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET is_active = :a WHERE id = :id');
        $stmt->execute([':a' => $active ? 't' : 'f', ':id' => $id]);
    }

    public static function aliasFor(int $id): string
    {
        $stmt = Database::pdo()->prepare('SELECT display_alias FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (string) ($stmt->fetchColumn() ?: '?');
    }
}
