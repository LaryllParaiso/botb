<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/AdminController.php';

$controller = new AdminController(Database::getInstance());

$pageTitle = 'Band Management — BotB Tabulator';
$pageScript = '/BOB_SYSTEM/assets/js/admin.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

        <div class="container-fluid p-4">
            <div class="print-header">
                <h3>Battle of the Bands — NEUST 2026</h3>
                <p>Band List</p>
            </div>

            <!-- Pending Judges Status Panel -->
            <div id="pendingJudgesPanel" class="mb-4"></div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <h4 class="mb-0"><i class="bi bi-music-player"></i> Band Management</h4>
                <button class="btn btn-primary btn-sm" onclick="openAddBandModal()">
                    <i class="bi bi-plus-circle"></i> Add Band
                </button>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 admin-table-compact">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Band Name</th>
                                    <th>Round</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="bandsTableBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <div class="spinner-border spinner-border-sm" role="status"></div> Loading bands...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- end mainContent flex wrapper -->
</div><!-- end d-flex -->

<!-- Band Add/Edit Modal -->
<div class="modal fade" id="bandModal" tabindex="-1" aria-labelledby="bandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="bandModalLabel">Add Band</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bandForm">
                <div class="modal-body">
                    <input type="hidden" id="bandId" value="">

                    <div class="mb-3">
                        <label for="bandName" class="form-label fw-semibold">Band Name</label>
                        <input type="text" class="form-control" id="bandName" placeholder="Enter band name" required>
                    </div>

                    <div class="mb-3">
                        <label for="bandRound" class="form-label fw-semibold">Round</label>
                        <select class="form-select" id="bandRound" required>
                            <option value="">Select round...</option>
                            <option value="1">Elimination</option>
                            <option value="2">Grand Finals</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="bandOrder" class="form-label fw-semibold">Performance Order</label>
                        <input type="number" class="form-control" id="bandOrder" min="1" placeholder="e.g., 1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Band
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
