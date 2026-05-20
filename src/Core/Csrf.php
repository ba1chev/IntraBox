<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function check(): void
    {
        $given    = $_POST['_csrf'] ?? '';
        $expected = $_SESSION[self::KEY] ?? '';
        if ($given === '' || $expected === '' || !hash_equals($expected, $given)) {
            http_response_code(419);
            throw new RuntimeException('CSRF token mismatch');
        }
    }
}
