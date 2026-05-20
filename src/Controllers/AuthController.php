<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

final class AuthController
{
    public function showLogin(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: /inbox');
            return;
        }
        View::render('auth/login', ['title' => 'Sign in']);
    }

    public function login(): void
    {
        Csrf::check();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $user = User::findByUsername($username);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            Session::flash('error', 'Invalid username or password.');
            header('Location: /login');
            return;
        }

        // Refresh hash if PHP recommends it.
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = \App\Core\Database::pdo()->prepare(
                'UPDATE users SET password_hash = :h WHERE id = :id'
            );
            $stmt->execute([':h' => $newHash, ':id' => $user['id']]);
        }

        Session::login((int) $user['id'], (string) $user['role']);
        Session::flash('success', 'Welcome, ' . $user['display_alias'] . '!');
        header('Location: /inbox');
    }

    public function logout(): void
    {
        Csrf::check();
        Session::logout();
        header('Location: /login');
    }
}
