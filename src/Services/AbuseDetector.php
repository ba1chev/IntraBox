<?php

declare(strict_types=1);

namespace App\Services;

/**
 * AbuseDetector — regex-based scanner that flags messages where the sender
 * may be trying to expose identifying information (which would defeat the
 * pseudo-anonymity of the system) or otherwise misbehave.
 *
 * Each pattern is run against the combined subject+body text. A non-empty
 * result list signals that the message contained suspicious content; the
 * caller decides whether to block, log, or pass it through.
 *
 * The patterns are intentionally tunable at runtime — admins can extend
 * this list without redeploying schema. The data shape:
 *   [
 *     ['pattern' => 'email',        'snippet' => 'pesho@gmail.com', 'severity' => 3],
 *     ['pattern' => 'name_self_disclosure', 'snippet' => 'казвам се Иван', 'severity' => 2],
 *   ]
 */
final class AbuseDetector
{
    /**
     * Built-in detection rules. Order matters only for cosmetic snippet ordering.
     *
     * @var array<int, array{name: string, regex: string, severity: int}>
     */
    private const PATTERNS = [
        [
            'name'     => 'email',
            'regex'    => '/[\w.+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/u',
            'severity' => 3,
        ],
        [
            'name'     => 'phone_bg',
            'regex'    => '/(?:\+?359|0)\s*(?:\d\s*){8,9}/u',
            'severity' => 3,
        ],
        [
            'name'     => 'ssn_egn',
            // EGN — exactly 10 digits as a standalone token.
            'regex'    => '/\b\d{10}\b/u',
            'severity' => 3,
        ],
        [
            'name'     => 'url',
            'regex'    => '/\bhttps?:\/\/\S+/iu',
            'severity' => 1,
        ],
        [
            'name'     => 'name_self_disclosure',
            // "казвам се X", "аз съм X", "my name is X", "i am X" + capitalized name.
            'regex'    => '/\b(?:казвам\s+се|аз\s+съм|my\s+name\s+is|i\s+am)\s+[A-ZА-Я][\p{L}\-]{1,}/iu',
            'severity' => 2,
        ],
        [
            'name'     => 'whitespace_obfuscation',
            // 4+ consecutive single letters separated by whitespace — the "i v a n" obfuscation trick.
            'regex'    => '/(?:\b\p{L}\b\s+){3,}\b\p{L}\b/u',
            'severity' => 2,
        ],
        [
            'name'     => 'profanity_lite',
            // A tiny example list — extendable from admin UI in the future.
            'regex'    => '/\b(idiot|moron|тъпак|идиот)\b/iu',
            'severity' => 1,
        ],
    ];

    /**
     * Run every detection pattern over the given text.
     *
     * @return list<array{pattern: string, snippet: string, severity: int}>
     */
    public static function scan(string $text): array
    {
        $findings = [];
        foreach (self::PATTERNS as $rule) {
            if (preg_match_all($rule['regex'], $text, $matches) > 0) {
                foreach ($matches[0] as $match) {
                    $findings[] = [
                        'pattern'  => $rule['name'],
                        'snippet'  => self::truncate((string) $match, 200),
                        'severity' => $rule['severity'],
                    ];
                }
            }
        }
        return $findings;
    }

    /**
     * Highest severity in a finding list (0 if empty).
     *
     * @param list<array{severity: int}> $findings
     */
    public static function maxSeverity(array $findings): int
    {
        $max = 0;
        foreach ($findings as $f) {
            if ($f['severity'] > $max) {
                $max = $f['severity'];
            }
        }
        return $max;
    }

    /**
     * Should the message be hard-blocked?
     * Policy: severity >= 3 (email, phone, EGN) blocks; lower severities are
     * flagged for admin review only.
     *
     * @param list<array{severity: int}> $findings
     */
    public static function shouldBlock(array $findings): bool
    {
        return self::maxSeverity($findings) >= 3;
    }

    private static function truncate(string $s, int $max): string
    {
        if (mb_strlen($s) <= $max) {
            return $s;
        }
        return mb_substr($s, 0, $max - 1) . '…';
    }
}
