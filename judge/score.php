<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/JudgeController.php';

// JudgeController constructor enforces judge role
$controller = new JudgeController(Database::getInstance());

$pageTitle = 'Scoring — BotB Tabulator';
$pageScript = '/BOB_SYSTEM/assets/js/judge.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_judge.php';
?>

        <div class="container-fluid px-3 px-md-4 py-4">

            <!-- Two-column layout: scoring left, recent scores right -->
            <div class="row g-4">
                <!-- Scoring Column -->
                <div class="col-lg-5">
                    <div id="bandDisplay">
                        <!-- Waiting state -->
                        <div id="waitingState" class="text-center">
                            <div class="card border shadow-sm mx-auto" style="max-width: 420px;">
                                <div class="card-body py-5 px-4">
                                    <div class="spinner-border text-primary waiting-pulse mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mb-1 fw-medium">Waiting for the next band to be activated...</p>
                                    <small class="text-muted">This page will update automatically.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Scoring form -->
                        <div id="scoringState" style="display: none;">
                            <div class="card border-0 shadow-sm mx-auto">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3 px-4">
                                    <h4 class="mb-0 fw-bold" id="bandName"></h4>
                                    <span class="badge bg-accent text-dark" id="roundBadge"></span>
                                </div>
                                <div class="card-body p-4">
                                    <form id="scoringForm">
                                        <div id="criteriaFields"></div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="score-total-inline fw-bold">
                                                Weighted Total: <span id="weightedTotal" class="text-accent">0.00</span>
                                            </div>
                                            <button type="submit" class="btn btn-accent px-4 py-2 fw-semibold" id="submitBtn" disabled>
                                                Submit Scores
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Scores Column (desktop only) -->
                <div class="col-lg-7 d-none d-lg-block">
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
