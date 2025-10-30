<?php
session_start();
include('./includes/header.php');
include('./includes/config.php');
?>

<!-- Hero Section -->
<section class="hero text-center py-5" style="background-color:#fff;">
  <div class="container">
    <h1 class="display-4 fw-bold" style="font-family: 'Playfair Display', serif;">
      Smell is a word, Perfume is <span style="font-style: italic;">literature</span>
    </h1>
    <p class="lead mt-3">Discover the beauty of fragrance with our collection of premium perfumes to enrich your everyday smell.</p>
    <a href="#popular-products" class="btn btn-dark mt-4 px-4 py-2">Shop Now</a>
  </div>
</section>

<!-- Brand Logos -->
<section class="brands text-center py-3">
  <div class="container">
    <div class="d-flex justify-content-center flex-wrap gap-4">
      <h5>Dior</h5>
      <h5>TOM FORD</h5>
      <h5>Calvin Klein</h5>
      <h5>CLINIQUE</h5>
      <h5>D&G</h5>
    </div>
  </div>
</section>

<!-- Cart Display -->
<?php
if (isset($_SESSION["cart_products"]) && count($_SESSION["cart_products"]) > 0) {
    echo '<div class="container my-5">';
    echo '<h3 class="mb-4 text-center">Your Shopping Cart</h3>';
    echo '<form method="POST" action="./cart/cart_update.php">';
    echo '<table class="table table-bordered">';
    echo '<thead class="table-light"><tr><th>Quantity</th><th>Product</th><th>Remove</th></tr></thead><tbody>';

    $total = 0;
    foreach ($_SESSION["cart_products"] as $cart_itm) {
        $product_name = $cart_itm["item_name"];
        $product_qty = $cart_itm["item_qty"];
        $product_price = $cart_itm["item_price"];
        $product_code = $cart_itm["item_id"];
        $subtotal = ($product_price * $product_qty);
        $total += $subtotal;

        echo "<tr>
                <td><input type='number' name='product_qty[$product_code]' value='$product_qty' min='1' class='form-control' /></td>
                <td>$product_name</td>
                <td><input type='checkbox' name='remove_code[]' value='$product_code' /> Remove</td>
              </tr>";
    }

    echo '</tbody></table>';
    echo "<div class='text-center mb-3'>
            <button type='submit' class='btn btn-outline-dark me-2'>Update</button>
            <a href='./cart/checkout.php' class='btn btn-dark'>Checkout</a>
          </div>";
    echo '</form></div>';
}
?>

<!-- Product Display -->
<section id="popular-products" class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-4">Popular Products</h2>
    <div class="row g-4 justify-content-center">

    <?php
    $sql = "
        SELECT 
            p.product_id AS productId,
            p.brand_name,
            p.description,
            p.price,
            p.image,
            i.quantity
        FROM products p
        INNER JOIN inventory i ON p.product_id = i.product_id
        WHERE i.quantity > 0 AND p.status = 'available'
        ORDER BY p.product_id ASC
    ";

    $results = mysqli_query($conn, $sql);

    if ($results) {
        while ($row = mysqli_fetch_assoc($results)) {
            $desc = htmlspecialchars($row['description']);
            $brand = htmlspecialchars($row['brand_name']);
            $price = number_format($row['price'], 2);
            $img = htmlspecialchars($row['image']);
            $maxQty = (int)$row['quantity'];
    ?>

      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm text-center p-3">
          <img src="./item/<?php echo $img; ?>" class="card-img-top img-fluid" style="height:250px; object-fit:cover;" alt="<?php echo $brand; ?>">
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
    }
    ?>
    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>
