/* ─── Booth Scanner ───────────────────────────────────────────────────────────── */
(function () {
    'use strict';

    let sessionCount = 0;
    let isProcessing = false;
    let resultTimeout = null;
    let scanner = null;

    const resultEl   = document.getElementById('scanResult');
    const iconEl     = document.getElementById('resultIcon');
    const titleEl    = document.getElementById('resultTitle');
    const nameEl     = document.getElementById('resultName');
    const detailEl   = document.getElementById('resultDetail');
    const progressEl = document.getElementById('resultProgress');
    const countEl    = document.getElementById('sessionCount');
    const scannerWrap = document.getElementById('scannerWrap');

    function showResult(type, icon, title, name, detail, progress) {
        resultEl.className = 'scan-result result-' + type;
        iconEl.innerHTML = icon;
        titleEl.textContent = title;
        nameEl.textContent = name || '';
        detailEl.textContent = detail || '';
        progressEl.textContent = progress || '';
        resultEl.classList.remove('hidden');
        // document.body.classList.add('modal-open'); // No longer using a modal backdrop

        if (resultTimeout) clearTimeout(resultTimeout);

        const delay = type === 'success' || type === 'complete' ? 2000 : 3000; // Faster hide for toast
        resultTimeout = setTimeout(hideResult, delay);
    }

    function hideResult() {
        resultEl.classList.add('hidden');
        resultEl.classList.remove('result-connection-error');
        // document.body.classList.remove('modal-open'); // No longer using a modal backdrop
        isProcessing = false;
    }

    function onScanSuccess(decodedText) {
        if (isProcessing) return;

        // Extract token from URL if the QR contains the full passport URL
        let token = decodedText.trim();
        const urlMatch = token.match(/[?&]token=([a-f0-9]{64})/i);
        if (urlMatch) {
            token = urlMatch[1];
        }

        // Validate token looks like a 64-char hex string
        if (!/^[a-f0-9]{64}$/i.test(token)) {
            showResult('error', '<i class="bi bi-shield-exclamation"></i>', 'Invalid QR Code', '', 'This QR code is not a valid Bestination passport.', '');
            return;
        }

        isProcessing = true;

        fetch(SCAN_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token, booth_id: BOOTH_ID }),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                sessionCount++;
                if (countEl) countEl.textContent = sessionCount;
                // On successful scan, do not show the modal/toast.
                // Immediately reset the state to allow for the next scan.
                hideResult();
            } else if (data.status === 'duplicate') {
                showResult(
                    'duplicate',
                    '<i class="bi bi-info-circle"></i>',
                    'Already Scanned',
                    data.student_name,
                    data.message || 'Already visited this booth.',
                    ''
                );
            } else {
                showResult(
                    'error',
                    '<i class="bi bi-x-circle"></i>',
                    'Not Found',
                    '',
                    data.message || 'QR code not recognized.',
                    ''
                );
            }
        })
        .catch(function() {
            showResult('error', '<i class="bi bi-wifi-off"></i>', 'Connection Error', '', 'Could not connect to server. Check your network.', '');
        });
    }

    function onScanFailure() {
        // Silently ignore — fires constantly when no QR in frame
    }

    // Initialize html5-qrcode
    function initScanner() {
        scanner = new Html5Qrcode('qr-reader');
        const config = {
            fps: 10,
            qrbox: { width: 260, height: 260 },
            aspectRatio: 1.0,
        };
        scanner.start(
            { facingMode: 'environment' },
            config,
            onScanSuccess,
            onScanFailure
        ).catch(function(err) {
            // If rear camera fails, try any camera
            scanner.start(
                { facingMode: 'user' },
                config,
                onScanSuccess,
                onScanFailure
            ).catch(function(err2) {
                document.getElementById('qr-reader').innerHTML =
                    '<div class="scanner-error"><div class="scanner-error-icon"><i class="bi bi-camera-video-off"></i></div><h3 class="scanner-error-title">Camera Error</h3><p>Camera access was denied. Please allow camera permission in your browser settings and reload the page.</p><button class="scanner-error-reload" onclick="window.location.reload()">Reload</button></div>';
            });
        });
    }

    // Click on result to dismiss early
    resultEl.addEventListener('click', function() {
        if (resultTimeout) clearTimeout(resultTimeout);
        hideResult();
    });

    // Start scanner when page loads
    if (typeof Html5Qrcode !== 'undefined') {
        initScanner();
    } else {
        const readerEl = document.getElementById('qr-reader');
        readerEl.innerHTML = `
            <div class="scanner-error">
                <div class="scanner-error-icon"><i class="bi bi-cloud-slash"></i></div>
                <h3 class="scanner-error-title">Scanner Failed to Load</h3>
                <p>The QR code scanner library could not be loaded. Please check your internet connection and try again.</p>
                <button class="scanner-error-reload" onclick="window.location.reload()">Reload Page</button>
            </div>`;
    }

})();
