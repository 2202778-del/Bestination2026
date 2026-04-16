<?php
$activePage = $activePage ?? ''; // Default to empty string if not set
?>
<nav class="admin-nav">
    <div class="admin-nav-brand">
        <img src="<?= BASE_URL ?>/assets/img/ub-logo-white.png" alt="UB Logo" class="logo-image-sm">
        <span>Bestination 2026</span>
    </div>
    <div class="admin-nav-links">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/participants.php" class="<?= $activePage === 'participants' ? 'active' : '' ?>">Participants</a>
        <a href="<?= BASE_URL ?>/admin/export.php" class="<?= $activePage === 'export' ? 'active' : '' ?>">Export CSV</a>
        <a href="<?= BASE_URL ?>/admin/logout.php" class="nav-logout">Logout</a>
    </div>
    <button class="admin-nav-toggle" aria-label="Toggle navigation">☰</button>
</nav>