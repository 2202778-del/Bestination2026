/* ─── Passport Page — Live Progress Polling ───────────────────────────────────── */
(function () {
    'use strict';

    let lastScanCount = parseInt(document.getElementById('progressCount')?.textContent || '0', 10);
    let isCompleted   = document.querySelector('.completion-banner') !== null;

    function updateUI(data) {
        const count = data.scan_count;
        const total = TOTAL_BOOTHS;

        // Update progress count
        const el = document.getElementById('progressCount');
        if (el) el.textContent = count + ' / ' + total;

        // Update progress bar
        const bar = document.getElementById('progressBar');
        if (bar) bar.style.width = Math.round((count / total) * 100) + '%';

        // Update booth grid cells
        (data.visited_booths || []).forEach(function (bid) {
            const card = document.querySelector('.booth-card[data-booth-id="' + bid + '"]');
            if (card && !card.classList.contains('visited')) {
                card.classList.remove('pending');
                card.classList.add('visited');
                const stamp = card.querySelector('.booth-stamp');
                if (stamp) stamp.innerHTML = '<span class="stamp-check">&#10003;</span>';
            }
        });

        // Show completion banner if newly completed
        if (data.completed && !isCompleted) {
            isCompleted = true;
            showCompletionBanner();
        }

        lastScanCount = count;

        // Update footer note
        const note = document.getElementById('updateNotice');
        if (note) note.textContent = 'Updated ' + new Date().toLocaleTimeString();
    }

    function showCompletionBanner() {
        // Remove any existing banner
        const existing = document.querySelector('.completion-banner');
        if (existing) return;

        const header = document.querySelector('.passport-header');
        if (header) {
            header.classList.add('completed');
        }

        const certUrl = PROGRESS_API.replace('/api/progress.php', '/certificate.php');
        const banner = document.createElement('div');
        banner.className = 'completion-banner';
        banner.innerHTML = `
            <div class="icon-flat flat-gold flat-lg">🎉</div>
            <h3>Mission Complete!</h3>
            <p>You have visited all ${TOTAL_BOOTHS} booths!</p>
            <a href="${certUrl}?token=${PASSPORT_TOKEN}" target="_blank" class="btn btn-primary btn-sm" style="margin-top: 16px;">
                <div class="icon icon-sm icon-download"></div> Download Certificate
            </a>
        `;

        const qrCard = document.querySelector('.qr-card');
        if (qrCard) {
            qrCard.parentNode.insertBefore(banner, qrCard);
        }
    }

    function fetchProgress() {
        if (!PASSPORT_TOKEN) return;
        fetch(PROGRESS_API + '?token=' + encodeURIComponent(PASSPORT_TOKEN))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.error) {
                    updateUI(data);
                }
            })
            .catch(function() {
                // Silently ignore network errors
            });
    }

    // Poll every 15 seconds
    setInterval(fetchProgress, 15000);

})();
