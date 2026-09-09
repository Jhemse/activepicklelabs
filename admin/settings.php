<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    updateSettings($pdo, [
        'site_name' => clean($_POST['site_name']),
        'tagline'   => clean($_POST['tagline']),
        'email'     => clean($_POST['email']),
        'phone'     => clean($_POST['phone']),
        'instagram' => clean($_POST['instagram']),
        'address'   => clean($_POST['address']),
    ]);
    setFlash('success', 'Settings saved.');
    redirect('settings.php');
}

$settings = getSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>Site Settings</h1></div>
        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="panel-card" style="max-width:560px">
            <form method="post">
                <div class="field"><label>Site name</label><input type="text" name="site_name" required value="<?= e($settings['site_name']) ?>"></div>
                <div class="field"><label>Tagline</label><input type="text" name="tagline" value="<?= e($settings['tagline']) ?>"></div>
                <div class="field"><label>Contact email</label><input type="email" name="email" value="<?= e($settings['email']) ?>"></div>
                <div class="field"><label>Phone</label><input type="text" name="phone" value="<?= e($settings['phone']) ?>"></div>
                <div class="field"><label>Instagram handle</label><input type="text" name="instagram" value="<?= e($settings['instagram']) ?>"></div>
                <div class="field"><label>Address</label><input type="text" name="address" value="<?= e($settings['address']) ?>"></div>
                <button type="submit" class="btn btn-solid">Save Settings</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
