<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Offcanvas sidebar for mobile -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="judgeSidebarMobile" aria-labelledby="judgeSidebarMobileLabel">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title" id="judgeSidebarMobileLabel"><i class="bi bi-music-note-beamed"></i> BotB Judge</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="nav flex-column">
            <a class="nav-link px-3 py-3 <?php echo $currentPage === 'score' ? 'active bg-primary text-white' : ''; ?>" href="/BOB_SYSTEM/judge/score.php">
                <i class="bi bi-pencil-square"></i> Score
            </a>
            <a class="nav-link px-3 py-3 <?php echo $currentPage === 'my_scores' ? 'active bg-primary text-white' : ''; ?>" href="/BOB_SYSTEM/judge/my_scores.php">
                <i class="bi bi-clock-history"></i> My Scores
            </a>
            <hr class="my-0">
            <a class="nav-link px-3 py-3 text-danger" href="/BOB_SYSTEM/logout.php">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </nav>
    </div>
</div>

<!-- Main content area (no desktop sidebar) -->
<div>
    <!-- Mobile top bar -->
    <nav class="navbar navbar-dark bg-primary d-lg-none">
        <div class="container-fluid">
            <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#judgeSidebarMobile">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-brand mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-music-note-beamed"></i>
                <span>Judge </span>
                <span class="badge bg-dark"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Judge'); ?></span>
            </span>
            <span></span>
        </div>
    </nav>

    <div id="judgeMainContent">
