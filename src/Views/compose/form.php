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
    <summary>Active usage rules (<?= count($visibleRules) ?>)</summary>
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

    <div class="to-field">
        <span class="to-label">To</span>
        <div class="recipient-wrap">
            <div class="recipient-tags" id="recipient-tags">                <?php if ($prefilledTarget !== ''): ?>
                    <?php foreach ($users as $u): ?>
                        <?php if ($prefilledTarget === 'user:' . $u['id']): ?>
                            <span class="recipient-tag" data-value="user:<?= (int) $u['id'] ?>">
                                <span class="tag-avatar" data-initial="<?= e(mb_strtoupper(mb_substr($u['display_alias'], 0, 1))) ?>">
                                    <?= e(mb_strtoupper(mb_substr($u['display_alias'], 0, 1))) ?>
                                </span>
                                <span class="tag-label">@<?= e($u['display_alias']) ?></span>
                                <button type="button" class="tag-remove" aria-label="Remove">×</button>
                                <input type="hidden" name="targets[]" value="user:<?= (int) $u['id'] ?>">
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <input type="text" id="recipient-search" class="recipient-search"
                       autocomplete="off" placeholder="Type a name or alias…">
            </div>
            <div id="recipient-list" class="recipient-list hidden"></div>
        </div>
    </div>

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

<script>
(function () {
    const RECIPIENTS = <?= json_encode(
        array_merge(
            array_map(fn($u) => [
                'value'   => 'user:' . $u['id'],
                'label'   => '@' . $u['display_alias'],
                'initial' => mb_strtoupper(mb_substr($u['display_alias'], 0, 1)),
                'type'    => 'user',
            ], $users),
            array_map(fn($g) => [
                'value'   => 'group:' . $g['id'],
                'label'   => '#' . $g['name'],
                'initial' => mb_strtoupper(mb_substr($g['name'], 0, 1)),
                'type'    => 'group',
                'meta'    => (int)$g['member_count'] . ' member' . ((int)$g['member_count'] !== 1 ? 's' : ''),
            ], $groups)
        ),
        JSON_HEX_TAG | JSON_HEX_AMP
    ) ?>;

    // Deterministic colour from initial letter
    const AVATAR_COLORS = ['#3b59f7','#8a2bbf','#1f8a4d','#c87f00','#d23a3a','#0891b2','#b45309','#6d28d9'];
    function avatarColor(initial) {
        return AVATAR_COLORS[(initial.charCodeAt(0) || 0) % AVATAR_COLORS.length];
    }

    const tags   = document.getElementById('recipient-tags');
    const search = document.getElementById('recipient-search');
    const list   = document.getElementById('recipient-list');
    let activeIdx = -1;
    let currentItems = [];

    // Colour pre-seeded server-side tags
    tags.querySelectorAll('.tag-avatar[data-initial]').forEach(el => {
        el.style.background = avatarColor(el.dataset.initial);
    });

    function selectedValues() {
        return [...tags.querySelectorAll('.recipient-tag')].map(t => t.dataset.value);
    }

    function addTag(item) {
        if (selectedValues().includes(item.value)) return;

        const avatar = document.createElement('span');
        avatar.className = 'tag-avatar';
        avatar.textContent = item.initial;
        avatar.style.background = item.type === 'group' ? '#6d28d9' : avatarColor(item.initial);

        const lbl = document.createElement('span');
        lbl.className = 'tag-label';
        lbl.textContent = item.label;

        const rm = document.createElement('button');
        rm.type = 'button';
        rm.className = 'tag-remove';
        rm.setAttribute('aria-label', 'Remove');
        rm.textContent = '×';

        const hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = 'targets[]';
        hidden.value = item.value;

        const span = document.createElement('span');
        span.className     = 'recipient-tag';
        span.dataset.value = item.value;
        span.appendChild(avatar);
        span.appendChild(lbl);
        span.appendChild(rm);
        span.appendChild(hidden);

        rm.addEventListener('click', () => span.remove());
        tags.insertBefore(span, search);
        search.value = '';
        closeList();
        search.focus();
    }

    function closeList() {
        list.classList.add('hidden');
        list.innerHTML = '';
        activeIdx = -1;
        currentItems = [];
    }

    function renderList(q) {
        const matches = RECIPIENTS.filter(r => r.label.toLowerCase().includes(q));
        const users  = matches.filter(r => r.type === 'user').slice(0, 6);
        const groups = matches.filter(r => r.type === 'group').slice(0, 4);
        currentItems = [...users, ...groups];

        list.innerHTML = '';
        if (!currentItems.length) { closeList(); return; }

        const sel = selectedValues();

        function makeSection(title, items, offset) {
            if (!items.length) return;
            const hdr = document.createElement('div');
            hdr.className = 'sugg-header';
            hdr.textContent = title;
            list.appendChild(hdr);

            items.forEach((item, i) => {
                const av = document.createElement('span');
                av.className = 'sugg-avatar';
                av.textContent = item.initial;
                av.style.background = item.type === 'group' ? '#6d28d9' : avatarColor(item.initial);

                const info = document.createElement('span');
                info.className = 'sugg-info';

                const name = document.createElement('span');
                name.className = 'sugg-name';
                name.textContent = item.label;

                info.appendChild(name);

                if (item.meta) {
                    const meta = document.createElement('span');
                    meta.className = 'sugg-meta';
                    meta.textContent = item.meta;
                    info.appendChild(meta);
                }

                const row = document.createElement('div');
                row.className = 'sugg-row';
                row.dataset.idx = offset + i;
                if (sel.includes(item.value)) row.classList.add('already-selected');
                row.appendChild(av);
                row.appendChild(info);
                row.addEventListener('mousedown', e => { e.preventDefault(); addTag(item); });
                row.addEventListener('mouseover', () => setActive(offset + i));
                list.appendChild(row);
            });
        }

        makeSection('People', users, 0);
        makeSection('Groups', groups, users.length);

        activeIdx = -1;
        list.classList.remove('hidden');
    }

    function setActive(i) {
        list.querySelectorAll('.sugg-row').forEach(el => el.classList.remove('active'));
        activeIdx = i;
        const row = list.querySelector(`.sugg-row[data-idx="${i}"]`);
        if (row) { row.classList.add('active'); row.scrollIntoView({ block: 'nearest' }); }
    }

    search.addEventListener('input', () => {
        search.setCustomValidity('');
        const q = search.value.trim().toLowerCase();
        if (!q) { closeList(); return; }
        renderList(q);
    });

    search.addEventListener('keydown', e => {
        const count = currentItems.length;
        if (e.key === 'ArrowDown') {
            e.preventDefault(); setActive(Math.min(activeIdx + 1, count - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault(); setActive(Math.max(activeIdx - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIdx >= 0 && currentItems[activeIdx]) addTag(currentItems[activeIdx]);
        } else if (e.key === 'Backspace' && search.value === '') {
            [...tags.querySelectorAll('.recipient-tag')].pop()?.remove();
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    document.addEventListener('click', e => {
        if (!tags.contains(e.target) && !list.contains(e.target)) closeList();
    });

    tags.addEventListener('click', e => {
        if (!e.target.closest('.tag-remove')) search.focus();
    });

    search.closest('form').addEventListener('submit', e => {
        if (selectedValues().length === 0) {
            e.preventDefault();
            search.setCustomValidity('Please add at least one recipient.');
            search.reportValidity();
        }
    });
})();
</script>
