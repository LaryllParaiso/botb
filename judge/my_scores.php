<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/JudgeController.php';

// JudgeController constructor enforces judge role
$controller = new JudgeController(Database::getInstance());

$pageTitle = 'My Scores — BotB Tabulator';
$pageScript = '/BOB_SYSTEM/assets/js/judge.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_judge.php';
?>

        <div class="container-fluid px-3 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-clock-history"></i> My Recent Scores</h5>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="max-width: 200px;">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="scoreSearchInput" placeholder="Search for band...">
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadMyScores()" title="Refresh">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            <div id="recentScoresContainer">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <span class="ms-2">Loading history...</span>
                </div>
            </div>
        </div>

    </div><!-- end judgeMainContent -->
</div><!-- end wrapper -->

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
