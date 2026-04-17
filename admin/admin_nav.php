<?php
$activePage = $activePage ?? ''; // Default to empty string if not set
?>
<nav class="admin-nav">
    <div class="admin-nav-brand">
        <img src="<?= BASE_URL ?>/assets/img/ub-logo-white.png" alt="UB Logo" class="logo-image-sm">
        <span>Bestination 2026</span>
    </div>
    <div class="admin-nav-links">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/participants.php" class="<?= $activePage === 'participants' ? 'active' : '' ?>"><i class="bi bi-people-fill"></i> Participants</a>
        <a href="<?= BASE_URL ?>/admin/export.php" class="<?= $activePage === 'export' ? 'active' : '' ?>"><i class="bi bi-file-earmark-spreadsheet-fill"></i> Export CSV</a>
        <a href="<?= BASE_URL ?>/admin/logout.php" class="nav-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    <button class="admin-nav-toggle" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
</nav>