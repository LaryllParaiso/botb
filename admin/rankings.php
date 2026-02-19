<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/AdminController.php';

$controller = new AdminController(Database::getInstance());

$pageTitle = 'Rankings — BotB Tabulator';
$pageScript = '/BOB_SYSTEM/assets/js/admin.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

        <div class="container-fluid p-4">
            <!-- Print header (hidden on screen, shown on print) -->
            <div class="print-header">
                <h3>Battle of the Bands — NEUST 2026</h3>
                <h5>118th Founding Anniversary / 28th Charter Day</h5>
                <p>Official Rankings — <?php echo date('F j, Y'); ?></p>
                <hr>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <h4 class="mb-0"><i class="bi bi-trophy"></i> Rankings</h4>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="topNInput" class="form-label mb-0 fw-semibold text-nowrap">
                            <i class="bi bi-star-fill text-warning"></i> Top
                        </label>
                        <input type="number" id="topNInput" class="form-control form-control-sm"
                               value="8" min="1" max="100" style="width: 70px;"
                               onchange="reloadCurrentRankings()">
                        <span class="text-muted text-nowrap">bands highlighted</span>
                    </div>
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>

            <!-- Round Tabs -->
            <ul class="nav nav-tabs mb-3 no-print" id="roundTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="elimination-tab" data-bs-toggle="tab"
                            data-bs-target="#elimination-pane" type="button" role="tab"
                            data-round-id="1">
                        <i class="bi bi-filter-circle"></i> Elimination
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="finals-tab" data-bs-toggle="tab"
                            data-bs-target="#finals-pane" type="button" role="tab"
                            data-round-id="2">
                        <i class="bi bi-star"></i> Grand Finals
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <div class="tab-pane fade show active" id="elimination-pane" role="tabpanel">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="rankingsTable">
                                    <thead class="table-dark" id="rankingsTableHead">
                                        <tr>
                                            <th>Rank</th>
                                            <th>Band Name</th>
                                            <th>Average Score</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rankingsTableBody">
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">
                                                <div class="spinner-border spinner-border-sm" role="status"></div> Loading rankings...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="finals-pane" role="tabpanel">
                    <!-- Reuses same table via JS -->
                </div>
            </div>

            <div class="text-muted small mt-3 no-print">
                <i class="bi bi-info-circle"></i> Rankings update when scores are submitted. Click <strong>Print</strong> for a clean printable view.
            </div>
        </div>

    </div><!-- end mainContent flex wrapper -->
</div><!-- end d-flex -->

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
