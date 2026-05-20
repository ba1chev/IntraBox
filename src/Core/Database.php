<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO singleton wrapper. All DB access goes through here.
 * Prepared statements are mandatory throughout the app — no raw concatenation.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = $_ENV['DB_HOST'] ?? 'db';
        $port = $_ENV['DB_PORT'] ?? '5432';
        $name = $_ENV['DB_NAME'] ?? 'intrabox';
        $user = $_ENV['DB_USER'] ?? 'intrabox';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        // Ensure admin password matches .env on first boot (or after .env change).
        self::ensureAdminPassword(self::$pdo);

        return self::$pdo;
    }

    /**
     * On first boot the seed in init.sql contains a placeholder hash.
     * Replace it with a real Argon2id hash of ADMIN_PASSWORD from the env.
     */
    private static function ensureAdminPassword(PDO $pdo): void
    {
        $username = $_ENV['ADMIN_USERNAME'] ?? 'admin';
        $password = $_ENV['ADMIN_PASSWORD'] ?? null;
        if ($password === null || $password === '') {
            return;
        }

        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();
        if ($row === false) {
            return;
        }

        $needsReset = str_contains((string) $row['password_hash'], 'placeholder_will_be_overwritten')
                      || !password_verify($password, (string) $row['password_hash']);

        if ($needsReset) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            $upd = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
            $upd->execute([':h' => $newHash, ':id' => $row['id']]);
        }
    }
}
