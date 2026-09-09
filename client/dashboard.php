<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireLogin();
if (isAdmin()) { redirect('../admin/dashboard.php'); }

$bookings = getBookingsForUser($pdo, $_SESSION['user_id']);
$upcoming = array_filter($bookings, fn($b) => strtotime($b['booking_date']) >= strtotime(date('Y-m-d')) && $b['status'] !== 'cancelled');
$pendingCount   = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$confirmedCount = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/client-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar">
            <h1>Welcome back, <?= e(explode(' ', $_SESSION['full_name'])[0]) ?></h1>
            <div class="who"><?= e($_SESSION['full_name']) ?></div>
        </div>

        <div class="stat-cards">
            <div class="stat-card"><div class="n"><?= count($bookings) ?></div><div class="l">Total Bookings</div></div>
            <div class="stat-card"><div class="n"><?= count($upcoming) ?></div><div class="l">Upcoming Sessions</div></div>
            <div class="stat-card"><div class="n"><?= $pendingCount ?></div><div class="l">Pending Confirmation</div></div>
            <div class="stat-card"><div class="n"><?= $confirmedCount ?></div><div class="l">Confirmed</div></div>
        </div>

        <div class="panel-card">
            <h2>Your Upcoming Sessions</h2>
            <?php if ($upcoming): ?>
                <table>
                    <thead><tr><th>Date</th><th>Time</th><th>Court</th><th>Type</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($upcoming, 0, 5) as $b): ?>
                        <tr>
                            <td><?= formatDate($b['booking_date']) ?></td>
                            <td><?= formatTime($b['start_time']) ?> - <?= formatTime($b['end_time']) ?></td>
                            <td><?= e($b['court_name']) ?></td>
                            <td><?= $b['booking_type'] === 'open_play' ? 'Open Play' : 'Private' ?></td>
                            <td><span class="<?= statusBadgeClass($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No upcoming sessions yet.</p>
                    <a class="btn btn-solid" href="book-court.php">Book a Court</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
