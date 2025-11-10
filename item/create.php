<?php
session_start();
// require admin before any output
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/header.php');
include('../includes/config.php');

// var_dump($_SESSION);
?>

<body>
    <div class="container">
        <form method="POST" action="store.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Item Name</label>
                <input type="text"
                    class="form-control"
                    id="name"
                    placeholder="Enter brand name"
                    name="brand_name"
                    value="<?php if (isset($_SESSION['brand'])) echo htmlspecialchars($_SESSION['brand']); ?>" />

                <small>
                    <?php
                    if (isset($_SESSION['brandError'])) {
                        echo $_SESSION['brandError'];
                        unset($_SESSION['brandError']);
                    }
                    ?>
                </small>

                <label for="scent">Scent / Short Description</label>
                <input type="text" class="form-control" id="scent" placeholder="Enter scent or short description" name="scent_type" value="<?php if (isset($_SESSION['scent'])) echo htmlspecialchars($_SESSION['scent']); ?>" />
                <small>
                    <?php if (isset($_SESSION['scentError'])) { echo $_SESSION['scentError']; unset($_SESSION['scentError']); } ?>
                </small>
                <label for="size">Size</label>

                <input type="text" class="form-control" id="size" placeholder="e.g. 50ml" name="size" value="<?php if (isset($_SESSION['size'])) echo $_SESSION['size']; ?>">
                <label for="price">Price</label>
                <input type="text" class="form-control" id="price" placeholder="Enter price" name="price" value="<?php if (isset($_SESSION['price'])) echo $_SESSION['price']; ?>">
                <small>
                    <?php if (isset($_SESSION['priceError'])) { echo $_SESSION['priceError']; unset($_SESSION['priceError']); } ?>
                </small>

                <label for="qty">quantity</label>

                <input type="number" class="form-control" id="qty" placeholder="1" name="quantity" value="<?php if (isset($_SESSION['qty'])) echo (int)$_SESSION['qty']; ?>" />
                <label for="description">Full Description</label>
                <textarea class="form-control" id="description" name="description"><?php if (isset($_SESSION['desc'])) echo htmlspecialchars($_SESSION['desc']); ?></textarea>
                <label for="images">Product images (JPG/PNG) — you can select multiple</label>
                <input class="form-control" type="file" name="images[]" id="images" multiple accept="image/png,image/jpeg" /><br />
                <small>
                    <?php
                    if (isset($_SESSION['imageError'])) {
                        echo $_SESSION['imageError'];
                        unset($_SESSION['imageError']);
                    }
                    ?></small>

            </div>
            <button type="submit" class="btn btn-primary" name="submit" value="submit">Submit</button>
            <a href="index.php" role="button" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <?php
    include('../includes/footer.php');
    ?>