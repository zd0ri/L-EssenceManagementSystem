<?php
session_start();
// require login before any output
require_once(__DIR__ . '/../includes/auth.php');
include('../includes/header.php');
include('../includes/config.php');

// Checkout: create order, order_items, update inventory, insert payment — all with prepared statements
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // disallow direct GET
    header('Location: ../index.php');
    exit();
}

// ensure user logged in
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (!$current_user_id) {
    $_SESSION['message'] = 'Please login to checkout.';
    header('Location: /essence_db/users/login.php');
    exit();
}

// fetch customer_id securely
$stmtCust = mysqli_prepare($conn, 'SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmtCust, 'i', $current_user_id);
mysqli_stmt_execute($stmtCust);
$resCust = mysqli_stmt_get_result($stmtCust);
$crow = $resCust ? mysqli_fetch_assoc($resCust) : null;
$customer_id = $crow ? (int)$crow['customer_id'] : null;
if (!$customer_id) {
    $_SESSION['message'] = 'Customer profile not found.';
    header('Location: /essence_db/users/profile.php');
    exit();
}

// compute total and validate cart
$total_amount = 0.0;
$cart = isset($_SESSION['cart_products']) && is_array($_SESSION['cart_products']) ? $_SESSION['cart_products'] : [];
if (count($cart) === 0) {
    $_SESSION['message'] = 'Your cart is empty.';
    header('Location: /essence_db/index.php');
    exit();
}
foreach ($cart as $cart_itm) {
    $total_amount += (float)$cart_itm['item_price'] * (int)$cart_itm['item_qty'];
}

// accept payment/delivery/remarks from form
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Online';
$delivery_method = isset($_POST['delivery_method']) ? trim($_POST['delivery_method']) : 'Standard';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
// decide payment_status based on method
$payment_status = in_array($payment_method, ['Cash on Delivery']) ? 'unpaid' : 'paid';

mysqli_begin_transaction($conn);
try {
    // Before creating the order, check inventory availability for each cart item (with row locks)
    $checkStmt = mysqli_prepare($conn, 'SELECT quantity FROM inventory WHERE product_id = ? FOR UPDATE');
    $insufficient = [];
    foreach ($cart as $cart_itm) {
        $product_id = (int)$cart_itm['item_id'];
        $product_qty = (int)$cart_itm['item_qty'];
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, 'i', $product_id);
            mysqli_stmt_execute($checkStmt);
            $res = mysqli_stmt_get_result($checkStmt);
            $r = $res ? mysqli_fetch_assoc($res) : null;
            $avail = $r ? (int)$r['quantity'] : 0;
            if ($avail < $product_qty) {
                $insufficient[] = [
                    'product_name' => $cart_itm['item_name'] ?? ('Product ' . $product_id),
                    'requested' => $product_qty,
                    'available' => $avail
                ];
            }
        }
    }
    if (!empty($insufficient)) {
        mysqli_rollback($conn);
        $msgs = [];
        foreach ($insufficient as $it) {
            $msgs[] = "{$it['product_name']}: requested {$it['requested']}, available {$it['available']}";
        }
        $_SESSION['message'] = 'Insufficient stock for: ' . implode('; ', $msgs);
        header('Location: /essence_db/cart/view_cart.php');
        exit();
    }
    // insert order
    $status = 'pending';

    $stmt1 = mysqli_prepare($conn, 'INSERT INTO orders (customer_id, total_amount, order_date, status, payment_status, delivery_method, remarks) VALUES (?, ?, NOW(), ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt1, 'idssss', $customer_id, $total_amount, $status, $payment_status, $delivery_method, $remarks);
    mysqli_stmt_execute($stmt1);
    $order_id = mysqli_insert_id($conn);

    // prepare order_items and inventory update
    $stmtItem = mysqli_prepare($conn, 'INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)');
    $stmtInv = mysqli_prepare($conn, 'UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?');

    foreach ($cart as $cart_itm) {
        $product_qty = (int)$cart_itm['item_qty'];
        $product_id = (int)$cart_itm['item_id'];
        $price_each = (float)$cart_itm['item_price'];

        mysqli_stmt_bind_param($stmtItem, 'iiid', $order_id, $product_id, $product_qty, $price_each);
        mysqli_stmt_execute($stmtItem);

        mysqli_stmt_bind_param($stmtInv, 'ii', $product_qty, $product_id);
        mysqli_stmt_execute($stmtInv);
    }

    // insert payment record
    $stmtPay = mysqli_prepare($conn, 'INSERT INTO payments (order_id, payment_method, amount_paid, date_paid, reference_no) VALUES (?, ?, ?, NOW(), ?)');
    $ref = null;
    mysqli_stmt_bind_param($stmtPay, 'isds', $order_id, $payment_method, $total_amount, $ref);
    mysqli_stmt_execute($stmtPay);

    mysqli_commit($conn);

    // clear cart
    unset($_SESSION['cart_products']);
    $_SESSION['success'] = 'Order placed successfully.';
    // Redirect to order success/receipt page
    header('Location: /essence_db/cart/order_success.php?id=' . $order_id);
    exit();
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = 'Checkout failed: ' . $e->getMessage();
    header('Location: /essence_db/cart/view_cart.php');
    exit();
}
