<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\User;
use PDOException;

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

    public function showRegister(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: /inbox');
            return;
        }
        View::render('auth/register', ['title' => 'Create account']);
    }

    public function register(): void
    {
        Csrf::check();

        $username     = trim((string) ($_POST['username']     ?? ''));
        $realName     = trim((string) ($_POST['real_name']    ?? ''));
        $displayAlias = trim((string) ($_POST['display_alias'] ?? ''));
        $email        = trim((string) ($_POST['email']        ?? ''));
        $password     = (string) ($_POST['password'] ?? '');

        $errors = [];
        if (!preg_match('/^[a-zA-Z0-9_.\-]{3,64}$/', $username)) {
            $errors[] = 'Username must be 3–64 characters (letters, digits, _ . -)';
        }
        if ($realName === '' || strlen($realName) > 128) {
            $errors[] = 'Real name is required (max 128 chars).';
        }
        if ($displayAlias === '' || strlen($displayAlias) > 64) {
            $errors[] = 'Display alias is required (max 64 chars).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            header('Location: /register');
            return;
        }

        try {
            $newId = User::create($username, $realName, $displayAlias, $email, $password, 'user');
        } catch (PDOException) {
            Session::flash('error', 'Username, alias, or email already in use.');
            header('Location: /register');
            return;
        }

        Session::login($newId, 'user');
        Session::flash('success', 'Welcome, ' . $displayAlias . '!');
        header('Location: /inbox');
    }
}
