<?php
// =========================================================================
// DEPENDENCIES & CORE INITIALIZATION
// =========================================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

// PURPOSE: Enforce user login and restrict admin users 
// from viewing client booking pages.
requireLogin();
if (isAdmin()) { redirect('../admin/dashboard.php'); }

// =========================================================================
// FORM SUBMISSION HANDLING (POST REQUESTS: DELETE & CANCEL)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- SCENARIO A: Delete Booking/Registration Record ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete_booking') {
        // Split the combined delete_target string back into ID and type
        list($parsedId, $deleteType) = explode('|', $_POST['delete_target']);
        $deleteId   = (int) $parsedId;
        $deleteType = $deleteType ?? 'private';

        // 1. First, check if the booking/registration is confirmed before allowing deletion
        if ($deleteType === 'open_play') {
            $checkStmt = $pdo->prepare("SELECT status FROM open_play_registrations WHERE id = :id AND user_id = :user_id");
        } else {
            $checkStmt = $pdo->prepare("SELECT status FROM bookings WHERE id = :id AND user_id = :user_id");
        }
        $checkStmt->execute(['id' => $deleteId, 'user_id' => $_SESSION['user_id']]);
        $record = $checkStmt->fetch();

        // 2. Block deletion if the status is confirmed
        if ($record && $record['status'] === 'confirmed') {
            setFlash('error', 'Confirmed bookings cannot be deleted.');
        } else {
            if ($deleteType === 'open_play') {
                $stmt = $pdo->prepare("DELETE FROM open_play_registrations WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $deleteId, 'user_id' => $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $deleteId, 'user_id' => $_SESSION['user_id']]);
            }
            setFlash('success', 'Booking deleted successfully.');
        }

        redirect('my-bookings.php');
    }

    // --- SCENARIO B: Request Cancellation (Pending Admin Approval) ---
    if (isset($_POST['cancel_id'])) {
        $cancelId   = (int) $_POST['cancel_id'];
        $cancelType = $_POST['cancel_type'] ?? 'private';

        if ($cancelType === 'open_play') {
            // Set status to 'pending_cancellation' so it stays on schedules until admin confirms
            $stmt = $pdo->prepare("UPDATE open_play_registrations SET status = 'pending_cancellation' WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $cancelId, 'user_id' => $_SESSION['user_id']]);
        } else {
            // Set private booking status to 'pending_cancellation' instead of immediate cancellation
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'pending_cancellation' WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $cancelId, 'user_id' => $_SESSION['user_id']]);
        }

        setFlash('success', 'Cancellation request submitted. Awaiting admin approval.');
        redirect('my-bookings.php');
    }
}

// =========================================================================
// FILTER & DATA FETCHING (COMBINED PRIVATE + OPEN PLAY)
// =========================================================================

// Capture active tab filter parameter (all, confirmed, pending, cancelled)
$activeTab = strtolower(trim($_GET['status'] ?? 'all'));
$user_id   = $_SESSION['user_id'];

// PURPOSE: Construct a unified SQL UNION query to merge standard private bookings 
// and open play registrations into a single stream for the user history table.
$query = "
    SELECT 
        id,
        user_name,
        booking_date,
        start_time,
        end_time,
        court_name,
        booking_type,
        status,
        players_or_notes,
        total_amount,
        created_at
    FROM (
        -- Subquery 1: Standard Private Court Bookings
        SELECT 
            b.id AS id,
            u.full_name AS user_name,
            b.booking_date AS booking_date,
            b.start_time AS start_time,
            b.end_time AS end_time,
            c.court_name AS court_name,
            COALESCE(b.booking_type, 'private') AS booking_type,
            b.status AS status,
            CONCAT('Players: ', COALESCE(b.players, 2), IF(b.notes IS NOT NULL AND b.notes != '', CONCAT(' | Notes: ', b.notes), '')) AS players_or_notes,
            COALESCE(b.total_price, 0.00) AS total_amount,
            b.created_at AS created_at
        FROM bookings b
        JOIN courts c ON b.court_id = c.id
        JOIN users u ON b.user_id = u.id
        WHERE b.user_id = :user_id1

        UNION ALL

        -- Subquery 2: Admin-Hosted Open Play Sessions Registrations
        SELECT 
            opr.id AS id,
            u.full_name AS user_name,
            ops.session_date AS booking_date,
            ops.start_time AS start_time,
            ops.end_time AS end_time,
            c.court_name AS court_name,
            'open_play' AS booking_type,
            opr.status AS status,
            CONCAT('Registered Players: ', opr.num_players, ' | Session Slots: ', ops.max_slots) AS players_or_notes,
            ROUND(opr.num_players * ops.price_per_player, 2) AS total_amount,
            opr.created_at AS created_at
        FROM open_play_registrations opr
        JOIN open_play_sessions ops ON opr.session_id = ops.id
        JOIN courts c ON ops.court_id = c.id
        JOIN users u ON opr.user_id = u.id
        WHERE opr.user_id = :user_id2
    ) AS combined_bookings
";

// Append status filter condition if a specific tab is selected
if (in_array($activeTab, ['confirmed', 'pending', 'cancelled'])) {
    $query .= " WHERE status = :active_tab ";
}
$query .= " ORDER BY booking_date DESC, start_time DESC";

// Execute prepared statement with bound parameters
$stmt = $pdo->prepare($query);
$params = [
    'user_id1' => $user_id,
    'user_id2' => $user_id
];
if (in_array($activeTab, ['confirmed', 'pending', 'cancelled'])) {
    $params['active_tab'] = $activeTab;
}
$stmt->execute($params);
$bookings = $stmt->fetchAll();
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

        <!-- Flash Notification Display -->
        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($err = getFlash('error')): ?><div class="alert alert-danger" style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:8px; margin-bottom:20px;"><?= e($err) ?></div><?php endif; ?>

        <!-- ======================================================= -->
        <!-- BOOKING FILTER TABS NAVIGATION                          -->
        <!-- ======================================================= -->
        <div class="booking-filter-tabs">
            <a href="my-bookings.php?status=all" class="tab-btn <?= $activeTab === 'all' ? 'active' : '' ?>">All</a>
            <a href="my-bookings.php?status=confirmed" class="tab-btn tab-confirmed <?= $activeTab === 'confirmed' ? 'active' : '' ?>">✓ Confirmed</a>
            <a href="my-bookings.php?status=pending" class="tab-btn <?= $activeTab === 'pending' ? 'active' : '' ?>">Pending</a>
            <a href="my-bookings.php?status=cancelled" class="tab-btn <?= $activeTab === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
        </div>

        <!-- ======================================================= -->
        <!-- BOOKINGS DATA TABLE PANEL                               -->
        <!-- ======================================================= -->
        <div class="panel-card">
            <?php if ($bookings): ?>
                <div class="table-responsive">
                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Court</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr class="<?= $b['status'] === 'confirmed' ? 'confirmed-row' : '' ?>">
                                <td><strong><?= formatDate($b['booking_date']) ?></strong></td>
                                <td><?= formatTime($b['start_time']) ?> - <?= formatTime($b['end_time']) ?></td>
                                <td><?= e($b['court_name']) ?></td>
                                <td>
                                    <span class="badge-type <?= $b['booking_type'] ?>">
                                        <?= $b['booking_type'] === 'open_play' ? 'Open Play' : 'Private' ?>
                                    </span>
                                </td>

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

                                <td>
                                   <div class="action-cell">
    <!-- VIEW DETAILS MODAL TRIGGER BUTTON -->
    <button type="button" 
            class="btn btn-ghost btn-sm btn-view-details" 
            data-id="<?= $b['id'] ?>"
            data-customer="<?= e($b['user_name'] ?? $_SESSION['user_name'] ?? 'Customer') ?>"
            data-date="<?= formatDate($b['booking_date']) ?>"
            data-time="<?= formatTime($b['start_time']) ?> - <?= formatTime($b['end_time']) ?>"
            data-court="<?= e($b['court_name']) ?>"
            data-type="<?= $b['booking_type'] === 'open_play' ? 'Open Play' : 'Private Court' ?>"
            data-status="<?= ucwords(str_replace('_', ' ', $b['status'])) ?>"
            data-info="<?= e($b['players_or_notes'] ?? 'None') ?>"
            data-amount="₱<?= number_format($b['total_amount'] ?? 0, 2) ?>">
        View
    </button>

    <!-- CONDITIONAL ACTIONS: Cancel if confirmed, Delete if pending or cancelled -->
    <?php if ($b['status'] === 'confirmed'): ?>
        <form method="post" style="display:inline">
            <input type="hidden" name="cancel_id" value="<?= $b['id'] ?>">
            <input type="hidden" name="cancel_type" value="<?= e($b['booking_type']) ?>">
            <button class="btn btn-ghost btn-sm btn-cancel-action" data-confirm="Request cancellation for this booking?" type="submit">Cancel</button>
        </form>
    <?php elseif ($b['status'] === 'pending' || $b['status'] === 'cancelled'): ?>
        <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
            <input type="hidden" name="action" value="delete_booking">
            <input type="hidden" name="delete_target" value="<?= $b['id'] . '|' . e($b['booking_type']) ?>">
            <button class="btn btn-ghost btn-sm" style="color: #ef4444;" type="submit">Delete</button>
        </form>
    <?php endif; ?>
</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- FALLBACK: Displayed when no records match filter criteria -->
                <div class="empty-state">
                    <p>No <?= $activeTab !== 'all' ? e($activeTab) : '' ?> bookings found.</p>
                    <a class="btn btn-solid" href="book-court.php">Book a Court</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Include Modal Component for Viewing Detailed Receipt/Info -->
<?php include __DIR__ . '/../components/view-modal.php'; ?>

<!-- ========================================================================= -->
<!-- CLIENT-SIDE MODAL POPUP INTERACTION SCRIPT                                -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('detailsModal');
    const closeModal = document.getElementById('closeModal');
    const backModalBtn = document.getElementById('backModalBtn');

    // Helper function to hide the details popup modal
    const hideModal = () => {
        modal.style.display = 'none';
        modal.classList.remove('open');
    };

    // Attach click listeners to all table 'View' buttons to populate data dynamically
    document.querySelectorAll('.btn-view-details').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('modalCustomer').textContent = this.dataset.customer || '--';
            document.getElementById('modalCourt').textContent = this.dataset.court || '--';
            document.getElementById('modalType').textContent = this.dataset.type || '--';
            document.getElementById('modalDate').textContent = this.dataset.date || '--';
            document.getElementById('modalTime').textContent = this.dataset.time || '--';
            document.getElementById('modalInfo').textContent = this.dataset.info || 'None';
            document.getElementById('modalAmount').textContent = this.dataset.amount || '₱0.00';
            
            const statusPill = document.getElementById('modalStatusPill');
            if (statusPill) {
                const status = this.dataset.status || 'Confirmed';
                statusPill.textContent = status;
                statusPill.className = 'receipt-status-pill status-' + status.toLowerCase().replace(/\s+/g, '');
            }

            modal.style.display = 'flex';
            modal.classList.add('open');
        });
    });

    // Modal dismissal event bindings
    if (closeModal) closeModal.addEventListener('click', hideModal);
    if (backModalBtn) backModalBtn.addEventListener('click', hideModal);
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) hideModal();
    });
});
</script>

<script src="../assets/js/main.js"></script>

<a href="../index.php" class="btn-back-home">Back to Home</a>

</body>
</html>