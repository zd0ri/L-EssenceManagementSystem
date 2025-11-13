<?php 
session_start();
require_once __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/header.php';
?>


<!-- page uses global theme from includes/style/style.css -->

<?php include_once __DIR__ . '/includes/alert.php'; ?>

<section id="popular-products" class="py-5">
  <div class="container text-center">
    <h2 class="mb-5">Popular Products</h2>

    <?php
    $search = '';
    $whereExtra = '';
    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
      $search = trim($_GET['search']);
      $searchEsc = mysqli_real_escape_string($conn, strtolower($search));
      $whereExtra = " AND (LOWER(p.brand_name) LIKE '%{$searchEsc}%' OR LOWER(p.scent_type) LIKE '%{$searchEsc}%' OR LOWER(p.description) LIKE '%{$searchEsc}%')";
      echo '<div class="search-result">Showing results for <strong>' . htmlspecialchars($search) . '</strong></div>';
    }

    $sql = "SELECT p.product_id AS productId, p.brand_name, p.description, p.price, p.image, i.quantity
            FROM products p
            INNER JOIN inventory i ON p.product_id = i.product_id
            WHERE i.quantity > 0 AND p.status = 'available' " . $whereExtra . " ORDER BY p.product_id ASC";

    $results = mysqli_query($conn, $sql);
    ?>

    <div class="row g-4 justify-content-center">
    <?php
    if ($results && mysqli_num_rows($results) > 0) {
      while ($row = mysqli_fetch_assoc($results)) {
        $desc = htmlspecialchars($row['description']);
        $brand = htmlspecialchars($row['brand_name']);
        $price = number_format($row['price'], 2);
        $rawImage = $row['image'];
        $maxQty = (int)$row['quantity'];

        $imgs = [];
        $qpi = mysqli_query($conn, "SELECT path FROM product_images WHERE product_id = {$row['productId']} ORDER BY product_image_id ASC");
        if ($qpi && mysqli_num_rows($qpi) > 0) {
          while ($rpi = mysqli_fetch_assoc($qpi)) {
            $imgs[] = $rpi['path'];
          }
        }
        if (empty($imgs) && !empty($rawImage)) {
          $imgs[] = $rawImage;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
    ?>

      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="card shadow-sm text-center p-2 product-card">
          <?php
          if (count($imgs) === 0) {
            echo '<div class="card-img-top missing">No image</div>';
          } elseif (count($imgs) === 1) {
            $p = str_replace('\\', '/', $imgs[0]);
            $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
            echo '<img src="' . htmlspecialchars($imgUrl) . '" class="card-img-top img-fluid" alt="' . htmlspecialchars($brand) . '">';
          } else {
            $carouselId = 'homeCarousel_' . $row['productId'];
            echo '<div id="' . $carouselId . '" class="carousel slide" data-bs-ride="carousel">';
            echo '<div class="carousel-inner">';
            foreach ($imgs as $i => $pRaw) {
              $p = str_replace('\\', '/', $pRaw);
              $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
              $active = $i === 0 ? ' active' : '';
              echo '<div class="carousel-item' . $active . '">';
              echo '<img src="' . htmlspecialchars($imgUrl) . '" class="d-block w-100" alt="' . htmlspecialchars($brand) . '">';
              echo '</div>';
            }
            echo '</div>';
            echo '</div>';
          }
          ?>

          <div class="card-body">
            <h5 class="card-title product-name"><?php echo $brand; ?></h5>
            <p class="text-muted small"><?php echo $desc; ?></p>
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

    <?php
      }
    } else {
      echo '<div class="col-12 text-center">No products found.</div>';
    }
    ?>
    </div>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
