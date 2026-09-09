<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    toggleClientStatus($pdo, (int) $_POST['toggle_id']);
    redirect('clients.php');
}

$clients = getAllClients($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clients - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="dash-shell">
    <?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
    <main class="dash-main">
        <div class="dash-topbar"><h1>Clients</h1></div>

        <div class="panel-card">
            <?php if ($clients): ?>
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($clients as $c): ?>
                        <tr>
                            <td><?= e($c['full_name']) ?></td>
                            <td><?= e($c['email']) ?></td>
                            <td><?= e($c['phone']) ?></td>
                            <td><?= formatDate($c['created_at']) ?></td>
                            <td><span class="badge <?= $c['status'] === 'active' ? 'badge-confirmed' : 'badge-cancelled' ?>"><?= ucfirst($c['status']) ?></span></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="toggle_id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" data-confirm="<?= $c['status'] === 'active' ? 'Disable' : 'Re-enable' ?> this account?" type="submit">
                                        <?= $c['status'] === 'active' ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">No clients have registered yet.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
