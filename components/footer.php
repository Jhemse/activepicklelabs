<?php
// =========================================================================
// SITE SETTINGS INITIALIZATION
// =========================================================================
// Fallback to default brand name if site settings are not defined
$siteName = $settings['site_name'] ?? 'Active Picklelabs'; 
?>
<footer class="site-footer">
    <!-- ======================================================= -->
    <!-- MAIN FOOTER GRID SECTION                                -->
    <!-- ======================================================= -->
    <div class="container footer-grid">
        
        <!-- Column 1: Brand Logo, Tagline & Contact Details -->
        <div class="footer-col">
            <div class="footer-logo-slot">
                <a href="index.php#home" aria-label="<?= e($siteName) ?>">
                    <img src="assets/images/WBEDEV-08.svg" alt="<?= e($siteName) ?> Logo" class="footer-logo-img">
                </a>
            </div>
            <p class="muted"><?= e($settings['tagline'] ?? '') ?></p>
            <p><?= e($settings['phone'] ?? '') ?></p>
            <p><?= e($settings['email'] ?? '') ?></p>
        </div>

        <!-- Column 2: Play & Train Navigation Links -->
        <div class="footer-col">
            <h4>Play &amp; Train</h4>
            <a href="index.php#booking">Book a Court</a>
            <a href="index.php#services">Coaching Programs</a>
            <a href="index.php#services">Group Classes</a>
            <a href="index.php#booking">Tournaments</a>
            <a href="index.php#services">Gear Rental</a>
        </div>

        <!-- Column 3: Site Quick Links -->
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="index.php#about">About Us</a>
            <a href="index.php#services">Our Services</a>
            <a href="index.php#booking">Courts &amp; Booking</a>
            <a href="index.php#contact">Contact Us</a>
        </div>

        <!-- Column 4: Social Media Channels -->
        <div class="footer-col">
            <h4>Follow Us on Social Media</h4>
            <a href="#">Instagram &middot; <?= e($settings['instagram'] ?? '') ?></a>
            <a href="#">Facebook &middot; <?= e($siteName) ?></a>
            <a href="#">TikTok &middot; <?= e($siteName) ?></a>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- FOOTER BOTTOM COPYRIGHT & LEGAL BAR                     -->
    <!-- ======================================================= -->
    <div class="container footer-bottom">
        <span>Copyright &copy; <?= date('Y') ?> <?= e($siteName) ?></span>
        <span>Terms of Use | Privacy Policy | Cookie Policy</span>
    </div>
</footer>