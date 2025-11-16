<?php
session_start();
require_once __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <h2>Brands</h2>
  <p>Choose a brand to view its products.</p>
  <div class="list-group">
    <?php
    // try to load dynamic brands from DB
    $q = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS brands (
      brand_id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(191) NOT NULL,
      description TEXT NULL,
      image VARCHAR(255) NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $rb = mysqli_query($conn, "SELECT brand_id, name, image FROM brands ORDER BY name ASC");
    if ($rb && mysqli_num_rows($rb) > 0) {
      while ($r = mysqli_fetch_assoc($rb)) {
        $url = '/essence_db/brand.php?brand=' . urlencode($r['name']);
        $img = '';
        if (!empty($r['image'])) $img = '<img src="' . htmlspecialchars('/essence_db/' . ltrim($r['image'], '/')) . '" style="height:28px;object-fit:contain;margin-right:8px;">';
        echo '<a href="' . htmlspecialchars($url) . '" class="list-group-item list-group-item-action">' . $img . htmlspecialchars($r['name']) . '</a>';
      }
    } else {
      // fallback static list
      $brands = ['Valentino','Creed','Perfume Dessert','Ian Darcy','Jo Malone'];
      foreach ($brands as $b) {
        $url = '/essence_db/brand.php?brand=' . urlencode($b);
        echo '<a href="' . htmlspecialchars($url) . '" class="list-group-item list-group-item-action">' . htmlspecialchars($b) . '</a>';
      }
    }
    ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>