<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;

final class RuleEngine
{
    /**
     * @return array{allowed: bool, reason: string, matched: list<array<string, mixed>>}
     */
    public static function canSend(
        int $senderId,
        ?int $recipientId,
        ?int $recipientGroup,
        ?DateTimeImmutable $now = null,
    ): array {
        $now = $now ?? new DateTimeImmutable('now');
        $weekdayBit = self::weekdayBit((int) $now->format('N'));
        $time = $now->format('H:i:s');

        $sql = <<<SQL
            SELECT r.*
            FROM rules r
            WHERE
                (r.weekday_mask & :wbit) <> 0
                AND :nowt::time BETWEEN r.time_from AND r.time_to

                AND (r.sender_user_id  IS NULL OR r.sender_user_id  = :sender)
                AND (r.sender_group_id IS NULL OR EXISTS (
                        SELECT 1 FROM group_members gm
                        WHERE gm.group_id = r.sender_group_id AND gm.user_id = :sender
                ))

                AND (r.target_user_id  IS NULL OR r.target_user_id  = :target_u)
                AND (r.target_group_id IS NULL OR r.target_group_id = :target_g
                     OR (
                        :target_u IS NOT NULL AND EXISTS (
                            SELECT 1 FROM group_members gm
                            WHERE gm.group_id = r.target_group_id AND gm.user_id = :target_u
                        )
                     )
                )
        SQL;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            ':wbit'     => $weekdayBit,
            ':nowt'     => $time,
            ':sender'   => $senderId,
            ':target_u' => $recipientId,
            ':target_g' => $recipientGroup,
        ]);
        $matched = $stmt->fetchAll();

        $denying = array_values(array_filter($matched, static fn ($r) => !$r['is_allow']));
        if ($denying !== []) {
            $first = $denying[0];
            return [
                'allowed' => false,
                'reason'  => 'Matching rule: ' . $first['name'],
                'matched' => $matched,
            ];
        }

        return ['allowed' => true, 'reason' => '', 'matched' => $matched];
    }

    private static function weekdayBit(int $isoWeekday): int
    {
        return 1 << ($isoWeekday - 1);
    }

    public static function weekdayMaskToString(int $mask): string
    {
        if ($mask === 127) {
            return 'every day';
        }
        $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $out = [];
        for ($i = 0; $i < 7; $i++) {
            if ($mask & (1 << $i)) {
                $out[] = $names[$i];
            }
        }
        return implode(', ', $out);
    }
}
