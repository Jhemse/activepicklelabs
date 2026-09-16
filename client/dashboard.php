<?php
// =========================================================================
// DEPENDENCIES & CORE INITIALIZATION
// =========================================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
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
                <div class="left-controls" style="grid-column: 1 / -1; max-width: 500px; margin: 0 auto; text-align: center;">
                    <a href="book-court.php" class="btn-nav-primary" style="display: block; margin-bottom: 20px;">Book My Own Court</a>
                    
                    <!-- Decorative NVZ (Non-Volley Zone) Court Graphic -->
                    <div class="court-graphic-card" style="display: inline-block;">
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
                
            </div>
        </div>
    </main>
</div>

<!-- Floating Back to Home Button -->
<a href="../index.php" class="btn-back-home">Back to Home</a>

</body>
</html>