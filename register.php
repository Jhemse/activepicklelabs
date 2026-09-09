<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'client/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = clean($_POST['full_name'] ?? '');
    $email    = clean($_POST['email'] ?? '');
    $phone    = clean($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        [$ok, $message] = registerUser($pdo, $name, $email, $phone, $password);
        if ($ok) {
            setFlash('success', $message);
            redirect('login.php');
        } else {
            $error = $message;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Active Picklelabs</title>
<link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-visual">
        <a class="brand brand-light" href="index.php">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-text">Active<br><strong>Picklelabs</strong></span>
        </a>
        <div>
            <h2>Join the club today.</h2>
            <p style="color:rgba(255,255,255,.75);max-width:32ch">Create a free account to start booking courts and open play sessions.</p>
        </div>
        <p style="color:rgba(255,255,255,.5);font-size:.85rem">&copy; <?= date('Y') ?> Active Picklelabs</p>
    </div>
    <div class="auth-form-side">
        <div class="auth-card">
            <h1>Create Account</h1>
            <p class="muted">It only takes a minute.</p>

            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

            <form method="post" novalidate data-role="register-form">
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="6" required>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                </div>
                <button type="submit" class="btn btn-solid btn-block">Register</button>
            </form>
            <p class="form-footnote">Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
