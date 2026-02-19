<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Offcanvas sidebar for mobile -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarMobile" aria-labelledby="sidebarMobileLabel">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title" id="sidebarMobileLabel"><i class="bi bi-music-note-beamed"></i> BotB Admin</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="nav flex-column">
            <a class="nav-link px-3 py-3 <?php echo $currentPage === 'bands' ? 'active bg-primary text-white' : ''; ?>" href="/BOB_SYSTEM/admin/bands.php">
                <i class="bi bi-music-player"></i> Bands
            </a>
            <a class="nav-link px-3 py-3 <?php echo $currentPage === 'judges' ? 'active bg-primary text-white' : ''; ?>" href="/BOB_SYSTEM/admin/judges.php">
                <i class="bi bi-people"></i> Judges
            </a>
            <a class="nav-link px-3 py-3 <?php echo $currentPage === 'rankings' ? 'active bg-primary text-white' : ''; ?>" href="/BOB_SYSTEM/admin/rankings.php">
                <i class="bi bi-trophy"></i> Rankings
            </a>
            <a class="nav-link px-3 py-3 <?php echo $currentPage === 'scores' ? 'active bg-primary text-white' : ''; ?>" href="/BOB_SYSTEM/admin/scores.php">
                <i class="bi bi-pencil-square"></i> Scores
            </a>
            <a class="nav-link px-3 py-3 <?php echo $currentPage === 'settings' ? 'active bg-primary text-white' : ''; ?>" href="/BOB_SYSTEM/admin/settings.php">
                <i class="bi bi-gear"></i> Settings
            </a>
            <hr class="my-0">
            <a class="nav-link px-3 py-3 text-danger" href="/BOB_SYSTEM/logout.php">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </nav>
    </div>
</div>

<!-- Fixed sidebar for desktop -->
<div class="d-flex">
    <nav id="sidebarDesktop" class="d-none d-lg-flex flex-column flex-shrink-0 bg-primary text-white" style="width: 250px; min-height: 100vh; position: fixed; top: 0; left: 0; z-index: 1000;">
        <div class="p-3 border-bottom border-light border-opacity-25">
            <h5 class="mb-0"><i class="bi bi-music-note-beamed"></i> BotB Admin</h5>
            <small class="text-white-50">NEUST 2026</small>
        </div>
        <nav class="nav flex-column mt-2">
            <a class="nav-link text-white px-3 py-3 <?php echo $currentPage === 'bands' ? 'bg-white bg-opacity-25' : ''; ?>" href="/BOB_SYSTEM/admin/bands.php">
                <i class="bi bi-music-player"></i> Bands
            </a>
            <a class="nav-link text-white px-3 py-3 <?php echo $currentPage === 'judges' ? 'bg-white bg-opacity-25' : ''; ?>" href="/BOB_SYSTEM/admin/judges.php">
                <i class="bi bi-people"></i> Judges
            </a>
            <a class="nav-link text-white px-3 py-3 <?php echo $currentPage === 'rankings' ? 'bg-white bg-opacity-25' : ''; ?>" href="/BOB_SYSTEM/admin/rankings.php">
                <i class="bi bi-trophy"></i> Rankings
            </a>
            <a class="nav-link text-white px-3 py-3 <?php echo $currentPage === 'scores' ? 'bg-white bg-opacity-25' : ''; ?>" href="/BOB_SYSTEM/admin/scores.php">
                <i class="bi bi-pencil-square"></i> Scores
            </a>
            <a class="nav-link text-white px-3 py-3 <?php echo $currentPage === 'settings' ? 'bg-white bg-opacity-25' : ''; ?>" href="/BOB_SYSTEM/admin/settings.php">
                <i class="bi bi-gear"></i> Settings
            </a>
        </nav>
        <div class="mt-auto p-3 border-top border-light border-opacity-25">
            <div class="mb-2 small text-white-50">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
            </div>
            <a class="btn btn-outline-light btn-sm w-100" href="/BOB_SYSTEM/logout.php">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Main content area -->
    <div class="flex-grow-1" style="margin-left: 250px;" id="mainContent">
        <!-- Mobile top bar -->
        <nav class="navbar navbar-dark bg-primary d-lg-none">
            <div class="container-fluid">
                <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand mb-0"><i class="bi bi-music-note-beamed"></i> BotB Admin</span>
                <span></span>
            </div>
        </nav>
