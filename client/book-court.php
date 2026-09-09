<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireLogin();
if (isAdmin()) { redirect('../admin/dashboard.php'); }

$courts  = getAvailableCourts($pdo);
$error   = null;
$success = null;
$type    = $_GET['type'] === 'open_play' ? 'open_play' : 'private';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courtId = (int) ($_POST['court_id'] ?? 0);
    $type    = $_POST['booking_type'] ?? 'private';
    $date    = clean($_POST['booking_date'] ?? '');
    $start   = clean($_POST['start_time'] ?? '');
    $end     = clean($_POST['end_time'] ?? '');
    $players = max(1, (int) ($_POST['players'] ?? 2));
    $notes   = clean($_POST['notes'] ?? '');

    if (!$courtId || !$date || !$start || !$end) {
        $error = 'Please fill in every required field.';
    } else {
        [$ok, $message] = createBooking($pdo, $_SESSION['user_id'], $courtId, $type, $date, $start, $end, $players, $notes);
        if ($ok) {
            setFlash('success', $message);
            redirect('my-bookings.php');
        } else {
            $error = $message;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book a Court - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/client-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>Book a Court</h1></div>

        <div class="panel-card" style="max-width:640px">
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

            <form method="post">
                <div class="field">
                    <label>Booking type</label>
                    <select name="booking_type">
                        <option value="private" <?= $type === 'private' ? 'selected' : '' ?>>Private Court Rental</option>
                        <option value="open_play" <?= $type === 'open_play' ? 'selected' : '' ?>>Open Play</option>
                    </select>
                </div>
                <div class="field">
                    <label>Court</label>
                    <select name="court_id" required>
                        <option value="">Select a court</option>
                        <?php foreach ($courts as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['court_name']) ?> - &#8369;<?= number_format($c['hourly_rate'], 2) ?>/hr</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Date</label>
                        <input type="date" name="booking_date" data-role="booking-date" required>
                    </div>
                    <div class="field">
                        <label>Players</label>
                        <input type="number" name="players" min="1" max="8" value="2" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Start time</label>
                        <input type="time" name="start_time" data-role="start-time" required>
                    </div>
                    <div class="field">
                        <label>End time</label>
                        <input type="time" name="end_time" data-role="end-time" required>
                    </div>
                </div>
                <div class="field">
                    <label>Notes (optional)</label>
                    <textarea name="notes" rows="3" placeholder="Paddle rental, coaching request, etc."></textarea>
                </div>
                <button type="submit" class="btn btn-solid btn-block">Submit Booking Request</button>
            </form>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
