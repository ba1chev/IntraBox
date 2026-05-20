<?php

declare(strict_types=1);

namespace App\Services;

final class AbuseDetector
{
    /**
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
            'regex'    => '/\b(?:казвам\s+се|аз\s+съм|my\s+name\s+is|i\s+am)\s+[A-ZА-Я][\p{L}\-]{1,}/iu',
            'severity' => 2,
        ],
        [
            'name'     => 'whitespace_obfuscation',
            'regex'    => '/(?:\b\p{L}\b\s+){3,}\b\p{L}\b/u',
            'severity' => 2,
        ],
        [
            'name'     => 'profanity_lite',
            'regex'    => '/\b(idiot|moron|тъпак|идиот)\b/iu',
            'severity' => 1,
        ],
    ];

    /**
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
