<?php
/*******************************************************************************
 * SECTION 1: INCLUDES, DEPENDENCIES & AUTHENTICATION
 *******************************************************************************/
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAdmin();

/*******************************************************************************
 * SECTION 2: FORM SUBMISSION & BACKEND ACTION LOGIC
 *******************************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Handle general text settings update
    if ($action === 'update_settings') {
        $settings = [
            'site_name'     => clean($_POST['site_name'] ?? ''),
            'tagline'       => clean($_POST['tagline'] ?? ''),
            'contact_email' => clean($_POST['contact_email'] ?? ''),
            'phone'         => clean($_POST['phone'] ?? ''),
            'instagram'     => clean($_POST['instagram'] ?? ''),
            'address'       => clean($_POST['address'] ?? '')
        ];

        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }

        setFlash('success', 'Site settings updated successfully.');
    }

    // Handle individual image update or removal
    elseif ($action === 'update_single_image') {
        $imageKey = $_POST['image_key'] ?? '';
        $allowedKeys = ['card_trusted', 'card_rated', 'card_flexible', 'contact_photo'];

        if (in_array($imageKey, $allowedKeys)) {
            // Check if removal was requested
            if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
                // Fetch existing path to unlink file if needed
                $stmt = $pdo->prepare("SELECT image_path FROM site_images WHERE section_key = ?");
                $stmt->execute([$imageKey]);
                $oldImage = $stmt->fetchColumn();

                if ($oldImage && file_exists(__DIR__ . '/../' . $oldImage)) {
                    @unlink(__DIR__ . '/../' . $oldImage);
                }

                // Clear from DB
                $delStmt = $pdo->prepare("DELETE FROM site_images WHERE section_key = ?");
                $delStmt->execute([$imageKey]);

                setFlash('success', 'Image removed successfully.');
            } 
            // Check if a new file was uploaded
            elseif (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/images/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileTmpPath = $_FILES['image_file']['tmp_name'];
                $fileName = time() . '_' . basename($_FILES['image_file']['name']);
                $destPath = $uploadDir . $fileName;
                $relativePath = 'assets/images/uploads/' . $fileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $stmt = $pdo->prepare("INSERT INTO site_images (section_key, image_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE image_path = ?");
                    $stmt->execute([$imageKey, $relativePath, $relativePath]);
                    setFlash('success', 'Image updated successfully.');
                }
            }
        }
    }

    redirect('/activepicklelabs/admin/settings.php');
}

/*******************************************************************************
 * SECTION 3: FETCH CURRENT SETTINGS & IMAGES
 *******************************************************************************/
$settingsData = [];
try {
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $settingsStmt->fetch()) {
        $settingsData[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$siteImages = [];
try {
    $imgStmt = $pdo->query("SELECT section_key, image_path FROM site_images");
    while ($row = $imgStmt->fetch()) {
        $siteImages[$row['section_key']] = $row['image_path'];
    }
} catch (Exception $e) {}
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

    <?php 
    /***************************************************************************
     * SECTION 4: ADMIN SIDEBAR COMPONENT
     ***************************************************************************/
    include __DIR__ . '/../components/admin-sidebar.php'; 
    ?>

    <main class="dash-main">
        <div class="dash-topbar"><h1>Site Settings</h1></div>

        <?php if ($msg = getFlash('success')): ?>
            <div class="alert alert-success alert-success-toast"><?= e($msg) ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- Left Card: General Text Settings -->
            <div class="panel-card panel-card-flush">
                <h2>General Information</h2>
                <form method="post">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="field field-spaced">
                        <label>Site name</label>
                        <input type="text" name="site_name" value="<?= e($settingsData['site_name'] ?? 'Active Picklelabs') ?>" required>
                    </div>

                    <div class="field field-spaced">
                        <label>Tagline</label>
                        <input type="text" name="tagline" value="<?= e($settingsData['tagline'] ?? 'The Picklelab That Gives You Ultimate Experience') ?>" required>
                    </div>

                    <div class="field field-spaced">
                        <label>Contact email</label>
                        <input type="email" name="contact_email" value="<?= e($settingsData['contact_email'] ?? 'info@activepicklelabs.com') ?>" required>
                    </div>

                    <div class="field field-spaced">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= e($settingsData['phone'] ?? '+0936-345-5567') ?>" required>
                    </div>

                    <div class="field field-spaced">
                        <label>Instagram handle</label>
                        <input type="text" name="instagram" value="<?= e($settingsData['instagram'] ?? 'ActivePicklelabs') ?>" required>
                    </div>

                    <div class="field field-roomy">
                        <label>Address</label>
                        <input type="text" name="address" value="<?= e($settingsData['address'] ?? 'Maayongtubig, Dauin, Negros Oriental, Philippines') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-solid">Save Settings</button>
                </form>
            </div>

            <!-- Right Card: Index Page Images Management -->
            <div class="panel-card panel-card-flush">
                <h2>Index Page Images</h2>
                <p class="muted subtitle-spaced">Update, replace, or remove individual category cards and contact photo.</p>
                
                <?php 
                $cards = [
                    'card_trusted'  => 'Trusted Quality Club Card',
                    'card_rated'    => 'Top Rated Players Card',
                    'card_flexible' => 'Flexible Access Card',
                    'contact_photo' => 'Contact Section Photo'
                ];

                foreach ($cards as $key => $label): 
                ?>
                    <div class="image-management-row">
                        <label class="image-row-label"><?= $label ?></label>
                        
                        <form method="post" enctype="multipart/form-data" class="image-item-form">
                            <input type="hidden" name="action" value="update_single_image">
                            <input type="hidden" name="image_key" value="<?= $key ?>">

                            <div class="image-preview-group">
                                <?php if (!empty($siteImages[$key])): ?>
                                    <img src="../<?= e($siteImages[$key]) ?>" alt="<?= $label ?>" class="setting-thumb">
                                <?php else: ?>
                                    <div class="no-thumb">No Image</div>
                                <?php endif; ?>
                            </div>

                            <div class="image-action-inputs">
                                <input type="file" name="image_file" accept="image/*" class="file-input-compact">
                                <div class="image-action-buttons">
                                    <button type="submit" class="btn btn-solid btn-sm">Save / Update</button>
                                    
                                    <?php if (!empty($siteImages[$key])): ?>
                                        <button type="submit" name="remove_image" value="1" class="btn btn-outline btn-sm btn-danger-text">Remove</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
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