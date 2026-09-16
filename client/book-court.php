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

$courts    = getAvailableCourts($pdo);
$error     = null;
$success   = null;
$todayDate = date('Y-m-d');

// =========================================================================
// FETCH ALL BOOKINGS FOR JAVASCRIPT TIME-SLOT VALIDATION
// =========================================================================
$stmtAllBookings = $pdo->prepare("
    SELECT court_id, booking_date AS slot_date, start_time, end_time, status 
    FROM bookings 
    WHERE status IN ('confirmed', 'pending', 'pending_cancellation')
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

foreach ($privateBookings as $row) {
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

// Form Submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courtId = (int) ($_POST['court_id'] ?? 0);
    $date    = clean($_POST['booking_date'] ?? '');
    $start   = clean($_POST['start_time'] ?? '');
    $end     = clean($_POST['end_time'] ?? '');
    $players = 2; 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book a Court - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/book-court.css">
<link rel="stylesheet" href="../assets/css/book-court-zoom.css">
</head>
<body>

<!-- Custom Notification Modal/Alert Box -->
<div id="slotOccupiedModal" class="notification-modal">
    ⚠️ This time slot is already occupied/booked!
</div>

<div class="dash-shell">
    <?php include __DIR__ . '/../components/client-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar">
            <h1>Book a Private Court</h1>
        </div>

        <form method="post" id="privateBookingForm">
            <input type="hidden" name="action" value="private_booking">

            <div class="booking-grid-layout" id="bookingGridLayout">
                
                <!-- ======================================================= -->
                <!-- LEFT COLUMN: COURT SELECTION & TODAY'S SCHEDULE         -->
                <!-- ======================================================= -->
                <div class="left-column-wrapper">
                    <div class="panel-card booking-form-panel booking-form-panel-flush">
                        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

                        <div class="field field-flush">
                            <label>Select Court</label>
                            <select name="court_id" id="private_court_id" required class="hidden-select">
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
                                            <div class="court-no-image">No Image</div>
                                        <?php endif; ?>
                                        <div class="court-card-title"><?= e($c['court_name']) ?></div>
                                        <div class="court-card-rate">&#8369;<?= number_format($c['hourly_rate'], 2) ?>/hr</div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="changeCourtBtn" class="change-court-btn" style="display: none;">← Change Court Selection</button>
                        </div>
                    </div>

                    <!-- TODAY'S SCHEDULE -->
                    <div class="schedule-panel" id="todayScheduleVertical">
                        <div class="schedule-header">
                            <h3 class="schedule-title-main">Today's Schedule</h3>
                            <span class="schedule-date-badge" id="scheduleDateBadge"><?= date('F j, Y', strtotime($todayDate)) ?></span>
                        </div>
                        <?php if (empty($courtSchedule)): ?>
                            <p class="schedule-empty-msg">No courts currently active.</p>
                        <?php else: ?>
                            <div class="schedule-courts-grid">
                                <?php foreach ($courtSchedule as $court): ?>
                                    <div class="court-schedule-card">
                                        <h4 class="court-title">
                                            <span class="court-title-text"><?= htmlspecialchars($court['name']) ?></span>
                                            <span class="court-status-active">● Active</span>
                                        </h4>
                                        <?php if (empty($court['bookings'])): ?>
                                            <p class="court-available-msg">✓ All slots available today</p>
                                        <?php else: ?>
                                            <div class="booked-slots-header">Booked Slots:</div>
                                            <?php foreach ($court['bookings'] as $slot): ?>
                                                <div class="slot-item">
                                                    <span class="slot-time-text"><?= $slot['time'] ?></span>
                                                    <span class="status-tag <?= strtolower($slot['status']) ?>"><?= $slot['status'] ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- RIGHT COLUMN: BOOKING DETAILS & CONFIGURATION PANEL     -->
                <!-- ======================================================= -->
                <div class="right-column-wrapper">
                    <div class="panel-card config-panel-card" id="bookingConfigPanel">
                        <h3 class="config-panel-heading">BOOKING CONFIGURATION</h3>
                        
                        <input type="hidden" name="start_time" id="private_start_time" data-role="start-time" required>
                        <input type="hidden" name="end_time" id="private_end_time" data-role="end-time" required>

                        <!-- Horizontal Calendar Date Picker Section -->
                        <div class="date-picker-section">
                            <div class="date-picker-header">
                                <div>
                                    <h3 id="displayDayName" class="selected-day-title">Day</h3>
                                    <p id="displayFullDate" class="selected-date-sub">Date</p>
                                </div>
                                <div class="date-picker-actions">
                                    <button type="button" id="jumpToDateTrigger" class="btn-jump-date" onclick="document.getElementById('booking_date').showPicker ? document.getElementById('booking_date').showPicker() : document.getElementById('booking_date').click();">
                                        📅 Jump to date
                                    </button>
                                    <input type="date" id="booking_date" name="booking_date" data-role="booking-date" min="<?= date('Y-m-d') ?>" style="display: none;" onchange="handleDatePickerChange(this.value)">
                                </div>
                            </div>

                            <div id="displayMonthYear" class="month-year-label">MONTH YEAR</div>

                            <div class="days-strip-container" id="daysStrip">
                                <!-- Dynamically rendered via JS -->
                            </div>
                        </div>

                        <!-- Available Time Slots Selection Boxes -->
                        <div class="field time-slots-container-wrapper">
                            <label class="time-slots-label">Available Time Slots (Click to Select Multiple)</label>
                            <div id="timeSlotsGrid" class="time-slots-grid">
                                <span class="time-slots-placeholder">Please pick a court and date to view time slots.</span>
                            </div>
                        </div>
                        
                        <div class="field field-notes-margin">
                            <label class="notes-label">Notes (optional)</label>
                            <textarea name="notes" rows="2" placeholder="Paddle rental, coaching request, etc." class="notes-textarea"></textarea>
                        </div>

                        <div class="price-summary-card">
                            <div class="price-summary-row">
                                <span class="price-title">Total Price:</span>
                                <span class="price-value" id="privateTotalDisplay">&#8369;0.00</span>
                            </div>
                            <small class="price-subtitle" id="privateDetailsDisplay">Select court and time slot(s) to calculate total.</small>
                        </div>

                        <button type="submit" class="btn btn-solid btn-block submit-booking-btn">Submit Booking Request</button>
                    </div>

                    <!-- Back to Home Button -->
                    <div class="back-to-home-wrapper">
                        <a href="../index.php" class="btn btn-outline back-home-btn">Back to Home</a>
                    </div>
                </div>

            </div>
        </form>
    </main>
</div>

<script src="../assets/js/main.js"></script>

<!-- EMBEDDED BOOKINGS MAP FOR JS -->
<script>
const GLOBAL_BOOKED_MAP = <?= json_encode($bookedSlotsMap); ?>;

function formatDateLocal(d) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const dayNumStr = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${dayNumStr}`;
}

document.addEventListener('DOMContentLoaded', function () {
    const today = new Date();
    renderDaysStrip(today);

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

            if (isBooked) {
                slotBox.classList.add('booked');
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
            clickedBox.classList.add('selected');
        } else if (currentSelected.length === 1 && currentSelected[0] === clickedBox) {
            clickedBox.classList.remove('selected');
        } else {
            const firstIndex = allBoxes.indexOf(currentSelected[0]);
            if (clickedIndex >= firstIndex) {
                let hasBookedInBetween = false;
                for (let i = firstIndex; i <= clickedIndex; i++) {
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
                allBoxes.forEach(b => b.classList.remove('selected'));
                clickedBox.classList.add('selected');
            }
        }

        allBoxes.forEach(b => {
            if (b.classList.contains('selected')) {
                b.classList.add('is-selected-state');
            } else {
                b.classList.remove('is-selected-state');
            }
        });

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

function renderDaysStrip(selectedDate) {
    const strip = document.getElementById("daysStrip");
    if (!strip) return;
    strip.innerHTML = "";

    const startDate = new Date();
    const year = startDate.getFullYear();
    const month = startDate.getMonth();
    const lastDayOfMonth = new Date(year, month + 1, 0).getDate();
    const daysInCurrentMonthRemaining = lastDayOfMonth - startDate.getDate() + 1;
    
    for (let i = 0; i < daysInCurrentMonthRemaining; i++) {
        const d = new Date(startDate);
        d.setDate(startDate.getDate() + i);

        const dYear = d.getFullYear();
        const dMonth = String(d.getMonth() + 1).padStart(2, '0');
        const dayNumStr = String(d.getDate()).padStart(2, '0');
        const dateStr = `${dYear}-${dMonth}-${dayNumStr}`;

        const dayName = d.toLocaleDateString('en-US', { weekday: 'short' });
        const dayNum = d.getDate();

        const isSelected = dateStr === formatDateLocal(selectedDate);

        const card = document.createElement("div");
        card.className = `day-card ${isSelected ? 'active' : ''}`;
        card.onclick = () => selectDate(d, dateStr);

        card.innerHTML = `
            <span class="day-name">${dayName}</span>
            <span class="day-number">${dayNum}</span>
        `;

        strip.appendChild(card);
    }

    updateHeaderDisplays(selectedDate);
}

function selectDate(dateObj, dateStr) {
    const dateInput = document.getElementById("booking_date");
    if (dateInput) {
        dateInput.value = dateStr;
        dateInput.dispatchEvent(new Event('change'));
    }
    renderDaysStrip(dateObj);
}

function handleDatePickerChange(val) {
    if (!val) return;
    const parts = val.split('-');
    const selectedDate = new Date(parts[0], parts[1] - 1, parts[2]);
    renderDaysStrip(selectedDate);
}

function updateHeaderDisplays(d) {
    const dayName = d.toLocaleDateString('en-US', { weekday: 'long' });
    const fullDate = d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    const monthYear = d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }).toUpperCase();

    const dayNameEl = document.getElementById("displayDayName");
    const fullDateEl = document.getElementById("displayFullDate");
    const monthYearEl = document.getElementById("displayMonthYear");
    const badgeEl = document.getElementById("scheduleDateBadge");

    if (dayNameEl) dayNameEl.innerText = dayName;
    if (fullDateEl) fullDateEl.innerText = fullDate;
    if (monthYearEl) monthYearEl.innerText = monthYear;
    if (badgeEl) badgeEl.innerText = d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}
</script>
</body>
</html>