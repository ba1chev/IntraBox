<?php
/** @var array<string, mixed> $anonStats */
/** @var array<string, mixed> $nonAnonStats */
/** @var int $unreviewedAbuse */
?>
<div class="page-header">
    <h1>Admin dashboard</h1>
    <nav class="subnav">
        <a href="/admin/users">Users</a>
        <a href="/admin/rules">Rules</a>
        <a href="/admin/abuse">Abuse <?php if ($unreviewedAbuse > 0): ?><span class="pill"><?= $unreviewedAbuse ?></span><?php endif; ?></a>
    </nav>
</div>

<section>
    <h2>Anonymous statistics</h2>
    <div class="kpi-grid">
        <div class="kpi"><span class="kpi-num"><?= (int) $anonStats['total_messages'] ?></span><span class="kpi-label">total messages</span></div>
        <div class="kpi"><span class="kpi-num"><?= (int) $anonStats['threads'] ?></span><span class="kpi-label">threads</span></div>
        <div class="kpi"><span class="kpi-num"><?= (int) $anonStats['reviews'] ?></span><span class="kpi-label">reviews (<?= e((string) $anonStats['review_pct']) ?>%)</span></div>
        <div class="kpi"><span class="kpi-num"><?= (int) $anonStats['last_24h'] ?></span><span class="kpi-label">last 24h</span></div>
        <div class="kpi"><span class="kpi-num"><?= (int) $anonStats['last_7d'] ?></span><span class="kpi-label">last 7 days</span></div>
        <div class="kpi"><span class="kpi-num"><?= e((string) $anonStats['avg_thread_size']) ?></span><span class="kpi-label">avg msgs / thread</span></div>
    </div>

    <h3>Activity by hour (last 7 days)</h3>
    <div id="hour-chart" class="chart" data-points='<?= e(json_encode($anonStats['by_hour'])) ?>'></div>

    <h3>Most active groups</h3>
    <ul class="ranked">
    <?php foreach ($anonStats['top_active_groups'] as $g): ?>
        <li>#<?= e($g['name']) ?> <span class="muted">— <?= (int) $g['msg_count'] ?> messages</span></li>
    <?php endforeach; ?>
    </ul>
</section>

<section>
    <h2>Non-anonymous statistics (admin)</h2>

    <?php if ($nonAnonStats['avg_read_seconds'] !== null): ?>
        <p class="muted">Average time to read: <strong><?= e((string) round($nonAnonStats['avg_read_seconds'])) ?>s</strong></p>
    <?php endif; ?>

    <h3>Per user</h3>
    <table class="table">
        <thead><tr><th>Alias</th><th>Real name</th><th>Sent</th><th>Received</th></tr></thead>
        <tbody>
        <?php foreach ($nonAnonStats['per_user'] as $u): ?>
            <tr>
                <td>@<?= e($u['display_alias']) ?></td>
                <td class="muted"><?= e($u['real_name']) ?></td>
                <td><?= (int) $u['sent_count'] ?></td>
                <td><?= (int) $u['received_count'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($nonAnonStats['top_abusers']): ?>
    <h3>Top flagged users</h3>
    <table class="table">
        <thead><tr><th>Alias</th><th>Real name</th><th>Detected abuses</th></tr></thead>
        <tbody>
        <?php foreach ($nonAnonStats['top_abusers'] as $u): ?>
            <tr>
                <td>@<?= e($u['display_alias']) ?></td>
                <td class="muted"><?= e($u['real_name']) ?></td>
                <td><strong><?= (int) $u['abuse_count'] ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<script src="/assets/js/dashboard.js" defer></script>
