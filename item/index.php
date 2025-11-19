<?php
session_start();
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/header.php');
include('../includes/config.php');

$keyword = '';
if (isset($_GET['search'])) {
    $raw = mysqli_real_escape_string($conn, trim($_GET['search']));
    $keyword = strtolower($raw);
}

if ($keyword !== '') {
    $sql = "SELECT p.*, b.name as brand_name, i.quantity FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id LEFT JOIN inventory i ON p.product_id = i.product_id WHERE LOWER(p.product_name) LIKE '%" . $keyword . "%' OR LOWER(p.description) LIKE '%" . $keyword . "%' OR LOWER(b.name) LIKE '%" . $keyword . "%'";
    $result = mysqli_query($conn, $sql);
} else {
    $sql = "SELECT p.*, b.name as brand_name, i.quantity FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id LEFT JOIN inventory i ON p.product_id = i.product_id";
    $result = mysqli_query($conn, $sql);
}

$itemCount = mysqli_num_rows($result);
?>


<div class="admin-page">
  <div class="admin-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 style="margin:0;">Items (<?=$itemCount ?>)</h2>
      <a href="create.php" class="btn btn-primary">+ Add Item</a>
    </div>
    
    <table class="table table-striped table-bordered admin-table">
      <thead>
        <tr>
          <th>Image</th><th>ID</th><th>Product Name</th><th>Brand</th><th>Description</th><th>Price</th><th>Qty</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            $img = htmlspecialchars($row['image'] ?? '');
            
            $pid = (int)$row['product_id'];
            $imgsPaths = [];
            $qpi = mysqli_query($conn, "SELECT path FROM product_images WHERE product_id = {$pid} ORDER BY product_image_id ASC");
            if ($qpi && mysqli_num_rows($qpi) > 0) {
                while ($rpi = mysqli_fetch_assoc($qpi)) {
                    $imgsPaths[] = $rpi['path'];
                }
            }
            
            if (empty($imgsPaths) && !empty($row['image'])) {
                $imgsPaths[] = $row['image'];
            }

            if (count($imgsPaths) > 0) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
                if (count($imgsPaths) === 1) {
                    $p = str_replace('\\', '/', $imgsPaths[0]);
                    if (preg_match('#^https?://#i', $p)) {
                        $imgUrl = $p;
                        $fileExists = true;
                    } else {
                        $imgUrl = $baseUrl . ltrim($p, '/');
                        $fileExists = file_exists(__DIR__ . '/../' . ltrim($p, '/'));
                    }
                    if (!empty($fileExists)) {
                        echo "<td><img src='" . htmlspecialchars($imgUrl) . "' width='150' height='150' style='object-fit:cover' /></td>";
                    } else {
                        echo "<td><div style='width:150px;height:150px;background:#f8d7da;color:#842029;display:flex;align-items:center;justify-content:center;'>Missing image</div></td>";
                    }
                } else {
                    $carouselId = 'prodCarousel_' . $pid;
                    echo "<td style='width:170px'>";
                    echo "<div id='$carouselId' class='carousel slide' data-bs-ride='carousel'>";
                    echo "<div class='carousel-indicators'>";
                    foreach ($imgsPaths as $i => $p) {
                        $active = $i === 0 ? 'class="active" aria-current="true"' : '';
                        echo "<button type='button' data-bs-target='#{$carouselId}' data-bs-slide-to='{$i}' {$active} aria-label='Slide " . ($i+1) . "'></button>";
                    }
                    echo "</div>"; 
                    echo "<div class='carousel-inner'>";
                    foreach ($imgsPaths as $i => $p) {
                        
                        $p = str_replace('\\', '/', $p);
                        if (preg_match('#^https?://#i', $p)) {
                            $imgUrl = $p;
                            $fileExists = true;
                        } else {
                            $imgUrl = $baseUrl . ltrim($p, '/');
                            $fileExists = file_exists(__DIR__ . '/../' . ltrim($p, '/'));
                        }
                        $activeClass = $i === 0 ? ' active' : '';
                        echo "<div class='carousel-item{$activeClass}'>";
                        if (!empty($fileExists)) {
                            echo "<img src='" . htmlspecialchars($imgUrl) . "' class='d-block w-100' style='height:150px;object-fit:cover;' alt='product image'>";
                        } else {
                            echo "<div style='width:100%;height:150px;background:#f8d7da;color:#842029;display:flex;align-items:center;justify-content:center;'>Missing image</div>";
                        }
                        echo "</div>";
                    }
                    echo "</div>";
                    echo "<button class='carousel-control-prev' type='button' data-bs-target='#{$carouselId}' data-bs-slide='prev'>";
                    echo "<span class='carousel-control-prev-icon' aria-hidden='true'></span><span class='visually-hidden'>Previous</span>";
                    echo "</button>";
                    echo "<button class='carousel-control-next' type='button' data-bs-target='#{$carouselId}' data-bs-slide='next'>";
                    echo "<span class='carousel-control-next-icon' aria-hidden='true'></span><span class='visually-hidden'>Next</span>";
                    echo "</button>";
                    echo "</div>";
                    echo "</td>";
                }
            } else {
                echo "<td><div style='width:150px;height:150px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#888'>No image</div></td>";
            }
            echo "<td>{$row['product_id']}</td>";
            echo "<td>" . htmlspecialchars($row['product_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['brand_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['description'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['price'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['quantity'] ?? 0) . "</td>";

            echo "<td><a href='edit.php?id={$row['product_id']}'><i class='fa-regular fa-pen-to-square' style='color: blue'></i></a><a href='delete.php?id={$row['product_id']}'><i class='fa-solid fa-trash' style='color: red'></i></a></td>";
            echo "</tr>";
        }
        ?>
    </table>
  </div>
</div>

