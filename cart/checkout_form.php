<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/config.php';

// Debug: log what we're receiving
@file_put_contents(__DIR__ . '/checkout_debug.log', json_encode([
    'time' => date('c'),
    'post_selected_items' => isset($_POST['selected_items']) ? $_POST['selected_items'] : 'NOT SET',
    'post_keys' => array_keys($_POST),
    'cart_products' => isset($_SESSION['cart_products']) ? array_column($_SESSION['cart_products'], 'item_id') : 'NOT SET'
]) . PHP_EOL, FILE_APPEND | LOCK_EX);

$all_cart = isset($_SESSION['cart_products']) && is_array($_SESSION['cart_products']) ? $_SESSION['cart_products'] : [];

// Filter cart to only selected items (from POST)
$selected_ids = isset($_POST['selected_items']) ? array_map('intval', (array)$_POST['selected_items']) : [];
$cart = [];
if (!empty($selected_ids)) {
    foreach ($all_cart as $item) {
        if (in_array((int)$item['item_id'], $selected_ids)) {
            $cart[] = $item;
        }
    }
}

if (count($cart) === 0) {
    $_SESSION['message'] = 'Please select items to checkout.';
    header('Location: /essence_db/cart/view_cart.php');
    exit();
}

// compute total
$total = 0.0;
foreach ($cart as $it) {
    $total += (float)$it['item_price'] * (int)$it['item_qty'];
}

// get customer info if logged in
$customer = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, 'SELECT c.* FROM customers c WHERE c.user_id = ? LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $customer = $res ? mysqli_fetch_assoc($res) : null;
    }
}
?>
<div class="container py-4">
  <h2>Checkout</h2>
  <div class="row">
    <div class="col-md-7">
      <h5>Order Summary</h5>
      <ul class="list-group mb-3">
        <?php foreach ($cart as $it): 
                $pid = (int)$it['item_id'];
                // fetch first image for product
                $imgPath = '';
                $qimg = mysqli_query($conn, "SELECT COALESCE((SELECT path FROM product_images WHERE product_id = p.product_id ORDER BY product_image_id ASC LIMIT 1), p.image) AS img_path FROM products p WHERE p.product_id = {$pid} LIMIT 1");
                if ($qimg && mysqli_num_rows($qimg) > 0) {
                    $rimg = mysqli_fetch_assoc($qimg);
                    $praw = $rimg['img_path'];
                    if (!empty($praw)) {
                        $p = str_replace('\\', '/', $praw);
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
                        $imgPath = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
                    }
                }
        ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <?php if (!empty($imgPath)): ?>
                <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:8px;margin-right:12px;">
              <?php endif; ?>
              <div>
                <div><?php echo htmlspecialchars($it['item_name']); ?></div>
                <small class="text-muted">Qty: <?php echo (int)$it['item_qty']; ?> x ₱<?php echo number_format((float)$it['item_price'],2); ?></small>
              </div>
            </div>
            <div>₱<?php echo number_format((float)$it['item_price'] * (int)$it['item_qty'],2); ?></div>
          </li>
        <?php endforeach; ?>
        <li class="list-group-item d-flex justify-content-between"><strong>Total</strong><strong>₱<?php echo number_format($total,2); ?></strong></li>
      </ul>

      <form method="POST" action="checkout.php">
        <!-- Pass selected items to checkout.php -->
        <?php foreach ($selected_ids as $id): ?>
          <input type="hidden" name="selected_items[]" value="<?php echo $id; ?>">
        <?php endforeach; ?>
        
        <h5>Payment Method</h5>
        <div class="mb-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_method" id="pm_gcash" value="GCash" checked>
            <label class="form-check-label" for="pm_gcash">GCash</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_method" id="pm_credit" value="Credit Card">
            <label class="form-check-label" for="pm_credit">Credit Card</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_method" id="pm_cod" value="Cash on Delivery">
            <label class="form-check-label" for="pm_cod">Cash on Delivery</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_method" id="pm_bank" value="Bank Transfer">
            <label class="form-check-label" for="pm_bank">Bank Transfer</label>
          </div>
        </div>

        <h5>Delivery Method</h5>
        <select name="delivery_method" class="form-select mb-3" style="max-width:300px">
          <option value="Standard">Standard</option>
          <option value="Express">Express</option>
        </select>

        <h5>Remarks</h5>
        <textarea name="remarks" class="form-control mb-3" placeholder="Optional notes for delivery or payment"></textarea>

        <button class="btn btn-primary" type="submit">Place Order</button>
        <a href="/essence_db/cart/view_cart.php" class="btn btn-secondary">Back to Cart</a>
      </form>
    </div>
    <div class="col-md-5">
      <h5>Billing Info</h5>
      <?php if ($customer): ?>
        <p><strong><?php echo htmlspecialchars($customer['fullname']); ?></strong><br><?php echo htmlspecialchars($customer['address']); ?><br><?php echo htmlspecialchars($customer['contact']); ?></p>
      <?php else: ?>
        <div class="alert alert-warning">Please complete your customer profile before checkout.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
