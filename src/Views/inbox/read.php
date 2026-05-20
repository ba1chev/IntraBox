<?php
/** @var list<array<string, mixed>> $thread */
/** @var int $rootId */
$first = $thread[0];
?>
<div class="page-header">
    <h1><?= e($first['subject']) ?></h1>
    <a href="/compose?reply_to=<?= (int) $first['id'] ?>" class="btn-primary">Reply</a>
</div>

<div class="thread">
<?php foreach ($thread as $i => $m): ?>
    <article class="message <?= $i === 0 ? 'message-root' : 'message-reply' ?>">
        <header class="message-head">
            <span class="alias"><?= e($m['sender_alias']) ?></span>
            <time class="muted small"><?= e(substr((string) $m['sent_at'], 0, 16)) ?></time>
            <?php if ($m['is_review']): ?><span class="badge badge-review">review</span><?php endif; ?>
        </header>
        <div class="message-body"><?= nl2br(e($m['body'])) ?></div>
    </article>
<?php endforeach; ?>
</div>

<p><a href="/inbox">← back to inbox</a></p>
