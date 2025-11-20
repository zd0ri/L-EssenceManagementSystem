<?php 
session_start();
require_once __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/header.php';
?>


<!-- page uses global theme from includes/style/style.css -->

<?php include_once __DIR__ . '/includes/alert.php'; ?>

<section class="hero">
  <div class="hero-inner">
    <div>
      <h1>The smell is a word. </h1>
        <h1><i>Perfume is literature.</i></h1>
      <p>Discover the beauty of fragrance with our collection of premium perfumes to enrich your everyday scent.</p>
      <div class="hero-cta">
        <a href="#popular-products" class="btn btn-dark btn-lg">Shop Now</a>
        <a href="/essence_db/brands.php" class="btn btn-outline-primary btn-lg">Browse Brands</a>
      </div>
    </div>
    <?php
    $heroImg = '/essence_db/uploads/hero.jpg';
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS site_settings (
      setting_id INT AUTO_INCREMENT PRIMARY KEY,
      setting_key VARCHAR(191) NOT NULL UNIQUE,
      setting_value LONGTEXT NULL,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $heroQuery = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key = 'hero_image' LIMIT 1");
    if ($heroQuery && mysqli_num_rows($heroQuery) > 0) {
      $heroRes = mysqli_fetch_assoc($heroQuery);
      if ($heroRes && !empty($heroRes['setting_value'])) {
        $heroImg = '/essence_db/' . ltrim($heroRes['setting_value'], '/');
      }
    }
    ?>
    <div class="hero-media">
      <img src="<?php echo htmlspecialchars($heroImg); ?>" alt="Hero image" onerror="this.src='/essence_db/images/hero.jpg'" />
    </div>
  </div>
</section>


<section class="py-5 featured-brands">
  <div class="container">
    <h3 class="mb-3 text-center">Featured Brands</h3>
    <div class="brands-card">
      <div class="brand-logos">
      <?php
      $brandRows = [];
      mysqli_query($conn, "CREATE TABLE IF NOT EXISTS brands (
        brand_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(191) NOT NULL,
        description TEXT NULL,
        image VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      $rb = mysqli_query($conn, "SELECT brand_id, name, image FROM brands ORDER BY name ASC LIMIT 10");
      if ($rb && mysqli_num_rows($rb) > 0) {
        while ($br = mysqli_fetch_assoc($rb)) $brandRows[] = $br;
      }
      if (count($brandRows) === 0) {
        
        $featuredBrands = ['Valentino','Creed','Perfume Dessert','Ian Darcy','Jo Malone', 'Dior', 'Tom Ford', 'Calvin Klein', 'Clinique', 'D&G'];
        foreach ($featuredBrands as $b) {
          echo '<a href="/essence_db/brand.php?brand=' . urlencode($b) . '"><img src="" alt="' . htmlspecialchars($b) . '" title="' . htmlspecialchars($b) . '" style="height:48px;object-fit:contain;" onerror="this.style.display=\'none\'" /></a>';
        }
      } else {
        foreach ($brandRows as $br) {
          if (!empty($br['image'])) {
            $img = htmlspecialchars('/essence_db/' . ltrim($br['image'], '/'));
            $url = '/essence_db/brand.php?brand=' . urlencode($br['name']);
            echo '<a href="' . htmlspecialchars($url) . '"><img src="' . $img . '" alt="' . htmlspecialchars($br['name']) . '" title="' . htmlspecialchars($br['name']) . '" style="height:48px;object-fit:contain;max-width:140px;" /></a>';
          }
        }
      }
      ?>
      </div>
    </div>
  </div>
</section>

<section id="popular-products" class="py-5">
  <div class="container text-center">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0">Popular Products</h2>
      <a href="/essence_db/brands.php" class="btn btn-sm btn-outline-secondary">Browse Brands</a>
    </div>


    <?php
    $search = '';
    $whereExtra = '';
    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
      $search = trim($_GET['search']);
      $searchEsc = mysqli_real_escape_string($conn, strtolower($search));
      $whereExtra = " AND (LOWER(p.product_name) LIKE '%{$searchEsc}%' OR LOWER(p.scent_type) LIKE '%{$searchEsc}%' OR LOWER(p.description) LIKE '%{$searchEsc}%' OR LOWER(b.name) LIKE '%{$searchEsc}%')";
      echo '<div class="search-result">Showing results for <strong>' . htmlspecialchars($search) . '</strong></div>';
    }

    
    if (trim($whereExtra) === '') {
    
      $sql = "SELECT p.product_id AS productId, p.product_name, b.name as brand_name, p.scent_type AS scent_type, p.price, p.image, i.quantity,
      COALESCE(SUM(oi.quantity),0) AS sales_count
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    INNER JOIN inventory i ON p.product_id = i.product_id
    WHERE i.quantity > 0 AND p.status = 'available'
    GROUP BY p.product_id
    HAVING COALESCE(SUM(oi.quantity),0) > 0
    ORDER BY sales_count DESC, p.product_id ASC";
    } else {
      
      $sql = "SELECT p.product_id AS productId, p.product_name, b.name as brand_name, p.scent_type AS scent_type, p.price, p.image, i.quantity,
      COALESCE(SUM(oi.quantity),0) AS sales_count
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    INNER JOIN inventory i ON p.product_id = i.product_id
    WHERE i.quantity > 0 AND p.status = 'available' " . $whereExtra . "
    GROUP BY p.product_id
    ORDER BY sales_count DESC, p.product_id ASC";
    }

    $results = mysqli_query($conn, $sql);
    ?>

    <div class="products-grid">
    <?php
    if ($results && mysqli_num_rows($results) > 0) {
      while ($row = mysqli_fetch_assoc($results)) {
        $scent = htmlspecialchars($row['scent_type']);
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

      <div class="product-item">
        <div class="card shadow-sm product-card">
          
          <div style="position: relative; overflow: hidden; border-radius: 12px 12px 0 0;">
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
            
            <?php if (!empty($row['sales_count']) && (int)$row['sales_count'] >= 3): ?>
              <div class="badge-bestseller">Best seller</div>
            <?php endif; ?>
          </div>

        
          <div class="card-body">
            <h5 class="card-title product-name"><a href="/essence_db/brand.php?brand=<?php echo urlencode($brand); ?>"><?php echo $brand; ?></a></h5>
            <p class="product-scent"><?php echo $scent; ?></p>
            <p class="product-price">₱<?php echo $price; ?></p>

            <form method="POST" action="./cart/cart_update.php" class="mt-auto">
              <input type="hidden" name="item_id" value="<?php echo $row['productId']; ?>">
              <input type="hidden" name="type" value="add">
              <div class="product-qty">
                <input type="number" name="item_qty" value="1" min="1" max="<?php echo $maxQty; ?>" class="form-control form-control-sm">
              </div>
              <div class="product-actions">
                <button type="submit" class="btn btn-dark btn-sm">Add to Cart</button>
                <a href="/essence_db/product.php?id=<?php echo $row['productId']; ?>" class="btn btn-outline-secondary btn-sm">View</a>
              </div>
            </form>
          </div>
        </div>
      </div>

    <?php
      }
    } else {
      echo '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;">No products found.</div>';
    }
    ?>
    </div>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
