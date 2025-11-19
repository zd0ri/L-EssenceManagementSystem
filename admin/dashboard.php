<?php
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/config.php';
?>

<div class="admin-main-content">
  <div class="admin-hero" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px;">
    <div>
      <h2 style="color: #5A4939">Dashboard</h2>
      <p style="color: #5A4939; font-size: 0.95rem; margin: 6px 0 0 0;">Welcome back! Here's an overview of your shop.</p>
    </div>
      <div style="display:flex; gap:10px;">
        <a href="/essence_db/item/create.php" class="btn btn-primary">
          <i class="fas fa-plus me-2"></i>Add Product
        </a>
        <a href="/essence_db/index.php" target="_blank" rel="noopener" class="btn btn-outline-primary">
          <i class="fas fa-eye me-2"></i>View Site
        </a>
      </div>
    </div>

    <!-- Quick stats -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="admin-card" style="padding: 20px; text-align: center;">
          <h4 style="margin: 0; color: #5A4939;">
            <?php
            $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products WHERE status = 'available'");
            $r = mysqli_fetch_assoc($q);
            echo $r['cnt'] ?? 0;
            ?>
          </h4>
          <p style="color: #5A4939; margin: 6px 0 0 0; font-size: 0.9rem;">Active Products</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="admin-card" style="padding: 20px; text-align: center;">
          <h4 style="margin: 0; color: #5A4939;">
            <?php
            // Show orders in the last 30 days, excluding refunded orders
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $end_date = date('Y-m-d');
            $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE payment_status != 'refunded' AND DATE(order_date) BETWEEN '$start_date' AND '$end_date'");
            $r = mysqli_fetch_assoc($q);
            echo $r['cnt'] ?? 0;
            ?>
          </h4>
          <p style="color: #5A4939; margin: 6px 0 0 0; font-size: 0.9rem;">Total Orders</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="admin-card" style="padding: 20px; text-align: center;">
          <h4 style="margin: 0; color: rgb(109, 96, 83);">
            <?php
            $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers");
            $r = mysqli_fetch_assoc($q);
            echo $r['cnt'] ?? 0;
            ?>
          </h4>
          <p style="color: #5A4939; margin: 6px 0 0 0; font-size: 0.9rem;">Total Customers</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="admin-card" style="padding: 20px; text-align: center;">
          <h4 style="margin: 0; color: rgb(109, 96, 83);">
            ₱<?php
            // Revenue for the last 30 days (only paid orders). Use DATE(order_date) for inclusive day range
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $end_date = date('Y-m-d');
            $q = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid' AND DATE(order_date) BETWEEN '$start_date' AND '$end_date'");
            $r = mysqli_fetch_assoc($q);
            echo number_format((float)($r['total'] ?? 0), 2);
            ?>
          </h4>
          <p style="color: #5A4939; margin: 6px 0 0 0; font-size: 0.9rem;">Revenue</p>
        </div>
      </div>
    </div>

    <!-- Sales Report Quick Access -->
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="admin-card" style="padding: 20px; background: linear-gradient(180deg, #5A4939 0%, #2C1A11 100%); color: white;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h5 style="margin: 0; font-weight: 600; color: #CFBB9F;">Sales Report</h5>
              <p style="margin: 6px 0 0 0; opacity: 0.9;">View detailed sales, expenses, and product breakdown</p>
            </div>
            <a href="/essence_db/admin/sales_report.php" class="btn btn-light" style="font-weight: 600;">
              <i class="fas fa-chart-bar me-2"></i>View Report
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Products -->
    <div class="admin-card">
      <div class="admin-hero" style="display: flex; justify-content: space-between; align-items: center; margin: 0; padding: 20px; border-bottom: 1px solid #dfe6e9;">
        <h5 style="margin: 0;color: #5A4939">Recent Products</h5>
        <a href="/essence_db/admin/products.php" class="btn btn-sm btn-outline-secondary" style="color: #47392cff; background-color: #CFBB9F;">View All</a>
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
// Select product_name and join brands table for correct brand display. Fall back to legacy p.brand_name if needed.
$q = mysqli_query($conn, "SELECT p.product_id, COALESCE(p.product_name, p.brand_name, '') AS product_name, COALESCE(b.name, p.brand_name, '') AS brand_name, p.price, i.quantity, COALESCE((SELECT path FROM product_images WHERE product_id = p.product_id ORDER BY product_image_id ASC LIMIT 1), p.image) AS image_path FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id INNER JOIN inventory i ON p.product_id = i.product_id WHERE p.status = 'available' ORDER BY p.product_id DESC LIMIT 8");
if ($q && mysqli_num_rows($q) > 0) {
  while ($r = mysqli_fetch_assoc($q)) {
    $img = $r['image_path'];
    $p = str_replace('\\','/',$img);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
    $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p,'/');
    echo "<tr>";
    echo "<td style='width: 72px;'><img src='" . htmlspecialchars($imgUrl) . "' class='table-img' onerror=\"this.src='/essence_db/images/placeholder.png'\" style='width: 72px; height: 72px; object-fit: cover; border-radius: 8px; font-color: #2C1A11;'></td>";
    echo "<td>" . htmlspecialchars($r['product_name']) . "</td>";
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
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
