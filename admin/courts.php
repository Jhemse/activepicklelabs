<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create') {
        createCourt($pdo, clean($_POST['court_name']), clean($_POST['description']), (float) $_POST['hourly_rate']);
        setFlash('success', 'Court added.');
    } elseif ($action === 'update') {
        updateCourt(
            $pdo,
            (int) $_POST['id'],
            clean($_POST['court_name']),
            clean($_POST['description']),
            (float) $_POST['hourly_rate'],
            $_POST['status']
        );
        setFlash('success', 'Court updated.');
    } elseif ($action === 'delete') {
        deleteCourt($pdo, (int) $_POST['id']);
        setFlash('success', 'Court removed.');
    }
    redirect('courts.php');
}

$courts = getAllCourts($pdo);
$editId = (int) ($_GET['edit'] ?? 0);
$editCourt = $editId ? getCourt($pdo, $editId) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Courts - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>Courts</h1></div>
        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="panel-card" style="max-width:520px">
            <h2><?= $editCourt ? 'Edit Court' : 'Add a Court' ?></h2>
            <form method="post">
                <input type="hidden" name="form_action" value="<?= $editCourt ? 'update' : 'create' ?>">
                <?php if ($editCourt): ?><input type="hidden" name="id" value="<?= $editCourt['id'] ?>"><?php endif; ?>
                <div class="field">
                    <label>Court name</label>
                    <input type="text" name="court_name" required value="<?= e($editCourt['court_name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Description</label>
                    <input type="text" name="description" value="<?= e($editCourt['description'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Hourly rate (&#8369;)</label>
                        <input type="number" step="0.01" name="hourly_rate" required value="<?= e((string)($editCourt['hourly_rate'] ?? '')) ?>">
                    </div>
                    <?php if ($editCourt): ?>
                    <div class="field">
                        <label>Status</label>
                        <select name="status">
                            <option value="available" <?= $editCourt['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="maintenance" <?= $editCourt['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-solid"><?= $editCourt ? 'Save Changes' : 'Add Court' ?></button>
                <?php if ($editCourt): ?><a class="btn btn-ghost" href="courts.php">Cancel</a><?php endif; ?>
            </form>
        </div>

        <div class="panel-card">
            <h2>All Courts</h2>
            <table>
                <thead><tr><th>Name</th><th>Description</th><th>Rate/hr</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($courts as $c): ?>
                    <tr>
                        <td><?= e($c['court_name']) ?></td>
                        <td><?= e($c['description']) ?></td>
                        <td>&#8369;<?= number_format($c['hourly_rate'], 2) ?></td>
                        <td><span class="badge <?= $c['status'] === 'available' ? 'badge-confirmed' : 'badge-pending' ?>"><?= ucfirst($c['status']) ?></span></td>
                        <td class="actions-inline">
                            <a class="btn btn-ghost btn-sm" href="?edit=<?= $c['id'] ?>">Edit</a>
                            <form method="post"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button class="btn btn-ghost btn-sm" data-confirm="Delete this court?" type="submit">Delete</button></form>
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
