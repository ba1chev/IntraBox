<?php
/** @var list<array{id: int, display_alias: string}> $users */
/** @var list<array<string, mixed>> $groups */
/** @var list<array<string, mixed>> $visibleRules */
/** @var int|null $replyTo */
/** @var int|null $replyTarget */
/** @var string|null $parentSubject */

use App\Services\RuleEngine;

$prefilledSubject = $parentSubject ? 'Re: ' . $parentSubject : '';
$prefilledTarget  = $replyTarget !== null ? 'user:' . $replyTarget : '';
?>
<h1><?= $replyTo ? 'Reply' : 'New message' ?></h1>

<?php if ($visibleRules !== []): ?>
<details class="rules-callout" open>
    <summary>📋 Active usage rules (<?= count($visibleRules) ?>)</summary>
    <ul>
    <?php foreach ($visibleRules as $r): ?>
        <li>
            <strong><?= e($r['name']) ?></strong>
            — <?= $r['is_allow'] ? 'allowed' : 'forbidden' ?>:
            <?= e(RuleEngine::weekdayMaskToString((int) $r['weekday_mask'])) ?>,
            <?= e(substr((string) $r['time_from'], 0, 5)) ?>–<?= e(substr((string) $r['time_to'], 0, 5)) ?>
            <?php if ($r['description']): ?><br><small class="muted"><?= e($r['description']) ?></small><?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
</details>
<?php endif; ?>

<form method="post" action="/compose" class="form" id="compose-form">
    <?= App\Core\Csrf::field() ?>
    <?php if ($replyTo): ?>
        <input type="hidden" name="parent_id" value="<?= (int) $replyTo ?>">
    <?php endif; ?>

    <label>
        <span>Recipient</span>
        <select name="target" required>
            <option value="">— select —</option>
            <optgroup label="Users">
                <?php foreach ($users as $u): ?>
                    <option value="user:<?= (int) $u['id'] ?>"
                        <?= $prefilledTarget === 'user:' . $u['id'] ? 'selected' : '' ?>>
                        @<?= e($u['display_alias']) ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
            <?php if ($groups !== []): ?>
            <optgroup label="Groups">
                <?php foreach ($groups as $g): ?>
                    <option value="group:<?= (int) $g['id'] ?>">#<?= e($g['name']) ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
        </select>
    </label>

    <label>
        <span>Subject</span>
        <input type="text" name="subject" value="<?= e($prefilledSubject) ?>" required maxlength="255">
    </label>

    <label>
        <span>Body</span>
        <textarea name="body" rows="10" required></textarea>
    </label>

    <label class="checkbox-row">
        <input type="checkbox" name="is_review" value="1">
        <span>This is a <strong>review</strong></span>
    </label>

    <div id="abuse-warn" class="warn hidden"></div>

    <button type="submit" class="btn-primary">Send</button>
    <a href="/inbox" class="btn-link">Cancel</a>
</form>
