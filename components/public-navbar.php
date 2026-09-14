<?php
// =========================================================================
// NAVBAR CONFIGURATION & INITIALIZATION
// =========================================================================
// Expects $settings (array) to already be loaded by the including page.
$siteName = $settings['site_name'] ?? 'Active Picklelabs';
$current  = basename($_SERVER['SCRIPT_NAME']);
?>

<header class="site-header">
    <div class="nav-wrap">
        <!-- ======================================================= -->
        <!-- BRAND LOGO & HOME LINK SLOT                             -->
        <!-- ======================================================= -->
        <a href="index.php#home" class="brand" aria-label="<?= e($siteName) ?>"></a>

        <div class="brand-logo-slot">
            <img src="assets/images/WBEDEV-08.svg" alt="<?= e($siteName) ?> Logo" class="brand-logo-img">
        </div>

        <!-- ======================================================= -->
        <!-- MAIN PUBLIC NAVIGATION LINKS                            -->
        <!-- ======================================================= -->
        <nav class="main-nav" id="mainNav">
            <a href="index.php#home" class="<?= $current === 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="index.php#about">About Us</a>
            <a href="index.php#booking">Courts &amp; Booking</a>
            <a href="index.php#services">Services</a>
            <a href="index.php#contact">Contact Us</a>
        </nav>

        <!-- ======================================================= -->
        <!-- NAVIGATION AUTHENTICATION ACTIONS & BUTTONS             -->
        <!-- ======================================================= -->
        <div class="nav-actions">
            <?php if (isLoggedIn()): ?>
                <a class="btn btn-outline" href="<?= isAdmin() ? 'admin/dashboard.php' : 'client/dashboard.php' ?>">
                    My Dashboard
                </a>
                <a class="btn btn-ghost" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="btn btn-outline" href="login.php">Login</a>
                <a class="btn btn-solid" href="register.php">Register</a>
            <?php endif; ?>
        </div>

        <!-- ======================================================= -->
        <!-- MOBILE NAVIGATION TOGGLE BUTTON                         -->
        <!-- ======================================================= -->
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>