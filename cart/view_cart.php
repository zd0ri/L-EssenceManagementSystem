<?php
session_start();
// require login before output
require_once(__DIR__ . '/../includes/auth.php');
include('../includes/header.php');
include('../includes/config.php');
// print_r($_SESSION);
?>

<?php
// DEBUG: log session/cart when viewing cart (temporary)
@file_put_contents(__DIR__ . '/cart_debug.log', json_encode([
    'time' => date('c'),
    'event' => 'view_cart',
    'session_cart' => isset($_SESSION['cart_products']) ? $_SESSION['cart_products'] : null,
    'cookie' => isset($_COOKIE) ? $_COOKIE : null,
    'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null
]) . PHP_EOL, FILE_APPEND | LOCK_EX);
?>

<div class="container py-4 cart-page">
    <h1 class="mb-4">Shopping Cart</h1>
    <form method="POST" action="cart_update.php">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <?php
                        $total = 0.0;
                        $selected_total = 0.0;
                        if (!empty($_SESSION["cart_products"])) {
                            foreach ($_SESSION["cart_products"] as $cart_itm) {
                                $product_name = $cart_itm["item_name"];
                                $product_qty = (int)$cart_itm["item_qty"];
                                $product_price = (float)$cart_itm["item_price"];
                                $product_code = (int)$cart_itm["item_id"];
                                $subtotal = $product_price * $product_qty;
                                // fetch image
                                $imgUrl = '';
                                $qimg = mysqli_query($conn, "SELECT COALESCE((SELECT path FROM product_images WHERE product_id = p.product_id ORDER BY product_image_id ASC LIMIT 1), p.image) AS img_path FROM products p WHERE p.product_id = {$product_code} LIMIT 1");
                                if ($qimg && mysqli_num_rows($qimg) > 0) {
                                    $ri = mysqli_fetch_assoc($qimg);
                                    $praw = $ri['img_path'];
                                    if (!empty($praw)) {
                                        $p = str_replace('\\', '/', $praw);
                                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                        $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
                                        $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
                                    }
                                }
                                ?>
                                <div class="d-flex align-items-center mb-3 product-item">
                                    <div style="width:30px;flex-shrink:0;">
                                        <input type="checkbox" name="selected_items[]" value="<?php echo $product_code; ?>" class="form-check-input item-checkbox" data-price="<?php echo $product_price; ?>" data-qty="<?php echo $product_qty; ?>">
                                    </div>
                                    <div style="width:84px;flex-shrink:0;margin-left:12px;">
                                        <img src="<?php echo htmlspecialchars($imgUrl ?: '/essence_db/images/placeholder.png'); ?>" alt="" class="product-img">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($product_name); ?></strong>
                                                <div class="text-muted small">Product Code: <?php echo $product_code; ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div>₱<?php echo number_format($product_price,2); ?></div>
                                                <div class="text-muted small">Total: ₱<?php echo number_format($subtotal,2); ?></div>
                                            </div>
                                        </div>
                                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <label class="small me-2">Qty</label>
                                                <input type="number" min="1" name="product_qty[<?php echo $product_code; ?>]" value="<?php echo $product_qty; ?>" class="form-control d-inline-block qty-input">
                                                <label class="ms-2 small"><input type="checkbox" name="remove_code[]" value="<?php echo $product_code; ?>"> Remove</label>
                                            </div>
                                            <div>
                                                <a href="../product.php?id=<?php echo $product_code; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">View</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr />
                                <?php
                                $total += $subtotal;
                            }
                        } else {
                            echo '<div class="text-center text-muted">Your cart is empty. <a href="../index.php">Shop now</a>.</div>';
                        }
                        ?>
                                        <div class="mt-3 d-flex justify-content-between">
                            <div class="continue-actions">
                                <a href="../index.php" class="btn btn-outline-dark">Continue Shopping</a>
                                <button type="submit" class="btn btn-outline-secondary ms-2">Update Cart</button>
                            </div>
                            <div>
                                <strong>Subtotal: ₱<?php echo number_format($total,2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body order-summary">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="d-flex justify-content-between"><div>Selected Items</div><div><span id="selected-count">0</span> items</div></div>
                            <div class="d-flex justify-content-between mt-2"><div>Subtotal</div><div>₱<span id="selected-subtotal">0.00</span></div></div>
                            <div class="d-flex justify-content-between mt-2"><div>Discount</div><div>-₱0.00</div></div>
                            <div class="d-flex justify-content-between mt-2"><div>Shipping</div><div>₱0.00</div></div>
                            <hr />
                            <div class="d-flex justify-content-between"><strong>Total</strong><strong>₱<span id="selected-total">0.00</span></strong></div>
                            <div class="mt-4">
                                <button type="button" class="btn btn-primary w-100" id="btn-checkout" onclick="checkoutSelected()" disabled>Checkout Now</button>
                            </div>
                        </div>
                </div>
            </div>
        </div>
        </form> <!-- end cart_update form -->

        <!-- Hidden form for checkout submission (must be outside the cart update form) -->
        <form id="checkout-form" method="POST" action="checkout_form.php" style="display:none;">
            <!-- selected items will be added here by JavaScript -->
        </form>

    <script>
    // Update order summary when checkboxes or quantity inputs change
    function updateOrderSummary() {
        let selectedCount = 0;
        let selectedTotal = 0.0;

        document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
            selectedCount++;
            let price = parseFloat(checkbox.dataset.price) || 0;
            // try to read live qty from the nearby qty input; fall back to dataset
            let qty = parseInt(checkbox.dataset.qty) || 0;
            const productItem = checkbox.closest('.product-item');
            if (productItem) {
                const qtyInput = productItem.querySelector('.qty-input');
                if (qtyInput) {
                    const qv = parseInt(qtyInput.value);
                    if (!isNaN(qv) && qv > 0) qty = qv;
                }
            }
            selectedTotal += price * qty;
        });

        document.getElementById('selected-count').textContent = selectedCount;
        document.getElementById('selected-subtotal').textContent = selectedTotal.toFixed(2);
        document.getElementById('selected-total').textContent = selectedTotal.toFixed(2);
        document.getElementById('btn-checkout').disabled = selectedCount === 0;
    }

    // Checkout with only selected items (include live quantities)
    function checkoutSelected() {
        let selectedIds = [];
        let quantities = {};
        document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
            const id = checkbox.value;
            selectedIds.push(id);
            // read live qty from the qty input if present
            let qty = parseInt(checkbox.dataset.qty) || 0;
            const productItem = checkbox.closest('.product-item');
            if (productItem) {
                const qtyInput = productItem.querySelector('.qty-input');
                if (qtyInput) {
                    const qv = parseInt(qtyInput.value);
                    if (!isNaN(qv) && qv > 0) qty = qv;
                }
            }
            quantities[id] = qty;
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one item to checkout.');
            return;
        }

        // Clear previous hidden inputs and add current selections
        let checkoutForm = document.getElementById('checkout-form');
        checkoutForm.innerHTML = ''; // Clear previous inputs

        selectedIds.forEach(id => {
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_items[]';
            input.value = id;
            checkoutForm.appendChild(input);

            // include quantity for this item so checkout_form.php can use the live qty
            let qinput = document.createElement('input');
            qinput.type = 'hidden';
            qinput.name = 'selected_qty[' + id + ']';
            qinput.value = quantities[id];
            checkoutForm.appendChild(qinput);
        });

        // Submit the form
        checkoutForm.submit();
    }

    // Initialize on page load and when checkboxes/qty inputs change
    document.addEventListener('DOMContentLoaded', function() {
        updateOrderSummary();
        document.querySelectorAll('.item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateOrderSummary);
        });
        // also update summary when quantity inputs change
        document.querySelectorAll('.qty-input').forEach(qi => {
            qi.addEventListener('input', updateOrderSummary);
            qi.addEventListener('change', updateOrderSummary);
        });
    });
    </script>
</div>
<?php
include('../includes/footer.php');
?>