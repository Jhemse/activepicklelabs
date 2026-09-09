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

function createService(PDO $pdo, string $title, string $desc, string $icon, int $order): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO services (title, description, icon, display_order) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$title, $desc, $icon, $order]);
}

function deleteService(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
    $stmt->execute([$id]);
}

/* -------------------- Bookings -------------------- */

/** Check whether a court is already booked (and not cancelled) for an overlapping time window. */
function isCourtTaken(PDO $pdo, int $courtId, string $date, string $start, string $end, ?int $excludeId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM bookings
            WHERE court_id = ? AND booking_date = ? AND status != "cancelled"
            AND start_time < ? AND end_time > ?';
    $params = [$courtId, $date, $end, $start];

    if ($excludeId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
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
        return [false, 'That court is already booked for the selected time. Please pick another slot.'];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO bookings (user_id, court_id, booking_type, booking_date, start_time, end_time, players, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $courtId, $type, $date, $start, $end, $players, $notes]);
    return [true, 'Your booking request has been submitted and is pending confirmation.'];
}

function getBookingsForUser(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT b.*, c.court_name FROM bookings b
         JOIN courts c ON c.id = b.court_id
         WHERE b.user_id = ?
         ORDER BY b.booking_date DESC, b.start_time DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUpcomingOpenPlay(PDO $pdo, int $limit = 6): array
{
    $stmt = $pdo->prepare(
        'SELECT b.*, c.court_name, u.full_name FROM bookings b
         JOIN courts c ON c.id = b.court_id
         JOIN users u ON u.id = b.user_id
         WHERE b.booking_type = "open_play" AND b.status != "cancelled"
           AND b.booking_date >= CURDATE()
         ORDER BY b.booking_date ASC, b.start_time ASC
         LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getAllBookings(PDO $pdo): array
{
    return $pdo->query(
        'SELECT b.*, c.court_name, u.full_name, u.email FROM bookings b
         JOIN courts c ON c.id = b.court_id
         JOIN users u ON u.id = b.user_id
         ORDER BY b.booking_date DESC, b.start_time DESC'
    )->fetchAll();
}

function updateBookingStatus(PDO $pdo, int $bookingId, string $status): void
{
    $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $stmt->execute([$status, $bookingId]);
}

function cancelBooking(PDO $pdo, int $bookingId, int $userId): void
{
    // A client may only cancel their own booking.
    $stmt = $pdo->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ? AND user_id = ?');
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
        'today'    => (int) $pdo->query('SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE() AND status != "cancelled"')->fetchColumn(),
    ];
}
