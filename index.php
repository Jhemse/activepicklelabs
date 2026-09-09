<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/booking-functions.php';

$settings   = getSettings($pdo);
$services   = getAllServices($pdo);
$openPlay   = getUpcomingOpenPlay($pdo, 3);
$serviceBg  = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($settings['site_name'] ?? 'Active Picklelabs') ?> - Court Booking &amp; Training</title>
<link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="hero" id="home">
    <?php include __DIR__ . '/components/public-navbar.php'; ?>

    <div class="container hero-inner">
        <div>
            <span class="eyebrow-pill">Play Pickleball</span>
            <h1 class="hero-title">The Picklelab That Gives You Ultimate Experience</h1>
            <p class="hero-lede"><?= e($settings['tagline'] ?? '') ?> Be part of the club and let us show you the true nature of being active.</p>
            <div style="margin-top:26px" class="nav-actions">
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

<div class="feature-row">
    <div class="feature-grid">
        <div class="feature-card f1"><span>Trusted<br>Quality Club</span></div>
        <div class="feature-card f2"><span>Top Rated<br>Pickleball Players</span></div>
        <div class="feature-card f3"><span>Always<br>Flexible Access</span></div>
    </div>
</div>

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

<section class="section" id="services" style="background:var(--paper)">
    <div class="services-wrap">
        <div>
            <span class="pill-label">Our Services</span>
            <h2>Premium pickleball options for everyone</h2>
            <p class="muted">From a quick private rally to full tournament hosting, every service is booked and managed right from your account.</p>
        </div>
        <div class="services-grid">
            <?php foreach ($services as $i => $s): ?>
                <div class="service-card <?= $serviceBg[$i % count($serviceBg)] ?>">
                    <div class="sc-icon">&#9679;</div>
                    <h3><?= e($s['title']) ?></h3>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="booking">
    <div class="booking-panel">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px">
            <h2>Courts &amp; Booking</h2>
            <p class="muted" style="max-width:360px;margin:0">Be part of the club and let us show you the true nature of being active. Active Picklelabs will serve!</p>
        </div>
        <div class="booking-grid">
            <div class="booking-actions">
                <a class="btn btn-navy" href="<?= isLoggedIn() ? 'client/book-court.php' : 'register.php' ?>">Book My Own Court</a>
                <a class="btn btn-navy" href="<?= isLoggedIn() ? 'client/book-court.php?type=open_play' : 'register.php' ?>">Join Open Play</a>
                <div class="court-diagram">
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
            <div>
                <h3 style="margin-bottom:16px">Upcoming Courts</h3>
                <div class="upcoming-list">
                    <?php if ($openPlay): ?>
                        <?php foreach ($openPlay as $i => $b): ?>
                            <div class="upcoming-item">
                                <span class="num"><?= $i + 1 ?></span>
                                <span class="info"><strong><?= formatDate($b['booking_date']) ?>, <?= formatTime($b['start_time']) ?>-<?= formatTime($b['end_time']) ?></strong> | <?= e($b['court_name']) ?></span>
                                <a class="btn btn-lime btn-sm" href="<?= isLoggedIn() ? 'client/book-court.php' : 'register.php' ?>">Join Open Play</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="upcoming-item"><span class="info muted">No open play sessions scheduled yet — be the first to book one!</span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="contact" style="background:var(--paper)">
    <span class="pill-label container" style="display:inline-block;margin-left:24px">Contact</span>
    <div class="container">
        <h2>Reach us for court bookings</h2>
    </div>
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
        <div class="contact-photo"></div>
        <div class="map-embed">
            <iframe src="https://maps.google.com/maps?q=<?= urlencode($settings['address'] ?? 'pickleball court') ?>&output=embed" loading="lazy"></iframe>
        </div>
    </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>

<script src="assets/js/main.js"></script>
</body>
</html>
