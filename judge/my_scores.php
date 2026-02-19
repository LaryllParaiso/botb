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

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-clock-history"></i> My Recent Scores</h5>
                <button class="btn btn-sm btn-outline-primary" onclick="loadMyScores()" title="Refresh">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div id="recentScoresContainer">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <span class="ms-2">Loading history...</span>
                </div>
            </div>
        </div>

    </div><!-- end judgeMainContent -->
</div><!-- end d-flex -->

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
