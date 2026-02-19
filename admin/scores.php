<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/AdminController.php';

// AdminController constructor enforces admin role
$controller = new AdminController(Database::getInstance());

$pageTitle = 'Score Management — BotB Tabulator';
$pageScript = '/BOB_SYSTEM/assets/js/admin.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Score Management</h4>
                <div class="input-group" style="max-width: 250px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="scoreSearchInput" placeholder="Search for band..." oninput="filterScoreCards(this.value)">
                </div>
            </div>

            <!-- Score cards container -->
            <div id="scoreCardsContainer">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                    <p class="mt-2">Loading scores...</p>
                </div>
            </div>

            <hr class="my-5">

            <!-- System Reset Section -->
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Danger Zone — System Reset</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">This will permanently delete <strong>ALL scores, bands, and judges</strong> from the system. The admin account will be preserved. This action <strong>cannot be undone</strong>.</p>
                    <button class="btn btn-danger" onclick="confirmSystemReset()">
                        <i class="bi bi-trash3-fill"></i> Reset All Data
                    </button>
                </div>
            </div>
        </div>

<!-- Edit Score Modal -->
<div class="modal fade" id="editScoreModal" tabindex="-1" aria-labelledby="editScoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editScoreModalLabel"><i class="bi bi-pencil"></i> Edit Score</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Judge:</strong> <span id="editScoreJudgeName"></span></p>
                <p><strong>Band:</strong> <span id="editScoreBandName"></span></p>
                <p><strong>Criteria:</strong> <span id="editScoreCriteriaName"></span></p>
                <div class="mb-3">
                    <label for="editScoreValue" class="form-label fw-semibold">Score <span class="text-muted fw-normal" id="editScoreMaxLabel"></span></label>
                    <input type="number" class="form-control" id="editScoreValue" min="0" step="0.01" required>
                    <div class="invalid-feedback" id="editScoreError">Invalid score.</div>
                </div>
                <input type="hidden" id="editScoreJudgeId">
                <input type="hidden" id="editScoreBandId">
                <input type="hidden" id="editScoreCriteriaId">
                <input type="hidden" id="editScoreMax">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveScoreEdit()">
                    <i class="bi bi-check-circle"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- System Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="resetModalLabel"><i class="bi bi-exclamation-triangle-fill"></i> Confirm System Reset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold text-danger">This will permanently delete ALL scores, bands, and judges.</p>
                <p>Type <code>RESET_ALL</code> below to confirm:</p>
                <input type="text" class="form-control" id="resetConfirmInput" placeholder="Type RESET_ALL">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="executeSystemReset()">
                    <i class="bi bi-trash3-fill"></i> Reset Everything
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
