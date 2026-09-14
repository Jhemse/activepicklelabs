<?php
/**
 * create_admin.php - run this ONCE in your browser after importing the SQL file:
 *   http://localhost/activepicklelabs/database/create_admin.php
 * It creates the first admin account. Delete this file afterwards.
 */

// =========================================================================
// DEPENDENCY INCLUSION & ENVIRONMENT SETUP
// =========================================================================
require_once __DIR__ . '/../includes/db.php';

// Initialize status flags and error messages
$done = false;
$error = null;

// =========================================================================
// FORM SUBMISSION & ADMIN CREATION LOGIC
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    // Validate inputs (ensure all fields are filled and password meets length requirement)
    if ($name && $email && strlen($pass) >= 6) {
        // Check if an account with this email address already exists
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        
        if ($check->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            // Hash the password securely and insert the new admin record
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, "admin")'
            );
            $stmt->execute([$name, $email, $hash]);
            $done = true;
        }
    } else {
        $error = 'Please fill every field (password must be at least 6 characters).';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Admin Account</title>
<style>
    body{font-family:Arial,sans-serif;background:#0d1b4c;color:#fff;display:flex;height:100vh;align-items:center;justify-content:center}
    .box{background:#fff;color:#111;padding:32px;border-radius:12px;width:340px}
    input{width:100%;padding:10px;margin:6px 0 14px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}
    button{width:100%;padding:10px;background:#e6e619;border:none;border-radius:6px;font-weight:bold;cursor:pointer}
    .ok{color:green} .err{color:#c0392b}
</style>
</head>
<body>
<div class="box">
    <h2>Create Admin</h2>
    <?php if ($done): ?>
        <!-- ======================================================= -->
        <!-- SUCCESS NOTIFICATION & SECURITY WARNING                 -->
        <!-- ======================================================= -->
        <p class="ok">Admin account created! You can now log in from login.php.</p>
        <p><strong>Delete this file (database/create_admin.php) now for security.</strong></p>
    <?php else: ?>
        <!-- ======================================================= -->
        <!-- ADMIN REGISTRATION FORM                                 -->
        <!-- ======================================================= -->
        <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post">
            <label>Full name</label>
            <input type="text" name="full_name" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" minlength="6" required>
            <button type="submit">Create Admin</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>