<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/config.php';

// only admins allowed (simple guard - adjust as your auth requires)

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  echo '<div class="container py-4"><div class="alert alert-danger">Access denied.</div></div>';
  include __DIR__ . '/../includes/footer.php';
  exit();
}

$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// normalize date range for queries
// Export CSV logic removed

// summary queries (prepared)
$summary_sql = "SELECT COALESCE(SUM(o.total_amount),0) AS total_sales, COUNT(DISTINCT o.order_id) AS total_orders
FROM orders o
WHERE o.order_date BETWEEN ? AND ? AND o.payment_status = 'paid'";
$stmt = mysqli_prepare($conn, $summary_sql);
mysqli_stmt_bind_param($stmt, 'ss', $start, $end);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$summary = $res ? mysqli_fetch_assoc($res) : ['total_sales' => 0, 'total_orders' => 0];

$items_sql = "SELECT COALESCE(SUM(oi.quantity),0) AS items_sold
FROM order_items oi
JOIN orders o ON oi.order_id = o.order_id
WHERE o.order_date BETWEEN ? AND ? AND o.payment_status = 'paid'";
$stmt2 = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($stmt2, 'ss', $start, $end);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
$items_row = $res2 ? mysqli_fetch_assoc($res2) : ['items_sold' => 0];




// Product breakdown: show all products with sales in the selected range
$breakdown = [];
$breakdown_sql = "
SELECT
  p.product_id,
  p.brand_name,
  p.scent_type,
  SUM(oi.quantity) AS qty_sold,
  SUM(oi.quantity * oi.price_each) AS revenue
FROM order_items oi
JOIN orders o ON oi.order_id = o.order_id
JOIN products p ON oi.product_id = p.product_id
WHERE o.order_date BETWEEN ? AND ?
  AND o.payment_status = 'paid'
GROUP BY p.product_id, p.brand_name, p.scent_type
HAVING qty_sold > 0
ORDER BY revenue DESC";
$stmt4 = mysqli_prepare($conn, $breakdown_sql);
mysqli_stmt_bind_param($stmt4, 'ss', $start, $end);
mysqli_stmt_execute($stmt4);
$res4 = mysqli_stmt_get_result($stmt4);
if ($res4) {
  while ($row = mysqli_fetch_assoc($res4)) {
    $breakdown[] = $row;
  }
}

?>
<style>
.dashboard-cards {
  display: flex;
  gap: 24px;
  margin-bottom: 32px;
  flex-wrap: wrap;
}
.dashboard-card {
  flex: 1 1 200px;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 12px rgba(44,26,17,0.07);
  padding: 28px 24px 20px 24px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 180px;
  min-height: 120px;
  position: relative;
}
.dashboard-card .card-label {
  font-size: 15px;
  color: #888;
  margin-bottom: 8px;
  font-weight: 500;
}
.dashboard-card .card-value {
  font-size: 2rem;
  font-weight: 700;
  color: #2C1A11;
  margin-bottom: 0;
}
.dashboard-card.sales { background: #ffe5e5; }
.dashboard-card.orders { background: #fff7e5; }
.dashboard-card.products { background: #e5f7ff; }
.dashboard-card.customers { background: #e5ffe9; }
.dashboard-section {
  background: #fff;
  margin: 0 auto;
  box-shadow: 0 2px 12px rgba(44,26,17,0.07);
  padding: 28px 24px 20px 24px;
  margin-bottom: 32px;
}
.dashboard-section h5 {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 18px;
  color: #2C1A11;
}
.dashboard-table th, .dashboard-table td {
<body class="hide-sidebar">
  font-size: 15px;
}
.dashboard-table th {
  color: #888;
  font-weight: 600;
}
.dashboard-table td {
  color: #2C1A11;
}

</style>

<div class="container py-4">
  <h2 class="mb-4">Sales Report</h2>
  <form method="get" class="row g-3 mb-4">
    <div class="col-auto">
      <label for="start" class="form-label">Start Date</label>
      <input type="date" id="start" name="start" class="form-control" value="<?php echo htmlspecialchars($start); ?>">
    </div>
    <div class="col-auto">
      <label for="end" class="form-label">End Date</label>
      <input type="date" id="end" name="end" class="form-control" value="<?php echo htmlspecialchars($end); ?>">
    </div>
    <div class="col-auto align-self-end">
      <button type="submit" class="btn btn-primary">Filter</button>
    </div>
  </form>

  <div class="dashboard-cards">
    <div class="dashboard-card sales">
      <div class="card-label">Total Sales</div>
      <div class="card-value">₱<?php echo number_format((float)$summary['total_sales'],2); ?></div>
    </div>
    <div class="dashboard-card orders">
      <div class="card-label">Total Orders</div>
      <div class="card-value"><?php echo (int)$summary['total_orders']; ?></div>
    </div>
    <div class="dashboard-card products">
      <div class="card-label">Items Sold</div>
      <div class="card-value"><?php echo (int)$items_row['items_sold']; ?></div>
    </div>
    <!-- Net Income card removed -->
  </div>

  .sales-report-main {
    margin-left: 20%;
    transition: margin 0.3s;
  }
  <div class="dashboard-section">
    <h5>Sales by Product</h5>
    <div class="table-responsive">
      <table class="table table-striped dashboard-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Brand</th>
            <th>Scent</th>
            <th class="text-end">Qty Sold</th>
            <th class="text-end">Revenue</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($breakdown as $b): ?>
            <tr>
              <td><?php echo (int)$b['product_id']; ?></td>
              <td><?php echo htmlspecialchars($b['brand_name']); ?></td>
              <td><?php echo htmlspecialchars($b['scent_type']); ?></td>
              <td class="text-end"><?php echo (int)$b['qty_sold']; ?></td>
</body>
              <td class="text-end">₱<?php echo number_format((float)$b['revenue'],2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <form method="post" class="mt-3">
      <input type="hidden" name="total_sales" value="<?php echo htmlspecialchars($summary['total_sales']); ?>">
      <!-- total_expenses hidden input removed -->
      <button type="submit" name="save_report" class="btn btn-success">Save Report</button>
      <a href="/essence_db/admin/" class="btn btn-outline-secondary ms-2">Back to Admin</a>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
