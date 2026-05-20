<?php /** @var list<array<string, mixed>> $groups */ ?>
<div class="page-header">
    <h1>Groups</h1>
    <a href="/groups/new" class="btn-primary">+ New group</a>
</div>

<?php if ($groups === []): ?>
    <p class="empty">No groups have been created yet.</p>
<?php else: ?>
<table class="table">
    <thead>
    <tr><th>Name</th><th>Description</th><th>Members</th><th>Created by</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($groups as $g): ?>
        <tr>
            <td>#<?= e($g['name']) ?></td>
            <td><?= e($g['description']) ?></td>
            <td><?= (int) $g['member_count'] ?></td>
            <td><?= e($g['creator_alias']) ?></td>
            <td><a href="/groups/<?= (int) $g['id'] ?>" class="link-small">manage</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
