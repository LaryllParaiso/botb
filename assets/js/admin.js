/**
 * Admin Dashboard — Battle of the Bands Tabulator
 * Handles: Judge CRUD, Band CRUD, Active Band Toggle, Rankings
 * Real-time updates via WebSocket with polling fallback
 */

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
const WS_URL = `ws://${window.location.hostname}:8081`;
let adminWs = null;
let adminWsReconnectTimer = null;
let adminWsConnected = false;

/**
 * Initialize WebSocket connection for admin
 */
function initAdminWebSocket() {
    if (adminWs && (adminWs.readyState === WebSocket.OPEN || adminWs.readyState === WebSocket.CONNECTING)) {
        return;
    }

    try {
        adminWs = new WebSocket(WS_URL);

        adminWs.onopen = function() {
            console.log('[Admin WS] Connected');
            adminWsConnected = true;

            // Stop polling fallback when WS is connected
            if (pendingPollTimer) {
                clearInterval(pendingPollTimer);
                pendingPollTimer = null;
            }

            adminWs.send(JSON.stringify({ action: 'register', role: 'admin', userId: null }));
        };

        adminWs.onmessage = function(e) {
            try {
                const msg = JSON.parse(e.data);
                console.log('[Admin WS] Message:', msg);

                if (msg.event === 'scores_submitted') {
                    // A judge submitted scores — refresh pending judges and bands
                    loadPendingJudges();
                    if (document.getElementById('bandsTableBody')) loadBands();
                } else if (msg.event === 'admin_update') {
                    // General admin data change — refresh relevant panels
                    loadPendingJudges();
                    if (document.getElementById('bandsTableBody')) loadBands();
                }
            } catch (err) {
                console.error('[Admin WS] Parse error:', err);
            }
        };

        adminWs.onclose = function() {
            console.warn('[Admin WS] Disconnected');
            adminWsConnected = false;
            adminWs = null;
            // Restart polling as fallback
            startPendingJudgesPoll();
            scheduleAdminReconnect();
        };

        adminWs.onerror = function() {
            adminWsConnected = false;
        };
    } catch (err) {
        console.warn('[Admin WS] Failed to connect, using polling fallback');
    }
}

/**
 * Schedule admin WebSocket reconnection
 */
function scheduleAdminReconnect() {
    if (adminWsReconnectTimer) return;
    adminWsReconnectTimer = setTimeout(() => {
        adminWsReconnectTimer = null;
        initAdminWebSocket();
    }, 5000);
}

/**
 * Show/hide loading overlay
 */
function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.style.display = show ? 'flex' : 'none';
}

/**
 * Generic AJAX helper
 */
async function ajaxRequest(url, method = 'GET', data = null) {
    showLoading(true);
    try {
        const options = {
            method: method,
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        };

        if (data && method !== 'GET') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }

        const res = await fetch(url, options);
        const json = await res.json();
        return json;
    } catch (err) {
        console.error('AJAX error:', err);
        return { success: false, message: 'Network error occurred.' };
    } finally {
        showLoading(false);
    }
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// JUDGE CRUD
// ============================================

/**
 * Load judges into table
 */
async function loadJudges() {
    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/judge_list.php');
    if (!data.success) return;

    const tbody = document.getElementById('judgesTableBody');
    if (!tbody) return;

    tbody.innerHTML = '';
    data.judges.forEach((judge, idx) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${idx + 1}</td>
            <td>${escapeHtml(judge.name)}</td>
            <td>${escapeHtml(judge.email)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editJudge(${judge.id}, '${escapeHtml(judge.name)}', '${escapeHtml(judge.email)}')">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteJudge(${judge.id}, '${escapeHtml(judge.name)}')">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Open Add Judge modal
 */
function openAddJudgeModal() {
    document.getElementById('judgeModalLabel').textContent = 'Add Judge';
    document.getElementById('judgeForm').reset();
    document.getElementById('judgeId').value = '';
    document.getElementById('judgePasswordHelp').textContent = '';
    document.getElementById('judgePassword').required = true;
    const modal = new bootstrap.Modal(document.getElementById('judgeModal'));
    modal.show();
}

/**
 * Open Edit Judge modal
 */
function editJudge(id, name, email) {
    document.getElementById('judgeModalLabel').textContent = 'Edit Judge';
    document.getElementById('judgeId').value = id;
    document.getElementById('judgeName').value = name;
    document.getElementById('judgeEmail').value = email;
    document.getElementById('judgePassword').value = '';
    document.getElementById('judgePassword').required = false;
    document.getElementById('judgePasswordHelp').textContent = 'Leave blank to keep current password.';
    const modal = new bootstrap.Modal(document.getElementById('judgeModal'));
    modal.show();
}

/**
 * Save Judge (create or update)
 */
async function saveJudge(e) {
    e.preventDefault();

    const id = document.getElementById('judgeId').value;
    const name = document.getElementById('judgeName').value.trim();
    const email = document.getElementById('judgeEmail').value.trim();
    const password = document.getElementById('judgePassword').value;

    if (!name || !email) {
        Swal.fire({ icon: 'warning', title: 'Missing Fields', text: 'Name and email are required.', confirmButtonColor: '#003366' });
        return;
    }

    if (!id && !password) {
        Swal.fire({ icon: 'warning', title: 'Missing Fields', text: 'Password is required for new judges.', confirmButtonColor: '#003366' });
        return;
    }

    const payload = { name, email };
    if (password) payload.password = password;

    let url, data;
    if (id) {
        payload.id = parseInt(id);
        data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/judge_update.php', 'POST', payload);
    } else {
        data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/judge_create.php', 'POST', payload);
    }

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('judgeModal')).hide();
        Swal.fire({ icon: 'success', title: 'Saved!', text: id ? 'Judge updated successfully.' : 'Judge created successfully.', timer: 1500, showConfirmButton: false });
        loadJudges();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to save judge.', confirmButtonColor: '#003366' });
    }
}

/**
 * Delete Judge
 */
async function deleteJudge(id, name) {
    const result = await Swal.fire({
        title: 'Delete Judge?',
        html: `Are you sure you want to delete <strong>${escapeHtml(name)}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Yes, delete',
        cancelButtonText: 'Cancel'
    });
    if (!result.isConfirmed) return;

    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/judge_delete.php', 'POST', { id });
    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Judge has been removed.', timer: 1500, showConfirmButton: false });
        loadJudges();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to delete judge.', confirmButtonColor: '#003366' });
    }
}

// ============================================
// BAND CRUD
// ============================================

/**
 * Load bands into table
 */
async function loadBands() {
    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/band_list.php');
    if (!data.success) return;

    const tbody = document.getElementById('bandsTableBody');
    if (!tbody) return;

    tbody.innerHTML = '';
    data.bands.forEach((band, idx) => {
        const roundLabel = band.round_name === 'elimination' ? 'Elimination' : 'Grand Finals';

        let statusBadge;
        if (band.is_active == 1) {
            statusBadge = '<span class="badge badge-now-performing">Now Performing</span>';
        } else if (band.is_finished) {
            statusBadge = '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Finished</span>';
        } else {
            statusBadge = '<span class="badge badge-standby">Standby</span>';
        }

        let activateBtn;
        if (band.is_active == 1) {
            activateBtn = `<button class="btn btn-sm btn-danger" onclick="deactivateBand('${escapeHtml(band.name)}')"><i class="bi bi-stop-circle"></i> Stop</button>`;
        } else if (band.is_finished) {
            activateBtn = `<button class="btn btn-sm btn-secondary" disabled><i class="bi bi-check-circle-fill"></i> Done</button>`;
        } else {
            activateBtn = `<button class="btn btn-sm btn-outline-success" onclick="activateBand(${band.id}, '${escapeHtml(band.name)}')"><i class="bi bi-broadcast"></i> Activate</button>`;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${idx + 1}</td>
            <td>${escapeHtml(band.name)}</td>
            <td>${escapeHtml(roundLabel)}</td>
            <td>${band.performance_order}</td>
            <td>${statusBadge}</td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                    ${activateBtn}
                    <button class="btn btn-sm btn-outline-primary" onclick="editBand(${band.id}, '${escapeHtml(band.name)}', ${band.round_id}, ${band.performance_order})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBand(${band.id}, '${escapeHtml(band.name)}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Open Add Band modal
 */
function openAddBandModal() {
    document.getElementById('bandModalLabel').textContent = 'Add Band';
    document.getElementById('bandForm').reset();
    document.getElementById('bandId').value = '';
    const modal = new bootstrap.Modal(document.getElementById('bandModal'));
    modal.show();
}

/**
 * Open Edit Band modal
 */
function editBand(id, name, roundId, order) {
    document.getElementById('bandModalLabel').textContent = 'Edit Band';
    document.getElementById('bandId').value = id;
    document.getElementById('bandName').value = name;
    document.getElementById('bandRound').value = roundId;
    document.getElementById('bandOrder').value = order;
    const modal = new bootstrap.Modal(document.getElementById('bandModal'));
    modal.show();
}

/**
 * Save Band (create or update)
 */
async function saveBand(e) {
    e.preventDefault();

    const id = document.getElementById('bandId').value;
    const name = document.getElementById('bandName').value.trim();
    const round_id = parseInt(document.getElementById('bandRound').value);
    const performance_order = parseInt(document.getElementById('bandOrder').value);

    if (!name || !round_id || !performance_order) {
        Swal.fire({ icon: 'warning', title: 'Missing Fields', text: 'All fields are required.', confirmButtonColor: '#003366' });
        return;
    }

    const payload = { name, round_id, performance_order };

    let data;
    if (id) {
        payload.id = parseInt(id);
        data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/band_update.php', 'POST', payload);
    } else {
        data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/band_create.php', 'POST', payload);
    }

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('bandModal')).hide();
        Swal.fire({ icon: 'success', title: 'Saved!', text: id ? 'Band updated successfully.' : 'Band created successfully.', timer: 1500, showConfirmButton: false });
        loadBands();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to save band.', confirmButtonColor: '#003366' });
    }
}

/**
 * Delete Band
 */
async function deleteBand(id, name) {
    const result = await Swal.fire({
        title: 'Delete Band?',
        html: `Are you sure you want to delete <strong>${escapeHtml(name)}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Yes, delete',
        cancelButtonText: 'Cancel'
    });
    if (!result.isConfirmed) return;

    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/band_delete.php', 'POST', { id });
    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Band has been removed.', timer: 1500, showConfirmButton: false });
        loadBands();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to delete band.', confirmButtonColor: '#003366' });
    }
}

/**
 * Activate Band — checks pending judges first
 */
async function activateBand(id, name) {
    const result = await Swal.fire({
        title: 'Activate Band?',
        html: `Set <strong>${escapeHtml(name)}</strong> as the current performing band?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-broadcast"></i> Yes, activate',
        cancelButtonText: 'Cancel'
    });
    if (!result.isConfirmed) return;

    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/band_activate.php', 'POST', { id });
    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Activated!', html: `<strong>${escapeHtml(name)}</strong> is now performing.`, timer: 2000, showConfirmButton: false });
        loadBands();
        loadPendingJudges();
    } else {
        Swal.fire({ icon: 'error', title: 'Cannot Activate', text: data.message || 'Failed to activate band.', confirmButtonColor: '#003366' });
    }
}

/**
 * Deactivate (stop) the currently active band
 */
async function deactivateBand(name) {
    const result = await Swal.fire({
        title: 'Stop Performance?',
        html: `Deactivate <strong>${escapeHtml(name)}</strong>?<br><small class="text-muted">No band will be performing and judges will return to the waiting screen.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-stop-circle"></i> Yes, stop',
        cancelButtonText: 'Cancel'
    });
    if (!result.isConfirmed) return;

    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/band_deactivate.php', 'POST', {});
    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Stopped', html: `<strong>${escapeHtml(name)}</strong> has been deactivated.`, timer: 2000, showConfirmButton: false });
        loadBands();
        loadPendingJudges();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to deactivate band.', confirmButtonColor: '#003366' });
    }
}

/**
 * Load pending judges status for the active band
 */
let pendingPollTimer = null;

async function loadPendingJudges() {
    const panel = document.getElementById('pendingJudgesPanel');
    if (!panel) return;

    try {
        const res = await fetch('/BOB_SYSTEM/admin/ajax/get_pending_judges.php', {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await res.json();

        if (!data.success) return;

        if (!data.active_band) {
            panel.innerHTML = `
                <div class="alert alert-secondary mb-0">
                    <i class="bi bi-info-circle"></i> No band is currently performing. Activate a band to begin scoring.
                </div>`;
            return;
        }

        const band = data.active_band;
        const pending = data.pending_judges;
        const total = data.total_judges;
        const submitted = data.submitted_count;

        let html = `<div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-broadcast"></i> Now Performing: <strong>${escapeHtml(band.name)}</strong></span>
                <span class="badge bg-light text-dark">${submitted}/${total} Submitted</span>
            </div>
            <div class="card-body p-0">`;

        if (pending.length === 0) {
            html += `<div class="p-3 text-success">
                <i class="bi bi-check-circle-fill"></i> <strong>All judges have submitted scores!</strong> You may now activate the next band.
            </div>`;
        } else {
            html += `<div class="p-3 text-warning-emphasis bg-warning-subtle">
                <i class="bi bi-exclamation-triangle-fill"></i> <strong>Waiting for ${pending.length} judge(s) to submit scores:</strong>
            </div>
            <ul class="list-group list-group-flush">`;
            pending.forEach(j => {
                html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-x text-danger"></i> ${escapeHtml(j.name)}</span>
                    <span class="badge bg-danger">Pending</span>
                </li>`;
            });
            html += `</ul>`;
        }

        html += `</div></div>`;
        panel.innerHTML = html;
    } catch (err) {
        console.error('Pending judges error:', err);
    }
}

/**
 * Start auto-refresh for pending judges panel (every 5s)
 */
function startPendingJudgesPoll() {
    if (pendingPollTimer) return;
    loadPendingJudges();
    pendingPollTimer = setInterval(loadPendingJudges, 5000);
}

// ============================================
// RANKINGS
// ============================================

/**
 * Track current round for top-N re-render without re-fetching
 */
let currentRankingsRoundId = null;
let currentRankingsData = null;

/**
 * Reload rankings for the currently active tab (used by topN input change)
 */
function reloadCurrentRankings() {
    if (currentRankingsRoundId !== null) {
        renderRankings(currentRankingsData);
    }
}

/**
 * Get the top-N cutoff value from the input (default 8)
 */
function getTopN() {
    const input = document.getElementById('topNInput');
    return input ? Math.max(1, parseInt(input.value) || 8) : 8;
}

/**
 * Load rankings for a specific round
 */
async function loadRankings(roundId) {
    currentRankingsRoundId = roundId;

    const data = await ajaxRequest(`/BOB_SYSTEM/admin/ajax/get_rankings.php?round_id=${roundId}`);
    if (!data.success) return;

    currentRankingsData = data;
    renderRankings(data);
}

/**
 * Render rankings table with top-N highlighting
 * Top N logic: include all bands until cumulative count >= N,
 * always completing the current tie group.
 */
function renderRankings(data) {
    const thead = document.getElementById('rankingsTableHead');
    const tbody = document.getElementById('rankingsTableBody');
    if (!thead || !tbody) return;

    const topN = getTopN();

    // Populate print header/footer from settings
    if (data.settings) {
        const titleEl = document.getElementById('printEventTitle');
        const subtitleEl = document.getElementById('printEventSubtitle');
        if (titleEl) titleEl.textContent = data.settings.event_title || 'Battle of the Bands — NEUST 2026';
        if (subtitleEl) subtitleEl.textContent = data.settings.event_subtitle || '118th Founding Anniversary / 28th Charter Day';

        // Dynamic logos
        const logoLeft = document.getElementById('printLogoLeft');
        const logoRight = document.getElementById('printLogoRight');
        const watermark = document.getElementById('printWatermarkImg');
        if (logoLeft && data.settings.logo_left) logoLeft.src = data.settings.logo_left;
        if (logoRight && data.settings.logo_right) logoRight.src = data.settings.logo_right;
        if (watermark && data.settings.logo_watermark) watermark.src = data.settings.logo_watermark;

        // Signatories (multiple, with custom font size and fixed labels)
        const sigContainer = document.getElementById('printSignatories');
        if (sigContainer) {
            let signatories = [];
            try { signatories = JSON.parse(data.settings.signatories || '[]'); } catch(e) {}
            const labels = ['Prepared by:', 'Confirmed by:', 'Approved by:'];

            sigContainer.innerHTML = signatories.slice(0, 3).map((sig, idx) => {
                const fs = sig.fontSize ? `font-size:${sig.fontSize}pt;` : '';
                const label = labels[idx] || '';
                return `
                    <div class="print-signatory-block">
                        <div class="print-signatory-label">${escapeHtml(label)}</div>
                        <div class="print-signatory-name" style="${fs}">
                            <strong>${escapeHtml(sig.name || '')}</strong><br>
                            <span>${escapeHtml(sig.title || '')}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    // Update print date to current date
    const printDateEl = document.getElementById('printDate');
    if (printDateEl) {
        printDateEl.textContent = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    // Populate print judges list from all registered judges
    if (data.all_judges) {
        const judgesListEl = document.getElementById('printJudgesList');
        if (judgesListEl) {
            judgesListEl.innerHTML = data.all_judges.map(j =>
                `<div class="print-judge-name"><strong><u>${escapeHtml(j.name)}</u></strong></div>`
            ).join('');
        }
    }

    // Build header
    let headerHtml = '<tr><th>Rank</th><th>Band Name</th>';
    if (data.judges && data.judges.length > 0) {
        data.judges.forEach(j => {
            headerHtml += `<th>${escapeHtml(j.name)}</th>`;
        });
    }
    headerHtml += '<th>Average Score</th></tr>';
    thead.innerHTML = headerHtml;

    // Build body
    tbody.innerHTML = '';
    if (!data.rankings || data.rankings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="100" class="text-center text-muted py-4">No scores submitted yet for this round.</td></tr>';
        return;
    }

    // Determine which bands are in the top N (tie-aware)
    const topNBandIds = new Set();
    let accumulated = 0;
    let cutoffReached = false;
    let lastIncludedRank = null;

    data.rankings.forEach(band => {
        if (cutoffReached && band.rank !== lastIncludedRank) return;
        topNBandIds.add(band.band_id);
        accumulated++;
        lastIncludedRank = band.rank;
        if (accumulated >= topN) cutoffReached = true;
    });

    data.rankings.forEach((band) => {
        const rank = band.rank || 0;
        const isTopN = topNBandIds.has(band.band_id);

        let rowClass = '';
        if (rank === 1) rowClass = 'rank-gold';
        else if (rank === 2) rowClass = 'rank-silver';
        else if (rank === 3) rowClass = 'rank-bronze';

        if (isTopN) rowClass += ' rank-top-n';

        let rowHtml = `<td><strong>${rank}</strong>`;
        if (isTopN) rowHtml += ` <span class="badge bg-warning text-dark top-n-badge">Top ${topN}</span>`;
        rowHtml += `</td><td>${isTopN ? `<strong>${escapeHtml(band.band_name)}</strong>` : escapeHtml(band.band_name)}</td>`;

        if (data.judges && data.judges.length > 0) {
            data.judges.forEach(j => {
                const judgeScore = band.judge_scores[j.id] ?? '—';
                rowHtml += `<td>${judgeScore !== '—' ? parseFloat(judgeScore).toFixed(2) : '—'}</td>`;
            });
        }
        rowHtml += `<td><strong>${parseFloat(band.average_score).toFixed(2)}</strong></td>`;

        const tr = document.createElement('tr');
        tr.className = rowClass;
        tr.innerHTML = rowHtml;
        tbody.appendChild(tr);
    });
}

// ============================================
// EXCEL EXPORT
// ============================================

/**
 * Download rankings as Excel file with orientation prompt
 */
async function downloadExcel() {
    if (!currentRankingsData || !currentRankingsData.rankings) {
        Swal.fire({ icon: 'info', title: 'No Data', text: 'Load rankings first before exporting.', confirmButtonColor: '#003366' });
        return;
    }

    const { value: orientation } = await Swal.fire({
        title: 'Excel Page Orientation',
        input: 'radio',
        inputOptions: { landscape: 'Landscape', portrait: 'Portrait' },
        inputValue: 'landscape',
        showCancelButton: true,
        confirmButtonText: 'Download',
        confirmButtonColor: '#003366',
        cancelButtonColor: '#6c757d'
    });

    if (!orientation) return;

    const data = currentRankingsData;
    const settings = data.settings || {};
    const topN = parseInt(document.getElementById('topNInput')?.value) || 8;
    const eventTitle = settings.event_title || 'Battle of the Bands — NEUST 2026';
    const eventSubtitle = settings.event_subtitle || '118th Founding Anniversary / 28th Charter Day';
    const currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const roundLabel = currentRankingsRoundId == 1 ? 'Elimination' : 'Grand Finals';

    // Build worksheet data
    const wsData = [];

    // Header rows (rows 0-3)
    wsData.push([eventTitle]);
    wsData.push([eventSubtitle]);
    wsData.push([`Official Rankings — ${currentDate}`]);
    wsData.push([]); // blank row

    // Table header (row 4)
    const headerRow = ['Rank', 'Band Name'];
    if (data.judges && data.judges.length > 0) {
        data.judges.forEach(j => headerRow.push(j.name));
    }
    headerRow.push('Average Score');
    wsData.push(headerRow);
    const tableHeaderRowIdx = wsData.length - 1;

    // Table body
    const tableStartRow = wsData.length;
    data.rankings.forEach(band => {
        const row = [band.rank, band.band_name];
        if (data.judges && data.judges.length > 0) {
            data.judges.forEach(j => {
                const s = band.judge_scores[j.id];
                row.push(s !== undefined && s !== null ? parseFloat(s) : '—');
            });
        }
        row.push(parseFloat(band.average_score));
        wsData.push(row);
    });
    const tableEndRow = wsData.length - 1;

    // Blank rows before judge footer (space for signatures)
    wsData.push([]);
    wsData.push([]);
    wsData.push([]);

    // Judge names in a single horizontal row
    const judgeFooterRow = wsData.length;
    const judgeRow = new Array(headerRow.length).fill('');
    if (data.all_judges && data.all_judges.length > 0) {
        data.all_judges.forEach((j, idx) => {
            if (idx < headerRow.length) judgeRow[idx] = j.name;
        });
    }
    wsData.push(judgeRow);

    // Blank rows before signatories
    wsData.push([]);
    wsData.push([]);

    // Signatories in a single horizontal row with labels (Prepared/Confirmed/Approved)
    let signatories = [];
    try { signatories = JSON.parse((data.settings || {}).signatories || '[]'); } catch(e) {}
    const sigStartRow = wsData.length;
    if (signatories.length > 0) {
        const labels = ['Prepared by:', 'Confirmed by:', 'Approved by:'];
        const maxSig = Math.min(3, signatories.length);

        const sigLabelRow   = new Array(headerRow.length).fill('');
        const sigContentRow = new Array(headerRow.length).fill('');

        // Fixed column pairs for up to three signatories:
        // 1st: (0,1), 2nd: (2,3), 3rd: (4,5)
        const pairs = [
            { labelCol: 0, contentCol: 1 },
            { labelCol: 2, contentCol: 3 },
            { labelCol: 4, contentCol: 5 }
        ];

        for (let i = 0; i < maxSig && i < pairs.length; i++) {
            const sig   = signatories[i];
            const pair  = pairs[i];
            const labelCol   = pair.labelCol;
            const contentCol = pair.contentCol;

            if (labelCol < headerRow.length && labels[i]) {
                sigLabelRow[labelCol] = labels[i];
            }

            if (contentCol < headerRow.length && sig) {
                const name  = sig.name  || '';
                const title = sig.title || '';
                if (name || title) {
                    sigContentRow[contentCol] = title ? `${name}\n${title}` : name;
                }
            }
        }

        wsData.push(sigLabelRow);
        wsData.push(sigContentRow);
    }

    // Create workbook & worksheet
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(wsData);
    const colCount = headerRow.length;
    const lastCol = colCount - 1;

    // --- Cell styling helpers ---
    const border = (style) => ({
        top: { style }, bottom: { style }, left: { style }, right: { style }
    });
    const centerBold = { font: { bold: true }, alignment: { horizontal: 'center' } };
    const centerBoldLg = { font: { bold: true, sz: 14 }, alignment: { horizontal: 'center' } };
    const centerBoldMd = { font: { bold: true, sz: 11 }, alignment: { horizontal: 'center' } };
    const centerItalic = { font: { italic: true, sz: 10 }, alignment: { horizontal: 'center' } };
    const thinBorder = border('thin');

    // Merge header rows across all columns
    if (!ws['!merges']) ws['!merges'] = [];
    for (let r = 0; r < 3; r++) {
        ws['!merges'].push({ s: { r, c: 0 }, e: { r, c: lastCol } });
    }

    // Style header rows
    const cellRef = (r, c) => XLSX.utils.encode_cell({ r, c });
    const setStyle = (r, c, style) => {
        const ref = cellRef(r, c);
        if (ws[ref]) ws[ref].s = style;
    };

    setStyle(0, 0, centerBoldLg);
    setStyle(1, 0, centerBoldMd);
    setStyle(2, 0, centerItalic);

    // Style table header row (bold + borders + dark bg)
    for (let c = 0; c < colCount; c++) {
        const ref = cellRef(tableHeaderRowIdx, c);
        if (ws[ref]) {
            ws[ref].s = {
                font: { bold: true, color: { rgb: 'FFFFFF' } },
                fill: { fgColor: { rgb: '003366' } },
                border: thinBorder,
                alignment: { horizontal: 'center' }
            };
        }
    }

    // Style table body rows (borders + number format + top-N highlighting)
    for (let r = tableStartRow; r <= tableEndRow; r++) {
        const bandIdx = r - tableStartRow;
        const band = data.rankings[bandIdx];
        const isTopN = band && band.rank <= topN;

        for (let c = 0; c < colCount; c++) {
            const ref = cellRef(r, c);
            if (ws[ref]) {
                ws[ref].s = ws[ref].s || {};
                ws[ref].s.border = thinBorder;

                if (isTopN) {
                    ws[ref].s.font = { bold: true };
                    ws[ref].s.fill = { fgColor: { rgb: 'FFF8DC' } };
                    if (c === 0) {
                        ws[ref].s.border = {
                            top: { style: 'thin' }, bottom: { style: 'thin' },
                            right: { style: 'thin' },
                            left: { style: 'medium', color: { rgb: '003366' } }
                        };
                    }
                }

                // Number columns: format to 2 decimal places
                if (c >= 2 && typeof ws[ref].v === 'number') {
                    ws[ref].z = '0.00';
                }
            }
        }
    }

    // Style judge names in footer row (bold + underline, horizontal)
    if (data.all_judges && data.all_judges.length > 0) {
        data.all_judges.forEach((j, idx) => {
            if (idx < colCount) {
                const ref = cellRef(judgeFooterRow, idx);
                if (ws[ref]) {
                    ws[ref].s = { font: { bold: true, underline: true }, alignment: { horizontal: 'center' } };
                }
            }
        });
    }

    // Style signatories (horizontal: labels on the left, bold+underline name, title below)
    if (signatories.length > 0) {
        const maxSig = Math.min(3, signatories.length);
        const pairs = [
            { labelCol: 0, contentCol: 1 },
            { labelCol: 2, contentCol: 3 },
            { labelCol: 4, contentCol: 5 }
        ];

        for (let i = 0; i < maxSig && i < pairs.length; i++) {
            const sig = signatories[i];
            const fs  = (sig && sig.fontSize) || 11;
            const { labelCol, contentCol } = pairs[i];

            // Label style
            if (labelCol < colCount) {
                const refLabel = cellRef(sigStartRow, labelCol);
                if (ws[refLabel]) {
                    ws[refLabel].s = {
                        font: { italic: true, sz: 9 },
                        alignment: { horizontal: 'right' }
                    };
                }
            }

            // Content (name + title stacked)
            if (contentCol < colCount) {
                const refContent = cellRef(sigStartRow + 1, contentCol);
                if (ws[refContent]) {
                    ws[refContent].s = {
                        font: { bold: true, underline: true, sz: fs },
                        alignment: { horizontal: 'left', vertical: 'top', wrapText: true }
                    };
                }
            }
        }
    }

    // Set column widths
    ws['!cols'] = [{ wch: 6 }, { wch: 22 }];
    for (let i = 2; i < colCount; i++) ws['!cols'].push({ wch: 16 });

    // Set print orientation
    if (!ws['!print']) ws['!print'] = {};
    ws['!print'].orientation = orientation;

    XLSX.utils.book_append_sheet(wb, ws, roundLabel);
    XLSX.writeFile(wb, `Rankings_${roundLabel.replace(/\s+/g, '_')}_${currentDate.replace(/,?\s+/g, '_')}.xlsx`);
}

// ============================================
// SCORE MANAGEMENT
// ============================================

/**
 * Load all scores grouped by band → judge for the scores page
 */
async function loadScores() {
    const container = document.getElementById('scoreCardsContainer');
    if (!container) return;

    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/score_list.php');
    if (!data.success) return;

    const scores = data.scores;
    if (!scores || scores.length === 0) {
        container.innerHTML = '<div class="alert alert-secondary text-center">No scores have been submitted yet.</div>';
        return;
    }

    // Group: band → judge → criteria scores
    const grouped = {};
    scores.forEach(s => {
        const bKey = s.band_id;
        if (!grouped[bKey]) {
            grouped[bKey] = {
                band_id: s.band_id,
                band_name: s.band_name,
                round_name: s.round_name,
                judges: {}
            };
        }
        const jKey = s.judge_id;
        if (!grouped[bKey].judges[jKey]) {
            grouped[bKey].judges[jKey] = {
                judge_id: s.judge_id,
                judge_name: s.judge_name,
                criteria: []
            };
        }
        grouped[bKey].judges[jKey].criteria.push({
            criteria_id: s.criteria_id,
            criteria_name: s.criteria_name,
            weight: parseFloat(s.weight),
            score: parseFloat(s.score)
        });
    });

    let html = '';
    Object.values(grouped).forEach(band => {
        const roundLabel = band.round_name === 'elimination' ? 'Elimination' : 'Grand Finals';
        html += `<div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <strong>${escapeHtml(band.band_name)}</strong>
                <span class="badge bg-light text-dark ms-2">${escapeHtml(roundLabel)}</span>
            </div>
            <div class="card-body p-0">`;

        Object.values(band.judges).forEach(judge => {
            let total = 0;
            html += `<div class="border-bottom p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0"><i class="bi bi-person-circle"></i> ${escapeHtml(judge.judge_name)}</h6>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteJudgeBandScores(${judge.judge_id}, ${band.band_id}, '${escapeHtml(judge.judge_name)}', '${escapeHtml(band.band_name)}')">
                        <i class="bi bi-trash"></i> Delete All Scores
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Criteria</th><th>Max</th><th>Score</th><th style="width:100px">Action</th></tr>
                        </thead>
                        <tbody>`;

            judge.criteria.forEach(c => {
                total += c.score;
                html += `<tr>
                    <td>${escapeHtml(c.criteria_name)}</td>
                    <td>${c.weight.toFixed(0)}</td>
                    <td><strong>${c.score.toFixed(2)}</strong></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="openEditScore(${judge.judge_id}, '${escapeHtml(judge.judge_name)}', ${band.band_id}, '${escapeHtml(band.band_name)}', ${c.criteria_id}, '${escapeHtml(c.criteria_name)}', ${c.weight}, ${c.score})">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>`;
            });

            html += `</tbody>
                        <tfoot class="table-light">
                            <tr><td colspan="2" class="text-end fw-bold">Total:</td><td colspan="2"><strong>${total.toFixed(2)}</strong></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>`;
        });

        html += `</div></div>`;
    });

    container.innerHTML = html;
}

/**
 * Filter score cards by band name
 */
function filterScoreCards(query) {
    const container = document.getElementById('scoreCardsContainer');
    if (!container) return;
    const cards = container.querySelectorAll('.card');
    const q = (query || '').toLowerCase().trim();
    cards.forEach(card => {
        const header = card.querySelector('.card-header');
        if (!header) return;
        const bandName = header.textContent.toLowerCase();
        card.style.display = (!q || bandName.includes(q)) ? '' : 'none';
    });
}

/**
 * Open edit score modal
 */
function openEditScore(judgeId, judgeName, bandId, bandName, criteriaId, criteriaName, maxScore, currentScore) {
    document.getElementById('editScoreJudgeId').value = judgeId;
    document.getElementById('editScoreBandId').value = bandId;
    document.getElementById('editScoreCriteriaId').value = criteriaId;
    document.getElementById('editScoreMax').value = maxScore;

    document.getElementById('editScoreJudgeName').textContent = judgeName;
    document.getElementById('editScoreBandName').textContent = bandName;
    document.getElementById('editScoreCriteriaName').textContent = criteriaName;
    document.getElementById('editScoreMaxLabel').textContent = `(0 – ${maxScore.toFixed(0)})`;

    const input = document.getElementById('editScoreValue');
    input.value = currentScore;
    input.max = maxScore;
    input.classList.remove('is-invalid');

    const modal = new bootstrap.Modal(document.getElementById('editScoreModal'));
    modal.show();
}

/**
 * Save edited score
 */
async function saveScoreEdit() {
    const judgeId    = parseInt(document.getElementById('editScoreJudgeId').value);
    const bandId     = parseInt(document.getElementById('editScoreBandId').value);
    const criteriaId = parseInt(document.getElementById('editScoreCriteriaId').value);
    const maxScore   = parseFloat(document.getElementById('editScoreMax').value);
    const scoreVal   = parseFloat(document.getElementById('editScoreValue').value);

    const input = document.getElementById('editScoreValue');
    if (isNaN(scoreVal) || scoreVal < 0 || scoreVal > maxScore) {
        input.classList.add('is-invalid');
        document.getElementById('editScoreError').textContent = `Score must be between 0 and ${maxScore.toFixed(0)}.`;
        return;
    }
    input.classList.remove('is-invalid');

    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/score_update.php', 'POST', {
        judge_id: judgeId,
        band_id: bandId,
        criteria_id: criteriaId,
        score: scoreVal
    });

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('editScoreModal')).hide();
        Swal.fire({ icon: 'success', title: 'Updated!', text: 'Score updated successfully.', timer: 1500, showConfirmButton: false });
        loadScores();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to update score.', confirmButtonColor: '#003366' });
    }
}

/**
 * Delete all scores for a judge on a band
 */
async function deleteJudgeBandScores(judgeId, bandId, judgeName, bandName) {
    const result = await Swal.fire({
        title: 'Delete All Scores?',
        html: `Delete <strong>ALL</strong> scores by <strong>${escapeHtml(judgeName)}</strong> for <strong>${escapeHtml(bandName)}</strong>?<br><small class="text-danger">This cannot be undone.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Yes, delete all',
        cancelButtonText: 'Cancel'
    });
    if (!result.isConfirmed) return;

    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/score_delete.php', 'POST', {
        judge_id: judgeId,
        band_id: bandId
    });

    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Scores have been removed.', timer: 1500, showConfirmButton: false });
        loadScores();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to delete scores.', confirmButtonColor: '#003366' });
    }
}

/**
 * Open system reset confirmation modal
 */
function confirmSystemReset() {
    document.getElementById('resetConfirmInput').value = '';
    const modal = new bootstrap.Modal(document.getElementById('resetModal'));
    modal.show();
}

/**
 * Execute system reset after confirmation
 */
async function executeSystemReset() {
    const confirmVal = document.getElementById('resetConfirmInput').value.trim();
    if (confirmVal !== 'RESET_ALL') {
        Swal.fire({ icon: 'warning', title: 'Confirmation Required', text: 'You must type RESET_ALL to confirm.', confirmButtonColor: '#003366' });
        return;
    }

    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/system_reset.php', 'POST', {
        confirm: 'RESET_ALL'
    });

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('resetModal')).hide();
        Swal.fire({ icon: 'success', title: 'System Reset Complete', text: 'All data has been reset successfully.', confirmButtonColor: '#003366' });
        loadScores();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to reset data.', confirmButtonColor: '#003366' });
    }
}

// ============================================
// SETTINGS
// ============================================

/**
 * Load settings into the settings form
 */
async function loadSettings() {
    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/settings_get.php');
    if (!data.success) return;

    const s = data.settings;
    const el = (id) => document.getElementById(id);
    if (el('settingEventTitle'))    el('settingEventTitle').value    = s.event_title || '';
    if (el('settingEventSubtitle')) el('settingEventSubtitle').value = s.event_subtitle || '';

    // Logo previews
    if (s.logo_left && el('logoLeftPreview'))           el('logoLeftPreview').src = s.logo_left;
    if (s.logo_right && el('logoRightPreview'))         el('logoRightPreview').src = s.logo_right;
    if (s.logo_watermark && el('logoWatermarkPreview')) el('logoWatermarkPreview').src = s.logo_watermark;

    // Signatories
    const container = document.getElementById('signatoriesContainer');
    if (container) {
        container.innerHTML = '';
        let signatories = [];
        try { signatories = JSON.parse(s.signatories || '[]'); } catch(e) {}
        if (!signatories.length) signatories = [{ name: '', title: '' }];
        signatories.forEach(sig => addSignatoryRow(sig.name, sig.title, sig.fontSize));
    }
}

/**
 * Add a signatory row to the settings form
 */
function addSignatoryRow(name, title, fontSize) {
    const container = document.getElementById('signatoriesContainer');
    if (!container) return;
    const row = document.createElement('div');
    row.className = 'signatory-row border rounded p-3 mb-3 position-relative';
    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2" onclick="this.closest('.signatory-row').remove()" title="Remove">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Signatory Name</label>
                <input type="text" class="form-control sig-name" placeholder="e.g. Dr. Juan Dela Cruz" value="${escapeHtml(name || '')}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Signatory Title</label>
                <input type="text" class="form-control sig-title" placeholder="e.g. Head, Student Affairs" value="${escapeHtml(title || '')}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Font Size (pt)</label>
                <input type="number" class="form-control sig-fontsize" placeholder="11" min="7" max="20" value="${escapeHtml(String(fontSize || '11'))}">
            </div>
        </div>
    `;
    container.appendChild(row);
}

/**
 * Upload a logo file for a given settings field
 */
async function uploadLogo(field, inputEl) {
    if (!inputEl.files || !inputEl.files[0]) return;

    const file = inputEl.files[0];
    if (file.size > 5 * 1024 * 1024) {
        Swal.fire({ icon: 'warning', title: 'File Too Large', text: 'Maximum file size is 5MB.', confirmButtonColor: '#003366' });
        inputEl.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('field', field);
    formData.append('logo', file);

    showLoading(true);
    try {
        const resp = await fetch('/BOB_SYSTEM/admin/ajax/logo_upload.php', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        showLoading(false);

        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Uploaded!', text: data.message, timer: 1500, showConfirmButton: false });
            // Update preview
            const previewMap = {
                logo_left: 'logoLeftPreview',
                logo_right: 'logoRightPreview',
                logo_watermark: 'logoWatermarkPreview'
            };
            const previewEl = document.getElementById(previewMap[field]);
            if (previewEl && data.path) previewEl.src = data.path;
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Upload failed.', confirmButtonColor: '#003366' });
        }
    } catch (err) {
        showLoading(false);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Upload request failed.', confirmButtonColor: '#003366' });
    }
    inputEl.value = '';
}

/**
 * Save event settings
 */
async function saveSettings() {
    // Collect signatories from dynamic rows
    const sigRows = document.querySelectorAll('.signatory-row');
    const signatories = [];
    sigRows.forEach(row => {
        const name     = row.querySelector('.sig-name')?.value.trim() || '';
        const title    = row.querySelector('.sig-title')?.value.trim() || '';
        const fontSize = parseInt(row.querySelector('.sig-fontsize')?.value) || 11;
        if (name || title) signatories.push({ name, title, fontSize });
    });

    const payload = {
        event_title:    document.getElementById('settingEventTitle')?.value.trim() || '',
        event_subtitle: document.getElementById('settingEventSubtitle')?.value.trim() || '',
        signatories:    signatories
    };

    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/settings_save.php', 'POST', payload);
    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Saved!', text: data.message, timer: 1500, showConfirmButton: false });
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to save settings.', confirmButtonColor: '#003366' });
    }
}

/**
 * Toggle password field visibility
 */
function togglePwd(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

/**
 * Save admin credentials
 */
async function saveAdminCredentials() {
    const currentPassword = document.getElementById('adminCurrentPassword')?.value.trim() || '';
    const name            = document.getElementById('adminName')?.value.trim() || '';
    const email           = document.getElementById('adminEmail')?.value.trim() || '';
    const newPassword     = document.getElementById('adminNewPassword')?.value.trim() || '';

    if (!currentPassword) {
        Swal.fire({ icon: 'warning', title: 'Required', text: 'Enter your current password to confirm changes.', confirmButtonColor: '#003366' });
        return;
    }

    const payload = { current_password: currentPassword, name, email, new_password: newPassword };
    const data = await ajaxRequest('/BOB_SYSTEM/admin/ajax/admin_update.php', 'POST', payload);

    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Updated!', text: data.message, timer: 1800, showConfirmButton: false });
        document.getElementById('adminCurrentPassword').value = '';
        document.getElementById('adminNewPassword').value = '';
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to update credentials.', confirmButtonColor: '#003366' });
    }
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Judge form
    const judgeForm = document.getElementById('judgeForm');
    if (judgeForm) judgeForm.addEventListener('submit', saveJudge);

    // Band form
    const bandForm = document.getElementById('bandForm');
    if (bandForm) bandForm.addEventListener('submit', saveBand);

    // Auto-load based on page
    if (document.getElementById('judgesTableBody')) loadJudges();
    if (document.getElementById('bandsTableBody')) {
        loadBands();
        startPendingJudgesPoll();
        initAdminWebSocket();
    }
    if (document.getElementById('scoreCardsContainer')) loadScores();

    // Settings page
    if (document.getElementById('settingEventTitle')) loadSettings();

    // Rankings tabs
    const rankingTabs = document.querySelectorAll('[data-round-id]');
    rankingTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            loadRankings(this.dataset.roundId);
        });
    });

    // Load default rankings tab if on rankings page
    const activeRankingTab = document.querySelector('[data-round-id].active');
    if (activeRankingTab) {
        loadRankings(activeRankingTab.dataset.roundId);
    }
});
