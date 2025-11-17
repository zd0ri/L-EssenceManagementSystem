<?php
session_start();
// require admin before any output
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/header.php');
include('../includes/config.php');

// var_dump($_SESSION);
?>

<body>
   <div class="admin-page">
  <div class="admin-card">
    <h2 class="mb-4">Create New Product</h2>
    <form method="POST" action="store.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name" class="form-label">Brand</label>
                                <?php
                                // try to load dynamic brands for a select box
                                $brandsForSelect = [];
                                $qb = mysqli_query($conn, "SELECT brand_id, name FROM brands ORDER BY name ASC");
                                if ($qb && mysqli_num_rows($qb) > 0) {
                                        while ($br = mysqli_fetch_assoc($qb)) $brandsForSelect[] = $br;
                                }
                                ?>
                                <?php if (count($brandsForSelect) > 0): ?>
                                    <select class="form-control form-select mb-3" id="brand_select" name="brand_select">
                                        <option value="">-- Select existing brand (or choose Other) --</option>
                                        <?php foreach ($brandsForSelect as $b): ?>
                                            <option value="<?php echo htmlspecialchars($b['name']); ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                                        <?php endforeach; ?>
                                        <option value="__other__">Other (enter below)</option>
                                    </select>
                                <?php endif; ?>
                                <input type="text"
                                        class="form-control mb-3"
                                        id="name"
                                        placeholder="Enter brand name"
                                        name="brand_name"
                                        value="<?php if (isset($_SESSION['brand'])) echo htmlspecialchars($_SESSION['brand']); ?>" />

                <small class="muted-small">
                    <?php
                    if (isset($_SESSION['brandError'])) {
                        echo $_SESSION['brandError'];
                        unset($_SESSION['brandError']);
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
                <input class="form-control mb-3" type="file" name="images[]" id="images" multiple accept="image/png,image/jpeg" />
                <small class="muted-small">
                    <?php
                    if (isset($_SESSION['imageError'])) {
                        echo $_SESSION['imageError'];
                        unset($_SESSION['imageError']);
                    }
                    ?></small>

            </div>
            <div class="admin-actions">
              <button type="submit" class="btn btn-primary" name="submit" value="submit">Save Product</button>
              <a href="index.php" role="button" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
  </div>
</div>
