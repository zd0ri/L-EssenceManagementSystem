<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo '<div class="container"><div class="alert alert-danger">Order not specified.</div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

// fetch order
$stmt = mysqli_prepare($conn, 'SELECT o.*, c.fullname, c.email, c.address, c.contact FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = $res ? mysqli_fetch_assoc($res) : null;
if (!$order) {
    echo '<div class="container"><div class="alert alert-danger">Order not found.</div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

// fetch items
$stmt2 = mysqli_prepare($conn, 'SELECT oi.*, p.brand_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?');
mysqli_stmt_bind_param($stmt2, 'i', $id);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
$items = [];
if ($res2) {
    while ($r = mysqli_fetch_assoc($res2)) $items[] = $r;
}

// fetch payment
$stmt3 = mysqli_prepare($conn, 'SELECT * FROM payments WHERE order_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt3, 'i', $id);
mysqli_stmt_execute($stmt3);
$res3 = mysqli_stmt_get_result($stmt3);
$payment = $res3 ? mysqli_fetch_assoc($res3) : null;

?>
<div class="container py-4">
  <h2>Order Confirmation</h2>
  <div class="card p-3 mb-3">
    <h5>Order #<?php echo $order['order_id']; ?> - <?php echo htmlspecialchars($order['status']); ?></h5>
    <div>Placed: <?php echo $order['order_date']; ?></div>
    <div>Customer: <?php echo htmlspecialchars($order['fullname'] ?? ''); ?></div>
    <div>Contact: <?php echo htmlspecialchars($order['contact'] ?? ''); ?></div>
    <div>Address: <?php echo htmlspecialchars($order['address'] ?? ''); ?></div>
  </div>

  <h5>Items</h5>
  <ul class="list-group mb-3">
    <?php foreach ($items as $it): ?>
      <li class="list-group-item d-flex justify-content-between">
        <div>
          <div><?php echo htmlspecialchars($it['brand_name'] ?? ''); ?></div>
          <small class="text-muted">Qty: <?php echo (int)$it['quantity']; ?> x ₱<?php echo number_format((float)$it['price_each'],2); ?></small>
        </div>
        <div>₱<?php echo number_format((float)$it['quantity'] * (float)$it['price_each'],2); ?></div>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="mb-3">
    <strong>Total: ₱<?php echo number_format((float)$order['total_amount'],2); ?></strong>
  </div>

  <h5>Payment</h5>
  <?php if ($payment): ?>
    <div>Method: <?php echo htmlspecialchars($payment['payment_method']); ?></div>
    <div>Amount: ₱<?php echo number_format((float)$payment['amount_paid'],2); ?></div>
    <div>Reference: <?php echo htmlspecialchars($payment['reference_no']); ?></div>
    <div>Paid: <?php echo $payment['date_paid']; ?></div>
  <?php else: ?>
    <div class="text-muted">No payment record found.</div>
  <?php endif; ?>

  <a href="/essence_db/index.php" class="btn btn-primary mt-3">Continue Shopping</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
