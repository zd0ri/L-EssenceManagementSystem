<?php
session_start();
require_once __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/header.php';
?>

<?php include_once __DIR__ . '/includes/alert.php'; ?>

<!-- Product Display -->
<section id="popular-products" class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-4">Popular Products</h2>

    <!-- Search is provided in the header (single global search form) -->

    <?php
    // prepare search filter
    $search = '';
    $whereExtra = '';
    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
      $search = trim($_GET['search']);
      $searchEsc = mysqli_real_escape_string($conn, strtolower($search));
      $whereExtra = " AND (LOWER(p.brand_name) LIKE '%{$searchEsc}%' OR LOWER(p.scent_type) LIKE '%{$searchEsc}%' OR LOWER(p.description) LIKE '%{$searchEsc}%')";
      echo '<div class="text-center mb-3">Showing results for <strong>' . htmlspecialchars($search) . '</strong></div>';
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

        // collect images for this product
        $imgs = [];
        $qpi = mysqli_query($conn, "SELECT path FROM product_images WHERE product_id = {$row['productId']} ORDER BY product_image_id ASC");
        if ($qpi && mysqli_num_rows($qpi) > 0) {
          while ($rpi = mysqli_fetch_assoc($qpi)) {
            $imgs[] = $rpi['path'];
          }
        }
        // fallback to legacy single image
        if (empty($imgs) && !empty($rawImage)) {
          $imgs[] = $rawImage;
        }

        // build base url
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
    ?>

      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm text-center p-3">
          <?php
          if (count($imgs) === 0) {
            echo '<div style="height:250px; background:#f0f0f0; display:flex;align-items:center;justify-content:center;color:#888">No image</div>';
          } elseif (count($imgs) === 1) {
            $p = str_replace('\\', '/', $imgs[0]);
            if (preg_match('#^https?://#i', $p)) {
              $imgUrl = $p;
              $fileExists = true;
            } else {
              $imgUrl = $baseUrl . ltrim($p, '/');
              $fileExists = file_exists(__DIR__ . '/' . ltrim($p, '/'));
            }
            if (!empty($fileExists)) {
              echo '<img src="' . htmlspecialchars($imgUrl) . '" class="card-img-top img-fluid" style="height:250px; object-fit:cover;" alt="' . htmlspecialchars($brand) . '">';
            } else {
              echo '<div style="height:250px; background:#fff3cd; display:flex;align-items:center;justify-content:center;color:#856404">Missing image</div>';
            }
          } else {
            // multiple images: render a bootstrap carousel
            $carouselId = 'homeCarousel_' . $row['productId'];
            echo '<div id="' . $carouselId . '" class="carousel slide" data-bs-ride="carousel">';
            echo '<div class="carousel-inner" style="height:250px;">';
            foreach ($imgs as $i => $pRaw) {
              $p = str_replace('\\', '/', $pRaw);
              if (preg_match('#^https?://#i', $p)) {
                $imgUrl = $p;
                $fileExists = true;
              } else {
                $imgUrl = $baseUrl . ltrim($p, '/');
                $fileExists = file_exists(__DIR__ . '/' . ltrim($p, '/'));
              }
              $active = $i === 0 ? ' active' : '';
              echo '<div class="carousel-item' . $active . '">';
              if ($fileExists) {
                echo '<img src="' . htmlspecialchars($imgUrl) . '" class="d-block w-100" style="height:250px; object-fit:cover;" alt="' . htmlspecialchars($brand) . '">';
              } else {
                echo '<div style="height:250px; background:#f8d7da; display:flex;align-items:center;justify-content:center;color:#842029">Missing image</div>';
              }
              echo '</div>';
            }
            echo '</div>';
            echo '<button class="carousel-control-prev" type="button" data-bs-target="#' . $carouselId . '" data-bs-slide="prev">';
            echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span>';
            echo '</button>';
            echo '<button class="carousel-control-next" type="button" data-bs-target="#' . $carouselId . '" data-bs-slide="next">';
            echo '<span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span>';
            echo '</button>';
            echo '</div>';
          }
          ?>
          <div class="card-body">
            <h5 class="card-title"><?php echo $brand; ?></h5>
            <p class="text-muted small"><?php echo $desc; ?></p>
            <p class="fw-bold">₱<?php echo $price; ?></p>

            <form method="POST" action="./cart/cart_update.php" class="mt-2">
              <input type="hidden" name="item_id" value="<?php echo $row['productId']; ?>">
              <input type="hidden" name="type" value="add">
              <input type="number" name="item_qty" value="1" min="1" max="<?php echo $maxQty; ?>" class="form-control mb-2">
              <button type="submit" class="btn btn-dark w-100">Add to Cart</button>
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
