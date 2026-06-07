<?php
/** @var list<array<string, mixed>> $logs */
/** @var string $filter */
?>
<div class="page-header">
    <h1>Abuse log</h1>
    <a href="/admin" class="btn-link">← dashboard</a>
</div>

<nav class="filter-tabs">
    <a href="?filter=unreviewed" class="<?= $filter === 'unreviewed' ? 'active' : '' ?>">unreviewed</a>
    <a href="?filter=reviewed" class="<?= $filter === 'reviewed' ? 'active' : '' ?>">reviewed</a>
    <a href="?filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">all</a>
</nav>

<?php if ($logs === []): ?>
    <p class="empty">No entries.</p>
<?php else: ?>
<table class="table">
    <thead>
    <tr><th>When</th><th>Sender</th><th>Pattern</th><th>Snippet</th><th>Severity</th><th>Message</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($logs as $a): ?>
        <tr class="<?= $a['reviewed'] ? 'muted-row' : '' ?>">
            <td><?= e(substr((string) $a['created_at'], 0, 16)) ?></td>
            <td>
                @<?= e($a['sender_alias']) ?>
                <small class="muted"><?= e($a['sender_real_name']) ?></small>
            </td>
            <td><span class="badge badge-pattern"><?= e($a['pattern_matched']) ?></span></td>
            <td class="snippet"><?= e($a['snippet']) ?></td>
            <td>
                <?= (int) $a['severity'] ?>
            </td>
            <td>
                <?php if ($a['message_id']): ?>
                    <a href="/messages/<?= (int) $a['message_id'] ?>">#<?= (int) $a['message_id'] ?></a>
                <?php else: ?>
                    <small class="muted">blocked</small>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!$a['reviewed']): ?>
                <form method="post" action="/admin/abuse/<?= (int) $a['id'] ?>/review" class="inline">
                    <?= App\Core\Csrf::field() ?>
                    <button type="submit" class="link-btn-small">mark reviewed</button>
                </form>
                <?php else: ?>
                    <small class="muted">✓</small>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
