<?php
/*******************************************************************************
 * SECTION 1: CONNECT TO DATABASE, LOAD HELPERS, AND VERIFY ADMIN PRIVILEGES
 *******************************************************************************/
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAdmin();


/*******************************************************************************
 * SECTION 2: PROCESS FORM SUBMISSIONS (CREATE SESSIONS, UPDATE REGISTRATIONS & DELETE SESSIONS)
 *******************************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create a new Open Play Session
    if ($action === 'create_session') {
        $courtId = (int) $_POST['court_id'];
        $date    = clean($_POST['session_date']);
        $start   = clean($_POST['start_time']);
        $end     = clean($_POST['end_time']);
        $slots   = max(8, (int) $_POST['max_slots']); // Minimum 8 players
        $price   = (float) $_POST['price_per_player'];

        $stmt = $pdo->prepare("INSERT INTO open_play_sessions (court_id, session_date, start_time, end_time, max_slots, price_per_player) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$courtId, $date, $start, $end, $slots, $price]);
        setFlash('success', 'Open Play session hosted successfully.');
    } 
    // Admin Confirms or Cancels a User's Request
    elseif ($action === 'update_registration') {
        $regId  = (int) $_POST['reg_id'];
        $status = clean($_POST['status']); // 'confirmed' or 'cancelled'

        $stmt = $pdo->prepare("UPDATE open_play_registrations SET status = ? WHERE id = ?");
        $stmt->execute([$status, $regId]);

        // Check if session is full after confirming
        if ($status === 'confirmed') {
            $regStmt = $pdo->prepare("SELECT session_id FROM open_play_registrations WHERE id = ?");
            $regStmt->execute([$regId]);
            $reg = $regStmt->fetch();

            if ($reg) {
                $sessId = $reg['session_id'];
                
                $countStmt = $pdo->prepare("SELECT COALESCE(SUM(num_players), 0) FROM open_play_registrations WHERE session_id = ? AND status = 'confirmed'");
                $countStmt->execute([$sessId]);
                $booked = (int) $countStmt->fetchColumn();

                $sessStmt = $pdo->prepare("SELECT max_slots FROM open_play_sessions WHERE id = ?");
                $sessStmt->execute([$sessId]);
                $maxSlots = (int) $sessStmt->fetchColumn();

                if ($booked >= $maxSlots) {
                    $updateSess = $pdo->prepare("UPDATE open_play_sessions SET status = 'full' WHERE id = ?");
                    $updateSess->execute([$sessId]);
                }
            }
        }
        setFlash('success', 'Registration status updated successfully.');
    }
    // ADDED: Handle deletion of hosted open play sessions
    elseif ($action === 'delete_session') {
        $sessionId = (int) $_POST['session_id'];
        $stmt = $pdo->prepare("DELETE FROM open_play_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        setFlash('success', 'Hosted Open Play session deleted successfully.');
    }
    redirect('/activepicklelabs/admin/open-play.php');
}


/*******************************************************************************
 * SECTION 3: FETCH AVAILABLE COURTS, HOSTED SESSIONS, AND USER JOIN REQUESTS
 *******************************************************************************/
$courts = $pdo->query("SELECT * FROM courts WHERE status = 'available'")->fetchAll();

$sessions = $pdo->query("SELECT ops.*, c.court_name, 
                        (SELECT COALESCE(SUM(num_players), 0) FROM open_play_registrations WHERE session_id = ops.id AND status = 'confirmed') as confirmed_players 
                        FROM open_play_sessions ops 
                        JOIN courts c ON ops.court_id = c.id 
                        ORDER BY ops.session_date DESC, ops.start_time DESC")->fetchAll();

$requests = $pdo->query("SELECT opr.*, u.full_name, u.email, ops.session_date, ops.start_time, c.court_name 
                        FROM open_play_registrations opr 
                        JOIN users u ON opr.user_id = u.id 
                        JOIN open_play_sessions ops ON opr.session_id = ops.id 
                        JOIN courts c ON ops.court_id = c.id 
                        ORDER BY (opr.status = 'pending') DESC, opr.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Open Play Management - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/openplay.css">
</head>
<body>
<div class="dash-shell">
    <?php 
    /***************************************************************************
     * SECTION 4: LOAD THE ADMIN SIDEBAR NAVIGATION MENU
     ***************************************************************************/
    include __DIR__ . '/../components/admin-sidebar.php'; 
    ?>
    <main class="dash-main">
        <div class="dash-topbar">
            <h1>Host Open Play Sessions</h1>
        </div>

        <?php if ($msg = getFlash('success')): ?>
            <div class="alert alert-success" style="margin-bottom:20px;"><?= e($msg) ?></div>
        <?php endif; ?>

        <?php 
        /*******************************************************************
         * SECTION 5: FORM TO CREATE A NEW OPEN PLAY SESSION
         *******************************************************************/
        ?>
        <!-- Form to Create Session -->
        <div class="panel-card" style="max-width: 600px;">
            <h2>Create New Session</h2>
            <form method="post">
                <input type="hidden" name="action" value="create_session">
                
                <div class="form-grid-2col">
                    <div class="field field-full">
                        <label>Court</label>
                        <select name="court_id" required>
                            <?php foreach($courts as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['court_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="field">
                        <label>Date</label>
                        <input type="date" name="session_date" required>
                    </div>

                    <div class="field">
                        <label>Price Per Player (₱)</label>
                        <input type="number" step="0.01" name="price_per_player" value="150.00" required>
                    </div>
                    
                    <div class="field">
                        <label>Start Time</label>
                        <input type="time" name="start_time" required>
                    </div>
                    
                    <div class="field">
                        <label>End Time</label>
                        <input type="time" name="end_time" required>
                    </div>
                    
                    <div class="field field-full">
                        <label>Max Slots (Min 8)</label>
                        <input type="number" name="max_slots" min="8" value="8" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-solid">Host Open Play</button>
            </form>
        </div>

        <?php 
        /*******************************************************************
         * SECTION 6: TABLE DISPLAYING ALL HOSTED OPEN PLAY SESSIONS
         *******************************************************************/
        ?>
        <!-- Active Open Play Sessions Overview -->
        <div class="panel-card" style="margin-bottom: 24px;">
            <h2>Hosted Sessions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Court</th>
                        <th>Date & Time</th>
                        <th>Capacity</th>
                        <th>Price/Player</th>
                        <th>Status</th>
                        <!-- ADDED: Header for hosted session actions -->
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sessions)): ?>
                    <tr><td colspan="6" class="muted">No Open Play sessions hosted yet.</td></tr>
                <?php else: ?>
                    <?php foreach($sessions as $s): ?>
                        <tr>
                            <td><?= e($s['court_name']) ?></td>
                            <td><?= formatDate($s['session_date']) ?> (<?= formatTime($s['start_time']) ?> - <?= formatTime($s['end_time']) ?>)</td>
                            <td><?= $s['confirmed_players'] ?> / <?= $s['max_slots'] ?> Players</td>
                            <td>₱<?= number_format($s['price_per_player'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= $s['status'] === 'open' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </td>
                            <!-- ADDED: Delete button form for hosted sessions -->
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this hosted session?');">
                                    <input type="hidden" name="action" value="delete_session">
                                    <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm" style="color: #ef4444;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php 
        /*******************************************************************
         * SECTION 7: TABLE DISPLAYING USER JOIN REQUESTS TO REVIEW
         *******************************************************************/
        ?>
        <!-- Pending Join Requests Notification List -->
        <div class="panel-card">
            <h2>User Join Requests</h2>
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Court / Time</th>
                        <th>Players</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="5" class="muted">No registration requests found.</td></tr>
                <?php else: ?>
                    <?php foreach($requests as $r): ?>
                        <tr>
                            <td><?= e($r['full_name']) ?><br><small class="muted"><?= e($r['email']) ?></small></td>
                            <td><?= e($r['court_name']) ?> (<?= formatDate($r['session_date']) ?> <?= formatTime($r['start_time']) ?>)</td>
                            <td><?= $r['num_players'] ?></td>
                            <td>₱<?= number_format($r['total_price'], 2) ?></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="update_registration">
                                        <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                                        <button name="status" value="confirmed" class="btn btn-solid btn-sm">Confirm</button>
                                        <button name="status" value="cancelled" class="btn btn-ghost btn-sm">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge"><?= ucfirst($r['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php 
/*******************************************************************************
 * SECTION 8: FOOTER SCRIPTS & GLOBAL NAVIGATION
 *******************************************************************************/
?>
<script src="../assets/js/main.js"></script>

<!-- Floating Back to Home Button -->
<a href="../index.php" class="btn-back-home">Back to Home</a>

</body>
</html>