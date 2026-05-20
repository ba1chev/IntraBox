<?php /** @var list<array<string, mixed>> $messages */ ?>
<div class="page-header">
    <h1>Sent</h1>
    <a href="/compose" class="btn-primary">+ New message</a>
</div>

<?php if ($messages === []): ?>
    <p class="empty">You haven't sent any messages yet.</p>
<?php else: ?>
<table class="table">
    <thead>
    <tr><th>To</th><th>Subject</th><th>Sent</th></tr>
    </thead>
    <tbody>
    <?php foreach ($messages as $m): ?>
        <tr>
            <td>
                <?php if ($m['recipient_alias']): ?>
                    <span class="alias"><?= e($m['recipient_alias']) ?></span>
                <?php elseif ($m['group_name']): ?>
                    #<?= e($m['group_name']) ?>
                <?php endif; ?>
            </td>
            <td><a href="/messages/<?= (int) $m['id'] ?>"><?= e($m['subject']) ?></a></td>
            <td><?= e(substr((string) $m['sent_at'], 0, 16)) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
