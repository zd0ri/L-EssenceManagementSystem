<?php
session_start();

// This script updates the cart in session. It should not output anything
// (no includes that echo) because we redirect after processing.
require_once __DIR__ . '/../includes/config.php';

// Helper to safe-get POST values
function post($k, $default = null) {
    return isset($_POST[$k]) ? $_POST[$k] : $default;
}

// Add item
if (post('type') === 'add') {
    $item_qty = (int) post('item_qty', 0);
    $item_id = (int) post('item_id', 0);
    if ($item_id > 0 && $item_qty > 0) {
        // fetch product info
        $product_id = $item_id;
        $sql = "SELECT p.product_id AS productId, p.brand_name as description, p.image as img_path, p.price as sell_price, i.quantity
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE p.product_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $product_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
        } else {
            $row = null;
        }

        if ($row) {
            $new_product = [];
            $new_product['item_id'] = $product_id;
            $new_product['item_qty'] = $item_qty;
            $new_product['item_name'] = $row['description'] ?? '';
            $new_product['item_price'] = $row['sell_price'] ?? 0;
            $new_product['max_qty'] = isset($row['quantity']) ? (int)$row['quantity'] : 0;

            if (!isset($_SESSION['cart_products']) || !is_array($_SESSION['cart_products'])) {
                $_SESSION['cart_products'] = [];
            }

            // replace any existing entry for this product
            $_SESSION['cart_products'][$product_id] = $new_product;
            // flash message (no direct 'View Cart' link as requested)
            $_SESSION['success'] = 'Item added to cart.';
        }
    }
}

// Update quantities or remove items
if (isset($_POST['product_qty']) && is_array($_POST['product_qty'])) {
    foreach ($_POST['product_qty'] as $key => $value) {
        $k = (int)$key;
        if (is_numeric($value) && isset($_SESSION['cart_products'][$k])) {
            $_SESSION['cart_products'][$k]['item_qty'] = (int)$value;
        }
    }
    $_SESSION['success'] = 'Cart updated.';
}

if (isset($_POST['remove_code']) && is_array($_POST['remove_code'])) {
    foreach ($_POST['remove_code'] as $key) {
        $k = (int)$key;
        if (isset($_SESSION['cart_products'][$k])) {
            unset($_SESSION['cart_products'][$k]);
        }
    }
    $_SESSION['success'] = 'Selected items removed from cart.';
}

// determine safe redirect: prefer referrer if within project, otherwise index
$redirect = '../index.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    // simple safety: only use referrer if it contains the project folder
    if (strpos($ref, '/essence_db') !== false) {
        $redirect = $ref;
    }
}

header('Location: ' . $redirect);
exit();
