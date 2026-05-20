<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\Group;
use App\Models\User;

final class GroupController
{
    public function index(): void
    {
        Session::requireLogin();
        View::render('groups/index', [
            'title'  => 'Groups',
            'groups' => Group::all(),
        ]);
    }

    public function createForm(): void
    {
        Session::requireLogin();
        View::render('groups/new', ['title' => 'New group']);
    }

    public function create(): void
    {
        Session::requireLogin();
        Csrf::check();
        $name = trim((string) ($_POST['name'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));
        if ($name === '') {
            Session::flash('error', 'Name is required.');
            header('Location: /groups/new');
            return;
        }
        try {
            $id = Group::create($name, $desc !== '' ? $desc : null, (int) Session::userId());
            Group::addMember($id, (int) Session::userId());
            Session::flash('success', 'Group created.');
            header('Location: /groups/' . $id);
        } catch (\PDOException $e) {
            Session::flash('error', 'A group with that name already exists.');
            header('Location: /groups/new');
        }
    }

    /** @param array<string, string> $params */
    public function edit(array $params): void
    {
        Session::requireLogin();
        $id = (int) $params['id'];
        $group = Group::findById($id);
        if ($group === null) {
            http_response_code(404);
            echo 'Group not found.';
            return;
        }
        View::render('groups/edit', [
            'title'   => $group['name'],
            'group'   => $group,
            'members' => Group::members($id),
            'allUsers'=> User::all(),
        ]);
    }

    /** @param array<string, string> $params */
    public function addMember(array $params): void
    {
        Session::requireLogin();
        Csrf::check();
        $id = (int) $params['id'];
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            Group::addMember($id, $userId);
            Session::flash('success', 'Member added.');
        }
        header('Location: /groups/' . $id);
    }

    /** @param array<string, string> $params */
    public function removeMember(array $params): void
    {
        Session::requireLogin();
        Csrf::check();
        $id = (int) $params['id'];
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            Group::removeMember($id, $userId);
            Session::flash('success', 'Member removed.');
        }
        header('Location: /groups/' . $id);
    }
}
