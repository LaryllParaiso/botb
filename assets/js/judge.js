/**
 * Judge Scoring — Battle of the Bands Tabulator
 * Handles: WebSocket real-time band updates, live score computation, submission
 * Falls back to polling if WebSocket is unavailable
 */

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
const POLL_INTERVAL = 10000; // 10s fallback polling
const WS_URL = `ws://${window.location.hostname}:8081`;
let currentBandId = null;
let currentCriteria = [];
let pollTimer = null;
let ws = null;
let wsReconnectTimer = null;
let wsConnected = false;

/**
 * Show/hide loading overlay
 */
function showLoading(show) {
    document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
}

/**
 * Initialize WebSocket connection
 */
function initWebSocket() {
    if (ws && (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING)) {
        return;
    }

    try {
        ws = new WebSocket(WS_URL);

        ws.onopen = function() {
            console.log('[WS] Connected');
            wsConnected = true;
            stopPolling(); // Stop fallback polling

            // Register as judge
            ws.send(JSON.stringify({ action: 'register', role: 'judge', userId: null }));
        };

        ws.onmessage = function(e) {
            try {
                const msg = JSON.parse(e.data);
                console.log('[WS] Message:', msg);

                if (msg.event === 'band_change') {
                    handleBandUpdate(msg.data.band_id, msg.data.band);
                }
            } catch (err) {
                console.error('[WS] Parse error:', err);
            }
        };

        ws.onclose = function() {
            console.warn('[WS] Disconnected');
            wsConnected = false;
            ws = null;
            startPolling(); // Fallback to polling
            scheduleReconnect();
        };

        ws.onerror = function(err) {
            console.error('[WS] Error:', err);
            wsConnected = false;
        };
    } catch (err) {
        console.warn('[WS] Failed to connect, using polling fallback');
        startPolling();
    }
}

/**
 * Schedule WebSocket reconnection
 */
function scheduleReconnect() {
    if (wsReconnectTimer) return;
    wsReconnectTimer = setTimeout(() => {
        wsReconnectTimer = null;
        initWebSocket();
    }, 5000);
}

/**
 * Close WebSocket connection
 */
function closeWebSocket() {
    if (ws) {
        ws.close();
        ws = null;
    }
    wsConnected = false;
    if (wsReconnectTimer) {
        clearTimeout(wsReconnectTimer);
        wsReconnectTimer = null;
    }
}

/**
 * Handle band update from SSE or polling
 */
async function handleBandUpdate(bandId, band) {
    if (bandId && band) {
        // Force reset so fetchActiveBandDetails always refreshes the form
        if (parseInt(bandId) !== currentBandId) {
            currentBandId = null;
        }
        await fetchActiveBandDetails();
    } else {
        // No active band
        currentBandId = null;
        currentCriteria = [];
        showWaitingState();
    }
}

/**
 * Fetch full active band details (criteria + finalization)
 */
async function fetchActiveBandDetails() {
    try {
        const res = await fetch('/BOB_SYSTEM/judge/ajax/get_active_band.php', {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await res.json();

        if (data.success && data.band) {
            const band = data.band;

            if (data.is_finalized) {
                showSubmittedState(band, data.scores, data.weighted_total);
                return;
            }

            // Band changed — show new scoring form
            if (parseInt(band.id) !== currentBandId) {
                currentBandId = parseInt(band.id);
                currentCriteria = data.criteria;
                showScoringForm(band, data.criteria);
            }
        } else {
            currentBandId = null;
            currentCriteria = [];
            showWaitingState();
        }
    } catch (err) {
        console.error('Fetch error:', err);
    }
}

/**
 * Start fallback polling
 */
function startPolling() {
    if (pollTimer) return;
    pollTimer = setInterval(fetchActiveBandDetails, POLL_INTERVAL);
}

/**
 * Stop fallback polling
 */
function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

/**
 * Show waiting state
 */
function showWaitingState() {
    document.getElementById('waitingState').style.display = '';
    document.getElementById('scoringState').style.display = 'none';
    document.getElementById('submittedState').style.display = 'none';
}

/**
 * Show scoring form
 */
function showScoringForm(band, criteria) {
    document.getElementById('waitingState').style.display = 'none';
    document.getElementById('scoringState').style.display = '';
    document.getElementById('submittedState').style.display = 'none';

    document.getElementById('bandName').textContent = band.name;
    document.getElementById('roundBadge').textContent =
        band.round_name === 'elimination' ? 'Elimination' : 'Grand Finals';

    const container = document.getElementById('criteriaFields');
    container.innerHTML = '';

    criteria.forEach(c => {
        const maxScore = parseFloat(c.weight);
        const div = document.createElement('div');
        div.className = 'mb-3';
        div.innerHTML = `
            <label class="form-label fw-semibold">${escapeHtml(c.name)}
                <span class="text-muted fw-normal"> — Max: ${maxScore.toFixed(0)} pts</span>
            </label>
            <input type="number" class="form-control score-input"
                   data-criteria-id="${c.id}" data-weight="${c.weight}"
                   min="0" max="${maxScore}" step="0.01" placeholder="0 – ${maxScore.toFixed(0)}" required>
            <div class="invalid-feedback">Score must be between 0 and ${maxScore.toFixed(0)}.</div>
        `;
        container.appendChild(div);
    });

    // Attach live computation
    container.querySelectorAll('.score-input').forEach(input => {
        input.addEventListener('input', computeWeightedTotal);
    });

    document.getElementById('weightedTotal').textContent = '0.00';
    document.getElementById('submitBtn').disabled = true;
}

/**
 * Show submitted read-only state
 */
function showSubmittedState(band, scores, weightedTotal) {
    document.getElementById('waitingState').style.display = 'none';
    document.getElementById('scoringState').style.display = 'none';
    document.getElementById('submittedState').style.display = '';

    // Keep SSE alive so judge sees next band change instantly
    // Only stop polling fallback
    stopPolling();

    const container = document.getElementById('submittedScores');
    container.innerHTML = '';

    if (scores && scores.length > 0) {
        scores.forEach(s => {
            const div = document.createElement('div');
            div.className = 'd-flex justify-content-between py-2 border-bottom';
            div.innerHTML = `
                <span class="fw-semibold">${escapeHtml(s.criteria_name)}
                    <span class="text-muted fw-normal"> — ${parseFloat(s.weight).toFixed(0)}%</span>
                </span>
                <span class="badge bg-primary fs-6">${parseFloat(s.score).toFixed(2)}</span>
            `;
            container.appendChild(div);
        });
    }

    document.getElementById('submittedTotal').textContent = parseFloat(weightedTotal).toFixed(2);
}

/**
 * Compute live weighted total
 */
function computeWeightedTotal() {
    const inputs = document.querySelectorAll('.score-input');
    let total = 0;
    let allFilled = true;
    let allValid = true;

    inputs.forEach(input => {
        const val = parseFloat(input.value);
        const weight = parseFloat(input.dataset.weight);

        const maxScore = parseFloat(input.max);

        if (input.value === '' || isNaN(val)) {
            allFilled = false;
            input.classList.remove('is-invalid');
        } else if (val < 0 || val > maxScore) {
            allValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
            total += val;
        }
    });

    document.getElementById('weightedTotal').textContent = total.toFixed(2);
    document.getElementById('submitBtn').disabled = !(allFilled && allValid);
}

/**
 * Handle form submission — show SweetAlert2 confirmation
 */
const scoringFormEl = document.getElementById('scoringForm');
if (scoringFormEl) {
    scoringFormEl.addEventListener('submit', async function(e) {
        e.preventDefault();

        const result = await Swal.fire({
            title: 'Submit Scores?',
            text: 'Are you sure you want to finalize your scores? This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#003366',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-check-circle"></i> Confirm & Submit',
            cancelButtonText: 'Cancel'
        });

        if (!result.isConfirmed) return;

        showLoading(true);

        const inputs = document.querySelectorAll('.score-input');
        const scores = [];

        inputs.forEach(input => {
            scores.push({
                criteria_id: parseInt(input.dataset.criteriaId),
                score: parseFloat(input.value)
            });
        });

        try {
            const res = await fetch('/BOB_SYSTEM/judge/ajax/submit_scores.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    band_id: currentBandId,
                    scores: scores
                })
            });

            const data = await res.json();

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Submitted!', text: 'Your scores have been recorded.', timer: 1800, showConfirmButton: false });
                await fetchActiveBandDetails();
                loadMyScores();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to submit scores.', confirmButtonColor: '#003366' });
            }
        } catch (err) {
            console.error('Submit error:', err);
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'An error occurred while submitting scores.', confirmButtonColor: '#003366' });
        } finally {
            showLoading(false);
        }
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// RECENT SCORES HISTORY (read-only)
// ============================================

/**
 * Load the judge's own submitted scores history
 */
async function loadMyScores() {
    const container = document.getElementById('recentScoresContainer');
    if (!container) return;

    try {
        const res = await fetch('/BOB_SYSTEM/judge/ajax/get_my_scores.php', {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await res.json();

        if (!data.success || !data.bands || data.bands.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">No scores submitted yet.</p>
                </div>`;
            return;
        }

        let html = '<div class="accordion" id="recentScoresAccordion">';

        data.bands.forEach((band, idx) => {
            const collapseId = `scoreCollapse${idx}`;
            const roundLabel = band.round_name === 'elimination' ? 'Elimination' : 'Grand Finals';
            const isFirst = idx === 0;

            html += `
            <div class="accordion-item border-0 mb-3 shadow-sm rounded overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button ${isFirst ? '' : 'collapsed'} py-2 px-3" type="button"
                            data-bs-toggle="collapse" data-bs-target="#${collapseId}"
                            aria-expanded="${isFirst}" aria-controls="${collapseId}">
                        <div class="d-flex justify-content-between align-items-center w-100 me-2">
                            <div>
                                <strong>${escapeHtml(band.band_name)}</strong>
                                <span class="badge bg-secondary ms-2" style="font-size:0.7rem;">${escapeHtml(roundLabel)}</span>
                            </div>
                            <span class="badge bg-primary rounded-pill">${parseFloat(band.total).toFixed(2)} pts</span>
                        </div>
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse collapse ${isFirst ? 'show' : ''}"
                     data-bs-parent="#recentScoresAccordion">
                    <div class="accordion-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Criteria</th>
                                    <th class="text-center" style="width:70px;">Max</th>
                                    <th class="text-end pe-3" style="width:80px;">Score</th>
                                </tr>
                            </thead>
                            <tbody>`;

            band.criteria.forEach(c => {
                const pct = c.weight > 0 ? ((c.score / c.weight) * 100).toFixed(0) : 0;
                html += `
                                <tr>
                                    <td class="ps-3">${escapeHtml(c.criteria_name)}</td>
                                    <td class="text-center text-muted">${parseFloat(c.weight).toFixed(0)}</td>
                                    <td class="text-end pe-3">
                                        <strong>${parseFloat(c.score).toFixed(2)}</strong>
                                        <small class="text-muted ms-1">(${pct}%)</small>
                                    </td>
                                </tr>`;
            });

            html += `
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td class="ps-3 fw-bold" colspan="2">Total</td>
                                    <td class="text-end pe-3 fw-bold">${parseFloat(band.total).toFixed(2)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>`;
        });

        html += '</div>';
        container.innerHTML = html;

    } catch (err) {
        console.error('Load my scores error:', err);
        container.innerHTML = '<div class="alert alert-danger">Failed to load score history.</div>';
    }
}

// Start WebSocket + scoring only on the scoring page (where bandDisplay exists)
if (document.getElementById('bandDisplay')) {
    fetchActiveBandDetails();
    initWebSocket();
}

// Load recent scores on any page that has the container
loadMyScores();
