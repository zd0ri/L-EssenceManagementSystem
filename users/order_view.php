<?php
session_start();
require_once(__DIR__ . '/../includes/auth.php');
include('../includes/header.php');
include('../includes/config.php');

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_user_id = (int)($_SESSION['user_id'] ?? 0);

// verify order belongs to this customer
$stmt = mysqli_prepare($conn, 'SELECT o.order_id, o.total_amount, o.status, o.payment_status, o.order_date, o.customer_id FROM orders o WHERE o.order_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = $res ? mysqli_fetch_assoc($res) : null;

if (!$order) {
  echo '<div class="container mt-4"><div class="alert alert-danger">Order not found.</div></div>';
  include __DIR__ . '/../includes/footer.php';
  exit();
}

// fetch customer_id for current user
$stmt2 = mysqli_prepare($conn, 'SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt2, 'i', $current_user_id);
mysqli_stmt_execute($stmt2);
$r2 = mysqli_stmt_get_result($stmt2);
$c = $r2 ? mysqli_fetch_assoc($r2) : null;
$customer_id = $c ? (int)$c['customer_id'] : null;

if ($customer_id !== (int)$order['customer_id']) {
  echo '<div class="container mt-4"><div class="alert alert-danger">You are not authorized to view this order.</div></div>';
  include __DIR__ . '/../includes/footer.php';
  exit();
}

// fetch order items
$q = mysqli_prepare($conn, 'SELECT oi.*, p.brand_name, COALESCE((SELECT path FROM product_images WHERE product_id = p.product_id ORDER BY product_image_id ASC LIMIT 1), p.image) AS image_path FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?');
mysqli_stmt_bind_param($q, 'i', $order_id);
mysqli_stmt_execute($q);
$resItems = mysqli_stmt_get_result($q);

?>
<div class="container py-4">
  <h2>Order #<?php echo (int)$order['order_id']; ?></h2>
  <p class="text-muted">Placed on <?php echo htmlspecialchars($order['order_date']); ?> — Status: <?php echo htmlspecialchars(ucfirst($order['status'])); ?></p>
  <?php
    // Allow customer to cancel the order if it's still pending or processing
    $canCancel = in_array($order['status'], ['pending','processing']);
    if ($canCancel):
  ?>
    <form method="POST" action="/essence_db/users/cancel_order.php" onsubmit="return confirm('Are you sure you want to cancel this order?');" style="display:inline-block;margin-bottom:8px;">
      <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
      <button type="submit" class="btn btn-sm btn-danger">Cancel Order</button>
    </form>
  <?php endif; ?>
  <div class="row">
    <div class="col-md-8">
      <div class="card mb-3"><div class="card-body">
        <?php
        if ($resItems && mysqli_num_rows($resItems) > 0) {
          while ($it = mysqli_fetch_assoc($resItems)) {
            $img = $it['image_path'];
            $p = str_replace('\\','/',$img);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
            $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p,'/');
            ?>
            <div class="d-flex align-items-center mb-3">
              <div style="width:84px;flex-shrink:0;"><img src="<?php echo htmlspecialchars($imgUrl); ?>" style="width:84px;height:84px;object-fit:cover;border-radius:8px;"></div>
              <div class="ms-3 flex-grow-1">
                <strong><?php echo htmlspecialchars($it['product_name'] ?? $it['brand_name']); ?></strong>
                <div class="text-muted small">Qty: <?php echo (int)$it['quantity']; ?> • Price: ₱<?php echo number_format((float)$it['price_each'],2); ?></div>
              </div>
              <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('review-form-<?php echo (int)$it['product_id']; ?>').style.display = (document.getElementById('review-form-<?php echo (int)$it['product_id']; ?>').style.display === 'block' ? 'none' : 'block')">Add Review</button>
                <div id="review-form-<?php echo (int)$it['product_id']; ?>" style="display:none; margin-top:8px; max-width:420px;">
                  <form method="POST" action="/essence_db/product.php?id=<?php echo (int)$it['product_id']; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="review_id" value="0">
                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    <div class="mb-1">
                      <label class="form-label">Rating</label>
                      <select name="rating" class="form-select form-select-sm" style="max-width:120px">
                        <?php for ($ri = 5; $ri >= 1; $ri--): ?>
                          <option value="<?php echo $ri; ?>"><?php echo $ri; ?></option>
                        <?php endfor; ?>
                      </select>
                    </div>
                    <div class="mb-1">
                      <textarea name="review_text" class="form-control" rows="2" placeholder="Write your review..."></textarea>
                    </div>
                    <div class="mb-1">
                      <input type="file" name="review_image" class="form-control form-control-sm">
                    </div>
                    <div>
                      <button class="btn btn-sm btn-primary" type="submit">Save</button>
                      <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('review-form-<?php echo (int)$it['product_id']; ?>').style.display='none'">Cancel</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <hr />
            <?php
          }
        } else {
          echo '<div class="text-muted">No items found for this order.</div>';
        }
        ?>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <h5 class="card-title">Order Summary</h5>
        <div class="d-flex justify-content-between"><div>Subtotal</div><div>₱<?php echo number_format((float)$order['total_amount'],2); ?></div></div>
        <div class="mt-3"><a href="../users/buy_again.php?order_id=<?php echo (int)$order['order_id']; ?>" class="btn btn-success w-100">Buy Again</a></div>
      </div></div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
