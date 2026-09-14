<?php
/*******************************************************************************
 * SECTION 1: CONNECT TO THE DATABASE AND CHECK IF USER IS AN ADMIN
 *******************************************************************************/
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAdmin();


/*******************************************************************************
 * SECTION 2: PROCESS FORM SUBMISSIONS (ADD, UPDATE, OR DELETE A COURT)
 *******************************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_court') {
        $name = clean($_POST['court_name']);
        $desc = clean($_POST['description']);
        $rate = (float) $_POST['hourly_rate'];

        $stmt = $pdo->prepare("INSERT INTO courts (court_name, description, hourly_rate, status) VALUES (?, ?, ?, 'available')");
        $stmt->execute([$name, $desc, $rate]);
        setFlash('success', 'Court added successfully.');
    } elseif ($action === 'update_court') {
        $id   = (int) $_POST['court_id'];
        $name = clean($_POST['court_name']);
        $desc = clean($_POST['description']);
        $rate = (float) $_POST['hourly_rate'];
        $status = clean($_POST['status']);

        $stmt = $pdo->prepare("UPDATE courts SET court_name = ?, description = ?, hourly_rate = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $desc, $rate, $status, $id]);
        setFlash('success', 'Court updated successfully.');
    } elseif ($action === 'delete_court') {
        $id = (int) $_POST['court_id'];
        $stmt = $pdo->prepare("DELETE FROM courts WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Court deleted successfully.');
    }
    redirect('/activepicklelabs/admin/courts.php');
}


/*******************************************************************************
 * SECTION 3: FETCH ALL COURTS FROM THE DATABASE TO DISPLAY
 *******************************************************************************/
$courts = $pdo->query("SELECT * FROM courts ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Courts Management - Active Picklelabs</title>
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/openplay.css?v=<?= time() ?>">
</head>
<body>
<div class="dash-shell">
    <?php 
    /***************************************************************************
     * SECTION 4: LOAD THE ADMIN SIDEBAR NAVIGATION MENU
     ***************************************************************************/
    include __DIR__ . '/../components/admin-sidebar.php'; 
    ?>
    <main class="dash-main">
        <div class="dash-topbar">
            <h1>Courts</h1>
            <button type="button" class="btn btn-solid" id="openAddCourtModal">+ Add Court</button>
        </div>

        <?php if ($msg = getFlash('success')): ?>
            <div class="alert alert-success" style="margin-bottom:20px;"><?= e($msg) ?></div>
        <?php endif; ?>

        <?php 
        /*******************************************************************
         * SECTION 5: SHOW THE LIST OF COURTS IN A TABLE
         *******************************************************************/
        ?>
        <div class="panel-card">
            <h2>All Courts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Rate/Hr</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($courts)): ?>
                    <tr><td colspan="5" class="muted">No courts available.</td></tr>
                <?php else: ?>
                    <?php foreach($courts as $c): ?>
                        <tr>
                            <td><?= e($c['court_name']) ?></td>
                            <td><?= e($c['description']) ?></td>
                            <td>₱<?= number_format($c['hourly_rate'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= $c['status'] === 'available' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($c['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <button class="btn-text text-primary open-edit-modal" 
                                        data-id="<?= $c['id'] ?>"
                                        data-name="<?= e($c['court_name']) ?>"
                                        data-desc="<?= e($c['description']) ?>"
                                        data-rate="<?= $c['hourly_rate'] ?>"
                                        data-status="<?= $c['status'] ?>">
                                        Edit
                                    </button>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this court?');">
                                        <input type="hidden" name="action" value="delete_court">
                                        <input type="hidden" name="court_id" value="<?= $c['id'] ?>">
                                        <button class="btn-text text-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php 
/*******************************************************************************
 * SECTION 6: POPUP WINDOWS (MODALS) FOR ADDING AND EDITING COURTS
 *******************************************************************************/
?>
<!-- ADD COURT MODAL -->
<div class="modal-overlay" id="courtModalOverlay">
    <div class="glass-modal">
        <div class="modal-header">
            <h2>ADD A COURT</h2>
            <button type="button" class="btn-close" id="closeAddCourtModal">&times;</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="create_court">
            
            <div class="field">
                <label>Court name</label>
                <input type="text" name="court_name" placeholder="e.g. Court 4" required>
            </div>

            <div class="field">
                <label>Description</label>
                <input type="text" name="description" placeholder="e.g. Indoor court, air-conditioned" required>
            </div>

            <div class="field">
                <label>Hourly rate (₱)</label>
                <input type="number" step="0.01" name="hourly_rate" placeholder="300.00" required>
            </div>

            <div class="modal-actions" style="margin-top: 20px;">
                <button type="submit" class="btn btn-solid full-width">Add Court</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT COURT MODAL -->
<div class="modal-overlay" id="editModalOverlay">
    <div class="glass-modal">
        <div class="modal-header">
            <h2>EDIT COURT</h2>
            <button type="button" class="btn-close" id="closeEditModal">&times;</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="update_court">
            <input type="hidden" name="court_id" id="edit_court_id">
            
            <div class="field">
                <label>Court name</label>
                <input type="text" name="court_name" id="edit_court_name" required>
            </div>

            <div class="field">
                <label>Description</label>
                <input type="text" name="description" id="edit_description" required>
            </div>

            <div class="field">
                <label>Hourly rate (₱)</label>
                <input type="number" step="0.01" name="hourly_rate" id="edit_hourly_rate" required>
            </div>

            <div class="field">
                <label>Status</label>
                <select name="status" id="edit_status" required style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: rgba(255, 255, 255, 0.9); font-size: 0.9rem; box-sizing: border-box;">
                    <option value="available">Available</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>

            <div class="modal-actions" style="margin-top: 20px;">
                <button type="submit" class="btn btn-solid full-width">Update Court</button>
            </div>
        </form>
    </div>
</div>

<a href="../index.php" class="btn-back-home">Back to Home</a>

<?php 
/*******************************************************************************
 * SECTION 7: JAVASCRIPT TO HANDLE OPENING AND CLOSING THE POPUPS
 *******************************************************************************/
?>
<script>
// Add Modal Script
const modalOverlay = document.getElementById('courtModalOverlay');
const openBtn = document.getElementById('openAddCourtModal');
const closeBtn = document.getElementById('closeAddCourtModal');

openBtn.addEventListener('click', () => modalOverlay.classList.add('active'));
closeBtn.addEventListener('click', () => modalOverlay.classList.remove('active'));
modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) modalOverlay.classList.remove('active'); });

// Edit Modal Script
const editModalOverlay = document.getElementById('editModalOverlay');
const closeEditBtn = document.getElementById('closeEditModal');

document.querySelectorAll('.open-edit-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit_court_id').value = btn.dataset.id;
        document.getElementById('edit_court_name').value = btn.dataset.name;
        document.getElementById('edit_description').value = btn.dataset.desc;
        document.getElementById('edit_hourly_rate').value = btn.dataset.rate;
        document.getElementById('edit_status').value = btn.dataset.status;
        editModalOverlay.classList.add('active');
    });
});

closeEditBtn.addEventListener('click', () => editModalOverlay.classList.remove('active'));
editModalOverlay.addEventListener('click', (e) => { if (e.target === editModalOverlay) editModalOverlay.classList.remove('active'); });
</script>
</body>
</html>