<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Message
{
    /**
     * Inbox: messages addressed to me directly OR to a group I'm a member of.
     * Returns the latest message per thread (we group by COALESCE(parent_id, id)).
     *
     * @return list<array<string, mixed>>
     */
    public static function inboxFor(int $userId): array
    {
        $sql = <<<SQL
            WITH visible AS (
                SELECT m.*,
                       u.display_alias AS sender_alias,
                       g.name          AS group_name,
                       COALESCE(m.parent_id, m.id) AS thread_root,
                       (mr.user_id IS NOT NULL) AS is_read
                FROM messages m
                JOIN users u ON u.id = m.sender_id
                LEFT JOIN groups g ON g.id = m.recipient_group
                LEFT JOIN message_reads mr ON mr.message_id = m.id AND mr.user_id = :uid
                WHERE m.recipient_id = :uid
                   OR m.recipient_group IN (
                        SELECT group_id FROM group_members WHERE user_id = :uid
                   )
            ),
            ranked AS (
                SELECT v.*,
                       ROW_NUMBER() OVER (PARTITION BY thread_root ORDER BY sent_at DESC) AS rn,
                       COUNT(*)      OVER (PARTITION BY thread_root) AS thread_size,
                       SUM(CASE WHEN NOT is_read THEN 1 ELSE 0 END)
                                     OVER (PARTITION BY thread_root) AS unread_in_thread
                FROM visible v
            )
            SELECT id, sender_id, sender_alias, recipient_id, recipient_group, group_name,
                   subject, body, is_review, parent_id, sent_at,
                   thread_root, thread_size, unread_in_thread
            FROM ranked
            WHERE rn = 1
            ORDER BY sent_at DESC
        SQL;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Sent box.
     *
     * @return list<array<string, mixed>>
     */
    public static function sentBy(int $userId): array
    {
        $sql = <<<SQL
            SELECT m.*,
                   u.display_alias AS recipient_alias,
                   g.name          AS group_name
            FROM messages m
            LEFT JOIN users  u ON u.id = m.recipient_id
            LEFT JOIN groups g ON g.id = m.recipient_group
            WHERE m.sender_id = :uid
            ORDER BY m.sent_at DESC
        SQL;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT m.*, u.display_alias AS sender_alias, g.name AS group_name
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             LEFT JOIN groups g ON g.id = m.recipient_group
             WHERE m.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function thread(int $rootId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT m.*, u.display_alias AS sender_alias
             FROM messages m JOIN users u ON u.id = m.sender_id
             WHERE m.id = :id OR m.parent_id = :id
             ORDER BY m.sent_at ASC'
        );
        $stmt->execute([':id' => $rootId]);
        return $stmt->fetchAll();
    }

    /**
     * True if $userId may read $message: they're sender, direct recipient, or
     * member of the recipient group.
     */
    public static function userCanRead(array $message, int $userId): bool
    {
        if ((int) $message['sender_id'] === $userId) {
            return true;
        }
        if ($message['recipient_id'] !== null && (int) $message['recipient_id'] === $userId) {
            return true;
        }
        if ($message['recipient_group'] !== null) {
            $stmt = Database::pdo()->prepare(
                'SELECT 1 FROM group_members WHERE group_id = :g AND user_id = :u'
            );
            $stmt->execute([':g' => $message['recipient_group'], ':u' => $userId]);
            return (bool) $stmt->fetchColumn();
        }
        return false;
    }

    public static function markRead(int $messageId, int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO message_reads (message_id, user_id) VALUES (:m, :u)
             ON CONFLICT DO NOTHING'
        );
        $stmt->execute([':m' => $messageId, ':u' => $userId]);
    }

    public static function send(
        int $senderId,
        ?int $recipientId,
        ?int $recipientGroup,
        string $subject,
        string $body,
        bool $isReview = false,
        ?int $parentId = null,
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO messages
                (sender_id, recipient_id, recipient_group, subject, body, is_review, parent_id)
             VALUES (:s, :r, :g, :sub, :body, :rev, :p) RETURNING id'
        );
        $stmt->execute([
            ':s'   => $senderId,
            ':r'   => $recipientId,
            ':g'   => $recipientGroup,
            ':sub' => $subject,
            ':body'=> $body,
            ':rev' => $isReview ? 't' : 'f',
            ':p'   => $parentId,
        ]);
        return (int) $stmt->fetchColumn();
    }
}
