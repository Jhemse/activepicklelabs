<?php
/*******************************************************************************
 * SECTION 1: CONNECT TO THE DATABASE, LOAD HELPERS, AND CHECK ADMIN ACCESS
 *******************************************************************************/
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();


/*******************************************************************************
 * SECTION 2: FETCH DASHBOARD STATISTICS AND RECENT BOOKING REQUESTS
 *******************************************************************************/
$stats = getAdminStats($pdo);
$recent = array_slice(getAllBookings($pdo), 0, 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php 
    /***************************************************************************
     * SECTION 3: LOAD THE ADMIN SIDEBAR NAVIGATION MENU
     ***************************************************************************/
    include __DIR__ . '/../components/admin-sidebar.php'; 
    ?>
    <main class="dash-main">
        <div class="dash-topbar">
            <h1>Admin Dashboard</h1>
            <div class="who"><?= e($_SESSION['full_name']) ?></div>
        </div>

        <?php 
        /*******************************************************************
         * SECTION 4: DISPLAY QUICK OVERVIEW STAT CARDS (NUMBERS)
         *******************************************************************/
        ?>
        <div class="stat-cards">
            <div class="stat-card"><div class="n"><?= $stats['clients'] ?></div><div class="l">Total Clients</div></div>
            <div class="stat-card"><div class="n"><?= $stats['courts'] ?></div><div class="l">Courts</div></div>
            <div class="stat-card"><div class="n"><?= $stats['pending'] ?></div><div class="l">Pending Requests</div></div>
            <div class="stat-card"><div class="n"><?= $stats['today'] ?></div><div class="l">Bookings Today</div></div>
        </div>

        <?php 
        /*******************************************************************
         * SECTION 5: SHOW RECENT BOOKING REQUESTS IN A TABLE
         *******************************************************************/
        ?>
        <div class="panel-card">
            <h2>Recent Booking Requests</h2>
            <?php if ($recent): ?>
                <table>
                    <thead><tr><th>Client</th><th>Date</th><th>Court</th><th>Type</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $b): ?>
                        <tr>
                            <td><?= e($b['full_name']) ?></td>
                            <td><?= formatDate($b['booking_date']) ?></td>
                            <td><?= e($b['court_name']) ?></td>
                            <td><?= $b['booking_type'] === 'open_play' ? 'Open Play' : 'Private' ?></td>

                            <td>
                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <span class="badge badge-confirmed">✓ Confirmed</span>
                                <?php elseif ($b['status'] === 'pending'): ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php elseif ($b['status'] === 'pending_cancellation' || $b['status'] === 'pending-cancellation'): ?>
                                    <span class="badge status-pending_cancellation">Pending Cancellation</span>
                                <?php elseif ($b['status'] === 'cancelled'): ?>
                                    <span class="badge badge-cancelled">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#fee2e2; color:#b91c1c;"><?= e($b['status'] !== '' ? $b['status'] : 'NULL / Empty') ?></span>
                                <?php endif; ?>
                            </td>
                            
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top:14px"><a href="requests.php">View all requests &rarr;</a></p>
            <?php else: ?>
                <div class="empty-state">No bookings yet.</div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Floating Back to Home Button -->
<a href="../index.php" class="btn-back-home">Back to Home</a>

</body>
</html>