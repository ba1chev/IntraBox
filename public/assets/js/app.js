(function () {
    'use strict';

    // Mirror of src/Services/AbuseDetector.php — kept in sync manually.
    const PATTERNS = [
        { name: 'email',                  regex: /[\w.+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/u,             severity: 3 },
        { name: 'phone_bg',               regex: /(?:\+?359|0)\s*(?:\d\s*){8,9}/u,                     severity: 3 },
        { name: 'ssn_egn',                regex: /\b\d{10}\b/u,                                         severity: 3 },
        { name: 'url',                    regex: /\bhttps?:\/\/\S+/iu,                                  severity: 1 },
        { name: 'name_self_disclosure',   regex: /\b(?:казвам\s+се|аз\s+съм|my\s+name\s+is|i\s+am)\s+\S+/iu, severity: 2 },
        { name: 'whitespace_obfuscation', regex: /(?:\b\p{L}\b\s+){3,}\b\p{L}\b/u,                      severity: 2 },
    ];

    const composeForm = document.getElementById('compose-form');
    if (composeForm) {
        const subj = composeForm.querySelector('input[name=subject]');
        const body = composeForm.querySelector('textarea[name=body]');
        const warn = document.getElementById('abuse-warn');

        function scan() {
            const text = (subj.value || '') + '\n' + (body.value || '');
            const hits = [];
            for (const p of PATTERNS) {
                const m = text.match(p.regex);
                if (m) hits.push({ name: p.name, severity: p.severity, sample: m[0] });
            }
            if (!hits.length) {
                warn.classList.add('hidden');
                warn.textContent = '';
                return;
            }
            const max = Math.max(...hits.map(h => h.severity));
            const tag = max >= 3 ? 'This message will be blocked:' : 'This message will be flagged for review:';
            const list = hits.map(h => `${h.name} (“${h.sample}”)`).join(', ');
            warn.textContent = `${tag} ${list}`;
            warn.classList.remove('hidden');
        }

        subj && subj.addEventListener('input', scan);
        body && body.addEventListener('input', scan);
    }

    document.querySelectorAll('form').forEach(f => {
        f.addEventListener('submit', () => {
            const btn = f.querySelector('button[type=submit]');
            if (btn) {
                btn.disabled = true;
                setTimeout(() => { btn.disabled = false; }, 4000);
            }
        });
    });
})();
