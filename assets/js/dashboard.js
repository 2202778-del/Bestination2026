/* ─── Admin Dashboard — Live Polling ─────────────────────────────────────────── */
(function () {
    'use strict';

    let secondsSinceUpdate = 0;
    let searchQuery = '';

    const searchInput = document.getElementById('progressSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchQuery = this.value.toLowerCase().trim();
            filterProgressTable();
        });
    }

    function filterProgressTable() {
        const rows = document.querySelectorAll('#progressBody tr[data-name]');
        rows.forEach(function(row) {
            const name   = (row.dataset.name   || '').toLowerCase();
            const school = (row.dataset.school || '').toLowerCase();
            if (!searchQuery || name.includes(searchQuery) || school.includes(searchQuery)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function fetchDashboardData() {
        fetch(DASHBOARD_API)
            .then(function(r) {
                if (!r.ok) throw new Error('Auth required');
                return r.json();
            })
            .then(function(data) {
                // The API should provide the active booth count. Fallback to TOTAL_BOOTHS constant or 9.
                const totalBooths = data.total_active_booths || (typeof TOTAL_BOOTHS !== 'undefined' ? TOTAL_BOOTHS : 9);
                updateStats(data);
                updateBoothTable(data.per_booth_counts);
                updateRecentCompletions(data.recent_completions);
                updateProgressTable(data.students_progress, totalBooths);
                updateLastUpdated();
                secondsSinceUpdate = 0;
            })
            .catch(function() {
                // Silently fail — will retry
            });
    }

    function updateStats(data) {
        const total     = data.total_registered   || 0;
        const completed = data.total_completed     || 0;
        const progress  = data.in_progress        || 0;
        const notStart  = Math.max(0, total - completed - progress);

        setText('statTotal',     total);
        setText('statCompleted', completed);
        setText('statProgress',  progress);
        setText('statPct', total > 0 ? Math.round((completed / total) * 100) + '%' : '0%');

        // Update "not started" stat card (4th card)
        const cards = document.querySelectorAll('.stat-card .stat-value');
        if (cards[3]) cards[3].textContent = notStart;
    }

    function updateBoothTable(booths) {
        if (!booths || !booths.length) return;
        const totalReg = parseInt(document.getElementById('statTotal')?.textContent || '0', 10);
        const tbody = document.querySelector('#boothTable tbody');
        if (!tbody) return;
        tbody.innerHTML = booths.map(function(b) {
            const pct = totalReg > 0 ? Math.round((b.scan_count / totalReg) * 100) : 0;
            return '<tr>' +
                '<td>' + b.booth_id + '</td>' +
                '<td>' + escHtml(b.name) + '</td>' +
                '<td><strong>' + b.scan_count + '</strong></td>' +
                '<td><div class="mini-bar-wrap"><div class="mini-bar" style="width:' + pct + '%"></div></div></td>' +
            '</tr>';
        }).join('');
    }

    function updateRecentCompletions(completions) {
        const el = document.getElementById('recentCompletions');
        if (!el) return;
        if (!completions || !completions.length) {
            el.innerHTML = '<p class="loading-msg">No completions yet.</p>';
            return;
        }
        el.innerHTML = completions.map(function(c) {
            return '<div class="recent-item">' +
                '<span class="status-badge badge-complete">&#10003;</span>' +
                '<div style="flex:1">' +
                  '<div style="font-weight:600;font-size:0.88rem;">' + escHtml(c.student_name) + '</div>' +
                  '<div style="font-size:0.78rem;color:#888;">' + formatTime(c.completed_at) + '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    function updateProgressTable(students, totalBooths) {
        const tbody = document.getElementById('progressBody');
        if (!tbody || !students) return;
        tbody.innerHTML = students.map(function(s) {
            const completed  = s.completed;
            const scanCount  = s.scan_count;
            let rowClass = completed ? 'row-complete' : (scanCount > 0 ? 'row-progress' : '');
            let pillClass = completed ? 'pill-complete' : (scanCount > 0 ? 'pill-progress' : 'pill-none');
            let badge = completed
                ? '<span class="status-badge badge-complete">&#10003; Complete</span>'
                : (scanCount > 0
                    ? '<span class="status-badge badge-progress">In Progress</span>'
                    : '<span class="status-badge badge-none">Not Started</span>');
            return '<tr class="' + rowClass + '" data-name="' + escAttr(s.full_name) + '" data-school="' + escAttr(s.school) + '">' +
                '<td style="font-weight:600;">' + escHtml(s.full_name) + '</td>' +
                '<td>' + escHtml(s.school) + '</td>' +
                '<td>' + escHtml(s.grade) + '</td>' +
                '<td class="td-center"><span class="booth-pill ' + pillClass + '">' + scanCount + '/' + totalBooths + '</span></td>' +
                '<td>' + badge + '</td>' +
                '<td style="font-size:0.8rem;color:#888;">' + formatTime(s.registered_at) + '</td>' +
            '</tr>';
        }).join('');

        if (searchQuery) filterProgressTable();
    }

    function updateLastUpdated() {
        const el = document.getElementById('lastUpdated');
        if (el) el.textContent = 'Updated ' + new Date().toLocaleTimeString();
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
    function escAttr(str) { return escHtml(str); }

    function formatTime(str) {
        if (!str) return '';
        const d = new Date(str.replace(' ', 'T'));
        return isNaN(d) ? str : d.toLocaleString('en-US', { month:'short', day:'numeric', hour:'numeric', minute:'2-digit' });
    }

    // Poll every 10 seconds
    fetchDashboardData();
    setInterval(fetchDashboardData, 10000);

    // Live "seconds since update" counter
    setInterval(function() {
        secondsSinceUpdate++;
    }, 1000);

})();
