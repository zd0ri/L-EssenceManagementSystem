<?php
session_start();
require_once __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/header.php';

$brand_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$brand_name = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$brand_display = '';

$products = [];

if ($brand_id > 0) {
    $sql = "SELECT p.product_id AS productId, p.product_name, b.name as brand_name, p.scent_type AS scent_type, p.price, p.image, i.quantity,
      COALESCE(SUM(oi.quantity),0) AS sales_count
      FROM products p
      LEFT JOIN brands b ON p.brand_id = b.brand_id
      LEFT JOIN order_items oi ON p.product_id = oi.product_id
      INNER JOIN inventory i ON p.product_id = i.product_id
      WHERE i.quantity > 0 AND p.status = 'available' AND p.brand_id = ?
      GROUP BY p.product_id
      ORDER BY sales_count DESC, p.product_id ASC";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'i', $brand_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $products[] = $row;
                if ($brand_display === '') $brand_display = $row['brand_name'];
            }
        }
    }
    // If no products found
    if ($brand_display === '') {
        $bq = mysqli_prepare($conn, "SELECT name FROM brands WHERE brand_id = ? LIMIT 1");
        if ($bq) {
            mysqli_stmt_bind_param($bq, 'i', $brand_id);
            mysqli_stmt_execute($bq);
            $br = mysqli_stmt_get_result($bq);
            $bf = $br ? mysqli_fetch_assoc($br) : null;
            if ($bf) $brand_display = $bf['name'];
        }
    }
} elseif ($brand_name !== '') {
    $sql = "SELECT p.product_id AS productId, p.product_name, b.name as brand_name, p.scent_type AS scent_type, p.price, p.image, i.quantity,
      COALESCE(SUM(oi.quantity),0) AS sales_count
      FROM products p
      LEFT JOIN brands b ON p.brand_id = b.brand_id
      LEFT JOIN order_items oi ON p.product_id = oi.product_id
      INNER JOIN inventory i ON p.product_id = i.product_id
      WHERE i.quantity > 0 AND p.status = 'available' AND LOWER(b.name) = LOWER(?)
      GROUP BY p.product_id
      ORDER BY sales_count DESC, p.product_id ASC";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 's', $brand_name);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $products[] = $row;
                if ($brand_display === '') $brand_display = $row['brand_name'];
            }
        }
    }
    if ($brand_display === '') $brand_display = $brand_name;
} else {
    echo '<div class="container py-4"><div class="alert alert-danger">Brand not specified.</div></div>';
    include __DIR__ . '/includes/footer.php';
    exit();
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
?>

<div class="container py-4">
  <h2>Brand: <?php echo htmlspecialchars($brand_display); ?></h2>
  <?php if (count($products) === 0): ?>
    <div class="alert alert-info">No available products found for this brand.</div>
  <?php else: ?>
    <div id="popular-products">
      <div class="container">
        <div class="products-grid">
      <?php foreach ($products as $row):
        $brandName = htmlspecialchars($row['brand_name']);
        $scent = htmlspecialchars($row['scent_type']);
        $price = number_format($row['price'],2);
        $rawImage = $row['image'];
        $maxQty = (int)$row['quantity'];
        $imgs = [];
        $qpi = mysqli_query($conn, "SELECT path FROM product_images WHERE product_id = {$row['productId']} ORDER BY product_image_id ASC");
        if ($qpi && mysqli_num_rows($qpi) > 0) {
          while ($rpi = mysqli_fetch_assoc($qpi)) $imgs[] = $rpi['path'];
        }
        if (empty($imgs) && !empty($rawImage)) $imgs[] = $rawImage;
      ?>
      <div class="product-tile">
        <div class="card shadow-sm text-center p-2 product-card">
          <?php
          if (count($imgs) === 0) {
            echo '<div class="card-img-top missing">No image</div>';
          } elseif (count($imgs) === 1) {
            $p = str_replace('\\', '/', $imgs[0]);
            $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
            echo '<img src="' . htmlspecialchars($imgUrl) . '" class="card-img-top img-fluid" alt="' . $brandName . '">';
          } else {
            $carouselId = 'brandCarousel_' . $row['productId'];
            echo '<div id="' . $carouselId . '" class="carousel slide" data-bs-ride="carousel">';
            echo '<div class="carousel-inner">';
            foreach ($imgs as $i => $pRaw) {
              $p = str_replace('\\', '/', $pRaw);
              $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
              $active = $i === 0 ? ' active' : '';
              echo '<div class="carousel-item' . $active . '">';
              echo '<img src="' . htmlspecialchars($imgUrl) . '" class="d-block w-100" alt="' . $brandName . '">';
              echo '</div>';
            }
            echo '</div>';
            echo '</div>';
          }
          ?>
          <div style="position:relative;">
            <?php if (!empty($row['sales_count']) && (int)$row['sales_count'] >= 3): ?>
              <div class="badge-bestseller">Best seller</div>
            <?php endif; ?>
            <div class="card-body">
              <h5 class="card-title product-name"><?php echo htmlspecialchars($row['product_name'] ?? $brandName); ?></h5>
              <div class="text-muted small"><?php echo htmlspecialchars($brandName); ?></div>
              <p class="text-muted small"><?php echo $scent; ?></p>
              <p class="fw-bold">₱<?php echo $price; ?></p>
            <form method="POST" action="./cart/cart_update.php" class="mt-2">
              <input type="hidden" name="item_id" value="<?php echo $row['productId']; ?>">
              <input type="hidden" name="type" value="add">
              <input type="number" name="item_qty" value="1" min="1" max="<?php echo $maxQty; ?>" class="form-control mb-2">
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-dark">Add to Cart</button>
                <a href="/essence_db/product.php?id=<?php echo $row['productId']; ?>" class="btn btn-outline-secondary">View</a>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>