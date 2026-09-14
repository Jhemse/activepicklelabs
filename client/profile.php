<?php
// =========================================================================
// DEPENDENCIES & CORE INITIALIZATION
// =========================================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// PURPOSE: Enforce user login and redirect admin users 
// away from client profile settings.
requireLogin();
if (isAdmin()) { redirect('../admin/dashboard.php'); }

// Fetch current user details from the database
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$error = null;

// =========================================================================
// FORM SUBMISSION HANDLING (PROFILE & PASSWORD UPDATES)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = clean($_POST['full_name'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    // Validate name requirement
    if ($name === '') {
        $error = 'Name cannot be empty.';
    } else {
        // Update user's general profile information (Name and Phone)
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->execute([$name, $phone, $_SESSION['user_id']]);
        $_SESSION['full_name'] = $name;

        // Process optional password change if a new password is provided
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([$hash, $_SESSION['user_id']]);
            }
        }

        // If no errors occurred during execution, set success message and redirect
        if (!$error) {
            setFlash('success', 'Profile updated.');
            redirect('profile.php');
        }
    }
    
    // Retain submitted values in memory if validation fails so fields stay populated
    $user['full_name'] = $name;
    $user['phone'] = $phone;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/client-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>My Profile</h1></div>

        <!-- Flash and Error Notification Banners -->
        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <!-- ======================================================= -->
        <!-- PROFILE EDIT FORM PANEL                                 -->
        <!-- ======================================================= -->
        <div class="panel-card" style="max-width:520px">
            <form method="post">
                <div class="field">
                    <label>Full name</label>
                    <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="field">
                    <label>Email (cannot be changed)</label>
                    <input type="email" value="<?= e($user['email']) ?>" disabled>
                </div>
                <div class="field">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= e($user['phone']) ?>">
                </div>
                <div class="field">
                    <label>New password (leave blank to keep current)</label>
                    <input type="password" name="new_password" minlength="6">
                </div>
                <button type="submit" class="btn btn-solid">Save Changes</button>
            </form>
        </div>
    </main>
</div>

<a href="../index.php" class="btn-back-home">Back to Home</a>

</body>
</html>