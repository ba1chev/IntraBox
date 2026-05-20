<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

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

        self::ensureAdminPassword(self::$pdo);

        return self::$pdo;
    }

    // Replace the placeholder hash from init.sql with a real Argon2id hash
    // of ADMIN_PASSWORD on first boot (or after .env change).
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
