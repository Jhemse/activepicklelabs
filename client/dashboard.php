<?php
// =========================================================================
// DEPENDENCIES & CORE INITIALIZATION
// =========================================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// =========================================================================
// UPCOMING OPEN PLAY SESSIONS QUERY
// =========================================================================
// PURPOSE: Fetch all upcoming, admin-hosted open play sessions that are active,
// filtering out past sessions and calculating real-time confirmed player counts.
$upcomingOpenPlay = $pdo->query("
    SELECT ops.*, c.court_name, 
           (SELECT COALESCE(SUM(num_players), 0) FROM open_play_registrations WHERE session_id = ops.id AND status = 'confirmed') as confirmed_players 
    FROM open_play_sessions ops 
    JOIN courts c ON ops.court_id = c.id 
    WHERE (ops.session_date > CURDATE() OR (ops.session_date = CURDATE() AND ops.end_time > CURTIME())) 
      AND ops.status = 'open'
    ORDER BY ops.session_date ASC, ops.start_time ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courts & Booking - Active Picklelabs</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/client-sidebar.php'; ?>
    
    <main class="dash-main">
        <div class="courts-section">
            
            <!-- ======================================================= -->
            <!-- HEADER SECTION                                          -->
            <!-- ======================================================= -->
            <div class="courts-header">
                <h2>COURTS & BOOKING</h2>
                <p>Be part of the club and let us show you the true nature of being active. Active Picklelabs will serve!</p>
            </div>

            <div class="courts-grid">
                
                <!-- ======================================================= -->
                <!-- LEFT CONTROL PANEL                                      -->
                <!-- ======================================================= -->
                <div class="left-controls">
                    <a href="book-court.php" class="btn-nav-primary">Book My Own Court</a>
                    
                    <!-- Decorative NVZ (Non-Volley Zone) Court Graphic -->
                    <div class="court-graphic-card">
                        <svg width="200" height="100" viewBox="0 0 200 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="200" height="100" fill="#0F172A"/>
                            <rect x="5" y="5" width="190" height="90" stroke="#FFFFFF" stroke-width="2"/>
                            <line x1="100" y1="5" x2="100" y2="95" stroke="#FFFFFF" stroke-width="2"/>
                            <rect x="60" y="5" width="80" height="90" fill="#EAB308"/>
                            <line x1="60" y1="5" x2="60" y2="95" stroke="#FFFFFF" stroke-width="2"/>
                            <line x1="140" y1="5" x2="140" y2="95" stroke="#FFFFFF" stroke-width="2"/>
                            <text x="75" y="53" fill="#0F172A" font-size="10" font-weight="bold">NVZ</text>
                            <text x="115" y="53" fill="#0F172A" font-size="10" font-weight="bold">NVZ</text>
                        </svg>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- RIGHT SIDE: DYNAMIC PROJECTION OF OPEN PLAY SESSIONS    -->
                <!-- ======================================================= -->
                <div class="right-display">
                    <div class="upcoming-title">UPCOMING COURTS</div>

                    <?php if (empty($upcomingOpenPlay)): ?>
                        <!-- FALLBACK: Displayed when no active sessions exist -->
                        <div class="open-play-card">
                            <span class="session-details session-empty">No open play sessions currently scheduled by admin.</span>
                        </div>
                    <?php else: ?>
                        <?php 
                        $displayIndex = 1;
                        foreach ($upcomingOpenPlay as $session): 
                            // Calculate remaining available slots for the session
                            $remainingSlots = $session['max_slots'] - $session['confirmed_players'];
                            if ($remainingSlots <= 0) continue; // Skip iteration if session is completely full

                            // Check if current server time has passed session end date/time
                            $sessionEndTimestamp = strtotime($session['session_date'] . ' ' . $session['end_time']);
                            $isPassed = time() > $sessionEndTimestamp;
                        ?>
                            <div class="open-play-card">
                                <!-- Session sequential counter index -->
                                <span class="session-num"><?= $displayIndex++ ?></span>
                                
                                <!-- Session formatting and details display -->
                                <div class="session-details">
                                    <?= date('M j, Y', strtotime($session['session_date'])) ?>, 
                                    <?= date('g:i A', strtotime($session['start_time'])) ?>-<?= date('g:i A', strtotime($session['end_time'])) ?> 
                                    | <span class="court-tag"><?= e($session['court_name']) ?></span>
                                </div>
                                
                                <!-- Conditional action button based on session timeline status -->
                                <?php if ($isPassed): ?>
                                    <button class="btn-disabled" disabled>Unavailable</button>
                                <?php else: ?>
                                    <a href="book-court.php?open_play_id=<?= $session['id'] ?>" class="btn-join-lime">Join Open Play</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </main>
</div>

<!-- Floating Back to Home Button -->
<a href="../index.php" class="btn-back-home">Back to Home</a>

</body>
</html>