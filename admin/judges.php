<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/AdminController.php';

$controller = new AdminController(Database::getInstance());

$pageTitle = 'Judge Management — BotB Tabulator';
$pageScript = '/BOB_SYSTEM/assets/js/admin.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

        <div class="container-fluid p-4">
            <!-- Print header (hidden on screen) -->
            <div class="print-header">
                <h3>Battle of the Bands — NEUST 2026</h3>
                <p>Judge List</p>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class="bi bi-people"></i> Judge Management</h4>
                <button class="btn btn-primary" onclick="openAddJudgeModal()">
                    <i class="bi bi-plus-circle"></i> Add Judge
                </button>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="judgesTableBody">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <div class="spinner-border spinner-border-sm" role="status"></div> Loading judges...
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

<!-- Judge Add/Edit Modal -->
<div class="modal fade" id="judgeModal" tabindex="-1" aria-labelledby="judgeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="judgeModalLabel">Add Judge</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="judgeForm">
                <div class="modal-body">
                    <input type="hidden" id="judgeId" value="">

                    <div class="mb-3">
                        <label for="judgeName" class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control" id="judgeName" placeholder="Enter judge's full name" required>
                    </div>

                    <div class="mb-3">
                        <label for="judgeEmail" class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" id="judgeEmail" placeholder="Enter email address" required>
                    </div>

                    <div class="mb-3">
                        <label for="judgePassword" class="form-label fw-semibold">Password</label>
                        <input type="password" class="form-control" id="judgePassword" placeholder="Enter password" required>
                        <small class="form-text text-muted" id="judgePasswordHelp"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Judge
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
