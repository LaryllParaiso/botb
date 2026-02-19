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

        <div class="container-fluid p-4">

            <div class="d-none d-lg-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-uppercase text-muted fw-semibold small letter-spacing-1">Judge</span>
                    <span class="badge bg-dark fs-6 rounded-pill px-3 py-2 shadow-sm">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Judge'); ?>
                    </span>
                </div>
            </div>

            <!-- Two-column layout: scoring left, recent scores right on desktop -->
            <div class="row">
                <!-- Scoring Column -->
                <div class="col-lg-6">
                    <div id="bandDisplay">
                        <!-- Waiting state -->
                        <div id="waitingState" class="text-center py-5">
                            <div class="card mx-auto" style="max-width: 500px;">
                                <div class="card-body py-5">
                                    <div class="spinner-border text-primary waiting-pulse mb-3" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <h5 class="text-muted">Waiting for the next band to be activated...</h5>
                                    <small class="text-muted">This page will update automatically.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Scoring form -->
                        <div id="scoringState" style="display: none;">
                            <div class="card mx-auto">
                                <div class="card-header bg-primary text-white text-center py-3">
                                    <h4 class="mb-1" id="bandName"></h4>
                                    <span class="badge bg-accent text-dark" id="roundBadge"></span>
                                </div>
                                <div class="card-body p-4">
                                    <form id="scoringForm">
                                        <div id="criteriaFields"></div>
                                        <hr>
                                        <div class="score-total-box text-center mb-3">
                                            Weighted Total: <span id="weightedTotal">0.00</span>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="submitBtn" disabled>
                                            <i class="bi bi-check-circle"></i> Submit Scores
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Read-only submitted state -->
                        <div id="submittedState" style="display: none;">
                            <div class="card mx-auto">
                                <div class="card-header bg-success text-white text-center py-3">
                                    <h5 class="mb-0"><i class="bi bi-check-circle-fill"></i> Scores Submitted</h5>
                                </div>
                                <div class="card-body p-4">
                                    <div id="submittedScores"></div>
                                    <hr>
                                    <div class="score-total-box text-center">
                                        Weighted Total: <span id="submittedTotal">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Scores Column (desktop only — mobile uses separate page) -->
                <div class="col-lg-6 d-none d-lg-block">
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
                    <div class="mt-3 text-center">
                        <a href="/BOB_SYSTEM/logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-left"></i> Logout
                        </a>
                    </div>
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
