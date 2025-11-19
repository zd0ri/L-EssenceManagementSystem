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

$stmt2 = mysqli_prepare($conn, 'SELECT oi.*, p.brand_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?');
mysqli_stmt_bind_param($stmt2, 'i', $id);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
$items = [];
if ($res2) {
    while ($r = mysqli_fetch_assoc($res2)) $items[] = $r;
}

$stmt3 = mysqli_prepare($conn, 'SELECT * FROM payments WHERE order_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt3, 'i', $id);
mysqli_stmt_execute($stmt3);
$res3 = mysqli_stmt_get_result($stmt3);
$payment = $res3 ? mysqli_fetch_assoc($res3) : null;

?>

<style>
  .order-confirmation-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, var(--cream-harvest) 0%, rgba(207, 187, 159, 0.1) 100%);
  }

  .confirmation-container {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    border-radius: var(--card-radius);
    padding: 50px 40px;
    box-shadow: 0 10px 40px rgba(44, 26, 17, 0.08);
    border: 1px solid rgba(207, 187, 159, 0.3);
  }

  .order-header {
    text-align: center;
    margin-bottom: 40px;
    border-bottom: 2px solid var(--golden-sand);
    padding-bottom: 30px;
  }

  .order-header h1 {
    font-size: 28px;
    color: var(--text-dark);
    margin-bottom: 8px;
    font-weight: 600;
  }

  .order-number {
    color: var(--golden-sand);
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 12px;
  }

  .order-status {
    display: inline-block;
    background: var(--golden-sand);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
  }

  .order-meta {
    background: var(--cream-harvest);
    padding: 24px;
    border-radius: var(--card-radius);
    margin-bottom: 30px;
  }

  .meta-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 14px;
  }

  .meta-row:last-child {
    margin-bottom: 0;
  }

  .meta-label {
    color: var(--text-dark);
    opacity: 0.7;
    font-weight: 500;
  }

  .meta-value {
    color: var(--text-dark);
    font-weight: 600;
  }

  .section-title {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-dark);
    margin-bottom: 16px;
    margin-top: 32px;
  }

  .section-title:first-of-type {
    margin-top: 0;
  }

  .items-list {
    background: var(--cream-harvest);
    border-radius: var(--card-radius);
    padding: 20px;
    margin-bottom: 30px;
  }

  .item-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px 0;
    border-bottom: 1px solid rgba(207, 187, 159, 0.5);
  }

  .item-row:last-child {
    border-bottom: none;
  }

  .item-details {
    flex: 1;
  }

  .item-name {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 4px;
    font-size: 14px;
  }

  .item-qty {
    font-size: 12px;
    color: var(--text-dark);
    opacity: 0.7;
  }

  .item-price {
    text-align: right;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
  }

  .order-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: var(--golden-sand);
    border-radius: var(--card-radius);
    margin-bottom: 30px;
  }

  .total-label {
    font-size: 16px;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .total-amount {
    font-size: 24px;
    font-weight: 700;
    color: white;
  }

  .payment-details {
    background: var(--cream-harvest);
    padding: 20px;
    border-radius: var(--card-radius);
    margin-bottom: 30px;
  }

  .payment-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 14px;
  }

  .payment-row:last-child {
    margin-bottom: 0;
  }

  .payment-label {
    color: var(--text-dark);
    opacity: 0.7;
    font-weight: 500;
  }

  .payment-value {
    color: var(--text-dark);
    font-weight: 600;
  }

  .cta-button {
    width: 100%;
    padding: 14px 24px;
    background: var(--btn-bg);
    color: white;
    border: none;
    border-radius: var(--card-radius);
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
  }

  .cta-button:hover {
    background: var(--btn-hover);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(44, 26, 17, 0.15);
    text-decoration: none;
    color: white;
  }

  .no-payment {
    color: var(--text-dark);
    opacity: 0.6;
    font-size: 14px;
    font-style: italic;
  }
</style>

<div class="order-confirmation-wrapper">
  <div class="confirmation-container">
    <div class="order-header">
      <h1>Thanks for your order.</h1>
      <div class="order-number">Order #<?php echo $order['order_id']; ?></div>
      <span class="order-status"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
    </div>

    <div class="order-meta">
      <div class="meta-row">
        <span class="meta-label">Placed</span>
        <span class="meta-value"><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Customer</span>
        <span class="meta-value"><?php echo htmlspecialchars($order['fullname'] ?? ''); ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Contact</span>
        <span class="meta-value"><?php echo htmlspecialchars($order['contact'] ?? ''); ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Address</span>
        <span class="meta-value"><?php echo htmlspecialchars($order['address'] ?? ''); ?></span>
      </div>
    </div>

    <div class="section-title">Items</div>
    <div class="items-list">
      <?php foreach ($items as $it): ?>
        <div class="item-row">
          <div class="item-details">
            <div class="item-name"><?php echo htmlspecialchars($it['brand_name'] ?? ''); ?></div>
            <div class="item-qty">Qty: <?php echo (int)$it['quantity']; ?> x ₱<?php echo number_format((float)$it['price_each'],2); ?></div>
          </div>
          <div class="item-price">₱<?php echo number_format((float)$it['quantity'] * (float)$it['price_each'],2); ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="order-total">
      <span class="total-label">Total</span>
      <span class="total-amount">₱<?php echo number_format((float)$order['total_amount'],2); ?></span>
    </div>

    <div class="section-title">Payment</div>
    <div class="payment-details">
      <?php if ($payment): ?>
        <div class="payment-row">
          <span class="payment-label">Method</span>
          <span class="payment-value"><?php echo htmlspecialchars($payment['payment_method']); ?></span>
        </div>
        <div class="payment-row">
          <span class="payment-label">Amount</span>
          <span class="payment-value">₱<?php echo number_format((float)$payment['amount_paid'],2); ?></span>
        </div>
        <div class="payment-row">
          <span class="payment-label">Reference</span>
          <span class="payment-value"><?php echo htmlspecialchars($payment['reference_no']); ?></span>
        </div>
        <div class="payment-row">
          <span class="payment-label">Paid</span>
          <span class="payment-value"><?php echo date('M d, Y H:i', strtotime($payment['date_paid'])); ?></span>
        </div>
      <?php else: ?>
        <div class="no-payment">No payment record found.</div>
      <?php endif; ?>
    </div>

    <a href="/essence_db/index.php" class="cta-button">Continue Shopping</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
