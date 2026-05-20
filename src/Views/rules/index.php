<?php
/** @var list<array<string, mixed>> $rules */
use App\Services\RuleEngine;
?>
<h1>Usage rules</h1>
<p class="muted">
    These rules are defined by an administrator and are enforced when sending
    messages. If a rule forbids you from writing to a particular user or group,
    you will see a message.
</p>

<?php if ($rules === []): ?>
    <p class="empty">No publicly visible rules at the moment.</p>
<?php else: ?>
<table class="table">
    <thead>
    <tr><th>Name</th><th>Type</th><th>From → To</th><th>Time</th><th>Description</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rules as $r): ?>
        <tr>
            <td><strong><?= e($r['name']) ?></strong></td>
            <td>
                <?php if ($r['is_allow']): ?>
                    <span class="badge badge-allow">allow</span>
                <?php else: ?>
                    <span class="badge badge-deny">deny</span>
                <?php endif; ?>
            </td>
            <td>
                <?= $r['sender_user_alias'] ? '@' . e($r['sender_user_alias']) : ($r['sender_group_name'] ? '#' . e($r['sender_group_name']) : 'anyone') ?>
                →
                <?= $r['target_user_alias'] ? '@' . e($r['target_user_alias']) : ($r['target_group_name'] ? '#' . e($r['target_group_name']) : 'anyone') ?>
            </td>
            <td>
                <?= e(RuleEngine::weekdayMaskToString((int) $r['weekday_mask'])) ?><br>
                <small class="muted"><?= e(substr((string) $r['time_from'], 0, 5)) ?>–<?= e(substr((string) $r['time_to'], 0, 5)) ?></small>
            </td>
            <td><?= e($r['description']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
