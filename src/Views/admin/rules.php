<?php
/** @var list<array<string, mixed>> $rules */
/** @var list<array<string, mixed>> $users */
/** @var list<array<string, mixed>> $groups */
use App\Services\RuleEngine;
?>
<div class="page-header">
    <h1>Manage rules</h1>
    <a href="/admin" class="btn-link">← dashboard</a>
</div>

<div class="card">
    <h2 style="margin:0 0 12px">New rule</h2>
    <form method="post" action="/admin/rules" class="form form-grid">
        <?= App\Core\Csrf::field() ?>
        <label class="span-2"><span>Name</span><input type="text" name="name" required maxlength="128"></label>
        <label class="span-2"><span>Description</span><input type="text" name="description" maxlength="500"></label>

        <fieldset>
            <legend>Sender (optional)</legend>
            <label>
                <span>User</span>
                <select name="sender_user_id">
                    <option value="">anyone</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u['id'] ?>">@<?= e($u['display_alias']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Group</span>
                <select name="sender_group_id">
                    <option value="">anyone</option>
                    <?php foreach ($groups as $g): ?>
                        <option value="<?= (int) $g['id'] ?>">#<?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </fieldset>

        <fieldset>
            <legend>Recipient (optional)</legend>
            <label>
                <span>User</span>
                <select name="target_user_id">
                    <option value="">anyone</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u['id'] ?>">@<?= e($u['display_alias']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Group</span>
                <select name="target_group_id">
                    <option value="">anyone</option>
                    <?php foreach ($groups as $g): ?>
                        <option value="<?= (int) $g['id'] ?>">#<?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </fieldset>

        <fieldset class="span-2">
            <legend>Time</legend>
            <div class="weekdays">
                <?php $names = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; ?>
                <?php foreach ($names as $i => $n): ?>
                    <label><input type="checkbox" name="weekdays[]" value="<?= $i+1 ?>" checked> <?= $n ?></label>
                <?php endforeach; ?>
            </div>
            <label class="inline-label"><span>From</span><input type="time" name="time_from" value="00:00"></label>
            <label class="inline-label"><span>To</span><input type="time" name="time_to" value="23:59"></label>
        </fieldset>

        <label>
            <span>Mode</span>
            <select name="mode">
                <option value="allow">allow</option>
                <option value="deny">deny</option>
            </select>
        </label>

        <label class="checkbox-row">
            <input type="checkbox" name="is_visible" value="1" checked>
            <span>visible to users</span>
        </label>

        <button type="submit" class="btn-primary span-2">Create rule</button>
    </form>
</div>

<h2>Active rules</h2>
<?php if ($rules === []): ?>
    <p class="empty">No rules defined.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Name</th><th>Mode</th><th>From → To</th><th>Time</th><th>Visible</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rules as $r): ?>
        <tr>
            <td><strong><?= e($r['name']) ?></strong><br><small class="muted"><?= e($r['description']) ?></small></td>
            <td><span class="badge <?= $r['is_allow'] ? 'badge-allow' : 'badge-deny' ?>">
                <?= $r['is_allow'] ? 'allow' : 'deny' ?>
            </span></td>
            <td>
                <?= $r['sender_user_alias'] ? '@' . e($r['sender_user_alias']) : ($r['sender_group_name'] ? '#' . e($r['sender_group_name']) : 'anyone') ?>
                →
                <?= $r['target_user_alias'] ? '@' . e($r['target_user_alias']) : ($r['target_group_name'] ? '#' . e($r['target_group_name']) : 'anyone') ?>
            </td>
            <td>
                <?= e(RuleEngine::weekdayMaskToString((int) $r['weekday_mask'])) ?><br>
                <small class="muted"><?= e(substr((string) $r['time_from'], 0, 5)) ?>–<?= e(substr((string) $r['time_to'], 0, 5)) ?></small>
            </td>
            <td><?= $r['is_visible'] ? '✓' : '—' ?></td>
            <td>
                <form method="post" action="/admin/rules/<?= (int) $r['id'] ?>/delete" class="inline">
                    <?= App\Core\Csrf::field() ?>
                    <button type="submit" class="link-btn-small" onclick="return confirm('Delete this rule?')">delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
