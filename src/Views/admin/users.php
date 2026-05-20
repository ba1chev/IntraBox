<?php /** @var list<array<string, mixed>> $users */ ?>
<div class="page-header">
    <h1>Users</h1>
    <a href="/admin" class="btn-link">← dashboard</a>
</div>

<details class="card" open>
    <summary><strong>+ New user</strong></summary>
    <form method="post" action="/admin/users" class="form form-grid">
        <?= App\Core\Csrf::field() ?>
        <label><span>Username</span><input type="text" name="username" required pattern="[a-zA-Z0-9_.\-]{3,64}"></label>
        <label><span>Real name</span><input type="text" name="real_name" required maxlength="128"></label>
        <label><span>Display alias</span><input type="text" name="display_alias" required maxlength="64"></label>
        <label><span>Email</span><input type="email" name="email" required></label>
        <label><span>Password</span><input type="text" name="password" required minlength="8"></label>
        <label>
            <span>Role</span>
            <select name="role"><option value="user">user</option><option value="admin">admin</option></select>
        </label>
        <button type="submit" class="btn-primary">Create</button>
    </form>
</details>

<table class="table">
    <thead><tr><th>ID</th><th>Alias</th><th>Real name</th><th>Email</th><th>Role</th><th>Active</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr class="<?= $u['is_active'] ? '' : 'muted-row' ?>">
            <td><?= (int) $u['id'] ?></td>
            <td>@<?= e($u['display_alias']) ?></td>
            <td><?= e($u['real_name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td><?= $u['is_active'] ? '✓' : '—' ?></td>
            <td>
                <?php if ($u['username'] !== ($_ENV['ADMIN_USERNAME'] ?? 'admin')): ?>
                <form method="post" action="/admin/users/<?= (int) $u['id'] ?>/toggle" class="inline">
                    <?= App\Core\Csrf::field() ?>
                    <button type="submit" class="link-btn-small">
                        <?= $u['is_active'] ? 'deactivate' : 'activate' ?>
                    </button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
