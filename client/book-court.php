<?php
// =========================================================================
// DEPENDENCIES & CORE INITIALIZATION
// =========================================================================
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireLogin();
if (isAdmin()) { redirect('../admin/dashboard.php'); }

$courts   = getAvailableCourts($pdo);
$error    = null;
$success  = null;
$todayDate = date('Y-m-d');

// =========================================================================
// FETCH ALL BOOKINGS & SESSIONS FOR JAVASCRIPT TIME-SLOT VALIDATION
// =========================================================================
$stmtAllBookings = $pdo->prepare("
    SELECT court_id, booking_date AS slot_date, start_time, end_time, status 
    FROM bookings 
    WHERE status IN ('confirmed', 'pending', 'pending_cancellation')
    UNION
    SELECT c.id AS court_id, ops.session_date AS slot_date, ops.start_time, ops.end_time, 'confirmed' AS status
    FROM open_play_sessions ops
    JOIN courts c ON ops.court_id = c.id
    WHERE ops.status = 'open'
");
$stmtAllBookings->execute();
$rawBookingsData = $stmtAllBookings->fetchAll(PDO::FETCH_ASSOC);

$bookedSlotsMap = [];
foreach ($rawBookingsData as $b) {
    $bookedSlotsMap[$b['court_id']][$b['slot_date']][] = [
        'start' => date('H:i', strtotime($b['start_time'])),
        'end'   => date('H:i', strtotime($b['end_time']))
    ];
}

// Schedule sidebar preparation
$courtSchedule = [];
foreach ($courts as $court) {
    $courtSchedule[$court['id']] = [
        'name' => $court['court_name'],
        'bookings' => []
    ];
}

$stmtPrivate = $pdo->prepare("
    SELECT c.id AS court_id, b.start_time, b.end_time, b.status 
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    WHERE b.booking_date = ? AND b.status IN ('confirmed', 'pending', 'pending_cancellation')
");
$stmtPrivate->execute([$todayDate]);
$privateBookings = $stmtPrivate->fetchAll(PDO::FETCH_ASSOC);

$stmtOpenPlay = $pdo->prepare("
    SELECT c.id AS court_id, ops.start_time, ops.end_time, 'confirmed' AS status
    FROM open_play_sessions ops
    JOIN courts c ON ops.court_id = c.id
    WHERE ops.session_date = ? AND ops.status = 'open'
");
$stmtOpenPlay->execute([$todayDate]);
$openPlaySessions = $stmtOpenPlay->fetchAll(PDO::FETCH_ASSOC);

$stmtOpenPlayCancels = $pdo->prepare("
    SELECT c.id AS court_id, ops.start_time, ops.end_time, opr.status 
    FROM open_play_registrations opr
    JOIN open_play_sessions ops ON opr.session_id = ops.id
    JOIN courts c ON ops.court_id = c.id
    WHERE ops.session_date = ? AND opr.status = 'pending_cancellation' AND opr.user_id = ?
");
$stmtOpenPlayCancels->execute([$todayDate, $_SESSION['user_id']]);
$openPlayCancels = $stmtOpenPlayCancels->fetchAll(PDO::FETCH_ASSOC);

$openPlaySessions = array_merge($openPlaySessions, $openPlayCancels);
$allTodaySlots = array_merge($privateBookings, $openPlaySessions);

foreach ($allTodaySlots as $row) {
    $cId = $row['court_id'];
    if (isset($courtSchedule[$cId]) && $row['start_time']) {
        $timeFormatted = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
        $exists = false;
        foreach ($courtSchedule[$cId]['bookings'] as $b) {
            if ($b['time'] === $timeFormatted) { $exists = true; break; }
        }
        if (!$exists) {
            $courtSchedule[$cId]['bookings'][] = ['time' => $timeFormatted, 'status' => $row['status']];
        }
    }
}

foreach ($courtSchedule as $cId => &$data) {
    usort($data['bookings'], function($a, $b) {
        return strtotime(explode(' - ', $a['time'])[0]) <=> strtotime(explode(' - ', $b['time'])[0]);
    });
}
unset($data);

// Open play join query handling
$openPlayId = (int) ($_GET['open_play_id'] ?? 0);
$openPlaySession = null;
if ($openPlayId > 0) {
    $stmt = $pdo->prepare("SELECT ops.*, c.court_name,
                          (SELECT COALESCE(SUM(num_players), 0) FROM open_play_registrations WHERE session_id = ops.id AND status = 'confirmed') as confirmed_players 
                          FROM open_play_sessions ops 
                          JOIN courts c ON ops.court_id = c.id 
                          WHERE ops.id = ? AND ops.status = 'open'");
    $stmt->execute([$openPlayId]);
    $openPlaySession = $stmt->fetch();
}

// Form Submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'private_booking';

    if ($action === 'join_open_play') {
        $sessionId  = (int) $_POST['session_id'];
        $numPlayers = max(1, (int) $_POST['num_players']);
        $unitPrice  = (float) $_POST['price_per_player'];
        $totalPrice = $numPlayers * $unitPrice;

        $sessStmt = $pdo->prepare("SELECT max_slots, 
                                  (SELECT COALESCE(SUM(num_players), 0) FROM open_play_registrations WHERE session_id = ? AND status = 'confirmed') as booked 
                                  FROM open_play_sessions WHERE id = ?");
        $sessStmt->execute([$sessionId, $sessionId]);
        $sessData = $sessStmt->fetch();

        if ($sessData && ($sessData['booked'] + $numPlayers) <= $sessData['max_slots']) {
            $regStmt = $pdo->prepare("INSERT INTO open_play_registrations (session_id, user_id, num_players, total_price, status) VALUES (?, ?, ?, ?, 'pending')");
            $regStmt->execute([$sessionId, $_SESSION['user_id'], $numPlayers, $totalPrice]);

            setFlash('success', 'Open Play join request submitted! Awaiting admin approval.');
            redirect('my-bookings.php');
        } else {
            $error = 'Not enough slots available for this Open Play session.';
        }
    } else {
        $courtId = (int) ($_POST['court_id'] ?? 0);
        $date    = clean($_POST['booking_date'] ?? '');
        $start   = clean($_POST['start_time'] ?? '');
        $end     = clean($_POST['end_time'] ?? '');
        $players = 2; // Default players value since input is removed
        $notes   = clean($_POST['notes'] ?? '');

        if (!$courtId || !$date || !$start || !$end) {
            $error = 'Please fill in every required field.';
        } else {
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
<link rel="stylesheet" href="../assets/css/book-court-zoom.css">
</head>
<body>

<!-- Custom Notification Modal/Alert Box -->
<div id="slotOccupiedModal" style="display:none; position:fixed; top:20px; right:20px; z-index:9999; background:#ef4444; color:#fff; padding:12px 20px; border-radius:8px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.2); font-weight:500; font-size:0.9rem; transition:opacity 0.3s ease;">
    ⚠️ This time slot is already occupied/booked!
</div>

<div class="dash-shell">
    <?php include __DIR__ . '/../components/client-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar">
            <h1><?= $openPlaySession ? 'Join Open Play Session' : 'Book a Private Court' ?></h1>
        </div>

        <form method="post" id="privateBookingForm">
            <input type="hidden" name="action" value="private_booking">

            <!-- Added ID for grid layout switching -->
            <div class="booking-grid-layout" id="bookingGridLayout">
                
                <!-- ======================================================= -->
                <!-- LEFT COLUMN: COURT SELECTION & TODAY'S SCHEDULE        -->
                <!-- ======================================================= -->
                <div class="panel-card booking-form-panel">
                    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

                    <div class="field">
                        <label>Select Court</label>
                        <select name="court_id" id="private_court_id" required style="display:none;">
                            <option value="" data-rate="0">Select a court</option>
                            <?php foreach ($courts as $c): ?>
                                <option value="<?= $c['id'] ?>" data-rate="<?= $c['hourly_rate'] ?? 200 ?>">
                                    <?= e($c['court_name']) ?> - &#8369;<?= number_format($c['hourly_rate'], 2) ?>/hr
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Visual Court Cards Grid -->
                        <div class="court-cards-grid">
                            <?php foreach ($courts as $c): ?>
                                <label class="court-select-card" data-court-id="<?= $c['id'] ?>">
                                    <input type="radio" name="court_id_radio" value="<?= $c['id'] ?>" data-rate="<?= $c['hourly_rate'] ?? 200 ?>">
                                    <?php if (!empty($c['image']) && file_exists(__DIR__ . '/../uploads/' . $c['image'])): ?>
                                        <img src="../uploads/<?= e($c['image']) ?>" alt="<?= e($c['court_name']) ?>">
                                    <?php else: ?>
                                        <div style="height:110px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;margin-bottom:8px;">No Image</div>
                                    <?php endif; ?>
                                    <div class="court-card-title"><?= e($c['court_name']) ?></div>
                                    <div class="court-card-rate">&#8369;<?= number_format($c['hourly_rate'], 2) ?>/hr</div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="changeCourtBtn" class="change-court-btn" style="display: none;">← Change Court Selection</button>
                    </div>

                    <!-- TODAY'S SCHEDULE: Hidden by default until court is clicked -->
                    <div class="schedule-panel" id="todayScheduleVertical" style="margin-top: 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                        <div class="schedule-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <h3 style="margin: 0; font-size: 1rem; color: #1e293b;">Today's Schedule</h3>
                            <span class="schedule-date-badge" style="font-size: 0.8rem; color: #64748b; font-weight: 500;"><?= date('F j, Y', strtotime($todayDate)) ?></span>
                        </div>
                        <?php if (empty($courtSchedule)): ?>
                            <p class="schedule-empty-msg" style="font-size: 0.85rem; color: #64748b; margin: 0;">No courts currently active.</p>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                                <?php foreach ($courtSchedule as $court): ?>
                                    <div class="court-schedule-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px;">
                                        <h4 class="court-title" style="display: flex; justify-content: space-between; align-items: center; margin: 0 0 8px 0; font-size: 0.85rem;">
                                            <span style="color: #0f172a; font-weight: 600;"><?= htmlspecialchars($court['name']) ?></span>
                                            <span class="court-status-active" style="font-size: 0.7rem; color: #16a34a;">● Active</span>
                                        </h4>
                                        <?php if (empty($court['bookings'])): ?>
                                            <p class="court-available-msg" style="font-size: 0.75rem; color: #64748b; margin: 0;">✓ All slots available today</p>
                                        <?php else: ?>
                                            <div class="booked-slots-header" style="font-size: 0.75rem; color: #475569; font-weight: 500; margin-bottom: 4px;">Booked Slots:</div>
                                            <?php foreach ($court['bookings'] as $slot): ?>
                                                <div class="slot-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; margin-bottom: 3px;">
                                                    <span style="color: #334155;"><?= $slot['time'] ?></span>
                                                    <span class="status-tag <?= strtolower($slot['status']) ?>" style="font-size: 0.65rem; padding: 1px 6px; border-radius: 4px;"><?= $slot['status'] ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Back to Home Button -->
                    <div style="margin-top: 16px;">
                        <a href="../index.php" class="btn btn-outline" style="display: block; text-align: center; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; font-weight: 600; text-decoration: none; background: #ffffff;">Back to Home</a>
                    </div>

                </div>

                <!-- ======================================================= -->
                <!-- RIGHT COLUMN: BOOKING DETAILS & CONFIGURATION PANEL     -->
                <!-- ======================================================= -->
                <div class="panel-card" id="bookingConfigPanel">
                    <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 1.1rem; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">BOOKING CONFIGURATION</h3>
                    
                    <!-- Hidden inputs to hold selected start/end values for backend handling -->
                    <input type="hidden" name="start_time" id="private_start_time" data-role="start-time" required>
                    <input type="hidden" name="end_time" id="private_end_time" data-role="end-time" required>

                    <div class="field" style="margin-bottom: 14px;">
                        <label style="font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">Date</label>
                        <input type="date" name="booking_date" id="booking_date" data-role="booking-date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                    </div>

                    <!-- Available Time Slots Selection Boxes -->
                    <div class="field time-slots-container" style="margin-bottom: 16px;">
                        <label style="font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">Available Time Slots (Click to Select Multiple)</label>
                        <div id="timeSlotsGrid" class="time-slots-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; max-height: 250px; overflow-y: auto;">
                            <span style="font-size: 0.8rem; color: #64748b; grid-column: 1 / -1;">Please pick a court and date to view time slots.</span>
                        </div>
                    </div>
                    
                    <div class="field" style="margin-bottom: 16px;">
                        <label style="font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">Notes (optional)</label>
                        <textarea name="notes" rows="2" placeholder="Paddle rental, coaching request, etc." style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; resize: vertical;"></textarea>
                    </div>

                    <div class="price-summary-card" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <div class="price-summary-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span class="price-title" style="font-weight: 600; color: #334155; font-size: 0.9rem;">Total Price:</span>
                            <span class="price-value" id="privateTotalDisplay" style="font-weight: 700; color: #2563eb; font-size: 1.1rem;">₱0.00</span>
                        </div>
                        <small class="price-subtitle" id="privateDetailsDisplay" style="color: #64748b; font-size: 0.75rem;">Select court and time slot(s) to calculate total.</small>
                    </div>

                    <button type="submit" class="btn btn-solid btn-block" style="width: 100%; padding: 10px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Submit Booking Request</button>
                </div>

            </div>
        </form>
    </main>
</div>

<script src="../assets/js/main.js"></script>

<!-- EMBEDDED BOOKINGS MAP FOR JS -->
<script>
const GLOBAL_BOOKED_MAP = <?= json_encode($bookedSlotsMap); ?>;

document.addEventListener('DOMContentLoaded', function () {
    const courtCards = document.querySelectorAll('.court-select-card');
    const courtCardsGrid = document.querySelector('.court-cards-grid');
    const courtSelect = document.getElementById('private_court_id');
    const bookingGridLayout = document.getElementById('bookingGridLayout');
    const bookingConfigPanel = document.getElementById('bookingConfigPanel');
    const todayScheduleVertical = document.getElementById('todayScheduleVertical');
    const changeCourtBtn = document.getElementById('changeCourtBtn');
    const dateInput = document.getElementById('booking_date');
    const timeSlotsGrid = document.getElementById('timeSlotsGrid');
    const startTimeInput = document.getElementById('private_start_time');
    const endTimeInput = document.getElementById('private_end_time');
    const notificationModal = document.getElementById('slotOccupiedModal');

    function showOccupiedNotification() {
        notificationModal.style.display = 'block';
        setTimeout(() => {
            notificationModal.style.display = 'none';
        }, 3000);
    }

    function renderTimeSlots() {
        if (!courtSelect.value || !dateInput.value) return;

        const courtId = courtSelect.value;
        const selectedDate = dateInput.value;
        const bookedList = (GLOBAL_BOOKED_MAP[courtId] && GLOBAL_BOOKED_MAP[courtId][selectedDate]) ? GLOBAL_BOOKED_MAP[courtId][selectedDate] : [];

        timeSlotsGrid.innerHTML = '';
        startTimeInput.value = '';
        endTimeInput.value = '';

        for (let hour = 7; hour < 22; hour++) {
            let startHourStr = String(hour).padStart(2, '0') + ':00';
            let endHourStr = String(hour + 1).padStart(2, '0') + ':00';
            
            let displayStart = hour === 0 ? '12:00 AM' : (hour < 12 ? hour + ':00 AM' : (hour === 12 ? '12:00 PM' : (hour - 12) + ':00 PM'));
            let nextHour = hour + 1;
            let displayEnd = nextHour === 24 ? '12:00 AM' : (nextHour < 12 ? nextHour + ':00 AM' : (nextHour === 12 ? '12:00 PM' : (nextHour - 12) + ':00 PM'));

            let isBooked = false;
            for (let b of bookedList) {
                if (startHourStr < b.end && endHourStr > b.start) {
                    isBooked = true;
                    break;
                }
            }

            const slotBox = document.createElement('div');
            slotBox.className = 'time-slot-box';
            slotBox.dataset.start = startHourStr;
            slotBox.dataset.end = endHourStr;
            slotBox.textContent = `${displayStart} - ${displayEnd}`;
            slotBox.style.cssText = "background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 4px; text-align: center; font-size: 0.75rem; font-weight: 500; color: #334155; cursor: pointer; transition: all 0.2s ease;";

            if (isBooked) {
                slotBox.classList.add('booked');
                slotBox.style.background = "#fee2e2";
                slotBox.style.borderColor = "#fca5a5";
                slotBox.style.color = "#991b1b";
                slotBox.style.cursor = "not-allowed";
                slotBox.addEventListener('click', function() {
                    showOccupiedNotification();
                });
            } else {
                slotBox.addEventListener('click', function() {
                    handleSlotSelection(slotBox);
                });
            }

            timeSlotsGrid.appendChild(slotBox);
        }
    }

    function handleSlotSelection(clickedBox) {
        const allBoxes = Array.from(timeSlotsGrid.querySelectorAll('.time-slot-box:not(.booked)'));
        const clickedIndex = allBoxes.indexOf(clickedBox);

        let currentSelected = allBoxes.filter(b => b.classList.contains('selected'));

        if (currentSelected.length === 0) {
            // First click
            clickedBox.classList.add('selected');
        } else if (currentSelected.length === 1 && currentSelected[0] === clickedBox) {
            // Unselect if clicking the only selected box
            clickedBox.classList.remove('selected');
        } else {
            // Check if click forms a valid continuous range from the first selection
            const firstIndex = allBoxes.indexOf(currentSelected[0]);
            if (clickedIndex >= firstIndex) {
                // Select all slots between firstIndex and clickedIndex
                let hasBookedInBetween = false;
                for (let i = firstIndex; i <= clickedIndex; i++) {
                    // Check if any blocked/booked slots exist in between
                    if (allBoxes[i].classList.contains('booked')) {
                        hasBookedInBetween = true;
                        break;
                    }
                }

                if (hasBookedInBetween) {
                    showOccupiedNotification();
                    return;
                }

                allBoxes.forEach(b => b.classList.remove('selected'));
                for (let i = firstIndex; i <= clickedIndex; i++) {
                    allBoxes[i].classList.add('selected');
                }
            } else {
                // Reset and select new starting point
                allBoxes.forEach(b => b.classList.remove('selected'));
                clickedBox.classList.add('selected');
            }
        }

        // Re-highlight styling
        allBoxes.forEach(b => {
            if (b.classList.contains('selected')) {
                b.style.background = '#2563eb';
                b.style.borderColor = '#1d4ed8';
                b.style.color = '#ffffff';
            } else {
                b.style.background = '#ffffff';
                b.style.borderColor = '#cbd5e1';
                b.style.color = '#334155';
            }
        });

        // Update hidden start and end time inputs based on continuous selection span
        const updatedSelected = allBoxes.filter(b => b.classList.contains('selected'));
        if (updatedSelected.length > 0) {
            startTimeInput.value = updatedSelected[0].dataset.start;
            endTimeInput.value = updatedSelected[updatedSelected.length - 1].dataset.end;
        } else {
            startTimeInput.value = '';
            endTimeInput.value = '';
        }

        startTimeInput.dispatchEvent(new Event('change'));
    }

    if (courtCards.length > 0 && courtSelect) {
        courtCards.forEach(card => {
            card.addEventListener('click', function() {
                courtCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    courtSelect.value = radio.value;
                    
                    // Reveal hidden panels and shift layout to 2-columns
                    if (bookingGridLayout) bookingGridLayout.classList.add('court-selected');
                    if (courtCardsGrid) courtCardsGrid.classList.add('court-chosen');
                    if (bookingConfigPanel) bookingConfigPanel.classList.add('visible');
                    if (todayScheduleVertical) todayScheduleVertical.classList.add('visible');
                    if (changeCourtBtn) changeCourtBtn.style.display = 'inline-block';

                    renderTimeSlots();
                    courtSelect.dispatchEvent(new Event('change'));
                }
            });
        });

        if (changeCourtBtn) {
            changeCourtBtn.addEventListener('click', function() {
                courtCards.forEach(c => c.classList.remove('selected'));
                courtCards.forEach(c => { const r = c.querySelector('input[type="radio"]'); if (r) r.checked = false; });
                courtSelect.value = '';
                
                // Hide panels back and reset full-width layout
                if (bookingGridLayout) bookingGridLayout.classList.remove('court-selected');
                if (courtCardsGrid) courtCardsGrid.classList.remove('court-chosen');
                if (bookingConfigPanel) bookingConfigPanel.classList.remove('visible');
                if (todayScheduleVertical) todayScheduleVertical.classList.remove('visible');
                this.style.display = 'none';

                courtSelect.dispatchEvent(new Event('change'));
            });
        }
    }

    if (dateInput) {
        dateInput.addEventListener('change', renderTimeSlots);
    }

    const startTimeInputEl = document.getElementById('private_start_time');
    const endTimeInputEl = document.getElementById('private_end_time');
    const privateTotalDisplay = document.getElementById('privateTotalDisplay');
    const privateDetailsDisplay = document.getElementById('privateDetailsDisplay');

    if (courtSelect && startTimeInputEl && endTimeInputEl && privateTotalDisplay) {
        function calculatePrivateTotal() {
            const selectedOption = courtSelect.options[courtSelect.selectedIndex];
            const hourlyRate = parseFloat(selectedOption.getAttribute('data-rate')) || 0;
            const startTime = startTimeInputEl.value;
            const endTime = endTimeInputEl.value;

            if (hourlyRate > 0 && startTime && endTime) {
                const start = new Date(`1970-01-01T${startTime}:00`);
                const end = new Date(`1970-01-01T${endTime}:00`);
                const diffMinutes = (end - start) / (1000 * 60);

                if (diffMinutes > 0) {
                    const hours = diffMinutes / 60;
                    const totalCost = hours * hourlyRate;
                    privateTotalDisplay.textContent = '₱' + totalCost.toFixed(2);
                    privateDetailsDisplay.textContent = `${hours.toFixed(1)} hrs × ₱${hourlyRate.toFixed(2)}/hr`;
                    return;
                }
            }
            privateTotalDisplay.textContent = '₱0.00';
            privateDetailsDisplay.textContent = 'Select court and time slot(s) to calculate total.';
        }

        courtSelect.addEventListener('change', calculatePrivateTotal);
        startTimeInputEl.addEventListener('change', calculatePrivateTotal);
        endTimeInputEl.addEventListener('change', calculatePrivateTotal);
    }
});
</script>
</body>
</html>