<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<aside class="dash-sidebar">
    <a class="brand brand-light" href="../index.php">
        <span class="brand-mark" aria-hidden="true"></span>
        <span class="brand-text">Active<br><strong>Picklelabs</strong></span>
    </a>
    <nav class="dash-nav">
        <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="book-court.php" class="<?= $current === 'book-court.php' ? 'active' : '' ?>">Book a Court</a>
        <a href="my-bookings.php" class="<?= $current === 'my-bookings.php' ? 'active' : '' ?>">My Bookings</a>
        <a href="profile.php" class="<?= $current === 'profile.php' ? 'active' : '' ?>">My Profile</a>
    </nav>
    <a class="btn btn-ghost logout-link" href="../logout.php">Logout</a>
</aside>
