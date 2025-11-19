<?php
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/config.php';

$create = "CREATE TABLE IF NOT EXISTS brands (
  brand_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  description TEXT NULL,
  image VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $create);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  if ($action === 'create' || $action === 'update') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name === '') {
      $_SESSION['message'] = 'Brand name is required.';
      header('Location: brands.php'); exit();
    }
    $imagePath = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $tmp = $_FILES['image']['tmp_name'];
      $type = mime_content_type($tmp);
      $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
      if (isset($allowed[$type])) {
        $ext = $allowed[$type];
        $dir = __DIR__ . '/../uploads/brands';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fname = 'brand_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . '/' . $fname)) {
          $imagePath = 'uploads/brands/' . $fname;
        }
      }
    }
    if ($action === 'create') {
      $stmt = mysqli_prepare($conn, "INSERT INTO brands (name, description, image) VALUES (?, ?, ?)");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sss', $name, $desc, $imagePath);
        mysqli_stmt_execute($stmt);
      }
      $_SESSION['success'] = 'Brand created successfully.';
    } else {
      $id = (int)($_POST['brand_id'] ?? 0);
      
      if ($imagePath) {
        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM brands WHERE brand_id = {$id} LIMIT 1"));
        if ($old && !empty($old['image'])) { $fp = __DIR__ . '/../' . ltrim($old['image'],'/'); if (file_exists($fp)) @unlink($fp); }
      }
      $stmt = mysqli_prepare($conn, "UPDATE brands SET name = ?, description = ?, image = ? WHERE brand_id = ?");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $desc, $imagePath, $id);
        mysqli_stmt_execute($stmt);
      }
      $_SESSION['success'] = 'Brand updated successfully.';
    }
  } elseif ($action === 'delete') {
    $id = (int)($_POST['brand_id'] ?? 0);
    $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM brands WHERE brand_id = {$id} LIMIT 1"));
    if ($old && !empty($old['image'])) { $fp = __DIR__ . '/../' . ltrim($old['image'],'/'); if (file_exists($fp)) @unlink($fp); }
    mysqli_query($conn, "DELETE FROM brands WHERE brand_id = {$id}");
    $_SESSION['success'] = 'Brand deleted successfully.';
  }
  
  $redirect = 'brands.php';
  if ((isset($_REQUEST['return']) && $_REQUEST['return'] === 'dashboard') || (isset($_POST['return']) && $_POST['return'] === 'dashboard')) {
    $redirect = '/essence_db/admin/dashboard.php';
  }
  header('Location: ' . $redirect); exit();
}

include __DIR__ . '/../includes/header.php';

// load brands
$brands = [];
$rb = mysqli_query($conn, "SELECT * FROM brands ORDER BY name ASC");
if ($rb && mysqli_num_rows($rb) > 0) while ($r = mysqli_fetch_assoc($rb)) $brands[] = $r;
?>
<div class="admin-main-content">
  <div class="admin-card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <h2 style="margin:0">Manage Brands</h2>
      <a href="/essence_db/admin/dashboard.php" class="btn btn-outline-secondary">&larr; Dashboard</a>
    </div>
    <?php if (isset($_SESSION['brand_admin_msg'])) { echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['brand_admin_msg']) . '</div>'; unset($_SESSION['brand_admin_msg']); } ?>
    <div class="row">
    <div class="col-md-6">
      <h5>Existing Brands</h5>
      <ul class="list-group">
        <?php foreach ($brands as $b): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <?php if (!empty($b['image'])): ?><img src="/essence_db/<?php echo ltrim($b['image'],'/'); ?>" style="height:36px;object-fit:contain;margin-right:8px;vertical-align:middle;" /><?php endif; ?>
              <strong><?php echo htmlspecialchars($b['name']); ?></strong>
              <div class="small text-muted"><?php echo htmlspecialchars($b['description']); ?></div>
            </div>
            <div>
              <button class="btn btn-sm btn-outline-primary" onclick="startEdit(<?php echo (int)$b['brand_id']; ?>)">Edit</button>
              <form method="POST" action="brands.php" style="display:inline-block;margin-left:.5rem;" onsubmit="return confirm('Delete brand: <?php echo htmlspecialchars(addslashes($b['name'])); ?> ?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="brand_id" value="<?php echo (int)$b['brand_id']; ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="col-md-6">
      <h5 id="form-title">Create Brand</h5>
      <form method="POST" action="brands.php" enctype="multipart/form-data" id="brand-form">
        <input type="hidden" name="action" id="form-action" value="create">
        <input type="hidden" name="brand_id" id="brand_id" value="0">
        <div class="mb-2">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" class="form-control">
        </div>
        <div class="mb-2">
          <label for="description">Description</label>
          <textarea id="description" name="description" class="form-control"></textarea>
        </div>
        <div class="mb-2">
          <label for="image">Image (optional)</label>
          <input type="file" id="image" name="image" class="form-control">
        </div>
  <button class="btn btn-primary" type="submit">Save</button>
  <button class="btn btn-outline-success" type="submit" name="return" value="dashboard" style="margin-left:8px;">Save & Return to Dashboard</button>
        <button type="button" class="btn btn-secondary" id="cancel-edit" style="display:none;">Cancel</button>
      </form>
    </div>
  </div>
    </div>
  </div>
</div>

<script>
  
  <?php foreach ($brands as $b): ?>
    window['brand_<?php echo (int)$b['brand_id']; ?>'] = <?php echo json_encode($b); ?>;
  <?php endforeach; ?>
  function startEdit(id) {
    var data = window['brand_' + id];
    if (!data) return;
    document.getElementById('form-title').textContent = 'Edit Brand';
    document.getElementById('form-action').value = 'update';
    document.getElementById('brand_id').value = data.brand_id;
    document.getElementById('name').value = data.name;
    document.getElementById('description').value = data.description;
    document.getElementById('cancel-edit').style.display = 'inline-block';
  }
  document.getElementById('cancel-edit').addEventListener('click', function(){
    document.getElementById('form-title').textContent = 'Create Brand';
    document.getElementById('form-action').value = 'create';
    document.getElementById('brand_id').value = 0;
    document.getElementById('name').value = '';
    document.getElementById('description').value = '';
    this.style.display = 'none';
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>