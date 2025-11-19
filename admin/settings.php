<?php
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/config.php';

$createTable = "CREATE TABLE IF NOT EXISTS site_settings (
  setting_id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(191) NOT NULL UNIQUE,
  setting_value LONGTEXT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $createTable);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  
  if ($action === 'update_hero_image') {
    $heroPath = null;
    if (!empty($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
      $tmp = $_FILES['hero_image']['tmp_name'];
      $type = mime_content_type($tmp);
      $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
      if (isset($allowed[$type])) {
        $ext = $allowed[$type];
        $dir = __DIR__ . '/../uploads/hero';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fname = 'hero_' . time() . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . '/' . $fname)) {
          $heroPath = 'uploads/hero/' . $fname;
        }
      }
    }
    
    if ($heroPath) {
      // Delete old hero image if exists
      $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key = 'hero_image' LIMIT 1"));
      if ($old && !empty($old['setting_value'])) {
        $fp = __DIR__ . '/../' . ltrim($old['setting_value'], '/');
        if (file_exists($fp)) @unlink($fp);
      }
      
      // Update or insert hero image path
      $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_id FROM site_settings WHERE setting_key = 'hero_image' LIMIT 1"));
      if ($check) {
        mysqli_query($conn, "UPDATE site_settings SET setting_value = '" . mysqli_real_escape_string($conn, $heroPath) . "' WHERE setting_key = 'hero_image'");
      } else {
        mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('hero_image', '" . mysqli_real_escape_string($conn, $heroPath) . "')");
      }
      $_SESSION['settings_msg'] = 'Hero image updated successfully.';
    } else {
      $_SESSION['settings_msg'] = 'No image uploaded or upload failed.';
    }
    header('Location: settings.php'); exit();
  }
  
  if ($action === 'delete_hero_image') {
    $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key = 'hero_image' LIMIT 1"));
    if ($old && !empty($old['setting_value'])) {
      $fp = __DIR__ . '/../' . ltrim($old['setting_value'], '/');
      if (file_exists($fp)) @unlink($fp);
    }
    mysqli_query($conn, "DELETE FROM site_settings WHERE setting_key = 'hero_image'");
    $_SESSION['settings_msg'] = 'Hero image deleted.';
    header('Location: settings.php'); exit();
  }
}

$heroImage = null;
$heroRes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key = 'hero_image' LIMIT 1"));
if ($heroRes && !empty($heroRes['setting_value'])) {
  $heroImage = $heroRes['setting_value'];
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="admin-page">
  <div class="admin-card">
    <h2>Site Settings</h2>
    <?php if (isset($_SESSION['settings_msg'])): ?>
      <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['settings_msg']); ?></div>
      <?php unset($_SESSION['settings_msg']); ?>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h5>Homepage Hero Image</h5>
          </div>
          <div class="card-body">
          <?php if ($heroImage): ?>
            <div class="mb-3">
              <label>Current Hero Image:</label>
              <div style="margin-top: 10px; margin-bottom: 10px;">
                <img src="/<?php echo ltrim($heroImage, '/'); ?>" style="max-width: 100%; max-height: 300px; border-radius: 8px; object-fit: cover;" alt="Hero image preview" />
              </div>
            </div>
          <?php else: ?>
            <div class="alert alert-secondary mb-3">No hero image set. Upload one to customize the homepage hero section.</div>
          <?php endif; ?>

          <form method="POST" action="settings.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_hero_image">
            <div class="mb-3">
              <label for="hero_image" class="form-label">Upload New Hero Image (JPG/PNG/GIF/WebP)</label>
              <input type="file" id="hero_image" name="hero_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required />
              <small class="form-text text-muted">Recommended: 1200x600px or wider. The image will be displayed on the right side of the homepage hero section.</small>
            </div>
            <button type="submit" class="btn btn-primary">Upload Hero Image</button>
          </form>

          <?php if ($heroImage): ?>
            <form method="POST" action="settings.php" style="display: inline-block; margin-top: 10px;">
              <input type="hidden" name="action" value="delete_hero_image">
              <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete the current hero image?');">Delete Hero Image</button>
            </form>
          <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
