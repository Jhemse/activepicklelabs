<?php
// =========================================================================
// DEPENDENCY INCLUSION & CORE INITIALIZATION
// =========================================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/booking-functions.php';

// Fetch global site settings and service listings
$settings   = getSettings($pdo);
$services   = getAllServices($pdo);
$serviceBg  = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'];

// Fetch site images from database for homepage cards
$siteImages = [];
try {
    $imgStmt = $pdo->query("SELECT section_key, image_path FROM site_images");
    while ($row = $imgStmt->fetch()) {
        $siteImages[$row['section_key']] = $row['image_path'];
    }
} catch (Exception $e) {
    // Handle exception gracefully
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($settings['site_name'] ?? 'Active Picklelabs') ?> - Court Booking &amp; Training</title>
<link rel="stylesheet" href="assets/css/styles.css?v=1.0">
</head>
<body>

<!-- ========================================================================= -->
<!-- HERO SECTION & PUBLIC NAVIGATION                                          -->
<!-- ========================================================================= -->
<div class="hero" id="home">
    <?php include __DIR__ . '/components/public-navbar.php'; ?>

<div class="container hero-inner">
        <div>
            <span class="eyebrow-pill">Play Pickleball</span>
            <h1 class="hero-title">The Picklelab That Gives You Ultimate Experience</h1>
            <p class="hero-lede"><?= e($settings['tagline'] ?? '') ?> Be part of the club and let us show you the true nature of being active.</p>
            <div style="margin-top:26px" class="hero-cta">
                <a class="btn btn-solid" href="<?= isLoggedIn() ? 'client/book-court.php' : 'register.php' ?>">Book a Court</a>
                <a class="btn btn-outline" href="#about">Learn More</a>
            </div>
        </div>
        <div class="hero-card">
            <div class="rating">5.0 <small>Member<br>rating</small></div>
            <div class="rating-img"></div>
            <p>Be part of the club and experience the true nature of being active. Active Picklelabs will serve!</p>
            <a href="#about">How It Works &gt;</a>
        </div>
    </div>
    <div class="hero-ghost">ACTIVE PICKLELABS</div>
</div>


<!-- ========================================================================= -->
<!-- ========================================================================= -->
<!-- FEATURE HIGHLIGHT STRIP                                                   -->
<!-- ========================================================================= -->
<div class="feature-row">
    <div class="feature-grid">
        <div class="feature-card f1" style="<?php echo !empty($siteImages['card_trusted']) ? "background-image: url('" . e($siteImages['card_trusted']) . "'); background-size: cover; background-position: center;" : ''; ?>">
            <span>Trusted<br>Quality Club</span>
        </div>
        <div class="feature-card f2" style="<?php echo !empty($siteImages['card_rated']) ? "background-image: url('" . e($siteImages['card_rated']) . "'); background-size: cover; background-position: center;" : ''; ?>">
            <span>Top Rated<br>Pickleball Players</span>
        </div>
        <div class="feature-card f3" style="<?php echo !empty($siteImages['card_flexible']) ? "background-image: url('" . e($siteImages['card_flexible']) . "'); background-size: cover; background-position: center;" : ''; ?>">
            <span>Always<br>Flexible Access</span>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- ABOUT SECTION & STATISTICS                                                -->
<!-- ========================================================================= -->
<section class="section" id="about">
    <div class="section-grid">
        <div>
            <div class="about-photo"></div>
            <div class="stats-row">
                <div>
                    <div class="stat-num">25K<small>+</small></div>
                    <div class="stat-label">Hours Played</div>
                </div>
                <div>
                    <div class="stat-num">460<small>+</small></div>
                    <div class="stat-label">Training Sessions</div>
                </div>
                <div>
                    <div class="stat-num">100K<small>+</small></div>
                    <div class="stat-label">Social Followers</div>
                </div>
            </div>
        </div>
        <div>
            <span class="pill-label">About Active Picklelabs</span>
            <h2>Activated passion meets quality playtime</h2>
            <div class="about-banner">
                <div>
                    <h3>A club built for every player</h3>
                    <p style="color:rgba(255,255,255,.85)">Be part of the club and let us show you the true nature of being active. Active Picklelabs will serve!</p>
                    <a class="btn btn-solid btn-sm" href="register.php">Discover More</a>
                </div>
                <div class="img-block"></div>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================= -->
<!-- SERVICES SECTION                                                          -->
<!-- ========================================================================= -->
<section class="section" id="services" style="background:var(--paper)">
    <div class="services-wrap">
        <div>
            <span class="pill-label">Our Services</span>
            <h2>Premium pickleball options for everyone</h2>
            <p class="muted">From a quick private rally to full tournament hosting, every service is booked and managed right from your account.</p>
        </div>
        <div class="services-grid">
            <?php foreach ($services as $i => $s): ?>
                <?php 
                    $hasImg = !empty($s['image_url']);
                    $cardStyle = $hasImg ? "style=\"background: url('" . e($s['image_url']) . "') center/cover no-repeat;\"" : '';
                    $fallbackBg = !$hasImg ? $serviceBg[$i % count($serviceBg)] : '';
                ?>
                <div class="service-card <?= $fallbackBg ?>" <?= $cardStyle ?>>
                    <div class="sc-icon">&#9679;</div>
                    <h3><?= e($s['title']) ?></h3>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========================================================================= -->
<!-- COURTS & BOOKING SECTION                                                  -->
<!-- ========================================================================= -->
<section class="section" id="booking">
    <div class="booking-panel" style="max-width: 800px; margin: 0 auto; text-align: center;">
        <div style="display:flex;flex-direction:column;align-items:center;gap:16px; margin-bottom: 30px;">
            <h2>Courts &amp; Booking</h2>
            <p class="muted" style="max-width:480px;margin:0">Be part of the club and let us show you the true nature of being active. Active Picklelabs will serve!</p>
        </div>
        
        <div style="display: flex; justify-content: center;">
            <div class="booking-actions" style="width: 100%; max-width: 450px;">
                <a class="btn btn-navy" href="<?= isLoggedIn() ? 'client/book-court.php' : 'register.php' ?>" style="display: block; margin-bottom: 25px;">Book My Own Court</a>
                <div class="court-diagram" style="margin: 0 auto;">
                    <!-- Left Court Half -->
                    <div class="court-left">
                        <div class="service-box top"></div>
                        <div class="service-box bottom"></div>
                        <div class="kitchen">NVZ</div>
                    </div>

                    <!-- Center Net -->
                    <div class="court-net"></div>

                    <!-- Right Court Half -->
                    <div class="court-right">
                        <div class="kitchen">NVZ</div>
                        <div class="service-box top"></div>
                        <div class="service-box bottom"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================= -->
<!-- CONTACT SECTION & MAP EMBED                                               -->
<!-- ========================================================================= -->
<section class="section" id="contact" style="background:var(--paper)">
    <div class="container">
        <span class="pill-label">Contact</span>
        <h2>Reach us for court bookings</h2>

        <div class="contact-grid" style="margin-top:30px">
            <div class="contact-cards">
                <div class="contact-card">
                    <div class="contact-icon">@</div>
                    <div><small>Email</small><strong><?= e($settings['email'] ?? '') ?></strong></div>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">IG</div>
                    <div><small>Instagram</small><strong><?= e($settings['instagram'] ?? '') ?></strong></div>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">&#9742;</div>
                    <div><small>Phone</small><strong><?= e($settings['phone'] ?? '') ?></strong></div>
                </div>
            </div>
          <div class="contact-photo" style="<?php echo !empty($siteImages['contact_photo']) ? "background-image: url('" . e($siteImages['contact_photo']) . "'); background-size: cover; background-position: center;" : ''; ?>"></div>
            <div class="map-embed">
                <iframe src="https://maps.google.com/maps?q=<?= urlencode($settings['address'] ?? 'pickleball court') ?>&output=embed" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================= -->
<!-- FOOTER & SCRIPT INCLUSIONS                                                -->
<!-- ========================================================================= -->
<?php include __DIR__ . '/components/footer.php'; ?>

<script src="assets/js/main.js"></script>
</body>
</html>