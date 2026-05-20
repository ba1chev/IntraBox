<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Group
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $sql = 'SELECT g.*, u.display_alias AS creator_alias,
                       (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS member_count
                FROM groups g
                JOIN users u ON u.id = g.created_by
                ORDER BY g.name';
        return Database::pdo()->query($sql)->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT g.id, g.name, g.description
             FROM groups g
             JOIN group_members gm ON gm.group_id = g.id
             WHERE gm.user_id = :uid
             ORDER BY g.name'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM groups WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function create(string $name, ?string $description, int $createdBy): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO groups (name, description, created_by) VALUES (:n, :d, :cb) RETURNING id'
        );
        $stmt->execute([':n' => $name, ':d' => $description, ':cb' => $createdBy]);
        return (int) $stmt->fetchColumn();
    }

    public static function addMember(int $groupId, int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO group_members (group_id, user_id) VALUES (:g, :u)
             ON CONFLICT DO NOTHING'
        );
        $stmt->execute([':g' => $groupId, ':u' => $userId]);
    }

    public static function removeMember(int $groupId, int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM group_members WHERE group_id = :g AND user_id = :u'
        );
        $stmt->execute([':g' => $groupId, ':u' => $userId]);
    }

    /** @return list<array{id: int, display_alias: string, real_name: string}> */
    public static function members(int $groupId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.display_alias, u.real_name
             FROM users u JOIN group_members gm ON gm.user_id = u.id
             WHERE gm.group_id = :g
             ORDER BY u.display_alias'
        );
        $stmt->execute([':g' => $groupId]);
        return $stmt->fetchAll();
    }

    public static function isMember(int $groupId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM group_members WHERE group_id = :g AND user_id = :u'
        );
        $stmt->execute([':g' => $groupId, ':u' => $userId]);
        return (bool) $stmt->fetchColumn();
    }
}
