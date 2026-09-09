<?php $siteName = $settings['site_name'] ?? 'Active Picklelabs'; ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-col">
            <a class="brand brand-light" href="index.php">
                <span class="brand-mark" aria-hidden="true"></span>
                <span class="brand-text">Active<br><strong>Picklelabs</strong></span>
            </a>
            <p class="muted"><?= e($settings['tagline'] ?? '') ?></p>
            <p><?= e($settings['phone'] ?? '') ?></p>
            <p><?= e($settings['email'] ?? '') ?></p>
        </div>
        <div class="footer-col">
            <h4>Play &amp; Train</h4>
            <a href="index.php#booking">Book a Court</a>
            <a href="index.php#services">Coaching Programs</a>
            <a href="index.php#services">Group Classes</a>
            <a href="index.php#booking">Tournaments</a>
            <a href="index.php#services">Gear Rental</a>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="index.php#about">About Us</a>
            <a href="index.php#services">Our Services</a>
            <a href="index.php#booking">Courts &amp; Booking</a>
            <a href="index.php#contact">Contact Us</a>
        </div>
        <div class="footer-col">
            <h4>Follow Us on Social Media</h4>
            <a href="#">Instagram &middot; <?= e($settings['instagram'] ?? '') ?></a>
            <a href="#">Facebook &middot; <?= e($siteName) ?></a>
            <a href="#">TikTok &middot; <?= e($siteName) ?></a>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>Copyright &copy; <?= date('Y') ?> <?= e($siteName) ?></span>
        <span>Terms of Use | Privacy Policy | Cookie Policy</span>
    </div>
</footer>
