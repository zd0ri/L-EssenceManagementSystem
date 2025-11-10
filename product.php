<?php
session_start();
require_once __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/header.php';

// helper to mask bad words
function mask_bad_words($text) {
    // list of words to mask (lowercase)
    $bad = [
        'shit','fuck','bitch','asshole','damn','crap'
    ];
    // build regex word boundaries, case-insensitive
    foreach ($bad as $w) {
        $pattern = '/\b' . preg_quote($w, '/') . '\b/iu';
        $text = preg_replace_callback($pattern, function($m){
            $len = mb_strlen($m[0]);
            return str_repeat('*', $len);
        }, $text);
    }
    return $text;
}

// ensure reviews table exists
$createReviews = "CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    customer_id INT,
    rating TINYINT NOT NULL DEFAULT 5,
    review_text TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY product_user_unique (product_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $createReviews);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo '<div class="container"><div class="alert alert-danger">Product not specified.</div></div>';
    include __DIR__ . '/includes/footer.php';
    exit();
}

// handle review POST (add or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    // find customer_id
    $cust_q = mysqli_prepare($conn, "SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1");
    if ($cust_q) {
        mysqli_stmt_bind_param($cust_q, 'i', $user_id);
        mysqli_stmt_execute($cust_q);
        $res = mysqli_stmt_get_result($cust_q);
        $cust = $res ? mysqli_fetch_assoc($res) : null;
        $customer_id = $cust ? (int)$cust['customer_id'] : null;
    } else {
        $customer_id = null;
    }

    // verify the user has purchased this product (exists in order_items for their customer_id)
    $hasBought = false;
    if ($customer_id) {
        $p = mysqli_prepare($conn, "SELECT oi.order_item_id FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE oi.product_id = ? AND o.customer_id = ? LIMIT 1");
        if ($p) {
            mysqli_stmt_bind_param($p, 'ii', $id, $customer_id);
            mysqli_stmt_execute($p);
            $r = mysqli_stmt_get_result($p);
            if ($r && mysqli_num_rows($r) > 0) $hasBought = true;
        }
    }

    if (!$hasBought) {
        $_SESSION['message'] = 'You can only review products you have purchased.';
        header('Location: product.php?id=' . $id);
        exit();
    }

    // collect rating and review
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
    if ($rating < 1) $rating = 1; if ($rating > 5) $rating = 5;
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
    // mask bad words before storing
    $masked = mask_bad_words($review_text);

    // insert or update (unique key product_id,user_id)
    $stmt = mysqli_prepare($conn, "INSERT INTO reviews (product_id, user_id, customer_id, rating, review_text) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = VALUES(review_text), updated_at = CURRENT_TIMESTAMP");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'iiiis', $id, $user_id, $customer_id, $rating, $masked);
    mysqli_stmt_execute($stmt);
    $_SESSION['success'] = 'Review saved.';
  } else {
    $_SESSION['message'] = 'Failed to save review.';
  }
  // redirect back to the product page and jump to the review form
  header('Location: product.php?id=' . $id . '#review-form');
  exit();
}

// fetch product
$product_q = mysqli_prepare($conn, "SELECT p.*, i.quantity FROM products p LEFT JOIN inventory i ON p.product_id = i.product_id WHERE p.product_id = ? LIMIT 1");
if ($product_q) {
    mysqli_stmt_bind_param($product_q, 'i', $id);
    mysqli_stmt_execute($product_q);
    $prod_res = mysqli_stmt_get_result($product_q);
    $product = $prod_res ? mysqli_fetch_assoc($prod_res) : null;
} else {
    $product = null;
}

if (!$product) {
    echo '<div class="container"><div class="alert alert-danger">Product not found.</div></div>';
    include __DIR__ . '/includes/footer.php';
    exit();
}

// images
$imgs = [];
$qpi = mysqli_query($conn, "SELECT path FROM product_images WHERE product_id = {$id} ORDER BY product_image_id ASC");
if ($qpi && mysqli_num_rows($qpi) > 0) {
    while ($rpi = mysqli_fetch_assoc($qpi)) $imgs[] = $rpi['path'];
}
if (empty($imgs) && !empty($product['image'])) $imgs[] = $product['image'];

// fetch reviews
$reviews = [];
$qr = mysqli_prepare($conn, "SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
if ($qr) {
    mysqli_stmt_bind_param($qr, 'i', $id);
    mysqli_stmt_execute($qr);
    $resr = mysqli_stmt_get_result($qr);
    if ($resr) {
        while ($row = mysqli_fetch_assoc($resr)) {
            // mask display of foul words as well just in case
            $row['display_text'] = mask_bad_words($row['review_text']);
            $reviews[] = $row;
        }
    }
}

?>
<div class="container py-4">
  <div class="row">
    <div class="col-md-6">
      <?php if (count($imgs) > 0): ?>
        <img src="/<?php echo ltrim($imgs[0], '/'); ?>" class="img-fluid" style="max-height:400px; object-fit:cover;" />
      <?php else: ?>
        <div style="height:400px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#888">No image</div>
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <h2><?php echo htmlspecialchars($product['brand_name']); ?></h2>
      <p class="text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
      <p class="fw-bold">₱<?php echo number_format($product['price'],2); ?></p>
      <p>Available: <?php echo (int)$product['quantity']; ?></p>

      <form method="POST" action="/essence_db/cart/cart_update.php" class="mb-3">
        <input type="hidden" name="item_id" value="<?php echo $product['product_id']; ?>">
        <input type="hidden" name="type" value="add">
        <div class="input-group mb-2" style="max-width:140px">
          <input type="number" name="item_qty" value="1" min="1" max="<?php echo (int)$product['quantity']; ?>" class="form-control" />
          <button class="btn btn-dark" type="submit">Add to Cart</button>
        </div>
      </form>

      <?php if (isset($_SESSION['user_id'])): ?>
        <?php
        // check if user can review (bought product)
        $canReview = false;
        $user_id = (int)$_SESSION['user_id'];
        $cust_q = mysqli_prepare($conn, "SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1");
        if ($cust_q) {
            mysqli_stmt_bind_param($cust_q, 'i', $user_id);
            mysqli_stmt_execute($cust_q);
            $cres = mysqli_stmt_get_result($cust_q);
            $crow = $cres ? mysqli_fetch_assoc($cres) : null;
            $customer_id = $crow ? (int)$crow['customer_id'] : null;
            if ($customer_id) {
                $p = mysqli_prepare($conn, "SELECT oi.order_item_id FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE oi.product_id = ? AND o.customer_id = ? LIMIT 1");
                if ($p) {
                    mysqli_stmt_bind_param($p, 'ii', $id, $customer_id);
                    mysqli_stmt_execute($p);
                    $r = mysqli_stmt_get_result($p);
                    if ($r && mysqli_num_rows($r) > 0) $canReview = true;
                }
            }
        }
        ?>

        <?php if ($canReview): ?>
          <h5>Add / Update your review</h5>
          <?php
          // load existing review if any
          $myReview = null;
          $s = mysqli_prepare($conn, "SELECT * FROM reviews WHERE product_id = ? AND user_id = ? LIMIT 1");
          if ($s) {
            mysqli_stmt_bind_param($s, 'ii', $id, $user_id);
            mysqli_stmt_execute($s);
            $rr = mysqli_stmt_get_result($s);
            $myReview = $rr ? mysqli_fetch_assoc($rr) : null;
          }
          ?>
          <form id="review-form" method="POST" action="product.php?id=<?php echo $id; ?>">
            <div class="mb-2">
              <label for="rating">Rating</label>
              <select name="rating" id="rating" class="form-select" style="max-width:120px">
                <?php for ($i=5;$i>=1;$i--): ?>
                  <option value="<?php echo $i; ?>" <?php echo ($myReview && (int)$myReview['rating'] === $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="mb-2">
              <label for="review_text">Your review</label>
              <textarea name="review_text" id="review_text" class="form-control"><?php echo $myReview ? htmlspecialchars($myReview['review_text']) : ''; ?></textarea>
            </div>
            <button class="btn btn-primary" type="submit">Save Review</button>
          </form>
        <?php else: ?>
          <div class="alert alert-info">You can only review this product after purchasing it.</div>
        <?php endif; ?>
      <?php else: ?>
        <div class="alert alert-secondary">Please <a href="/essence_db/users/login.php">login</a> to leave a review.</div>
      <?php endif; ?>

    </div>
  </div>

  <hr />
  <h4>Reviews</h4>
  <?php if (count($reviews) === 0): ?>
    <div class="text-muted">No reviews yet.</div>
  <?php else: ?>
    <?php foreach ($reviews as $r): ?>
      <div class="border rounded p-3 mb-2">
        <div class="d-flex justify-content-between">
          <strong><?php echo htmlspecialchars($r['username']); ?></strong>
          <small><?php echo number_format((float)$r['rating'],1); ?> / 5</small>
        </div>
        <div><?php echo nl2br(htmlspecialchars($r['display_text'])); ?></div>
        <div class="text-muted small">Posted: <?php echo $r['created_at']; ?></div>
        <?php if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$r['user_id']): ?>
          <div class="mt-2">
            <a href="#review-form" class="btn btn-sm btn-outline-secondary">Edit your review</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// if page loaded with #review-form, focus the textarea for convenience
document.addEventListener('DOMContentLoaded', function(){
  if (window.location.hash === '#review-form') {
    var el = document.getElementById('review_text');
    if (el) {
      el.focus();
      el.scrollIntoView({behavior:'smooth', block:'center'});
    }
  }
});
</script>


</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
