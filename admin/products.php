<?php
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/config.php';

$qstr = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '';
if ($qstr !== '') {
  $esc = mysqli_real_escape_string($conn, $qstr);
  // allow searching by brand name (brands.name), legacy p.brand_name, product_name, scent or description
  $where = "AND (COALESCE(b.name, p.brand_name) LIKE '%{$esc}%' OR p.product_name LIKE '%{$esc}%' OR p.scent_type LIKE '%{$esc}%' OR p.description LIKE '%{$esc}%')";
}

// pagination
$perPage = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// get total count
$countSql = "SELECT COUNT(p.product_id) AS cnt FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id INNER JOIN inventory i ON p.product_id = i.product_id WHERE p.status = 'available' {$where}";
$cntRes = mysqli_query($conn, $countSql);
$totalItems = 0;
if ($cntRes) {
  $crow = mysqli_fetch_assoc($cntRes);
  $totalItems = (int)$crow['cnt'];
}

?>

<div class="admin-main-content">
  <div class="admin-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 style="margin: 0;">Products (<?php echo $totalItems; ?>)</h2>
      <a href="/essence_db/item/create.php" class="btn btn-primary">+ Add Product</a>
    </div>

    <!-- Search Form -->
    <form method="GET" class="mb-3 d-flex gap-2">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search by brand, scent, or description..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
      <button type="submit" class="btn btn-outline-secondary btn-sm">Search</button>
      <?php if (!empty($qstr)): ?>
        <a href="products.php" class="btn btn-outline-secondary btn-sm">Clear</a>
      <?php endif; ?>
    </form>

    <div class="table-responsive">
      <table class="table table-striped table-bordered admin-table align-middle">
          <thead>
            <tr>
              <th style="width: 80px;">Image</th>
              <th>Product</th>
              <th>Brand</th>
              <th>Description</th>
              <th style="width: 70px;">Qty</th>
              <th style="width: 110px;">Price</th>
              <th style="width: 200px;">Actions</th>
            </tr>
          </thead>
          <tbody>
<?php
$sql = "SELECT p.product_id, COALESCE(p.product_name, p.brand_name, '') AS product_name, COALESCE(b.name, p.brand_name, '') AS brand_name, p.scent_type, p.description, p.price, i.quantity, COALESCE((SELECT path FROM product_images WHERE product_id = p.product_id ORDER BY product_image_id ASC LIMIT 1), p.image) AS image_path FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id INNER JOIN inventory i ON p.product_id = i.product_id WHERE p.status = 'available' {$where} ORDER BY p.product_id DESC LIMIT {$perPage} OFFSET {$offset}";
$res = mysqli_query($conn, $sql);
if ($res && mysqli_num_rows($res) > 0) {
  while ($row = mysqli_fetch_assoc($res)) {
    $img = $row['image_path'];
    $p = str_replace('\\', '/', $img);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
    $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
    $pid = (int)$row['product_id'];
    
    echo "<tr>";
    echo "<td style='padding: 8px;'><img src='" . htmlspecialchars($imgUrl) . "' style='width: 70px; height: 70px; object-fit: cover; border-radius: 6px;' onerror=\"this.style.display='none'\"></td>";
    echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['brand_name']) . "</td>";
    echo "<td>" . htmlspecialchars(substr($row['description'] ?? '', 0, 40)) . "...</td>";
    echo "<td class='text-center'>" . (int)$row['quantity'] . "</td>";
    echo "<td>₱" . number_format((float)$row['price'], 2) . "</td>";
    echo "<td class='text-center'>";
    echo "<a class='btn btn-sm btn-outline-primary me-1' href='/essence_db/item/edit.php?id={$pid}'>Edit</a>";
    echo "<a class='btn btn-sm btn-outline-danger me-1' href='/essence_db/item/delete.php?id={$pid}' onclick=\"return confirm('Delete this product?');\">Delete</a>";
    echo "<a class='btn btn-sm btn-outline-secondary' href='/essence_db/product.php?id={$pid}' target='_blank'>View</a>";
    echo "</td>";
    echo "</tr>";
  }
} else {
  echo "<tr><td colspan='7' class='text-center text-muted'>No products found.</td></tr>";
}
?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php
      $totalPages = max(1, ceil($totalItems / $perPage));
      if ($totalPages > 1): 
      ?>
      <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
              <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"><?php echo $p; ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
      <?php endif; ?>
    </div>
  </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
