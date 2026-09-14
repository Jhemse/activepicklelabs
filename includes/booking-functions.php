<?php
/**
 * booking-functions.php - all court / service / booking database logic
 * lives here so pages just call these functions instead of writing SQL.
 */

/* -------------------- Courts -------------------- */

function getAllCourts(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM courts ORDER BY court_name')->fetchAll();
}

function getAvailableCourts(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM courts WHERE status = "available" ORDER BY court_name')->fetchAll();
}

function getCourt(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM courts WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function createCourt(PDO $pdo, string $name, string $desc, float $rate): void
{
    $stmt = $pdo->prepare('INSERT INTO courts (court_name, description, hourly_rate) VALUES (?, ?, ?)');
    $stmt->execute([$name, $desc, $rate]);
}

function updateCourt(PDO $pdo, int $id, string $name, string $desc, float $rate, string $status): void
{
    $stmt = $pdo->prepare(
        'UPDATE courts SET court_name = ?, description = ?, hourly_rate = ?, status = ? WHERE id = ?'
    );
    $stmt->execute([$name, $desc, $rate, $status, $id]);
}

function deleteCourt(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM courts WHERE id = ?');
    $stmt->execute([$id]);
}

/* -------------------- Services -------------------- */

function getAllServices(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM services ORDER BY display_order')->fetchAll();
}

function createService($pdo, $title, $description, $imageUrl = null, $icon = 'sparkle', $displayOrder = 1) {
    $stmt = $pdo->prepare("INSERT INTO services (title, description, image_url, icon, display_order) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$title, $description, $imageUrl, $icon, $displayOrder]);
}

function deleteService($pdo, $id) {
    // 1. Delete the requested service
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$id]);

    // 2. Fetch all remaining services ordered by current display_order
    $stmt = $pdo->query("SELECT id FROM services ORDER BY display_order ASC, id ASC");
    $services = $stmt->fetchAll();

    // 3. Re-assign sequential display_order (1, 2, 3...)
    $updateStmt = $pdo->prepare("UPDATE services SET display_order = ? WHERE id = ?");
    foreach ($services as $index => $s) {
        $newOrder = $index + 1; // 1-based index
        $updateStmt->execute([$newOrder, $s['id']]);
    }
}

/* -------------------- Bookings -------------------- */

/** Check whether a court is already booked or overlaps with private bookings and open-play sessions. */
function isCourtTaken(PDO $pdo, int $courtId, string $date, string $start, string $end, ?int $excludeId = null): bool
{
    // PURPOSE: Check for time overlaps against existing confirmed or pending private bookings
    $sql = 'SELECT COUNT(*) FROM bookings
            WHERE court_id = ? AND booking_date = ? AND status IN ("confirmed", "pending")
            AND start_time < ? AND end_time > ?';
    $params = [$courtId, $date, $end, $start];

    if ($excludeId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ((int) $stmt->fetchColumn() > 0) {
        return true;
    }

    // PURPOSE: Also check for time overlaps against active open-play sessions scheduled on this court
    $openPlaySql = 'SELECT COUNT(*) FROM open_play_sessions
                    WHERE court_id = ? AND session_date = ? AND status = "open"
                    AND start_time < ? AND end_time > ?';
    $stmtOpen = $pdo->prepare($openPlaySql);
    $stmtOpen->execute([$courtId, $date, $end, $start]);
    
    return (int) $stmtOpen->fetchColumn() > 0;
}

/** Create a booking. Returns [success(bool), message(string)]. */
function createBooking(
    PDO $pdo,
    int $userId,
    int $courtId,
    string $type,
    string $date,
    string $start,
    string $end,
    int $players,
    string $notes
): array {
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        return [false, 'Please choose a date in the future.'];
    }
    if (strtotime($start) >= strtotime($end)) {
        return [false, 'End time must be after start time.'];
    }
    if (isCourtTaken($pdo, $courtId, $date, $start, $end)) {
        return [false, 'This time slot conflicts with an existing booking or scheduled open-play session. Please choose another time.'];
    }

    // 1. Fetch court rates
    $courtStmt = $pdo->prepare('SELECT * FROM courts WHERE id = ?');
    $courtStmt->execute([$courtId]);
    $court = $courtStmt->fetch(PDO::FETCH_ASSOC);

    if (!$court) {
        return [false, 'Selected court was not found.'];
    }

    $hourlyRate = (float) ($court['hourly_rate'] ?? $court['price_per_hour'] ?? 0);

    // 2. Calculate Total Price based on Booking Type
    if ($type === 'open_play') {
        // Open Play: Rate per player × number of players
        $pricePerPlayer = (float) ($court['price_per_player'] ?? 150.00); 
        $totalPrice = $players * $pricePerPlayer;
    } else {
        // Private Booking: Duration in hours × hourly rate
        $startTime = new DateTime($start);
        $endTime   = new DateTime($end);
        $interval  = $startTime->diff($endTime);
        $hours     = $interval->h + ($interval->i / 60);

        if ($hours <= 0) {
            return [false, 'Invalid booking duration.'];
        }

        $totalPrice = $hours * $hourlyRate;
    }

    // 3. Save to database including total_price
    $stmt = $pdo->prepare(
        'INSERT INTO bookings (user_id, court_id, booking_type, booking_date, start_time, end_time, players, total_price, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    
    $stmt->execute([$userId, $courtId, $type, $date, $start, $end, $players, $totalPrice, $notes]);

    return [true, 'Your booking request has been submitted and is pending confirmation.'];
}

function getBookingsForUser(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT b.*, c.court_name, c.hourly_rate FROM bookings b
         JOIN courts c ON c.id = b.court_id
         WHERE b.user_id = ?
         ORDER BY b.booking_date DESC, b.start_time DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUpcomingOpenPlay(PDO $pdo, int $limit = 6): array
{
    $stmt = $pdo->prepare(
        'SELECT b.*, c.court_name, u.full_name FROM bookings b
         JOIN courts c ON c.id = b.court_id
         JOIN users u ON u.id = b.user_id
         WHERE b.booking_type = "open_play" AND b.status NOT IN ("cancelled", "pending_cancellation")
           AND b.booking_date >= CURDATE()
         ORDER BY b.booking_date ASC, b.start_time ASC
         LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllBookings(PDO $pdo): array
{
    return $pdo->query(
        'SELECT b.*, c.court_name, u.full_name, u.email FROM bookings b
         JOIN courts c ON c.id = b.court_id
         JOIN users u ON u.id = b.user_id
         ORDER BY b.booking_date DESC, b.start_time DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
}

function updateBookingStatus(PDO $pdo, int $bookingId, string $status): void
{
    $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $stmt->execute([$status, $bookingId]);
}

function cancelBooking(PDO $pdo, int $bookingId, int $userId): void
{
    // A client may only cancel their own booking, setting status to pending cancellation for admin review.
    $stmt = $pdo->prepare('UPDATE bookings SET status = "pending_cancellation" WHERE id = ? AND user_id = ?');
    $stmt->execute([$bookingId, $userId]);
}
/* -------------------- Users (admin "clients" list) -------------------- */

function getAllClients(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM users WHERE role = "client" ORDER BY created_at DESC')->fetchAll();
}

function toggleClientStatus(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare(
        'UPDATE users SET status = IF(status = "active", "disabled", "active") WHERE id = ? AND role = "client"'
    );
    $stmt->execute([$userId]);
}

/* -------------------- Settings -------------------- */

function getSettings(PDO $pdo): array
{
    $row = $pdo->query('SELECT * FROM settings WHERE id = 1')->fetch();
    return $row ?: [];
}

function updateSettings(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare(
        'UPDATE settings SET site_name=?, tagline=?, email=?, phone=?, instagram=?, address=? WHERE id = 1'
    );
    $stmt->execute([
        $data['site_name'], $data['tagline'], $data['email'],
        $data['phone'], $data['instagram'], $data['address'],
    ]);
}

/* -------------------- Dashboard stats -------------------- */

function getAdminStats(PDO $pdo): array
{
    return [
        'clients'  => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "client"')->fetchColumn(),
        'courts'   => (int) $pdo->query('SELECT COUNT(*) FROM courts')->fetchColumn(),
        'pending'  => (int) $pdo->query('SELECT COUNT(*) FROM bookings WHERE status = "pending"')->fetchColumn(),
        'today'    => (int) $pdo->query('SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE() AND status NOT IN ("cancelled", "pending_cancellation")')->fetchColumn(),
    ];
}

/** Fetch list of valid bookings for today's schedule view */
function getTodayBookings(PDO $pdo): array
{
    $stmt = $pdo->prepare('SELECT b.*, c.court_name, u.full_name FROM bookings b JOIN courts c ON c.id = b.court_id JOIN users u ON u.id = b.user_id WHERE b.booking_date = CURDATE() AND b.status NOT IN ("cancelled", "pending_cancellation") ORDER BY b.start_time ASC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}