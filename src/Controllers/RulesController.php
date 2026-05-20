<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\Group;
use App\Models\Rule;
use App\Models\User;

final class RulesController
{
    public function index(): void
    {
        Session::requireLogin();
        View::render('rules/index', [
            'title' => 'Usage rules',
            'rules' => Rule::visible(),
        ]);
    }

    public function adminIndex(): void
    {
        Session::requireAdmin();
        View::render('admin/rules', [
            'title'  => 'Manage rules',
            'rules'  => Rule::all(),
            'users'  => User::all(),
            'groups' => Group::all(),
        ]);
    }

    public function create(): void
    {
        Session::requireAdmin();
        Csrf::check();

        $mask = 0;
        foreach (($_POST['weekdays'] ?? []) as $w) {
            $bit = 1 << (((int) $w) - 1);
            $mask |= $bit;
        }
        if ($mask === 0) {
            $mask = 127;
        }

        $data = [
            'name'            => trim((string) ($_POST['name'] ?? '')),
            'description'     => trim((string) ($_POST['description'] ?? '')) ?: null,
            'sender_user_id'  => self::nullableInt($_POST['sender_user_id'] ?? null),
            'sender_group_id' => self::nullableInt($_POST['sender_group_id'] ?? null),
            'target_user_id'  => self::nullableInt($_POST['target_user_id'] ?? null),
            'target_group_id' => self::nullableInt($_POST['target_group_id'] ?? null),
            'weekday_mask'    => $mask,
            'time_from'       => $_POST['time_from'] ?: '00:00',
            'time_to'         => $_POST['time_to']   ?: '23:59',
            'is_allow'        => ($_POST['mode'] ?? 'allow') === 'allow',
            'is_visible'      => !empty($_POST['is_visible']),
        ];

        if ($data['name'] === '') {
            Session::flash('error', 'Rule name is required.');
            header('Location: /admin/rules');
            return;
        }

        Rule::create($data);
        Session::flash('success', 'Rule created.');
        header('Location: /admin/rules');
    }

    /** @param array<string, string> $params */
    public function delete(array $params): void
    {
        Session::requireAdmin();
        Csrf::check();
        Rule::delete((int) $params['id']);
        Session::flash('success', 'Rule deleted.');
        header('Location: /admin/rules');
    }

    private static function nullableInt(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        $n = (int) $v;
        return $n > 0 ? $n : null;
    }
}
