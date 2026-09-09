<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<aside class="dash-sidebar">
    <a class="brand brand-light" href="../index.php">
        <span class="brand-mark" aria-hidden="true"></span>
        <span class="brand-text">Active<br><strong>Picklelabs</strong></span>
    </a>
    <nav class="dash-nav">
        <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="requests.php" class="<?= $current === 'requests.php' ? 'active' : '' ?>">Booking Requests</a>
        <a href="courts.php" class="<?= $current === 'courts.php' ? 'active' : '' ?>">Courts</a>
        <a href="services.php" class="<?= $current === 'services.php' ? 'active' : '' ?>">Services</a>
        <a href="clients.php" class="<?= $current === 'clients.php' ? 'active' : '' ?>">Clients</a>
        <a href="settings.php" class="<?= $current === 'settings.php' ? 'active' : '' ?>">Settings</a>
    </nav>
    <a class="btn btn-ghost logout-link" href="../logout.php">Logout</a>
</aside>
