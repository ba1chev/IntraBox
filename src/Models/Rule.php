<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Rule
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $sql = 'SELECT r.*,
                       su.display_alias AS sender_user_alias,
                       sg.name          AS sender_group_name,
                       tu.display_alias AS target_user_alias,
                       tg.name          AS target_group_name
                FROM rules r
                LEFT JOIN users  su ON su.id = r.sender_user_id
                LEFT JOIN groups sg ON sg.id = r.sender_group_id
                LEFT JOIN users  tu ON tu.id = r.target_user_id
                LEFT JOIN groups tg ON tg.id = r.target_group_id
                ORDER BY r.created_at DESC';
        return Database::pdo()->query($sql)->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function visible(): array
    {
        $sql = 'SELECT r.*,
                       su.display_alias AS sender_user_alias,
                       sg.name          AS sender_group_name,
                       tu.display_alias AS target_user_alias,
                       tg.name          AS target_group_name
                FROM rules r
                LEFT JOIN users  su ON su.id = r.sender_user_id
                LEFT JOIN groups sg ON sg.id = r.sender_group_id
                LEFT JOIN users  tu ON tu.id = r.target_user_id
                LEFT JOIN groups tg ON tg.id = r.target_group_id
                WHERE r.is_visible = TRUE
                ORDER BY r.created_at DESC';
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO rules
                (name, description, sender_user_id, sender_group_id,
                 target_user_id, target_group_id, weekday_mask,
                 time_from, time_to, is_allow, is_visible)
             VALUES
                (:n, :d, :su, :sg, :tu, :tg, :wm, :tf, :tt, :ia, :iv)
             RETURNING id'
        );
        $stmt->execute([
            ':n'  => $data['name'],
            ':d'  => $data['description'] ?? null,
            ':su' => $data['sender_user_id'] ?? null,
            ':sg' => $data['sender_group_id'] ?? null,
            ':tu' => $data['target_user_id'] ?? null,
            ':tg' => $data['target_group_id'] ?? null,
            ':wm' => $data['weekday_mask'] ?? 127,
            ':tf' => $data['time_from'] ?? '00:00',
            ':tt' => $data['time_to']   ?? '23:59',
            ':ia' => ($data['is_allow'] ?? true) ? 't' : 'f',
            ':iv' => ($data['is_visible'] ?? true) ? 't' : 'f',
        ]);
        return (int) $stmt->fetchColumn();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM rules WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
