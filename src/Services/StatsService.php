<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * StatsService — provides both:
 *   - anonymous stats (visible to all users): aggregates that don't reveal
 *     individual identities,
 *   - non-anonymous stats (admin-only): per-user breakdowns including real
 *     names and abuse counts.
 */
final class StatsService
{
    /**
     * Anonymous, public-safe stats.
     *
     * @return array<string, mixed>
     */
    public static function anonymous(): array
    {
        $pdo = Database::pdo();

        $total = (int) $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();

        $reviews = (int) $pdo->query(
            'SELECT COUNT(*) FROM messages WHERE is_review = TRUE'
        )->fetchColumn();

        $threads = (int) $pdo->query(
            'SELECT COUNT(DISTINCT COALESCE(parent_id, id)) FROM messages'
        )->fetchColumn();

        $last24h = (int) $pdo->query(
            "SELECT COUNT(*) FROM messages WHERE sent_at > NOW() - INTERVAL '24 hours'"
        )->fetchColumn();

        $last7d = (int) $pdo->query(
            "SELECT COUNT(*) FROM messages WHERE sent_at > NOW() - INTERVAL '7 days'"
        )->fetchColumn();

        // Most active groups by message volume — group names are public, no PII.
        $activeGroups = $pdo->query(
            "SELECT g.name, COUNT(m.id) AS msg_count
             FROM groups g
             LEFT JOIN messages m ON m.recipient_group = g.id
             GROUP BY g.id, g.name
             ORDER BY msg_count DESC
             LIMIT 5"
        )->fetchAll();

        // Activity by hour-of-day (last 7 days) — useful chart, no PII.
        $byHour = $pdo->query(
            "SELECT EXTRACT(HOUR FROM sent_at)::int AS hour, COUNT(*) AS n
             FROM messages
             WHERE sent_at > NOW() - INTERVAL '7 days'
             GROUP BY hour
             ORDER BY hour"
        )->fetchAll();

        return [
            'total_messages'   => $total,
            'reviews'          => $reviews,
            'review_pct'       => $total > 0 ? round(100.0 * $reviews / $total, 1) : 0.0,
            'threads'          => $threads,
            'avg_thread_size'  => $threads > 0 ? round($total / $threads, 2) : 0,
            'last_24h'         => $last24h,
            'last_7d'          => $last7d,
            'top_active_groups'=> $activeGroups,
            'by_hour'          => $byHour,
        ];
    }

    /**
     * Non-anonymous, admin-only stats.
     *
     * @return array<string, mixed>
     */
    public static function nonAnonymous(): array
    {
        $pdo = Database::pdo();

        $perUser = $pdo->query(
            "SELECT u.id, u.display_alias, u.real_name,
                    COUNT(DISTINCT s.id) AS sent_count,
                    COUNT(DISTINCT r.id) AS received_count
             FROM users u
             LEFT JOIN messages s ON s.sender_id = u.id
             LEFT JOIN messages r ON r.recipient_id = u.id
             GROUP BY u.id, u.display_alias, u.real_name
             ORDER BY sent_count DESC, received_count DESC"
        )->fetchAll();

        $topAbusers = $pdo->query(
            "SELECT u.id, u.display_alias, u.real_name, COUNT(a.id) AS abuse_count
             FROM users u
             JOIN abuse_log a ON a.sender_id = u.id
             GROUP BY u.id, u.display_alias, u.real_name
             ORDER BY abuse_count DESC
             LIMIT 10"
        )->fetchAll();

        // Average time-to-read in seconds, computed over messages that were read.
        $avgRead = $pdo->query(
            "SELECT AVG(EXTRACT(EPOCH FROM (mr.read_at - m.sent_at)))::numeric(10,1) AS avg_seconds
             FROM message_reads mr
             JOIN messages m ON m.id = mr.message_id"
        )->fetchColumn();

        $unreviewedAbuse = (int) $pdo->query(
            'SELECT COUNT(*) FROM abuse_log WHERE reviewed = FALSE'
        )->fetchColumn();

        return [
            'per_user'          => $perUser,
            'top_abusers'       => $topAbusers,
            'avg_read_seconds'  => $avgRead === false ? null : (float) $avgRead,
            'unreviewed_abuse'  => $unreviewedAbuse,
        ];
    }
}
