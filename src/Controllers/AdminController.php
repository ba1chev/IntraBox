<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\AbuseLog;
use App\Models\User;
use App\Services\StatsService;

final class AdminController
{
    public function dashboard(): void
    {
        Session::requireAdmin();
        View::render('admin/dashboard', [
            'title'           => 'Admin dashboard',
            'anonStats'       => StatsService::anonymous(),
            'nonAnonStats'    => StatsService::nonAnonymous(),
            'unreviewedAbuse' => AbuseLog::unreviewedCount(),
        ]);
    }

    public function users(): void
    {
        Session::requireAdmin();
        View::render('admin/users', [
            'title' => 'Users',
            'users' => User::all(true),
        ]);
    }

    public function createUser(): void
    {
        Session::requireAdmin();
        Csrf::check();

        $username      = trim((string) ($_POST['username'] ?? ''));
        $realName      = trim((string) ($_POST['real_name'] ?? ''));
        $displayAlias  = trim((string) ($_POST['display_alias'] ?? ''));
        $email         = trim((string) ($_POST['email'] ?? ''));
        $password      = (string) ($_POST['password'] ?? '');
        $role          = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

        $errors = [];
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,64}$/', $username)) {
            $errors[] = 'Invalid username.';
        }
        if ($realName === '' || mb_strlen($realName) > 128) {
            $errors[] = 'Invalid real name.';
        }
        if ($displayAlias === '' || mb_strlen($displayAlias) > 64) {
            $errors[] = 'Invalid display alias.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            header('Location: /admin/users');
            return;
        }

        try {
            User::create($username, $realName, $displayAlias, $email, $password, $role);
            Session::flash('success', 'User created.');
        } catch (\PDOException $e) {
            Session::flash('error', 'Conflict: username, alias, or email already in use.');
        }
        header('Location: /admin/users');
    }

    /** @param array<string, string> $params */
    public function toggleUser(array $params): void
    {
        Session::requireAdmin();
        Csrf::check();
        $id = (int) $params['id'];
        $user = User::findById($id);
        if ($user !== null) {
            User::setActive($id, !$user['is_active']);
            Session::flash('success', $user['is_active'] ? 'Deactivated.' : 'Activated.');
        }
        header('Location: /admin/users');
    }

    public function abuse(): void
    {
        Session::requireAdmin();
        $filter = $_GET['filter'] ?? 'unreviewed';
        $reviewed = $filter === 'all' ? null : ($filter === 'reviewed');
        View::render('admin/abuse', [
            'title'   => 'Abuse log',
            'logs'    => AbuseLog::recent(200, $reviewed),
            'filter'  => $filter,
        ]);
    }

    /** @param array<string, string> $params */
    public function markAbuseReviewed(array $params): void
    {
        Session::requireAdmin();
        Csrf::check();
        AbuseLog::markReviewed((int) $params['id']);
        header('Location: /admin/abuse');
    }

    public function statsJson(): void
    {
        Session::requireAdmin();
        View::json([
            'anonymous'     => StatsService::anonymous(),
            'non_anonymous' => StatsService::nonAnonymous(),
        ]);
    }
}
