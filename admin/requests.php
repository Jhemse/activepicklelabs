<?php
/*******************************************************************************
 * SECTION 1: CONNECT TO DATABASE, LOAD HELPERS, AND PROCESS FORM OR FILTERS
 *******************************************************************************/
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();

// Handle status confirmation, cancellation updates, or deletion via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($id && in_array($action, ['confirmed', 'cancelled', 'delete'], true)) {
        // ADDED: Handle deletion for cancelled bookings
        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Booking permanently deleted.');
        } else {
            // Fetch current status to intercept approval of cancellation requests
            $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $currentBooking = $stmt->fetch();

            // If it's a pending cancellation and admin clicks 'Confirm' (approve), 
            // treat it as a cancellation approval instead!
            if ($currentBooking && ($currentBooking['status'] === 'pending_cancellation' || $currentBooking['status'] === 'pending-cancellation') && $action === 'confirmed') {
                $action = 'cancelled';
            }

            updateBookingStatus($pdo, $id, $action);
            setFlash('success', 'Booking updated.');
        }
    }
    redirect('requests.php');
}


/*******************************************************************************
 * SECTION 2: FETCH ALL BOOKINGS AND APPLY STATUS FILTERING
 *******************************************************************************/
$filter = $_GET['status'] ?? 'all';
$bookings = getAllBookings($pdo);
if ($filter !== 'all') {
    $bookings = array_filter($bookings, function($b) use ($filter) {
        $status = $b['status'] ?? '';
        if ($filter === 'pending_cancellation') {
            return $status === 'pending_cancellation' || $status === 'pending-cancellation';
        }
        return $status === $filter;
    });
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
    <?php 
    /***************************************************************************
     * SECTION 3: LOAD THE ADMIN SIDEBAR NAVIGATION MENU
     ***************************************************************************/
    include __DIR__ . '/../components/admin-sidebar.php'; 
    ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>Booking Requests</h1></div>

        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="panel-card">
            <?php 
            /*******************************************************************
             * SECTION 4: STATUS FILTER BUTTONS (ALL, PENDING, CONFIRMED, CANCELLED)
             *******************************************************************/
            ?>
            <div class="actions-inline" style="margin-bottom:18px">
                <a class="btn btn-sm <?= $filter === 'all' ? 'btn-navy' : 'btn-ghost' ?>" href="?status=all">All</a>
                <a class="btn btn-sm <?= $filter === 'pending' ? 'btn-navy' : 'btn-ghost' ?>" href="?status=pending">Pending</a>
                <a class="btn btn-sm <?= $filter === 'confirmed' ? 'btn-navy' : 'btn-ghost' ?>" href="?status=confirmed">Confirmed</a>
                <a class="btn btn-sm <?= $filter === 'pending_cancellation' ? 'btn-navy' : 'btn-ghost' ?>" href="?status=pending_cancellation">Pending Cancellation</a>
                <a class="btn btn-sm <?= $filter === 'cancelled' ? 'btn-navy' : 'btn-ghost' ?>" href="?status=cancelled">Cancelled</a>
            </div>

            <?php 
            /*******************************************************************
             * SECTION 5: DISPLAY THE BOOKING REQUESTS TABLE AND ACTIONS
             *******************************************************************/
            ?>
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
                            
                            <td class="actions-inline">
                                <a class="btn btn-ghost btn-sm" href="request-details.php?id=<?= $b['id'] ?>">View</a>
                                <?php if ($b['status'] === 'pending'): ?>
                                    <form method="post"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="confirmed">
                                        <button class="btn btn-lime btn-sm" type="submit">Confirm</button></form>
                                <?php elseif ($b['status'] === 'pending_cancellation' || $b['status'] === 'pending-cancellation'): ?>
                                    <form method="post"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="confirmed">
                                        <button class="btn btn-lime btn-sm" data-confirm="Approve cancellation request?" type="submit">Approve Cancellation</button></form>
                                <?php endif; ?>
                                <?php if ($b['status'] !== 'cancelled'): ?>
                                    <form method="post"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="cancelled">
                                        <button class="btn btn-ghost btn-sm" data-confirm="Cancel this booking?" type="submit">Cancel</button></form>
                                <?php endif; ?>

                                <!-- ADDED: Delete button shown exclusively for cancelled bookings -->
                                <?php if ($b['status'] === 'cancelled'): ?>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to permanently delete this cancelled booking?');">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="btn btn-ghost btn-sm" style="color: #ef4444;" type="submit">Delete</button>
                                    </form>
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

<?php 
/*******************************************************************************
 * SECTION 6: FOOTER SCRIPTS & GLOBAL NAVIGATION
 *******************************************************************************/
?>
<script src="../assets/js/main.js"></script>

<!-- Floating Back to Home Button -->
<a href="../index.php" class="btn-back-home">Back to Home</a>

</body>
</html>