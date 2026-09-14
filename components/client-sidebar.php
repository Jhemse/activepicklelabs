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
        <a href="../index.php" aria-label="Active Picklelabs">
            <img src="../assets/images/WBEDEV-08.svg" alt="Active Picklelabs Logo" class="sidebar-logo-img">
        </a>
    </div>

    <!-- ======================================================= -->
    <!-- CLIENT NAVIGATION MENU                                  -->
    <!-- ======================================================= -->
    <nav class="dash-nav">
        <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="book-court.php" class="<?= $current === 'book-court.php' ? 'active' : '' ?>">Book a Court</a>
        <a href="my-bookings.php" class="<?= $current === 'my-bookings.php' ? 'active' : '' ?>">My Bookings</a>
        <a href="profile.php" class="<?= $current === 'profile.php' ? 'active' : '' ?>">My Profile</a>
    </nav>

    <!-- ======================================================= -->
    <!-- LOGOUT ACTION LINK                                      -->
    <!-- ======================================================= -->
    <a class="btn btn-ghost logout-link" href="../logout.php">Logout</a>
</aside>