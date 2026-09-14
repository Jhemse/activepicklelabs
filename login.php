<?php
// =========================================================================
// DEPENDENCY INCLUSION & AUTHENTICATION CHECK
// =========================================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect user if they are already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'client/dashboard.php');
}

$error = null;

// =========================================================================
// LOGIN FORM SUBMISSION HANDLING
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } elseif (attemptLogin($pdo, $email, $password)) {
        redirect(isAdmin() ? 'admin/dashboard.php' : 'client/dashboard.php');
    } else {
        $error = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Active Picklelabs</title>
<link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="auth-shell">
    <!-- ======================================================= -->
    <!-- LEFT AUTHENTICATION VISUAL PANEL                        -->
    <!-- ======================================================= -->
    <div class="auth-visual">
        <a class="brand brand-light" href="index.php">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-text"><br><strong></strong></span>
        </a>
        <div>
            <h2>Welcome back to the club.</h2>
            <p style="color:rgba(255,255,255,.75);max-width:32ch">Log in to book courts, join open play, and manage your sessions.</p>
        </div>
        <p style="color:rgba(255,255,255,.5);font-size:.85rem">&copy; <?= date('Y') ?> Active Picklelabs</p>
    </div>

    <!-- ======================================================= -->
    <!-- RIGHT AUTHENTICATION FORM CONTAINER                     -->
    <!-- ======================================================= -->
    <div class="auth-form-side">
        <div class="auth-card">
            <h1>Log In</h1>
            <p class="muted">Enter your details to access your account.</p>

            <!-- Error and Success Flash Notification Alerts -->
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

            <!-- Login Credentials Submission Form -->
            <form method="post" novalidate>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-solid btn-block">Log In</button>
            </form>
            <p class="form-footnote">Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>