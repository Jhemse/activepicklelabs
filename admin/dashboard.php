<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();

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
    <?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar">
            <h1>Admin Dashboard</h1>
            <div class="who"><?= e($_SESSION['full_name']) ?></div>
        </div>

        <div class="stat-cards">
            <div class="stat-card"><div class="n"><?= $stats['clients'] ?></div><div class="l">Total Clients</div></div>
            <div class="stat-card"><div class="n"><?= $stats['courts'] ?></div><div class="l">Courts</div></div>
            <div class="stat-card"><div class="n"><?= $stats['pending'] ?></div><div class="l">Pending Requests</div></div>
            <div class="stat-card"><div class="n"><?= $stats['today'] ?></div><div class="l">Bookings Today</div></div>
        </div>

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
                            <td><span class="<?= statusBadgeClass($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
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
</body>
</html>
