<?php
session_start();
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/header.php');
include('../includes/config.php');

?>

<body>
   <div class="admin-page">
  <div class="admin-card">
    <h2 class="mb-4">Create New Product</h2>
    <form method="POST" action="store.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="brand_id" class="form-label">Brand</label>
                <?php
                $brandsForSelect = [];
                $qb = mysqli_query($conn, "SELECT brand_id, name FROM brands ORDER BY name ASC");
                if ($qb && mysqli_num_rows($qb) > 0) {
                    while ($br = mysqli_fetch_assoc($qb)) $brandsForSelect[] = $br;
                }
                ?>
                <select class="form-control form-select mb-3" id="brand_id" name="brand_id" required>
                    <option value="">-- Select a Brand --</option>
                    <?php foreach ($brandsForSelect as $b): ?>
                        <option value="<?php echo htmlspecialchars($b['brand_id']); ?>" <?php if (isset($_SESSION['brand_id']) && $_SESSION['brand_id'] == $b['brand_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($b['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="muted-small">
                    <?php
                    if (isset($_SESSION['brandError'])) {
                        echo $_SESSION['brandError'];
                        unset($_SESSION['brandError']);
                    }
                    ?>
                </small>

                <label for="product_name" class="form-label">Product Name</label>
                <input type="text"
                        class="form-control mb-3"
                        id="product_name"
                        placeholder="Enter product name"
                        name="product_name"
                        required
                        value="<?php if (isset($_SESSION['product_name'])) echo htmlspecialchars($_SESSION['product_name']); ?>" />
                <small class="muted-small">
                    <?php
                    if (isset($_SESSION['productNameError'])) {
                        echo $_SESSION['productNameError'];
                        unset($_SESSION['productNameError']);
                    }
                    ?>
                </small>

                <label for="scent" class="form-label">Scent / Short Description</label>
                <input type="text" class="form-control mb-3" id="scent" placeholder="Enter scent or short description" name="scent_type" value="<?php if (isset($_SESSION['scent'])) echo htmlspecialchars($_SESSION['scent']); ?>" />
                <small class="muted-small">
                    <?php if (isset($_SESSION['scentError'])) { echo $_SESSION['scentError']; unset($_SESSION['scentError']); } ?>
                </small>

                <label for="size" class="form-label">Size</label>
                <input type="text" class="form-control mb-3" id="size" placeholder="e.g. 50ml" name="size" value="<?php if (isset($_SESSION['size'])) echo $_SESSION['size']; ?>">

                <label for="price" class="form-label">Price</label>
                <input type="text" class="form-control mb-3" id="price" placeholder="Enter price" name="price" value="<?php if (isset($_SESSION['price'])) echo $_SESSION['price']; ?>">
                <small class="muted-small">
                    <?php if (isset($_SESSION['priceError'])) { echo $_SESSION['priceError']; unset($_SESSION['priceError']); } ?>
                </small>

                <label for="qty" class="form-label">Quantity</label>
                <input type="number" class="form-control mb-3" id="qty" placeholder="1" name="quantity" value="<?php echo isset($_SESSION['qty']) ? (int)$_SESSION['qty'] : 1; ?>" />

                <label for="description" class="form-label">Full Description</label>
                <textarea class="form-control mb-3" id="description" name="description" rows="4"><?php if (isset($_SESSION['desc'])) echo htmlspecialchars($_SESSION['desc']); ?></textarea>

                <label for="images" class="form-label">Product Images (JPG/PNG) — Select Multiple</label>
                <input class="form-control mb-2" type="file" name="images[]" id="images" multiple accept="image/png,image/jpeg" />
                <div id="imagePreview" style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;"></div>
                <div id="imageCount" style="font-size: 12px; color: var(--smoky-oak); margin-bottom: 12px;"></div>
                <small class="muted-small">
                    <?php
                    if (isset($_SESSION['imageError'])) {
                        echo $_SESSION['imageError'];
                        unset($_SESSION['imageError']);
                    }
                    ?></small>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const imagesInput = document.getElementById('images');
                    const imagePreview = document.getElementById('imagePreview');
                    const imageCount = document.getElementById('imageCount');
                    
                    imagesInput.addEventListener('change', function(e) {
                        imagePreview.innerHTML = '';
                        imageCount.innerHTML = '';
                        const files = e.target.files;
                        let validCount = 0;
                        
                        if (files.length === 0) {
                            return;
                        }
                        
                        for (let i = 0; i < files.length; i++) {
                            const file = files[i];
                            
                            if (!file.type.match('image.*')) {
                                continue;
                            }
                            
                            validCount++;
                            const currentIndex = validCount;
                            const reader = new FileReader();
                            
                            reader.onload = function(event) {
                                const div = document.createElement('div');
                                div.style.position = 'relative';
                                div.style.width = '100px';
                                div.style.height = '100px';
                                div.style.borderRadius = '8px';
                                div.style.overflow = 'hidden';
                                div.style.boxShadow = '0 2px 8px rgba(44, 26, 17, 0.1)';
                                div.style.cursor = 'default';
                                
                                const img = document.createElement('img');
                                img.src = event.target.result;
                                img.style.width = '100%';
                                img.style.height = '100%';
                                img.style.objectFit = 'cover';
                                
                                const label = document.createElement('div');
                                label.textContent = currentIndex;
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
                        
                        imageCount.innerHTML = '<strong>' + validCount + '</strong> image' + (validCount !== 1 ? 's' : '') + ' selected';
                    });
                });
                </script>

            </div>
                        <div class="admin-actions">
                            <button type="submit" class="btn btn-primary" name="submit" value="submit">Save Product</button>
                            <button type="submit" class="btn btn-outline-success" name="return" value="dashboard" style="margin-left:8px;">Save & Return to Dashboard</button>
                            <a href="index.php" role="button" class="btn btn-outline-secondary" style="margin-left:8px;">Cancel</a>
                        </div>
        </form>
  </div>
</div>
