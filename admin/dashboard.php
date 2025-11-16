<?php
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/config.php';
?>

<div class="container-fluid admin-shell" style="padding: 28px;">
  <aside class="admin-sidebar">
    <div class="admin-card mb-3" style="padding: 16px;">
      <h5 class="mb-3">Admin</h5>
      <div class="list-group">
        <a href="/essence_db/admin/dashboard.php" class="list-group-item list-group-item-action active">
          <i class="fas fa-chart-line me-2"></i>Dashboard
        </a>
        <a href="/essence_db/admin/products.php" class="list-group-item list-group-item-action">
          <i class="fas fa-box me-2"></i>Products
        </a>
        <a href="/essence_db/admin/orders.php" class="list-group-item list-group-item-action">
          <i class="fas fa-receipt me-2"></i>Orders
        </a>
        <a href="/essence_db/admin/users_manage.php" class="list-group-item list-group-item-action">
          <i class="fas fa-users me-2"></i>Customers
        </a>
        <a href="/essence_db/admin/brands.php" class="list-group-item list-group-item-action">
          <i class="fas fa-tag me-2"></i>Brands
        </a>
        <a href="/essence_db/admin/settings.php" class="list-group-item list-group-item-action">
          <i class="fas fa-cog me-2"></i>Settings
        </a>
      </div>
    </div>
  </aside>

  <main class="admin-main" style="flex: 1;">
    <div class="admin-hero" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px;">
      <div>
        <h2>Dashboard</h2>
        <p style="color: #636e72; font-size: 0.95rem; margin: 6px 0 0 0;">Welcome back! Here's an overview of your shop.</p>
      </div>
      <div>
        <a href="/essence_db/item/create.php" class="btn btn-primary">
          <i class="fas fa-plus me-2"></i>Add Product
        </a>
      </div>
    </div>

    <!-- Quick stats -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="admin-card" style="padding: 20px; text-align: center;">
          <h4 style="margin: 0; color: #6c5ce7;">
            <?php
            $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products WHERE status = 'available'");
            $r = mysqli_fetch_assoc($q);
            echo $r['cnt'] ?? 0;
            ?>
          </h4>
          <p style="color: #636e72; margin: 6px 0 0 0; font-size: 0.9rem;">Active Products</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="admin-card" style="padding: 20px; text-align: center;">
          <h4 style="margin: 0; color: #27ae60;">
            <?php
            $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE status != 'cancelled'");
            $r = mysqli_fetch_assoc($q);
            echo $r['cnt'] ?? 0;
            ?>
          </h4>
          <p style="color: #636e72; margin: 6px 0 0 0; font-size: 0.9rem;">Total Orders</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="admin-card" style="padding: 20px; text-align: center;">
          <h4 style="margin: 0; color: #e74c3c;">
            <?php
            $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers");
            $r = mysqli_fetch_assoc($q);
            echo $r['cnt'] ?? 0;
            ?>
          </h4>
          <p style="color: #636e72; margin: 6px 0 0 0; font-size: 0.9rem;">Total Customers</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="admin-card" style="padding: 20px; text-align: center;">
          <h4 style="margin: 0; color: #f39c12;">
            ₱<?php
            $q = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
            $r = mysqli_fetch_assoc($q);
            echo number_format((float)($r['total'] ?? 0), 2);
            ?>
          </h4>
          <p style="color: #636e72; margin: 6px 0 0 0; font-size: 0.9rem;">Revenue</p>
        </div>
      </div>
    </div>

    <!-- Recent Products -->
    <div class="admin-card">
      <div class="admin-hero" style="display: flex; justify-content: space-between; align-items: center; margin: 0; padding: 20px; border-bottom: 1px solid #dfe6e9;">
        <h5 style="margin: 0;">Recent Products</h5>
        <a href="/essence_db/admin/products.php" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="table-responsive" style="padding: 20px;">
        <table class="table">
          <thead>
            <tr>
              <th></th>
              <th>Product Name</th>
              <th>Brand</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
<?php
$q = mysqli_query($conn, "SELECT p.product_id, p.brand_name, p.price, i.quantity, COALESCE((SELECT path FROM product_images WHERE product_id = p.product_id ORDER BY product_image_id ASC LIMIT 1), p.image) AS image_path FROM products p INNER JOIN inventory i ON p.product_id = i.product_id WHERE p.status = 'available' ORDER BY p.product_id DESC LIMIT 8");
if ($q && mysqli_num_rows($q) > 0) {
  while ($r = mysqli_fetch_assoc($q)) {
    $img = $r['image_path'];
    $p = str_replace('\\','/',$img);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
    $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p,'/');
    echo "<tr>";
    echo "<td style='width: 72px;'><img src='" . htmlspecialchars($imgUrl) . "' class='table-img' onerror=\"this.src='/essence_db/images/placeholder.png'\" style='width: 72px; height: 72px; object-fit: cover; border-radius: 8px;'></td>";
    echo "<td>" . htmlspecialchars($r['brand_name']) . "</td>";
    echo "<td>" . htmlspecialchars($r['brand_name']) . "</td>";
    echo "<td>₱" . number_format((float)$r['price'],2) . "</td>";
    echo "<td><span class='badge " . ($r['quantity'] > 10 ? 'badge-success' : ($r['quantity'] > 0 ? 'badge-warning' : 'badge-danger')) . "'>" . (int)$r['quantity'] . "</span></td>";
    echo "<td><a class='btn btn-sm btn-outline-secondary' href='/essence_db/item/edit.php?id=" . (int)$r['product_id'] . "'>Edit</a></td>";
    echo "</tr>";
  }
} else {
  echo "<tr><td colspan='6' class='text-muted'>No products available.</td></tr>";
}
?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<style>
  .admin-shell { display: flex; gap: 24px; padding: 28px; }
  .admin-sidebar { width: 220px; flex-shrink: 0; }
  .admin-main { flex: 1; }
  .admin-card { background: #fff; border: 1px solid #dfe6e9; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
  .table-img { width: 72px; height: 72px; object-fit: cover; border-radius: 8px; }
  @media (max-width: 768px) {
    .admin-shell { flex-direction: column; }
    .admin-sidebar { width: 100%; }
  }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
