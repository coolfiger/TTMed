<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $branch->name }} — Queue Display</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg:        #0f172a;
            --surface:   #1e293b;
            --border:    #334155;
            --text:      #f1f5f9;
            --muted:     #94a3b8;
            --accent:    #38bdf8;
            --green:     #4ade80;
            --amber:     #fbbf24;
            --red:       #f87171;
            --pulse-bg:  #1d4ed8;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── Header ─────────────────────────────────────── */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 18px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .header-left { display: flex; align-items: center; gap: 16px; }
        .header-dot {
            width: 12px; height: 12px; border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 0 4px rgba(74,222,128,.25);
            animation: live-pulse 2s ease-in-out infinite;
        }
        @keyframes live-pulse {
            0%, 100% { box-shadow: 0 0 0 4px rgba(74,222,128,.25); }
            50%       { box-shadow: 0 0 0 8px rgba(74,222,128,.08); }
        }
        .branch-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
        }
        .header-right { text-align: right; }
        .clock {
            font-size: 2.2rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: var(--accent);
            letter-spacing: 0.02em;
            line-height: 1;
        }
        .date-str { font-size: 0.85rem; color: var(--muted); margin-top: 2px; }

        /* ── Main layout ─────────────────────────────────── */
        .main {
            flex: 1;
            display: flex;
            min-h-0: 0;
            overflow: hidden;
            gap: 2px;
        }

        /* ── Now Calling panel ───────────────────────────── */
        .calling-panel {
            flex: 2;
            display: flex;
            flex-direction: column;
            padding: 40px 48px;
            overflow: hidden;
        }
        .panel-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 24px;
        }
        .calling-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            opacity: 0.3;
        }
        .calling-empty-icon { font-size: 4rem; }
        .calling-empty-text { font-size: 1.1rem; color: var(--muted); }

        .calling-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            flex: 1;
            overflow: hidden;
            justify-content: center;
        }
        .calling-card {
            background: var(--pulse-bg);
            border: 2px solid rgba(56,189,248,.4);
            border-radius: 20px;
            padding: 28px 36px;
            display: flex;
            align-items: center;
            gap: 28px;
            animation: call-in 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        .calling-card.flash {
            animation: call-in 0.4s cubic-bezier(0.34,1.56,0.64,1),
                       name-flash 0.7s ease-in-out 0.4s 6;
        }
        @keyframes call-in {
            from { opacity: 0; transform: scale(0.92) translateY(12px); }
            to   { opacity: 1; transform: scale(1)    translateY(0);    }
        }
        @keyframes name-flash {
            0%, 100% { background: var(--pulse-bg); border-color: rgba(56,189,248,.4); }
            50%       { background: #1e40af;         border-color: rgba(56,189,248,.9); }
        }
        .calling-number {
            font-size: 3rem;
            font-weight: 900;
            color: var(--accent);
            font-variant-numeric: tabular-nums;
            line-height: 1;
            min-width: 80px;
            text-align: center;
        }
        .calling-info { flex: 1; min-width: 0; }
        .calling-name {
            font-size: 2.4rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .calling-dest {
            font-size: 1rem;
            color: var(--accent);
            margin-top: 4px;
            font-weight: 500;
        }
        .calling-arrow {
            font-size: 2rem;
            color: rgba(56,189,248,.6);
            animation: arrow-bounce 1s ease-in-out infinite;
        }
        @keyframes arrow-bounce {
            0%, 100% { transform: translateX(0); }
            50%       { transform: translateX(8px); }
        }

        /* ── Waiting queue panel ─────────────────────────── */
        .waiting-panel {
            width: 360px;
            flex-shrink: 0;
            background: var(--surface);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .waiting-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .waiting-title {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .waiting-count {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1.1;
            margin-top: 4px;
        }
        .waiting-count span { font-size: 1rem; font-weight: 400; color: var(--muted); margin-left: 6px; }

        .waiting-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }
        .waiting-list::-webkit-scrollbar { width: 4px; }
        .waiting-list::-webkit-scrollbar-track { background: transparent; }
        .waiting-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .queue-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 24px;
            border-bottom: 1px solid rgba(51,65,85,.5);
            transition: background 0.2s;
        }
        .queue-row:last-child { border-bottom: none; }
        .queue-num {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--accent);
            font-variant-numeric: tabular-nums;
            min-width: 36px;
        }
        .queue-name {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text);
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .priority-badge {
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--amber);
            background: rgba(251,191,36,.12);
            border: 1px solid rgba(251,191,36,.3);
            border-radius: 4px;
            padding: 2px 6px;
            flex-shrink: 0;
        }

        .waiting-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0.3;
            padding: 40px 24px;
        }
        .waiting-empty-icon { font-size: 2.5rem; }
        .waiting-empty-text { font-size: 0.9rem; color: var(--muted); text-align: center; }

        /* ── Footer ─────────────────────────────────────── */
        .footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 10px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .footer-left { font-size: 0.75rem; color: var(--muted); }
        .footer-right { font-size: 0.75rem; color: var(--muted); }
        .in-consultation {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--green);
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="header-dot"></div>
            <div class="branch-name" id="branch-name">{{ $branch->name }}</div>
        </div>
        <div class="header-right">
            <div class="clock" id="clock">--:--</div>
            <div class="date-str" id="date-str">Loading…</div>
        </div>
    </div>

    <!-- Main -->
    <div class="main">

        <!-- Now Calling -->
        <div class="calling-panel">
            <div class="panel-label">Now Calling</div>
            <div id="calling-list" class="calling-list">
                <div class="calling-empty">
                    <div class="calling-empty-icon">🔔</div>
                    <div class="calling-empty-text">Waiting for patients to be called…</div>
                </div>
            </div>
        </div>

        <!-- Queue -->
        <div class="waiting-panel">
            <div class="waiting-header">
                <div class="waiting-title">In Queue</div>
                <div class="waiting-count" id="waiting-count">
                    0 <span>waiting</span>
                </div>
            </div>
            <div id="waiting-list" class="waiting-list">
                <div class="waiting-empty">
                    <div class="waiting-empty-icon">🏥</div>
                    <div class="waiting-empty-text">No patients waiting right now</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-left">
            <span class="in-consultation">
                <span>●</span>
                <span id="in-consultation-count">0</span> in consultation
            </span>
        </div>
        <div class="footer-right" id="last-updated">Connecting…</div>
    </div>

    <script>
    (function () {
        const DATA_URL    = '{{ route("tenant.queue.screen.data", $branch->screen_code ?? $branch->id) }}';
        const POLL_MS     = 6000;
        const FLASH_SECS  = 90;

        let knownCallingIds = new Set();
        let clockTimer;

        // ── Live clock ──────────────────────────────────────────────
        function startClock() {
            function tick() {
                const now = new Date();
                document.getElementById('clock').textContent =
                    now.getHours().toString().padStart(2, '0') + ':' +
                    now.getMinutes().toString().padStart(2, '0');
            }
            tick();
            clockTimer = setInterval(tick, 1000);
        }

        // ── Fetch and render ────────────────────────────────────────
        async function refresh() {
            try {
                const res  = await fetch(DATA_URL, { cache: 'no-store' });
                const data = await res.json();

                document.getElementById('date-str').textContent         = data.date;
                document.getElementById('branch-name').textContent      = data.branch;
                document.getElementById('in-consultation-count').textContent = data.in_consultation;
                document.getElementById('last-updated').textContent =
                    'Updated ' + data.time;

                renderCalling(data.calling);
                renderWaiting(data.waiting);
            } catch (e) {
                document.getElementById('last-updated').textContent = 'Connection error — retrying…';
            }
        }

        function renderCalling(entries) {
            const container = document.getElementById('calling-list');
            if (!entries || entries.length === 0) {
                knownCallingIds.clear();
                container.innerHTML = `
                    <div class="calling-empty">
                        <div class="calling-empty-icon">🔔</div>
                        <div class="calling-empty-text">Waiting for patients to be called…</div>
                    </div>`;
                return;
            }

            const newIds = new Set(entries.map(e => e.id));

            container.innerHTML = entries.map(entry => {
                const isNew   = !knownCallingIds.has(entry.id);
                const classes = 'calling-card' + (isNew ? ' flash' : '');
                return `
                <div class="${classes}" data-id="${entry.id}">
                    <div class="calling-number">#${entry.queue_number}</div>
                    <div class="calling-info">
                        <div class="calling-name">${escHtml(entry.name)}</div>
                        <div class="calling-dest">→ ${escHtml(entry.destination)}</div>
                    </div>
                    <div class="calling-arrow">→</div>
                </div>`;
            }).join('');

            knownCallingIds = newIds;
        }

        function renderWaiting(entries) {
            const countEl = document.getElementById('waiting-count');
            const listEl  = document.getElementById('waiting-list');

            if (!entries || entries.length === 0) {
                countEl.innerHTML = '0 <span>waiting</span>';
                listEl.innerHTML  = `
                    <div class="waiting-empty">
                        <div class="waiting-empty-icon">🏥</div>
                        <div class="waiting-empty-text">No patients waiting right now</div>
                    </div>`;
                return;
            }

            countEl.innerHTML = `${entries.length} <span>waiting</span>`;
            listEl.innerHTML  = entries.map(e => `
                <div class="queue-row">
                    <div class="queue-num">#${e.queue_number}</div>
                    <div class="queue-name">${escHtml(e.name)}</div>
                    ${e.is_priority ? '<span class="priority-badge">Priority</span>' : ''}
                </div>`).join('');
        }

        function escHtml(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // ── Boot ────────────────────────────────────────────────────
        startClock();
        refresh();
        setInterval(refresh, POLL_MS);
    })();
    </script>

</body>
</html>
