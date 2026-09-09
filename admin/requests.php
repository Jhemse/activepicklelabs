<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id && in_array($action, ['confirmed', 'cancelled'], true)) {
        updateBookingStatus($pdo, $id, $action);
        setFlash('success', 'Booking updated.');
    }
    redirect('requests.php');
}

$filter = $_GET['status'] ?? 'all';
$bookings = getAllBookings($pdo);
if ($filter !== 'all') {
    $bookings = array_filter($bookings, fn($b) => $b['status'] === $filter);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Requests - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>Booking Requests</h1></div>

        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="panel-card">
            <div class="actions-inline" style="margin-bottom:18px">
                <a class="btn btn-sm <?= $filter === 'all' ? 'btn-navy' : 'btn-ghost' ?>" href="?status=all">All</a>
                <a class="btn btn-sm <?= $filter === 'pending' ? 'btn-navy' : 'btn-ghost' ?>" href="?status=pending">Pending</a>
                <a class="btn btn-sm <?= $filter === 'confirmed' ? 'btn-navy' : 'btn-ghost' ?>" href="?status=confirmed">Confirmed</a>
                <a class="btn btn-sm <?= $filter === 'cancelled' ? 'btn-navy' : 'btn-ghost' ?>" href="?status=cancelled">Cancelled</a>
            </div>

            <?php if ($bookings): ?>
                <table>
                    <thead><tr><th>Client</th><th>Date</th><th>Time</th><th>Court</th><th>Type</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?= e($b['full_name']) ?></td>
                            <td><?= formatDate($b['booking_date']) ?></td>
                            <td><?= formatTime($b['start_time']) ?> - <?= formatTime($b['end_time']) ?></td>
                            <td><?= e($b['court_name']) ?></td>
                            <td><?= $b['booking_type'] === 'open_play' ? 'Open Play' : 'Private' ?></td>
                            <td><span class="<?= statusBadgeClass($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
                            <td class="actions-inline">
                                <a class="btn btn-ghost btn-sm" href="request-details.php?id=<?= $b['id'] ?>">View</a>
                                <?php if ($b['status'] === 'pending'): ?>
                                    <form method="post"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="confirmed">
                                        <button class="btn btn-lime btn-sm" type="submit">Confirm</button></form>
                                <?php endif; ?>
                                <?php if ($b['status'] !== 'cancelled'): ?>
                                    <form method="post"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="cancelled">
                                        <button class="btn btn-ghost btn-sm" data-confirm="Cancel this booking?" type="submit">Cancel</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">No bookings match this filter.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
