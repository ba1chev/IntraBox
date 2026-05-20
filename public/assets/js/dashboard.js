// Renders a tiny inline SVG bar chart of "messages by hour-of-day".
// No external chart libraries — this project uses vanilla JS only.

(function () {
    const el = document.getElementById('hour-chart');
    if (!el) return;
    let points;
    try {
        points = JSON.parse(el.dataset.points || '[]');
    } catch (_) { return; }

    const buckets = new Array(24).fill(0);
    points.forEach(p => { buckets[p.hour] = parseInt(p.n, 10) || 0; });
    const max = Math.max(1, ...buckets);

    const W = 720, H = 140, P = 24;
    const bw = (W - P * 2) / 24;
    let bars = '';
    for (let h = 0; h < 24; h++) {
        const v = buckets[h];
        const bh = ((H - P * 2) * v) / max;
        const x = P + h * bw;
        const y = H - P - bh;
        bars += `<rect x="${x + 1}" y="${y}" width="${bw - 2}" height="${bh}" rx="2" fill="#3b59f7" opacity="${0.4 + 0.6 * (v / max)}"/>`;
        if (h % 3 === 0) {
            bars += `<text x="${x + bw / 2}" y="${H - 6}" text-anchor="middle" font-size="10" fill="#6b7280">${h}h</text>`;
        }
    }
    el.innerHTML =
        `<svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="xMidYMid meet" style="width:100%;height:auto;">` +
        `<line x1="${P}" y1="${H-P}" x2="${W-P}" y2="${H-P}" stroke="#e2e6ee"/>` +
        bars + '</svg>';
})();
