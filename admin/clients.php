<?php
/*******************************************************************************
 * SECTION 1: INCLUDES, DEPENDENCIES & AUTHENTICATION
 *******************************************************************************/
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();


/*******************************************************************************
 * SECTION 2: FORM SUBMISSION & BACKEND ACTION LOGIC
 *******************************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle status toggle (Enable/Disable)
    if (isset($_POST['toggle_id'])) {
        toggleClientStatus($pdo, (int) $_POST['toggle_id']);
        redirect('clients.php');
    }
    
    // ADDED: Handle permanent deletion of client accounts
    if (isset($_POST['delete_client_id'])) {
        $clientId = (int) $_POST['delete_client_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$clientId]);
        setFlash('success', 'Client account permanently deleted.');
        redirect('clients.php');
    }
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

    <?php 
    /***************************************************************************
     * SECTION 3: ADMIN SIDEBAR COMPONENT
     ***************************************************************************/
    include __DIR__ . '/../components/admin-sidebar.php'; 
    ?>

    <main class="dash-main">
        <div class="dash-topbar"><h1>Clients</h1></div>

        <?php if ($msg = getFlash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="panel-card">
            <?php 
            /*******************************************************************
             * SECTION 4: CLIENTS DATA TABLE & STATUS LOOP
             *******************************************************************/
            if ($clients): 
            ?>
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
                            <td class="actions-inline">
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="toggle_id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" data-confirm="<?= $c['status'] === 'active' ? 'Disable' : 'Re-enable' ?> this account?" type="submit">
                                        <?= $c['status'] === 'active' ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>

                                <!-- ADDED: Delete button for client accounts -->
                                <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to permanently delete this client account?');">
                                    <input type="hidden" name="delete_client_id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" style="color: #ef4444;" type="submit">Delete</button>
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

<?php 
/*******************************************************************************
 * SECTION 5: FOOTER SCRIPTS & GLOBAL NAVIGATION
 *******************************************************************************/
?>
<script src="../assets/js/main.js"></script>

<!-- Floating Back to Home Button -->
<a href="../index.php" class="btn-back-home">Back to Home</a>

</body>
</html>