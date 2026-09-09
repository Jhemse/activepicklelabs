<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireLogin();
if (isAdmin()) { redirect('../admin/dashboard.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    cancelBooking($pdo, (int) $_POST['cancel_id'], $_SESSION['user_id']);
    setFlash('success', 'Booking cancelled.');
    redirect('my-bookings.php');
}

$bookings = getBookingsForUser($pdo, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/client-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>My Bookings</h1></div>

        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="panel-card">
            <?php if ($bookings): ?>
                <table>
                    <thead><tr><th>Date</th><th>Time</th><th>Court</th><th>Type</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?= formatDate($b['booking_date']) ?></td>
                            <td><?= formatTime($b['start_time']) ?> - <?= formatTime($b['end_time']) ?></td>
                            <td><?= e($b['court_name']) ?></td>
                            <td><?= $b['booking_type'] === 'open_play' ? 'Open Play' : 'Private' ?></td>
                            <td><span class="<?= statusBadgeClass($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
                            <td>
                                <?php if ($b['status'] !== 'cancelled'): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="cancel_id" value="<?= $b['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" data-confirm="Cancel this booking?" type="submit">Cancel</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't booked a court yet.</p>
                    <a class="btn btn-solid" href="book-court.php">Book a Court</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
