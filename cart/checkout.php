<?php
session_start();
include('../includes/header.php');
include('../includes/config.php');

try {
    mysqli_query($conn, 'START TRANSACTION');
    
    // get customer id from customers table using session user_id
    $current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    if (!$current_user_id) {
        throw new Exception('User not logged in');
    }

    $sql = "SELECT customer_id FROM customers WHERE user_id = {$current_user_id} LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $customer_id = $row['customer_id'] ?? null;
    if (!$customer_id) {
        throw new Exception('Customer profile not found');
    }

    // create order
    $q = 'INSERT INTO orders (customer_id, total_amount, order_date, status, payment_status, delivery_method, remarks) VALUES (?, ?, NOW(), ?, ?, ?, ?)';
    $stmt1 = mysqli_prepare($conn, $q);

    // compute total from cart
    $total_amount = 0.0;
    foreach ($_SESSION["cart_products"] as $cart_itm) {
        $total_amount += (float)$cart_itm['item_price'] * (int)$cart_itm['item_qty'];
    }

    $status = 'pending';
    $payment_status = 'unpaid';
    $delivery_method = 'Standard';
    $remarks = '';

    // Bind parameters: customer_id (int), total_amount (double), then strings
    mysqli_stmt_bind_param($stmt1, 'idssss', $customer_id, $total_amount, $status, $payment_status, $delivery_method, $remarks);
    mysqli_stmt_execute($stmt1);
    $order_id = mysqli_insert_id($conn);

    // prepare order_items insert and inventory update
    $q2 = 'INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)';
    $stmt2 = mysqli_prepare($conn, $q2);

    $q3 = 'UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?';
    $stmt3 = mysqli_prepare($conn, $q3);

    foreach ($_SESSION["cart_products"] as $cart_itm) {
        $product_qty = (int)$cart_itm["item_qty"];
        $product_id = (int)$cart_itm["item_id"];
        $price_each = (float)$cart_itm['item_price'];

        mysqli_stmt_bind_param($stmt2, 'iiid', $order_id, $product_id, $product_qty, $price_each);
        mysqli_stmt_execute($stmt2);

        mysqli_stmt_bind_param($stmt3, 'ii', $product_qty, $product_id);
        mysqli_stmt_execute($stmt3);
    }

    mysqli_commit($conn);
    unset($_SESSION['cart_products']);
    header('Location: ../index.php');
    exit();
} catch (mysqli_sql_exception $e) {
    echo $e->getMessage();
    mysqli_rollback($conn);
    
}
