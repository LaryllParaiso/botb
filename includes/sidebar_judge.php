<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Mobile offcanvas sidebar -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="judgeSidebarMobile" aria-labelledby="judgeSidebarMobileLabel">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title fw-bold" id="judgeSidebarMobileLabel">
            <i class="bi bi-music-note-beamed"></i> BotB Judge
        </h5>
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

<!-- Desktop: top navbar with name + logout -->
<nav class="navbar navbar-dark bg-primary judge-topbar d-none d-lg-flex">
    <div class="container-fluid px-3 px-md-4">
        <span class="navbar-brand mb-0 fw-bold fs-5">
            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Judge'); ?>
        </span>
        <a href="/BOB_SYSTEM/logout.php" class="btn btn-link text-danger text-decoration-none fw-semibold p-0">
            <i class="bi bi-box-arrow-right"></i> Log Out
        </a>
    </div>
</nav>

<!-- Mobile: top bar with hamburger + judge name -->
<nav class="navbar navbar-dark bg-primary d-lg-none judge-topbar">
    <div class="container-fluid px-3">
        <button class="btn btn-outline-light border-0 p-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#judgeSidebarMobile">
            <i class="bi bi-list fs-4"></i>
        </button>
        <span class="navbar-brand mb-0 fw-bold">
            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Judge'); ?>
        </span>
        <span style="width:32px;"></span>
    </div>
</nav>

<div>
    <div id="judgeMainContent">
