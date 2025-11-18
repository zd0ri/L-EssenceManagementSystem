<?php
session_start();
// require admin before sending any output
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/header.php');
include('../includes/config.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'Invalid product id.';
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$q = "SELECT p.*, i.quantity FROM products p LEFT JOIN inventory i ON p.product_id = i.product_id WHERE p.product_id = {$id} LIMIT 1";
$res = mysqli_query($conn, $q);
if (!$res || mysqli_num_rows($res) === 0) {
    $_SESSION['message'] = 'Product not found.';
    header('Location: index.php');
    exit();
}
$row = mysqli_fetch_assoc($res);

$brand = $row['brand_name'] ?? '';
$scent = $row['scent_type'] ?? '';
$size = $row['size'] ?? '';
$price = $row['price'] ?? '';
$desc = $row['description'] ?? '';
$qty = $row['quantity'] ?? 0;
$image = $row['image'] ?? '';

?>
<div class="admin-page">
    <div class="admin-card">
    <h2>Edit Product</h2>
    <form method="POST" action="update.php" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?php echo (int)$id; ?>">
        <div class="form-group mb-2">
            <label>Brand name</label>
            <input class="form-control" type="text" name="brand_name" value="<?php echo htmlspecialchars($brand); ?>">
        </div>
        <div class="form-group mb-2">
            <label>Scent / short desc</label>
            <input class="form-control" type="text" name="scent_type" value="<?php echo htmlspecialchars($scent); ?>">
        </div>
        <div class="form-group mb-2">
            <label>Size</label>
            <input class="form-control" type="text" name="size" value="<?php echo htmlspecialchars($size); ?>">
        </div>
        <div class="form-group mb-2">
            <label>Price</label>
            <input class="form-control" type="text" name="price" value="<?php echo htmlspecialchars($price); ?>">
        </div>
        <div class="form-group mb-2">
            <label>Quantity</label>
            <input class="form-control" type="number" name="quantity" value="<?php echo (int)$qty; ?>">
        </div>
        <div class="form-group mb-2">
            <label>Description</label>
            <textarea class="form-control" name="description"><?php echo htmlspecialchars($desc); ?></textarea>
        </div>
                <div class="form-group mb-2">
                        <label>Current Images</label><br />
                        <?php
                        // fetch product images
                        $imgs = [];
                        $qi = mysqli_query($conn, "SELECT product_image_id, path FROM product_images WHERE product_id = {$id} ORDER BY product_image_id ASC");
                        if ($qi) {
                                while ($ri = mysqli_fetch_assoc($qi)) {
                                        $imgs[] = $ri;
                                }
                        }
                        if (count($imgs) > 0) {
                                $carouselId = 'carousel_' . $id;
                                ?>
                                <div id="<?php echo $carouselId; ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-indicators">
                                        <?php foreach ($imgs as $i => $im): ?>
                                            <button type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide-to="<?php echo $i; ?>" <?php if ($i===0) echo 'class="active" aria-current="true"'; ?> aria-label="Slide <?php echo $i+1; ?>"></button>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="carousel-inner">
                                                        <?php foreach ($imgs as $i => $im):
                                                              $rawPath = $im['path'];
                                                              // normalize slashes stored in DB
                                                              $rawPath = str_replace('\\', '/', $rawPath);
                                                              // build absolute base url
                                                              $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                                              $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
                                                              // convert stored path (uploads/...) to absolute URL
                                                              if (preg_match('#^https?://#i', $rawPath)) {
                                                                  $urlPath = $rawPath;
                                                                  $fileExists = true; // assume remote URL exists
                                                              } else {
                                                                  $urlPath = $baseUrl . ltrim($rawPath, '/');
                                                                  $fileExists = file_exists(__DIR__ . '/../' . ltrim($rawPath, '/'));
                                                              }
                                                              $p = htmlspecialchars($urlPath);
                                                        ?>
                                                                    <div class="carousel-item <?php if ($i===0) echo 'active'; ?>">
                                                                                                <?php if ($fileExists): ?>
                                                                                                    <img src="<?php echo $p; ?>" class="d-block w-100" style="max-height:300px;object-fit:cover;" alt="product image <?php echo $i+1; ?>">
                                                                                                <?php else: ?>
                                                                                                    <div style="width:100%;height:300px;background:#f8d7da;color:#842029;display:flex;align-items:center;justify-content:center;">Missing image file</div>
                                                                                                <?php endif; ?>
                                                <div class="carousel-caption d-none d-md-block">
                                                    <a class="btn btn-sm btn-danger" href="image_delete.php?id=<?php echo $im['product_image_id']; ?>&product_id=<?php echo $id; ?>">Delete</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                </div>
                        <?php
                        // also show direct links below for quick verification
                        echo '<div class="mt-2">';
                        foreach ($imgs as $im) {
                            $rawPath2 = $im['path'];
                            if (preg_match('#^https?://#i', $rawPath2)) {
                                $url2 = $rawPath2;
                                $exists2 = true;
                            } else {
                                $url2 = $baseUrl . ltrim($rawPath2, '/');
                                $exists2 = file_exists(__DIR__ . '/../' . ltrim($rawPath2, '/'));
                            }
                            echo '<div>'; 
                            echo ($exists2 ? '' : '<span style="color:#842029;">[missing]</span> ');
                            echo '<a href="' . htmlspecialchars($url2) . '" target="_blank" rel="noopener">' . htmlspecialchars($url2) . '</a>';
                            echo '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<div class="text-muted">No images</div>';
                    }
                    ?>
                </div>
        <div class="form-group mb-2">
            <label>Add / Replace Images (you can upload multiple)</label>
            <input class="form-control mb-2" type="file" name="images[]" id="images" multiple accept="image/png,image/jpeg">
            <div id="imagePreview" style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;"></div>
        </div>
        <script>
        const imagesInput = document.getElementById('images');
        const imagePreview = document.getElementById('imagePreview');
        
        imagesInput.addEventListener('change', function(e) {
            imagePreview.innerHTML = '';
            const files = e.target.files;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (!file.type.match('image.*')) continue;
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    const div = document.createElement('div');
                    div.style.position = 'relative';
                    div.style.width = '100px';
                    div.style.height = '100px';
                    div.style.borderRadius = '8px';
                    div.style.overflow = 'hidden';
                    div.style.boxShadow = '0 2px 8px rgba(44, 26, 17, 0.1)';
                    
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    
                    const label = document.createElement('div');
                    label.textContent = (i + 1);
                    label.style.position = 'absolute';
                    label.style.top = '4px';
                    label.style.right = '4px';
                    label.style.background = 'var(--golden-sand)';
                    label.style.color = 'white';
                    label.style.borderRadius = '50%';
                    label.style.width = '24px';
                    label.style.height = '24px';
                    label.style.display = 'flex';
                    label.style.alignItems = 'center';
                    label.style.justifyContent = 'center';
                    label.style.fontSize = '12px';
                    label.style.fontWeight = 'bold';
                    
                    div.appendChild(img);
                    div.appendChild(label);
                    imagePreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            }
        });
        </script>
    <button class="btn btn-primary" type="submit">Save changes</button>
    <button class="btn btn-outline-success" type="submit" name="return" value="dashboard" style="margin-left:8px;">Save & Return to Dashboard</button>
    <a class="btn btn-secondary" href="index.php" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
