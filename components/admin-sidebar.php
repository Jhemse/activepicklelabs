<?php
// =========================================================================
// ACTIVE PAGE DETECTION
// =========================================================================
// Capture the filename of the current script to highlight active menu links
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="dash-sidebar">
    <!-- ======================================================= -->
    <!-- SIDEBAR LOGO BRANDING SLOT                              -->
    <!-- ======================================================= -->
    <div class="sidebar-logo-slot">
        <a href="/ActivePicklelabs/index.php" aria-label="Active Picklelabs">
            <img src="/ActivePicklelabs/assets/images/WBEDEV-08.svg" alt="Active Picklelabs Logo" class="sidebar-logo-img">
        </a>
    </div>

    <!-- ======================================================= -->
    <!-- ADMIN NAVIGATION MENU                                   -->
    <!-- ======================================================= -->
    <nav class="dash-nav">
        <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="requests.php" class="<?= $current === 'requests.php' ? 'active' : '' ?>">Booking Requests</a>
        <a href="courts.php" class="<?= $current === 'courts.php' ? 'active' : '' ?>">Courts</a>
        <a href="services.php" class="<?= $current === 'services.php' ? 'active' : '' ?>">Services</a>
        <a href="clients.php" class="<?= $current === 'clients.php' ? 'active' : '' ?>">Clients</a>
        <a href="settings.php" class="<?= $current === 'settings.php' ? 'active' : '' ?>">Settings</a>
    </nav>

    <!-- ======================================================= -->
    <!-- LOGOUT ACTION LINK                                      -->
    <!-- ======================================================= -->
    <a class="btn btn-ghost logout-link" href="/ActivePicklelabs/logout.php">Logout</a>
</aside>