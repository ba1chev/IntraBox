<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AbuseLog
{
    public static function record(
        ?int $messageId,
        int $senderId,
        string $pattern,
        string $snippet,
        int $severity = 1,
    ): void {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO abuse_log (message_id, sender_id, pattern_matched, snippet, severity)
             VALUES (:m, :s, :p, :sn, :sev)'
        );
        $stmt->execute([
            ':m'   => $messageId,
            ':s'   => $senderId,
            ':p'   => $pattern,
            ':sn'  => $snippet,
            ':sev' => $severity,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 100, ?bool $reviewed = null): array
    {
        $sql = 'SELECT a.*, u.display_alias AS sender_alias, u.real_name AS sender_real_name
                FROM abuse_log a
                JOIN users u ON u.id = a.sender_id';
        $params = [];
        if ($reviewed !== null) {
            $sql .= ' WHERE a.reviewed = :rv';
            $params[':rv'] = $reviewed ? 't' : 'f';
        }
        $sql .= ' ORDER BY a.created_at DESC LIMIT :lim';
        $params[':lim'] = $limit;

        $stmt = Database::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function markReviewed(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE abuse_log SET reviewed = TRUE WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    public static function unreviewedCount(): int
    {
        return (int) Database::pdo()
            ->query('SELECT COUNT(*) FROM abuse_log WHERE reviewed = FALSE')
            ->fetchColumn();
    }
}
