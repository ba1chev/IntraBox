<?php
/** @var list<array<string, mixed>> $messages */

use App\Services\RuleEngine;
?>
<div class="page-header">
    <h1>Inbox</h1>
    <a href="/compose" class="btn-primary">+ New message</a>
</div>

<?php if ($messages === []): ?>
    <p class="empty">No messages. When someone writes you, the message will show up here.</p>
<?php else: ?>
<table class="table">
    <thead>
    <tr>
        <th>From</th>
        <th>Subject</th>
        <th>Received</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($messages as $m): ?>
        <tr class="<?= $m['unread_in_thread'] > 0 ? 'unread' : '' ?>">
            <td>
                <span class="alias"><?= e($m['sender_alias']) ?></span>
                <?php if ($m['group_name']): ?>
                    <small class="muted">→ #<?= e($m['group_name']) ?></small>
                <?php endif; ?>
            </td>
            <td>
                <a href="/messages/<?= (int) $m['id'] ?>"><?= e($m['subject']) ?></a>
                <?php if ($m['is_review']): ?><span class="badge badge-review">review</span><?php endif; ?>
                <?php if ($m['thread_size'] > 1): ?>
                    <span class="badge badge-thread"><?= (int) $m['thread_size'] ?> messages</span>
                <?php endif; ?>
            </td>
            <td><time><?= e(substr((string) $m['sent_at'], 0, 16)) ?></time></td>
            <td><a href="/compose?reply_to=<?= (int) $m['id'] ?>" class="link-small">reply</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
