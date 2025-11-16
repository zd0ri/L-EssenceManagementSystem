<?php
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/config.php';

$qstr = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '';
if ($qstr !== '') {
  $esc = mysqli_real_escape_string($conn, $qstr);
  $where = "AND (p.brand_name LIKE '%{$esc}%' OR p.scent_type LIKE '%{$esc}%')";
}

?>

<div class="admin-page">
  <div class="admin-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 style="margin: 0;">Products</h2>
      <a href="/essence_db/item/create.php" class="btn btn-primary">+ Add Product</a>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-bordered admin-table">
          <thead>
            <tr>
              <th></th>
              <th>Product</th>
              <th>Qty</th>
              <th>Brand</th>
              <th>Price</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
<?php
$sql = "SELECT p.product_id, p.brand_name, p.price, i.quantity, COALESCE((SELECT path FROM product_images WHERE product_id = p.product_id ORDER BY product_image_id ASC LIMIT 1), p.image) AS image_path FROM products p INNER JOIN inventory i ON p.product_id = i.product_id WHERE p.status = 'available' {$where} ORDER BY p.product_id DESC";
$res = mysqli_query($conn, $sql);
if ($res && mysqli_num_rows($res) > 0) {
  while ($row = mysqli_fetch_assoc($res)) {
    $img = $row['image_path'];
    $p = str_replace('\\', '/', $img);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
    $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
    echo "<tr>";
    echo "<td><img src='" . htmlspecialchars($imgUrl) . "' class='table-img' onerror=\"this.src='/essence_db/images/placeholder.png'\"></td>";
    echo "<td>" . htmlspecialchars($row['brand_name']) . "<div class='text-muted'>" . htmlspecialchars($row['scent_type'] ?? '') . "</div></td>";
    echo "<td>" . (int)$row['quantity'] . "</td>";
    echo "<td>" . htmlspecialchars($row['brand_name']) . "</td>";
    echo "<td>₱" . number_format((float)$row['price'],2) . "</td>";
    echo "<td><a class='btn btn-sm btn-outline-secondary' href='/essence_db/item/edit.php?id=" . (int)$row['product_id'] . "'>Edit</a></td>";
    echo "</tr>";
  }
} else {
  echo "<tr><td colspan='6' class='text-muted'>No products found.</td></tr>";
}
?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
