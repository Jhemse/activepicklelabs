<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT b.*, c.court_name, c.hourly_rate, u.full_name, u.email, u.phone
     FROM bookings b JOIN courts c ON c.id = b.court_id JOIN users u ON u.id = b.user_id
     WHERE b.id = ?'
);
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect('requests.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['confirmed', 'cancelled'], true)) {
        updateBookingStatus($pdo, $id, $action);
        setFlash('success', 'Booking updated.');
        redirect('request-details.php?id=' . $id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request #<?= $booking['id'] ?> - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>Booking Request #<?= $booking['id'] ?></h1></div>

        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="panel-card" style="max-width:560px">
            <p><strong>Status:</strong> <span class="<?= statusBadgeClass($booking['status']) ?>"><?= ucfirst($booking['status']) ?></span></p>
            <p><strong>Client:</strong> <?= e($booking['full_name']) ?> (<?= e($booking['email']) ?>, <?= e($booking['phone']) ?>)</p>
            <p><strong>Court:</strong> <?= e($booking['court_name']) ?> - &#8369;<?= number_format($booking['hourly_rate'], 2) ?>/hr</p>
            <p><strong>Type:</strong> <?= $booking['booking_type'] === 'open_play' ? 'Open Play' : 'Private Rental' ?></p>
            <p><strong>Date:</strong> <?= formatDate($booking['booking_date']) ?></p>
            <p><strong>Time:</strong> <?= formatTime($booking['start_time']) ?> - <?= formatTime($booking['end_time']) ?></p>
            <p><strong>Players:</strong> <?= (int) $booking['players'] ?></p>
            <p><strong>Notes:</strong> <?= e($booking['notes']) ?: '—' ?></p>

            <div class="actions-inline" style="margin-top:20px">
                <?php if ($booking['status'] === 'pending'): ?>
                    <form method="post"><input type="hidden" name="action" value="confirmed">
                        <button class="btn btn-lime" type="submit">Confirm Booking</button></form>
                <?php endif; ?>
                <?php if ($booking['status'] !== 'cancelled'): ?>
                    <form method="post"><input type="hidden" name="action" value="cancelled">
                        <button class="btn btn-ghost" data-confirm="Cancel this booking?" type="submit">Cancel Booking</button></form>
                <?php endif; ?>
                <a class="btn btn-outline" style="color:var(--ink);border-color:#ccc" href="requests.php">&larr; Back to Requests</a>
            </div>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
