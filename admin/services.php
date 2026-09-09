<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'create') {
        createService($pdo, clean($_POST['title']), clean($_POST['description']), 'sparkle', (int) $_POST['display_order']);
        setFlash('success', 'Service added.');
    } elseif ($action === 'delete') {
        deleteService($pdo, (int) $_POST['id']);
        setFlash('success', 'Service removed.');
    }
    redirect('services.php');
}

$services = getAllServices($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Services - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>Services</h1></div>
        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="panel-card" style="max-width:520px">
            <h2>Add a Service</h2>
            <form method="post">
                <input type="hidden" name="form_action" value="create">
                <div class="field"><label>Title</label><input type="text" name="title" required></div>
                <div class="field"><label>Description</label><input type="text" name="description"></div>
                <div class="field"><label>Display order</label><input type="number" name="display_order" value="<?= count($services) + 1 ?>"></div>
                <button type="submit" class="btn btn-solid">Add Service</button>
            </form>
        </div>

        <div class="panel-card">
            <h2>All Services (shown on the homepage)</h2>
            <table>
                <thead><tr><th>#</th><th>Title</th><th>Description</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td><?= $s['display_order'] ?></td>
                        <td><?= e($s['title']) ?></td>
                        <td><?= e($s['description']) ?></td>
                        <td>
                            <form method="post"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button class="btn btn-ghost btn-sm" data-confirm="Remove this service?" type="submit">Delete</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
