<?php
/*******************************************************************************
 * SECTION 1: CONNECT TO DATABASE, LOAD DEPENDENCIES, AND VERIFY ADMIN ACCESS
 *******************************************************************************/
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking-functions.php';

requireAdmin();

$error = null;
$success = null;


/*******************************************************************************
 * SECTION 2: PROCESS FORM SUBMISSIONS (ADD, UPDATE, OR UPLOAD SERVICE BANNER)
 *******************************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $title         = clean($_POST['title'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $displayOrder= (int) ($_POST['display_order'] ?? 1);
    
    // Handle image upload if provided
    $imageUrl = null;
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['banner']['tmp_name'];
        $fileName = $_FILES['banner']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../uploads/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $imageUrl = 'uploads/' . $newFileName;
            }
        }
    }

    if (empty($title)) {
        $error = 'Service title is required.';
    } else {
        if ($action === 'add_service') {
            createService($pdo, $title, $description, $imageUrl, 'sparkle', $displayOrder);
            setFlash('success', 'Service added successfully!');
        } elseif ($action === 'edit_service') {
            $serviceId = (int) $_POST['service_id'];
            
            if (!$imageUrl) {
                $existingStmt = $pdo->prepare('SELECT image_url FROM services WHERE id = ?');
                $existingStmt->execute([$serviceId]);
                $existingService = $existingStmt->fetch();
                $imageUrl = $existingService['image_url'] ?? null;
            }

            $updateStmt = $pdo->prepare('UPDATE services SET title = ?, description = ?, image_url = ?, display_order = ? WHERE id = ?');
            $updateStmt->execute([$title, $description, $imageUrl, $displayOrder, $serviceId]);
            setFlash('success', 'Service updated successfully!');
        }
        redirect('services.php');
    }
}


/*******************************************************************************
 * SECTION 3: HANDLE SERVICE DELETION VIA GET REQUEST
 *******************************************************************************/
if (isset($_GET['delete'])) {
    $serviceId = (int) $_GET['delete'];
    deleteService($pdo, $serviceId);
    setFlash('success', 'Service deleted successfully.');
    redirect('services.php');
}


/*******************************************************************************
 * SECTION 4: FETCH ALL SERVICES FOR DISPLAY
 *******************************************************************************/
$services = getAllServices($pdo);
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Active Picklelabs</title>
    <link rel="stylesheet" href="/ActivePicklelabs/assets/css/styles.css">
    <link rel="stylesheet" href="/ActivePicklelabs/assets/css/openplay.css?v=<?= time() ?>">
</head>
<body>
<div class="dash-shell">
    <?php 
    /***************************************************************************
     * SECTION 5: LOAD THE ADMIN SIDEBAR NAVIGATION MENU
     ***************************************************************************/
    ?>
    <aside class="dash-sidebar">
        <div class="sidebar-logo-slot">
            <a href="/ActivePicklelabs/index.php" aria-label="Active Picklelabs">
                <img src="/ActivePicklelabs/assets/images/WBEDEV-08.svg" alt="Active Picklelabs Logo" class="sidebar-logo-img">
            </a>
        </div>
        <nav class="dash-nav">
            <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="requests.php" class="<?= $current === 'requests.php' ? 'active' : '' ?>">Booking Requests</a>
            <a href="open-play.php" class="<?= $current === 'open-play.php' ? 'active' : '' ?>">Open Play Sessions</a>
            <a href="courts.php" class="<?= $current === 'courts.php' ? 'active' : '' ?>">Courts</a>
            <a href="services.php" class="<?= $current === 'services.php' ? 'active' : '' ?>">Services</a>
            <a href="clients.php" class="<?= $current === 'clients.php' ? 'active' : '' ?>">Clients</a>
            <a href="settings.php" class="<?= $current === 'settings.php' ? 'active' : '' ?>">Settings</a>
        </nav>
        <a class="btn btn-ghost logout-link" href="/ActivePicklelabs/logout.php">Logout</a>
    </aside>
    
    <main class="dash-main">
        <div class="dash-topbar">
            <h1>SERVICES</h1>
        </div>

        <?php if ($success = getFlash('success')): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="admin-header-row">
            <h2>ALL SERVICES (SHOWN ON THE HOMEPAGE)</h2>
            <button type="button" class="btn btn-solid" id="openAddServiceModal">ADD A SERVICE</button>
        </div>

        <?php 
        /*******************************************************************
         * SECTION 6: DISPLAY ALL SERVICES IN A TABLE WITH ACTIONS
         *******************************************************************/
        ?>
        <div class="panel-card">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Banner</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr><td colspan="5" class="muted text-center">No services found.</td></tr>
                    <?php else: foreach ($services as $index => $service): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <?php if (!empty($service['image_url'])): ?>
                                    <img src="../<?= htmlspecialchars($service['image_url']) ?>" class="table-img-thumb">
                                <?php else: ?>
                                    <span class="muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold"><?= htmlspecialchars($service['title']) ?></td>
                            <td class="muted"><?= htmlspecialchars($service['description']) ?></td>
                            <td class="text-right">
                                <div class="action-flex">
                                    <button type="button" class="btn-text text-primary open-edit-modal" 
                                        data-id="<?= $service['id'] ?>"
                                        data-title="<?= htmlspecialchars($service['title']) ?>"
                                        data-desc="<?= htmlspecialchars($service['description']) ?>"
                                        data-order="<?= $service['display_order'] ?? 1 ?>">
                                        Edit
                                    </button>
                                    <a href="services.php?delete=<?= $service['id'] ?>" onclick="return confirm('Are you sure you want to delete this service?');" class="btn-text text-danger">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php 
/*******************************************************************************
 * SECTION 7: POPUP MODAL FOR ADDING / EDITING SERVICES
 *********************************--------------------------------**************/
?>
<div class="modal-overlay" id="serviceModalOverlay">
    <div class="glass-modal">
        <div class="modal-header">
            <h2 id="modalTitle">SERVICES</h2>
            <button type="button" class="btn-close" id="closeServiceModal">&times;</button>
        </div>
        <form action="services.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add_service">
            <input type="hidden" name="service_id" id="serviceId" value="">
            
            <div class="field">
                <label>Title</label>
                <input type="text" name="title" id="serviceTitle" required>
            </div>

            <div class="field">
                <label>Description</label>
                <textarea name="description" id="serviceDescription" rows="3"></textarea>
            </div>

            <div class="field">
                <label>Banner / Image</label>
                <input type="file" name="banner">
                <small class="field-hint">Leave blank to keep current image when editing.</small>
            </div>

            <div class="field">
                <label>Display order</label>
                <input type="number" name="display_order" id="serviceOrder" value="1">
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-solid full-width" id="submitBtn">Add Service</button>
            </div>
        </form>
    </div>
</div>

<?php 
/*******************************************************************************
 * SECTION 8: JAVASCRIPT TO HANDLE MODAL OPEN/CLOSE & EDIT FORM POPULATION
 *********************************--------------------------------**************/
?>
<script>
const serviceModalOverlay = document.getElementById('serviceModalOverlay');
const openAddBtn = document.getElementById('openAddServiceModal');
const closeServiceModalBtn = document.getElementById('closeServiceModal');

openAddBtn.addEventListener('click', () => {
    document.getElementById('modalTitle').innerText = 'SERVICES';
    document.getElementById('formAction').value = 'add_service';
    document.getElementById('serviceId').value = '';
    document.getElementById('serviceTitle').value = '';
    document.getElementById('serviceDescription').value = '';
    document.getElementById('serviceOrder').value = '1';
    document.getElementById('submitBtn').innerText = 'Add Service';
    serviceModalOverlay.classList.add('active');
});

document.querySelectorAll('.open-edit-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('modalTitle').innerText = 'SERVICES';
        document.getElementById('formAction').value = 'edit_service';
        document.getElementById('serviceId').value = btn.dataset.id;
        document.getElementById('serviceTitle').value = btn.dataset.title;
        document.getElementById('serviceDescription').value = btn.dataset.desc;
        document.getElementById('serviceOrder').value = btn.dataset.order;
        document.getElementById('submitBtn').innerText = 'Update Service';
        serviceModalOverlay.classList.add('active');
    });
});

closeServiceModalBtn.addEventListener('click', () => serviceModalOverlay.classList.remove('active'));
serviceModalOverlay.addEventListener('click', (e) => { if (e.target === serviceModalOverlay) serviceModalOverlay.classList.remove('active'); });
</script>
</body>
</html>