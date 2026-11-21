<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $branch->name }} — Queue Display</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg:       #0b1120;
            --surface:  #111827;
            --card:     #1a2235;
            --border:   #1f2d44;
            --text:     #f1f5f9;
            --muted:    #64748b;
            --dim:      #94a3b8;
            --accent:   #38bdf8;
            --green:    #4ade80;
            --amber:    #fbbf24;
            --purple:   #a78bfa;
            --now-bg:   #0f2347;
            --now-glow: rgba(56,189,248,.15);
        }

        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow: hidden;
        }

        .screen {
            height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto auto;
        }

        /* ── Header ──────────────────────────────────── */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 14px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .live-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--green);
            animation: pulse-ring 2.5s ease-in-out infinite;
        }
        @keyframes pulse-ring {
            0%,100% { box-shadow: 0 0 0 0 rgba(74,222,128,.5); }
            50%      { box-shadow: 0 0 0 8px rgba(74,222,128,0); }
        }
        .header-left  { display: flex; align-items: center; gap: 12px; }
        .branch-name  { font-size: 1.3rem; font-weight: 700; letter-spacing: -0.01em; }
        .header-right { text-align: right; }
        .clock        {
            font-size: 2rem; font-weight: 800; color: var(--accent);
            font-variant-numeric: tabular-nums; line-height: 1;
        }
        .date-line    { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }

        /* ── Main: now-calling + queue ───────────────── */
        .main {
            display: flex;
            overflow: hidden;
            min-height: 0;
        }

        /* ── Now Calling panel ───────────────────────── */
        .calling-panel {
            flex: 1.4;
            display: flex;
            flex-direction: column;
            padding: 32px 44px 28px;
            overflow: hidden;
            background: var(--bg);
        }
        .section-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 16px;
        }

        /* Current call card */
        .now-card {
            background: var(--now-bg);
            border: 1.5px solid rgba(56,189,248,.3);
            border-radius: 20px;
            padding: 32px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
            flex: 1;
            min-height: 0;
            box-shadow: 0 0 60px var(--now-glow);
            transition: box-shadow .4s;
            position: relative;
            overflow: hidden;
        }
        .now-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 50%, rgba(56,189,248,.06) 0%, transparent 70%);
            pointer-events: none;
        }
        .now-card.flash {
            animation: card-flash 0.6s ease-in-out 6;
        }
        @keyframes card-flash {
            0%,100% { background: var(--now-bg); border-color: rgba(56,189,248,.3); box-shadow: 0 0 60px var(--now-glow); }
            50%      { background: #0d2a5e;       border-color: rgba(56,189,248,.9); box-shadow: 0 0 80px rgba(56,189,248,.35); }
        }
        .now-empty {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 12px; height: 100%;
            opacity: 0.2;
        }
        .now-empty-icon { font-size: 3.5rem; }
        .now-empty-text { font-size: 1rem; color: var(--muted); }

        .now-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--accent);
            opacity: 0.8;
        }
        .now-num {
            font-size: 1.3rem;
            font-weight: 900;
            color: var(--accent);
            font-variant-numeric: tabular-nums;
        }
        .now-name {
            font-size: clamp(2.2rem, 4vw, 3.4rem);
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.025em;
            line-height: 1.05;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .now-dest {
            font-size: 1.15rem;
            color: var(--accent);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .now-dest::before {
            content: '→';
            font-size: 1rem;
            opacity: 0.7;
        }

        /* Last called strip */
        .last-called {
            margin-top: 16px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }
        .last-label {
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--muted);
            white-space: nowrap;
        }
        .last-divider {
            width: 1px; height: 28px; background: var(--border); flex-shrink: 0;
        }
        .last-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--dim);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .last-dest {
            font-size: 0.85rem;
            color: var(--muted);
            margin-left: auto;
            flex-shrink: 0;
        }
        .last-empty {
            font-size: 0.85rem;
            color: var(--muted);
            opacity: 0.5;
        }

        /* ── Queue panel ─────────────────────────────── */
        .queue-panel {
            width: 320px;
            flex-shrink: 0;
            background: var(--surface);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .queue-header {
            padding: 20px 20px 14px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
        }
        .queue-title {
            font-size: 0.65rem; font-weight: 700;
            letter-spacing: 0.18em; text-transform: uppercase; color: var(--muted);
        }
        .queue-count {
            font-size: 1.5rem; font-weight: 800; color: var(--text);
        }
        .queue-count span { font-size: 0.8rem; font-weight: 400; color: var(--muted); margin-left: 4px; }

        .queue-list {
            flex: 1; overflow-y: auto; padding: 4px 0;
        }
        .queue-list::-webkit-scrollbar { width: 3px; }
        .queue-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .q-row {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 20px;
            border-bottom: 1px solid rgba(31,45,68,.8);
        }
        .q-row:last-child { border-bottom: none; }
        .q-num {
            font-size: 0.95rem; font-weight: 800;
            color: var(--accent); font-variant-numeric: tabular-nums;
            min-width: 32px;
        }
        .q-name {
            font-size: 0.9rem; font-weight: 500; color: var(--text);
            flex: 1; min-width: 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .q-badge {
            font-size: 0.6rem; font-weight: 800;
            letter-spacing: 0.05em;
            border-radius: 5px;
            padding: 3px 7px;
            flex-shrink: 0;
        }
        .q-badge.N { background: rgba(167,139,250,.15); color: var(--purple); border: 1px solid rgba(167,139,250,.3); }
        .q-badge.D { background: rgba(56,189,248,.12); color: var(--accent); border: 1px solid rgba(56,189,248,.3); }
        .q-priority {
            font-size: 0.55rem; font-weight: 800; letter-spacing: 0.08em;
            text-transform: uppercase; color: var(--amber);
            background: rgba(251,191,36,.1); border: 1px solid rgba(251,191,36,.25);
            border-radius: 4px; padding: 2px 5px; flex-shrink: 0;
        }
        .q-empty {
            padding: 40px 20px; text-align: center;
            opacity: 0.25;
        }
        .q-empty-icon { font-size: 2rem; }
        .q-empty-text { font-size: 0.8rem; color: var(--muted); margin-top: 8px; }

        /* ── News ticker ─────────────────────────────── */
        .ticker-bar {
            background: var(--card);
            border-top: 1px solid var(--border);
            padding: 0;
            height: 36px;
            display: flex;
            align-items: stretch;
            overflow: hidden;
            flex-shrink: 0;
        }
        .ticker-label {
            background: var(--accent);
            color: #0b1120;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0 16px;
            display: flex;
            align-items: center;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .ticker-track {
            flex: 1;
            overflow: hidden;
            display: flex;
            align-items: center;
            position: relative;
        }
        .ticker-inner {
            display: flex;
            align-items: center;
            gap: 0;
            white-space: nowrap;
            will-change: transform;
        }
        .ticker-item {
            font-size: 0.75rem;
            color: var(--dim);
            padding: 0 48px 0 0;
        }
        .ticker-item::before {
            content: '●';
            color: var(--accent);
            margin-right: 10px;
            font-size: 0.5rem;
            vertical-align: middle;
        }

        /* ── Footer ──────────────────────────────────── */
        .footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 8px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .consult-stat {
            display: flex; align-items: center; gap: 7px;
            font-size: 0.75rem; color: var(--green); font-weight: 600;
        }
        .consult-stat span.dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--green);
        }
        .updated-at { font-size: 0.72rem; color: var(--muted); }

        /* ── Ad placeholder ──────────────────────────── */
        /* Right side of calling panel bottom area — reserved for future ads.
           Currently hidden; set display:flex to activate. */
        .ad-slot {
            display: none;
            width: 200px; height: 120px; flex-shrink: 0;
            border: 1px dashed var(--border);
            border-radius: 10px;
            align-items: center; justify-content: center;
            font-size: 0.65rem; color: var(--muted);
            letter-spacing: 0.1em; text-transform: uppercase;
        }
    </style>
</head>
<body>
<div class="screen">

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <div class="live-dot"></div>
            <div class="branch-name" id="branch-name">{{ $branch->name }}</div>
        </div>
        <div class="header-right">
            <div class="clock" id="clock">--:--</div>
            <div class="date-line" id="date-line">Loading…</div>
        </div>
    </header>

    <!-- Main -->
    <div class="main">

        <!-- Now Calling -->
        <div class="calling-panel">
            <div class="section-label">Now Calling</div>

            <div class="now-card" id="now-card">
                <div class="now-empty" id="now-empty">
                    <div class="now-empty-icon">🔔</div>
                    <div class="now-empty-text">Waiting for first call…</div>
                </div>
                <div id="now-content" style="display:none; flex-direction:column; gap:8px;">
                    <div class="now-label">Now Calling</div>
                    <div class="now-num" id="now-num"></div>
                    <div class="now-name" id="now-name"></div>
                    <div class="now-dest" id="now-dest"></div>
                </div>
            </div>

            <div class="last-called" id="last-called-row">
                <div class="last-label">Last Called</div>
                <div class="last-divider"></div>
                <span class="last-empty" id="last-empty">—</span>
                <div id="last-content" style="display:none; align-items:center; gap:14px; flex:1; min-width:0;">
                    <div class="last-name" id="last-name"></div>
                    <div class="last-dest" id="last-dest"></div>
                </div>
            </div>
        </div>

        <!-- Queue -->
        <div class="queue-panel">
            <div class="queue-header">
                <div>
                    <div class="queue-title">In Queue</div>
                    <div class="queue-count" id="q-count">0 <span>waiting</span></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:.6rem;color:var(--muted);letter-spacing:.1em;text-transform:uppercase;margin-bottom:2px;">In Consult</div>
                    <div id="in-consult" style="font-size:1.3rem;font-weight:800;color:var(--green);">0</div>
                </div>
            </div>
            <div class="queue-list" id="q-list">
                <div class="q-empty">
                    <div class="q-empty-icon">🏥</div>
                    <div class="q-empty-text">No patients waiting</div>
                </div>
            </div>
        </div>

    </div>

    <!-- News Ticker -->
    <div class="ticker-bar">
        <div class="ticker-label">TT News</div>
        <div class="ticker-track">
            <div class="ticker-inner" id="ticker-inner">
                <span class="ticker-item" style="opacity:.4;">Loading news…</span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="consult-stat">
            <span class="dot"></span>
            <span id="footer-consult">0</span> in consultation
        </div>
        <div class="updated-at" id="updated-at">Connecting…</div>
    </footer>

</div>

<script>
(function () {
    const DATA_URL  = '{{ route("tenant.queue.screen.data", $branch->screen_code ?? $branch->id) }}';
    const NEWS_URL  = '{{ route("tenant.queue.screen.news", $branch->screen_code ?? $branch->id) }}';
    const POLL_MS   = 6000;
    const NEWS_MS   = 300000; // refresh news every 5 minutes

    let knownNowIds  = new Set();
    let tickerAnim   = null;
    let tickerItems  = [];

    // ── Clock ──────────────────────────────────────────────────
    (function clock() {
        function tick() {
            const n    = new Date();
            let   h    = n.getHours();
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            document.getElementById('clock').textContent =
                h + ':' + String(n.getMinutes()).padStart(2,'0') + ' ' + ampm;
        }
        tick();
        setInterval(tick, 1000);
    })();

    // ── Queue data ─────────────────────────────────────────────
    async function refresh() {
        try {
            const r    = await fetch(DATA_URL, { cache: 'no-store' });
            const data = await r.json();

            document.getElementById('branch-name').textContent = data.branch;
            document.getElementById('date-line').textContent   = data.date;
            document.getElementById('updated-at').textContent  = 'Updated ' + data.time;
            document.getElementById('in-consult').textContent  = data.in_consultation;
            document.getElementById('footer-consult').textContent = data.in_consultation;

            renderNow(data.calling_now);
            renderLast(data.last_called);
            renderQueue(data.waiting);
        } catch {
            document.getElementById('updated-at').textContent = 'Connection error — retrying…';
        }
    }

    function renderNow(entries) {
        const card    = document.getElementById('now-card');
        const empty   = document.getElementById('now-empty');
        const content = document.getElementById('now-content');

        if (!entries || entries.length === 0) {
            knownNowIds.clear();
            empty.style.display   = 'flex';
            content.style.display = 'none';
            card.classList.remove('flash');
            return;
        }

        const top    = entries[0];
        const isNew  = !knownNowIds.has(top.id);

        document.getElementById('now-num').textContent  = '#' + top.queue_number;
        document.getElementById('now-name').textContent = top.name;
        document.getElementById('now-dest').textContent = top.destination;

        empty.style.display   = 'none';
        content.style.display = 'flex';

        if (isNew) {
            card.classList.remove('flash');
            void card.offsetWidth; // reflow to restart animation
            card.classList.add('flash');
        }

        knownNowIds = new Set(entries.map(e => e.id));
    }

    function renderLast(entry) {
        const empty   = document.getElementById('last-empty');
        const content = document.getElementById('last-content');

        if (!entry) {
            empty.style.display   = 'inline';
            content.style.display = 'none';
            return;
        }

        document.getElementById('last-name').textContent = entry.name;
        document.getElementById('last-dest').textContent = '→ ' + entry.destination;
        empty.style.display   = 'none';
        content.style.display = 'flex';
    }

    function renderQueue(entries) {
        const countEl = document.getElementById('q-count');
        const listEl  = document.getElementById('q-list');

        if (!entries || entries.length === 0) {
            countEl.innerHTML = '0 <span>waiting</span>';
            listEl.innerHTML  = '<div class="q-empty"><div class="q-empty-icon">🏥</div><div class="q-empty-text">No patients waiting</div></div>';
            return;
        }

        countEl.innerHTML = entries.length + ' <span>waiting</span>';
        listEl.innerHTML  = entries.map(e => `
            <div class="q-row">
                <div class="q-num">#${e.queue_number}</div>
                <div class="q-name">${esc(e.name)}</div>
                ${e.is_priority ? '<span class="q-priority">Priority</span>' : ''}
                <span class="q-badge ${e.badge}">${e.badge}</span>
            </div>`).join('');
    }

    // ── News ticker ────────────────────────────────────────────
    async function loadNews() {
        try {
            const r    = await fetch(NEWS_URL, { cache: 'no-store' });
            const data = await r.json();
            if (data.items && data.items.length) {
                tickerItems = data.items;
                startTicker();
            }
        } catch { /* silent */ }
    }

    function startTicker() {
        if (!tickerItems.length) return;

        const inner = document.getElementById('ticker-inner');
        // Duplicate items so the scroll loops seamlessly
        const html = [...tickerItems, ...tickerItems]
            .map(t => `<span class="ticker-item">${esc(t)}</span>`)
            .join('');
        inner.innerHTML = html;

        if (tickerAnim) tickerAnim.cancel();

        // Measure total width of one set of items
        const track    = inner.parentElement;
        const fullW    = inner.scrollWidth / 2;
        const duration = fullW / 60; // ~60px/s

        tickerAnim = inner.animate(
            [{ transform: 'translateX(0)' }, { transform: `translateX(-${fullW}px)` }],
            { duration: duration * 1000, iterations: Infinity, easing: 'linear' }
        );
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ── Boot ───────────────────────────────────────────────────
    refresh();
    setInterval(refresh, POLL_MS);
    loadNews();
    setInterval(loadNews, NEWS_MS);
})();
</script>
</body>
</html>
