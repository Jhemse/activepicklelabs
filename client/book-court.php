<?php
// =========================================================================
// DEPENDENCIES & CORE INITIALIZATION
// =========================================================================


date_default_timezone_set('Asia/Manila'); // Or your local timezone
require_once __DIR__ . '/../includes/db.php';
// ...


require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

// PURPOSE: Enforce user authentication and redirect admin users 
// away from client-facing booking pages to the admin dashboard.
requireLogin();
if (isAdmin()) { redirect('../admin/dashboard.php'); }

$courts   = getAvailableCourts($pdo);
$error    = null;
$success = null;

// Define today's date string for schedule lookups
$todayDate = date('Y-m-d');


// =========================================================================
// SCHEDULE DATA PREPARATION & MERGING
// =========================================================================

// PURPOSE: Initialize all available courts in the schedule array first 
// so that empty courts (like Court 2 or Court 3) don't disappear from the sidebar.
$courtSchedule = [];
foreach ($courts as $court) {
    $courtSchedule[$court['id']] = [
        'name' => $court['court_name'],
        'bookings' => []
    ];
}

// PURPOSE: Fetch today's confirmed, pending, and pending cancellation private court bookings from the database.
$stmtPrivate = $pdo->prepare("
    SELECT c.id AS court_id, b.start_time, b.end_time, b.status 
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    WHERE b.booking_date = ? AND b.status IN ('confirmed', 'pending', 'pending_cancellation')
");
$stmtPrivate->execute([$todayDate]);
$privateBookings = $stmtPrivate->fetchAll(PDO::FETCH_ASSOC);

// PURPOSE: Fetch today's active open play sessions to display them side-by-side with private bookings.
$stmtOpenPlay = $pdo->prepare("
    SELECT c.id AS court_id, ops.start_time, ops.end_time, 'confirmed' AS status
    FROM open_play_sessions ops
    JOIN courts c ON ops.court_id = c.id
    WHERE ops.session_date = ? AND ops.status = 'open'
");
$stmtOpenPlay->execute([$todayDate]);
$openPlaySessions = $stmtOpenPlay->fetchAll(PDO::FETCH_ASSOC);

// EDIT: Fetch today's pending cancellation open play registrations so they show up on the sidebar
$stmtOpenPlayCancels = $pdo->prepare("
    SELECT c.id AS court_id, ops.start_time, ops.end_time, opr.status 
    FROM open_play_registrations opr
    JOIN open_play_sessions ops ON opr.session_id = ops.id
    JOIN courts c ON ops.court_id = c.id
    WHERE ops.session_date = ? AND opr.status = 'pending_cancellation' AND opr.user_id = ?
");
$stmtOpenPlayCancels->execute([$todayDate, $_SESSION['user_id']]);
$openPlayCancels = $stmtOpenPlayCancels->fetchAll(PDO::FETCH_ASSOC);

// EDIT: Merge pending cancellation open play items into the open play stream
$openPlaySessions = array_merge($openPlaySessions, $openPlayCancels);

// PURPOSE: Merge private bookings and open play slots together, 
// then map them into their respective court schedule arrays while avoiding duplicates.
$allTodaySlots = array_merge($privateBookings, $openPlaySessions);

foreach ($allTodaySlots as $row) {
    $cId = $row['court_id'];
    if (isset($courtSchedule[$cId]) && $row['start_time']) {
        $timeFormatted = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
        $exists = false;
        
        // Check for identical time slots to prevent duplicate display entries
        foreach ($courtSchedule[$cId]['bookings'] as $b) {
            if ($b['time'] === $timeFormatted) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $courtSchedule[$cId]['bookings'][] = [
                'time' => $timeFormatted,
                'status' => $row['status']
            ];
        }
    }
}

// PURPOSE: Sort each court's schedule entries chronologically by their start time.
foreach ($courtSchedule as $cId => &$data) {
    usort($data['bookings'], function($a, $b) {
        return strtotime(explode(' - ', $a['time'])[0]) <=> strtotime(explode(' - ', $b['time'])[0]);
    });
}
unset($data);


// =========================================================================
// OPEN PLAY SESSION QUERY (IF JOIN REQUESTED)
// =========================================================================

// Check if user is attempting to view/join a specific Open Play session via URL parameter
$openPlayId = (int) ($_GET['open_play_id'] ?? 0);
$openPlaySession = null;

if ($openPlayId > 0) {
    // PURPOSE: Fetch session details along with a subquery counting currently confirmed players
    $stmt = $pdo->prepare("SELECT ops.*, c.court_name,
                          (SELECT COALESCE(SUM(num_players), 0) FROM open_play_registrations WHERE session_id = ops.id AND status = 'confirmed') as confirmed_players 
                          FROM open_play_sessions ops 
                          JOIN courts c ON ops.court_id = c.id 
                          WHERE ops.id = ? AND ops.status = 'open'");
    $stmt->execute([$openPlayId]);
    $openPlaySession = $stmt->fetch();
}


// =========================================================================
// FORM SUBMISSION HANDLING (POST REQUESTS)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'private_booking';

    if ($action === 'join_open_play') {
        // --- SCENARIO A: Joining an Open Play Session ---
        $sessionId  = (int) $_POST['session_id'];
        $numPlayers = max(1, (int) $_POST['num_players']);
        $unitPrice  = (float) $_POST['price_per_player'];
        $totalPrice = $numPlayers * $unitPrice;

        // PURPOSE: Check real-time slot capacity before processing the join request
        $sessStmt = $pdo->prepare("SELECT max_slots, 
                                  (SELECT COALESCE(SUM(num_players), 0) FROM open_play_registrations WHERE session_id = ? AND status = 'confirmed') as booked 
                                   FROM open_play_sessions WHERE id = ?");
        $sessStmt->execute([$sessionId, $sessionId]);
        $sessData = $sessStmt->fetch();

        // Validate that remaining slots can accommodate the requested number of players
        if ($sessData && ($sessData['booked'] + $numPlayers) <= $sessData['max_slots']) {
            $regStmt = $pdo->prepare("INSERT INTO open_play_registrations (session_id, user_id, num_players, total_price, status) VALUES (?, ?, ?, ?, 'pending')");
            $regStmt->execute([$sessionId, $_SESSION['user_id'], $numPlayers, $totalPrice]);

            setFlash('success', 'Open Play join request submitted! Awaiting admin approval.');
            redirect('my-bookings.php');
        } else {
            $error = 'Not enough slots available for this Open Play session.';
        }
    } else {
        // --- SCENARIO B: Standard Private Court Booking ---
        $courtId = (int) ($_POST['court_id'] ?? 0);
        $date    = clean($_POST['booking_date'] ?? '');
        $start   = clean($_POST['start_time'] ?? '');
        $end     = clean($_POST['end_time'] ?? '');
        $players = max(1, (int) ($_POST['players'] ?? 2));
        $notes   = clean($_POST['notes'] ?? '');

        // Validate required fields
        if (!$courtId || !$date || !$start || !$end) {
            $error = 'Please fill in every required field.';
        } else {
            // Process booking via helper function
            [$ok, $message] = createBooking($pdo, $_SESSION['user_id'], $courtId, 'private', $date, $start, $end, $players, $notes);
            if ($ok) {
                setFlash('success', $message);
                redirect('my-bookings.php');
            } else {
                $error = $message;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $openPlaySession ? 'Join Open Play' : 'Book a Court' ?> - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/book-court.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/client-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar">
            <h1><?= $openPlaySession ? 'Join Open Play Session' : 'Book a Private Court' ?></h1>
        </div>

        <div class="booking-grid-layout">
            
            <!-- ======================================================= -->
            <!-- LEFT COLUMN: BOOKING FORM PANEL                          -->
            <!-- ======================================================= -->
            <div class="panel-card booking-form-panel">
                <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

                <?php if ($openPlaySession): ?>
                    <!-- FORM VARIANT: JOIN AN ADMIN-HOSTED OPEN PLAY SESSION -->
                    <form method="post">
                        <input type="hidden" name="action" value="join_open_play">
                        <input type="hidden" name="session_id" value="<?= $openPlaySession['id'] ?>">
                        <input type="hidden" id="price_per_player_val" name="price_per_player" value="<?= $openPlaySession['price_per_player'] ?>">

                        <div class="field">
                            <label>Selected Session</label>
                            <input type="text" value="<?= e($openPlaySession['court_name']) ?> — <?= formatDate($openPlaySession['session_date']) ?> (<?= formatTime($openPlaySession['start_time']) ?> - <?= formatTime($openPlaySession['end_time']) ?>)" disabled>
                        </div>

                        <div class="field">
                            <label>Available Slots</label>
                            <input type="text" value="<?= $openPlaySession['max_slots'] - $openPlaySession['confirmed_players'] ?> slots remaining" disabled>
                        </div>

                        <div class="field">
                            <label>Number of Players Joining</label>
                            <select name="num_players" id="num_players" required>
                                <option value="1">1 Player (₱<?= number_format($openPlaySession['price_per_player'], 2) ?>)</option>
                                <option value="2">2 Players (₱<?= number_format($openPlaySession['price_per_player'] * 2, 2) ?>)</option>
                                <option value="3">3 Players (₱<?= number_format($openPlaySession['price_per_player'] * 3, 2) ?>)</option>
                                <option value="4">4 Players (₱<?= number_format($openPlaySession['price_per_player'] * 4, 2) ?>)</option>
                            </select>
                        </div>

                        <div class="field price-display-wrapper">
                            <label>Total Price:</label>
                            <h3 id="total_price_display" class="price-display-value">₱<?= number_format($openPlaySession['price_per_player'], 2) ?></h3>
                        </div>

                        <button type="submit" class="btn btn-solid btn-block">Submit Join Request</button>
                        <a href="book-court.php" class="btn btn-ghost btn-block btn-cancel-link">Cancel</a>
                    </form>

                <?php else: ?>
                    <!-- FORM VARIANT: STANDARD PRIVATE COURT BOOKING FORM -->
                    <form method="post" id="privateBookingForm">
                        <input type="hidden" name="action" value="private_booking">
                        
                        <div class="field">
                            <label>Court</label>
                            <select name="court_id" id="private_court_id" required>
                                <option value="" data-rate="0">Select a court</option>
                                <?php foreach ($courts as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-rate="<?= $c['hourly_rate'] ?? 200 ?>">
                                        <?= e($c['court_name']) ?> - &#8369;<?= number_format($c['hourly_rate'], 2) ?>/hr
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="field">
                                <label>Date</label>
                                <input type="date" name="booking_date" data-role="booking-date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="field">
                                <label>Players</label>
                                <input type="number" name="players" min="1" max="8" value="2" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="field">
                                <label>Start time</label>
                                <input type="time" name="start_time" id="private_start_time" data-role="start-time" required>
                            </div>
                            <div class="field">
                                <label>End time</label>
                                <input type="time" name="end_time" id="private_end_time" data-role="end-time" required>
                            </div>
                        </div>
                        
                        <div class="field">
                            <label>Notes (optional)</label>
                            <textarea name="notes" rows="3" placeholder="Paddle rental, coaching request, etc."></textarea>
                        </div>

                        <!-- Dynamic Price Calculation Box -->
                        <div class="price-summary-card">
                            <div class="price-summary-row">
                                <span class="price-title">Total Price:</span>
                                <span class="price-value" id="privateTotalDisplay">₱0.00</span>
                            </div>
                            <small class="price-subtitle" id="privateDetailsDisplay">Select court, start time, and end time to calculate total.</small>
                        </div>

                        <button type="submit" class="btn btn-solid btn-block">Submit Booking Request</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- ======================================================= -->
            <!-- RIGHT COLUMN: TODAY'S COURT AVAILABILITY SCHEDULE        -->
            <!-- ======================================================= -->
            <div class="schedule-panel">
                <div class="schedule-header">
                    <h3>Today's Schedule</h3>
                    <span class="schedule-date-badge"><?= date('F j, Y', strtotime($todayDate)) ?></span>
                </div>

                <?php if (empty($courtSchedule)): ?>
                    <p class="schedule-empty-msg">No courts currently active.</p>
                <?php else: ?>
                    <?php foreach ($courtSchedule as $court): ?>
                        <div class="court-schedule-card">
                            <h4 class="court-title">
                                <span><?= htmlspecialchars($court['name']) ?></span>
                                <span class="court-status-active">● Active</span>
                            </h4>

                            <?php if (empty($court['bookings'])): ?>
                                <p class="court-available-msg">
                                    ✓ All slots available today
                                </p>
                            <?php else: ?>
                                <div class="booked-slots-header">
                                    Booked Slots:
                                </div>
                                <?php foreach ($court['bookings'] as $slot): ?>
                                    <div class="slot-item">
                                        <span><?= $slot['time'] ?></span>
                                        <span class="status-tag <?= strtolower($slot['status']) ?>"><?= $slot['status'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>

<!-- ========================================================================= -->
<!-- CLIENT-SIDE LIVE PRICE CALCULATION SCRIPTS                                -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // -----------------------------------------------------------------
    // 1. OPEN PLAY SESSION PRICE CALCULATOR
    // -----------------------------------------------------------------
    const playerSelect = document.getElementById('num_players');
    const priceInput = document.getElementById('price_per_player_val');
    const totalDisplay = document.getElementById('total_price_display');

    if (playerSelect && priceInput && totalDisplay) {
        playerSelect.addEventListener('change', function() {
            const pricePerPlayer = parseFloat(priceInput.value) || 0;
            const numPlayers = parseInt(this.value, 10) || 1;
            const total = numPlayers * pricePerPlayer;
            totalDisplay.textContent = '₱' + total.toFixed(2);
        });
    }

    // -----------------------------------------------------------------
    // 2. PRIVATE COURT BOOKING DURATION & PRICE CALCULATOR
    // -----------------------------------------------------------------
    const courtSelect = document.getElementById('private_court_id');
    const startTimeInput = document.getElementById('private_start_time');
    const endTimeInput = document.getElementById('private_end_time');
    const privateTotalDisplay = document.getElementById('privateTotalDisplay');
    const privateDetailsDisplay = document.getElementById('privateDetailsDisplay');

    if (courtSelect && startTimeInput && endTimeInput && privateTotalDisplay) {
        function calculatePrivateTotal() {
            const selectedOption = courtSelect.options[courtSelect.selectedIndex];
            const hourlyRate = parseFloat(selectedOption.getAttribute('data-rate')) || 0;
            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;

            if (hourlyRate > 0 && startTime && endTime) {
                // Parse time inputs using a dummy reference date for calculation
                const start = new Date(`1970-01-01T${startTime}:00`);
                const end = new Date(`1970-01-01T${endTime}:00`);
                const diffMinutes = (end - start) / (1000 * 60);

                if (diffMinutes > 0) {
                    const hours = diffMinutes / 60;
                    const totalCost = hours * hourlyRate;
                    privateTotalDisplay.textContent = '₱' + totalCost.toFixed(2);
                    privateDetailsDisplay.textContent = `${hours.toFixed(1)} hrs × ₱${hourlyRate.toFixed(2)}/hr`;
                    return;
                } else {
                    privateDetailsDisplay.textContent = 'End time must be after start time.';
                }
            } else {
                privateDetailsDisplay.textContent = 'Select court, start time, and end time to calculate total.';
            }

            privateTotalDisplay.textContent = '₱0.00';
        }

        // Bind calculation triggers to form elements
        courtSelect.addEventListener('change', calculatePrivateTotal);
        startTimeInput.addEventListener('change', calculatePrivateTotal);
        endTimeInput.addEventListener('change', calculatePrivateTotal);
    }
});
</script>

<a href="../index.php" class="btn-back-home">Back to Home</a>

</body>
</html>