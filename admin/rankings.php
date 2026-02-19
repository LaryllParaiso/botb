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
                <div class="print-logos">
                    <img id="printLogoLeft" src="/BOB_SYSTEM/assets/image 23.png" alt="NEUST Seal" class="print-logo">
                    <div class="print-title-block">
                        <h3 id="printEventTitle">Battle of the Bands — NEUST 2026</h3>
                        <h5 id="printEventSubtitle">118th Founding Anniversary / 28th Charter Day</h5>
                        <p>Official Rankings — <span id="printDate"><?php echo date('F j, Y'); ?></span></p>
                    </div>
                    <img id="printLogoRight" src="/BOB_SYSTEM/assets/Mask group.png" alt="BotB Logo" class="print-logo">
                </div>
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
                    <button class="btn btn-outline-success" onclick="downloadExcel()">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
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

            <!-- Tab Content (tabs just control round; table is shared below) -->
            <div class="tab-content mb-3">
                <div class="tab-pane fade show active" id="elimination-pane" role="tabpanel">
                    <!-- Elimination round selected; shared table below -->
                </div>
                <div class="tab-pane fade" id="finals-pane" role="tabpanel">
                    <!-- Grand finals selected; shared table below -->
                </div>
            </div>

            <!-- Shared rankings table used for both rounds -->
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

            <!-- Print watermark: fixed behind content on every page -->
            <div class="print-watermark">
                <img id="printWatermarkImg" src="/BOB_SYSTEM/assets/Mask group (1).png" alt="Watermark">
            </div>

            <!-- Print footer: judges + signatory (hidden on screen) -->
            <div class="print-footer">
                <div class="print-judges" id="printJudgesList"></div>
                <div class="print-signatories" id="printSignatories"></div>
            </div>

            <div class="text-muted small mt-3 no-print">
                <i class="bi bi-info-circle"></i> Rankings update when scores are submitted. Click <strong>Print</strong> for a clean printable view or <strong>Excel</strong> to download.
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
