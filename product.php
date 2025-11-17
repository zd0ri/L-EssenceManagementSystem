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

// helper: get customer_id for a user (or null)
function get_customer_id_for_user($conn, $user_id) {
  $customer_id = null;
  $cust_q = mysqli_prepare($conn, "SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1");
  if ($cust_q) {
    mysqli_stmt_bind_param($cust_q, 'i', $user_id);
    mysqli_stmt_execute($cust_q);
    $res = mysqli_stmt_get_result($cust_q);
    $crow = $res ? mysqli_fetch_assoc($res) : null;
    if ($crow) $customer_id = (int)$crow['customer_id'];
  }
  return $customer_id;
}

// helper: return true if the given customer_id has purchased the given product_id
function customer_bought_product($conn, $customer_id, $product_id) {
  if (!$customer_id) return false;
  $p = mysqli_prepare($conn, "SELECT oi.order_item_id FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE oi.product_id = ? AND o.customer_id = ? LIMIT 1");
  if ($p) {
    mysqli_stmt_bind_param($p, 'ii', $product_id, $customer_id);
    mysqli_stmt_execute($p);
    $r = mysqli_stmt_get_result($p);
    if ($r && mysqli_num_rows($r) > 0) return true;
  }
  return false;
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
  -- allow multiple reviews per user/product
  KEY idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  // If the table already existed with a unique constraint, remove it so multiple reviews are allowed
  $checkIdx = mysqli_query($conn, "SHOW INDEX FROM reviews WHERE Key_name = 'product_user_unique'");
  if ($checkIdx && mysqli_num_rows($checkIdx) > 0) {
    mysqli_query($conn, "ALTER TABLE reviews DROP INDEX product_user_unique");
  }
  // ensure review_image column exists
  $checkCol = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'review_image'");
  if (!($checkCol && mysqli_num_rows($checkCol) > 0)) {
    mysqli_query($conn, "ALTER TABLE reviews ADD COLUMN review_image VARCHAR(255) NULL AFTER review_text");
  }
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
    // handle delete request
    if (isset($_POST['delete_review']) && isset($_POST['review_id'])) {
      $rid = (int)$_POST['review_id'];
      // verify owner
      $qv = mysqli_prepare($conn, "SELECT review_image FROM reviews WHERE review_id = ? AND user_id = ? LIMIT 1");
      if ($qv) {
        mysqli_stmt_bind_param($qv, 'ii', $rid, $user_id);
        mysqli_stmt_execute($qv);
        $rv = mysqli_stmt_get_result($qv);
        $rowv = $rv ? mysqli_fetch_assoc($rv) : null;
        if ($rowv) {
          // delete file if exists
          if (!empty($rowv['review_image'])) {
            $filePath = __DIR__ . '/' . ltrim($rowv['review_image'], '/');
            if (file_exists($filePath)) @unlink($filePath);
          }
          $d = mysqli_prepare($conn, "DELETE FROM reviews WHERE review_id = ? AND user_id = ?");
          if ($d) {
            mysqli_stmt_bind_param($d, 'ii', $rid, $user_id);
            mysqli_stmt_execute($d);
            $_SESSION['success'] = 'Review deleted.';
          }
        }
      }
      header('Location: product.php?id=' . $id . '#reviews');
      exit();
    }
    // collect rating and review
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
    if ($rating < 1) $rating = 1; if ($rating > 5) $rating = 5;
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
    $masked = mask_bad_words($review_text);
    // handle file upload (optional)
    $uploadedPath = null;
    if (!empty($_FILES['review_image']) && $_FILES['review_image']['error'] !== UPLOAD_ERR_NO_FILE) {
      $up = $_FILES['review_image'];
      if ($up['error'] === UPLOAD_ERR_OK) {
        // validate size (<=5MB) and mime
        if ($up['size'] <= 5 * 1024 * 1024) {
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $mime = finfo_file($finfo, $up['tmp_name']);
          finfo_close($finfo);
          $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
          if (isset($allowed[$mime])) {
            $ext = $allowed[$mime];
            $targetDir = __DIR__ . '/uploads/reviews';
            if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);
            $baseName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $targetPath = $targetDir . '/' . $baseName;
            if (move_uploaded_file($up['tmp_name'], $targetPath)) {
              $uploadedPath = 'uploads/reviews/' . $baseName;
            }
          }
        }
      }
    }
    // if review_id provided -> update specific review
    if (isset($_POST['review_id']) && (int)$_POST['review_id'] > 0) {
      $rid = (int)$_POST['review_id'];
      // verify ownership
      $sv = mysqli_prepare($conn, "SELECT review_image FROM reviews WHERE review_id = ? AND user_id = ? LIMIT 1");
      $existingImage = null;
      $canEdit = false;
      if ($sv) {
        mysqli_stmt_bind_param($sv, 'ii', $rid, $user_id);
        mysqli_stmt_execute($sv);
        $resv = mysqli_stmt_get_result($sv);
        $rowv = $resv ? mysqli_fetch_assoc($resv) : null;
        if ($rowv) { $canEdit = true; $existingImage = $rowv['review_image']; }
      }
      if ($canEdit) {
        // handle remove_image flag
        if (isset($_POST['remove_image']) && $existingImage) {
          $fp = __DIR__ . '/' . ltrim($existingImage, '/'); if (file_exists($fp)) @unlink($fp); $existingImage = null;
        }
        // if new upload provided, delete old
        if ($uploadedPath && $existingImage) { $fp = __DIR__ . '/' . ltrim($existingImage, '/'); if (file_exists($fp)) @unlink($fp); }
        $newImageToStore = $uploadedPath ? $uploadedPath : $existingImage;
        $u = mysqli_prepare($conn, "UPDATE reviews SET rating = ?, review_text = ?, review_image = ?, updated_at = CURRENT_TIMESTAMP WHERE review_id = ? AND user_id = ?");
        if ($u) {
          mysqli_stmt_bind_param($u, 'issii', $rating, $masked, $newImageToStore, $rid, $user_id);
          mysqli_stmt_execute($u);
          $_SESSION['success'] = 'Review updated.';
        }
      }
    } else {
      // insert new review
      $stmt = mysqli_prepare($conn, "INSERT INTO reviews (product_id, user_id, customer_id, rating, review_text, review_image) VALUES (?, ?, ?, ?, ?, ?)");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iiiiss', $id, $user_id, $customer_id, $rating, $masked, $uploadedPath);
        mysqli_stmt_execute($stmt);
        $_SESSION['success'] = 'Review saved.';
      } else {
        $_SESSION['message'] = 'Failed to save review.';
      }
    }
  header('Location: product.php?id=' . $id . '#reviews');
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

// build base URL like index.php to resolve uploads and relative paths correctly
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';

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
      <?php if (count($imgs) === 0): ?>
        <div style="height:400px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#888">No image</div>
      <?php elseif (count($imgs) === 1): ?>
        <?php $p = str_replace('\\', '/', $imgs[0]); $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/'); ?>
        <img src="<?php echo htmlspecialchars($imgUrl); ?>" class="img-fluid" style="max-height:400px; object-fit:cover;" alt="<?php echo htmlspecialchars($product['brand_name']); ?>" />
      <?php else: ?>
        <?php $carouselId = 'productCarousel_' . $product['product_id']; ?>
        <div id="<?php echo $carouselId; ?>" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-inner">
            <?php foreach ($imgs as $i => $pRaw):
              $p = str_replace('\\', '/', $pRaw);
              $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
              $active = ($i === 0) ? ' active' : '';
            ?>
              <div class="carousel-item<?php echo $active; ?>">
                <img src="<?php echo htmlspecialchars($imgUrl); ?>" class="d-block w-100" style="max-width:100; max-height:400px; object-fit:cover;" alt="<?php echo htmlspecialchars($product['brand_name']); ?>">
              </div>
            <?php endforeach; ?>
          </div>
        </div>
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
          <form id="review-form" method="POST" action="product.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="review_id" id="review_id" value="0">
            <div class="mb-2">
              <label for="rating">Rating</label>
              <select name="rating" id="rating" class="form-select" style="max-width:120px">
                <?php for ($i=5;$i>=1;$i--): ?>
                  <option value="<?php echo $i; ?>" <?php echo ($i === 5) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="mb-2">
              <label for="review_text">Your review</label>
              <textarea name="review_text" id="review_text" class="form-control"></textarea>
            </div>
            <div class="mb-2">
              <label for="review_image">Photo (optional)</label>
              <input type="file" name="review_image" id="review_image" class="form-control">
              <div id="current-image-preview" style="margin-top:.5rem;display:none;">
                <img src="" id="current-image-src" style="max-width:150px;max-height:150px;object-fit:cover;border-radius:.25rem;border:1px solid #ddd;" />
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1">
                  <label class="form-check-label" for="remove_image">Remove current image</label>
                </div>
              </div>
            </div>
            <button class="btn btn-primary" type="submit">Save Review</button>
            <button type="button" id="cancel-edit" class="btn btn-secondary" style="display:none;margin-left:.5rem;">Cancel</button>
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
  <h4 id="reviews">Reviews</h4>
  <?php if (count($reviews) === 0): ?>
    <div class="text-muted">No reviews yet.</div>
  <?php else: ?>
    <?php foreach ($reviews as $r): ?>
      <div class="border rounded p-3 mb-2" id="review-block-<?php echo $r['review_id']; ?>">
        <div class="d-flex justify-content-between">
          <strong><?php echo htmlspecialchars($r['username']); ?></strong>
          <small><?php echo number_format((float)$r['rating'],1); ?> / 5</small>
        </div>
        <div><?php echo nl2br(htmlspecialchars($r['display_text'])); ?></div>
        <?php if (!empty($r['review_image'])): ?>
          <div style="margin-top:.75rem;"><img src="<?php echo htmlspecialchars($baseUrl . ltrim($r['review_image'], '/')); ?>" style="max-width:200px;max-height:200px;object-fit:cover;border-radius:.25rem;border:1px solid #ddd;" alt="review image"></div>
        <?php endif; ?>
        <div class="text-muted small">Posted: <?php echo $r['created_at']; ?></div>
        <?php if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$r['user_id']): ?>
          <div class="mt-2">
            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="startEdit(<?php echo (int)$r['review_id']; ?>)">Edit</button>
            <form method="POST" action="product.php?id=<?php echo $id; ?>" style="display:inline-block;margin-left:.5rem;" onsubmit="return confirm('Delete this review?');">
              <input type="hidden" name="review_id" value="<?php echo (int)$r['review_id']; ?>">
              <input type="hidden" name="delete_review" value="1">
              <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
            </form>
          </div>
          <script>
            // attach review data for editing
            window['reviewData_' + <?php echo (int)$r['review_id']; ?>] = <?php echo json_encode(['id'=>(int)$r['review_id'],'rating'=>(int)$r['rating'],'text'=>$r['review_text'],'image'=>($r['review_image'] ?: null)]); ?>;
          </script>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

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
  // cancel edit button
  var cancelBtn = document.getElementById('cancel-edit');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function(){ resetReviewForm(); });
  }
});

function startEdit(id) {
  var data = window['reviewData_' + id];
  if (!data) return;
  document.getElementById('review_id').value = data.id;
  document.getElementById('rating').value = data.rating;
  document.getElementById('review_text').value = data.text || '';
  var preview = document.getElementById('current-image-preview');
  var previewImg = document.getElementById('current-image-src');
  var removeCheckbox = document.getElementById('remove_image');
  if (data.image) {
    previewImg.src = '<?php echo $baseUrl; ?>' + data.image.replace(/^\/+/, '');
    preview.style.display = 'block';
    if (removeCheckbox) removeCheckbox.checked = false;
  } else {
    previewImg.src = '';
    preview.style.display = 'none';
    if (removeCheckbox) removeCheckbox.checked = false;
  }
  // change button text and show cancel
  var submitBtn = document.querySelector('#review-form button[type=submit]');
  if (submitBtn) submitBtn.textContent = 'Update Review';
  var cancelBtn = document.getElementById('cancel-edit'); if (cancelBtn) cancelBtn.style.display = 'inline-block';
  // scroll to form
  var form = document.getElementById('review-form'); if (form) { form.scrollIntoView({behavior:'smooth', block:'center'}); }
}

function resetReviewForm(){
  document.getElementById('review_id').value = 0;
  document.getElementById('rating').value = '5';
  document.getElementById('review_text').value = '';
  var preview = document.getElementById('current-image-preview');
  var previewImg = document.getElementById('current-image-src');
  var removeCheckbox = document.getElementById('remove_image');
  if (preview) { preview.style.display = 'none'; }
  if (previewImg) previewImg.src = '';
  if (removeCheckbox) removeCheckbox.checked = false;
  var submitBtn = document.querySelector('#review-form button[type=submit]');
  if (submitBtn) submitBtn.textContent = 'Save Review';
  var cancelBtn = document.getElementById('cancel-edit'); if (cancelBtn) cancelBtn.style.display = 'none';
}
</script>


</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
