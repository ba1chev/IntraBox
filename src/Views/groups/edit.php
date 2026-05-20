<?php
/** @var array<string, mixed> $group */
/** @var list<array<string, mixed>> $members */
/** @var list<array<string, mixed>> $allUsers */

$memberIds = array_column($members, 'id');
$nonMembers = array_filter($allUsers, fn ($u) => !in_array($u['id'], $memberIds, true));
?>
<div class="page-header">
    <h1>#<?= e($group['name']) ?></h1>
    <a href="/groups" class="btn-link">← groups</a>
</div>

<?php if ($group['description']): ?>
    <p class="muted"><?= e($group['description']) ?></p>
<?php endif; ?>

<h2>Members (<?= count($members) ?>)</h2>
<?php if ($members === []): ?>
    <p class="empty">No members yet.</p>
<?php else: ?>
<ul class="member-list">
<?php foreach ($members as $m): ?>
    <li>
        <span class="alias"><?= e($m['display_alias']) ?></span>
        <?php if (App\Core\Session::isAdmin()): ?>
            <small class="muted">(<?= e($m['real_name']) ?>)</small>
        <?php endif; ?>
        <form method="post" action="/groups/<?= (int) $group['id'] ?>/remove" class="inline">
            <?= App\Core\Csrf::field() ?>
            <input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>">
            <button type="submit" class="link-btn-small" onclick="return confirm('Remove this member?')">remove</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($nonMembers): ?>
<h3>Add member</h3>
<form method="post" action="/groups/<?= (int) $group['id'] ?>/members" class="form-inline">
    <?= App\Core\Csrf::field() ?>
    <select name="user_id" required>
        <option value="">— select —</option>
        <?php foreach ($nonMembers as $u): ?>
            <option value="<?= (int) $u['id'] ?>">@<?= e($u['display_alias']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-primary">Add</button>
</form>
<?php endif; ?>
