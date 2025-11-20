<?php
session_start();
// require login and config before any output
require_once(__DIR__ . '/../includes/auth.php');
include(__DIR__ . '/../includes/config.php');

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

$total_amount = 0.0;
$all_cart = isset($_SESSION['cart_products']) && is_array($_SESSION['cart_products']) ? $_SESSION['cart_products'] : [];

$selected_ids = isset($_POST['selected_items']) ? array_map('intval', (array)$_POST['selected_items']) : [];
$cart = [];
if (!empty($selected_ids)) {
    foreach ($all_cart as $item) {
        if (in_array((int)$item['item_id'], $selected_ids)) {
            $cart[] = $item;
        }
    }
}

if (count($cart) === 0) {
    $_SESSION['message'] = 'Please select items to checkout.';
    header('Location: /essence_db/cart/view_cart.php');
    exit();
}

foreach ($cart as $cart_itm) {
    $total_amount += (float)$cart_itm['item_price'] * (int)$cart_itm['item_qty'];
}

$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Online';
$delivery_method = isset($_POST['delivery_method']) ? trim($_POST['delivery_method']) : 'Standard';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

$payment_status = in_array($payment_method, ['Cash on Delivery']) ? 'unpaid' : 'paid';

$checkout_success = false;
$redirect_url = null;

mysqli_begin_transaction($conn);
try {
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
        $redirect_url = '/essence_db/cart/view_cart.php';
    } else {
        
        $status = 'pending';

        $stmt1 = mysqli_prepare($conn, 'INSERT INTO orders (customer_id, total_amount, order_date, status, payment_status, delivery_method, remarks) VALUES (?, ?, NOW(), ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt1, 'idssss', $customer_id, $total_amount, $status, $payment_status, $delivery_method, $remarks);
        mysqli_stmt_execute($stmt1);
        $order_id = mysqli_insert_id($conn);
        
    $stmtItem = mysqli_prepare($conn, 'INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)');

        foreach ($cart as $cart_itm) {
            $product_qty = (int)$cart_itm['item_qty'];
            $product_id = (int)$cart_itm['item_id'];
            $price_each = (float)$cart_itm['item_price'];

            mysqli_stmt_bind_param($stmtItem, 'iiid', $order_id, $product_id, $product_qty, $price_each);
            mysqli_stmt_execute($stmtItem);

            // inventory deducted when 'shipped'.
        }
        $stmtPay = mysqli_prepare($conn, 'INSERT INTO payments (order_id, payment_method, amount_paid, date_paid, reference_no) VALUES (?, ?, ?, NOW(), ?)');
        $ref = null;
        mysqli_stmt_bind_param($stmtPay, 'isds', $order_id, $payment_method, $total_amount, $ref);
        mysqli_stmt_execute($stmtPay);

        mysqli_commit($conn);

        // send order confirmation email to customer (if email available)
        include_once __DIR__ . '/../includes/mail.php';
        $custInfoStmt = mysqli_prepare($conn, 'SELECT fullname, email FROM customers WHERE customer_id = ? LIMIT 1');
        $cust = null;
        if ($custInfoStmt) {
            mysqli_stmt_bind_param($custInfoStmt, 'i', $customer_id);
            mysqli_stmt_execute($custInfoStmt);
            $cres = mysqli_stmt_get_result($custInfoStmt);
            $cust = $cres ? mysqli_fetch_assoc($cres) : null;
        }

        if ($cust && !empty($cust['email'])) {
            $items = [];
            $si = mysqli_prepare($conn, 'SELECT oi.product_id, oi.quantity, oi.price_each, p.product_name, b.name as brand_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id LEFT JOIN brands b ON p.brand_id = b.brand_id WHERE oi.order_id = ?');
            if ($si) {
                mysqli_stmt_bind_param($si, 'i', $order_id);
                mysqli_stmt_execute($si);
                $rsi = mysqli_stmt_get_result($si);
                if ($rsi) {
                    while ($row = mysqli_fetch_assoc($rsi)) $items[] = $row;
                }
            }

            $html = "<h3>Order #{$order_id} - Confirmation</h3>";
            $html .= "<p>Hi " . htmlspecialchars($cust['fullname']) . ",</p>";
            $html .= "<p>Thank you for your order. Here are the details:</p>";
            $html .= "<table style='width:100%;border-collapse:collapse'>";
            $html .= "<tr><th style='text-align:left;border-bottom:1px solid #ddd;padding:8px'>Product</th><th style='text-align:right;border-bottom:1px solid #ddd;padding:8px'>Qty</th><th style='text-align:right;border-bottom:1px solid #ddd;padding:8px'>Price</th><th style='text-align:right;border-bottom:1px solid #ddd;padding:8px'>Subtotal</th></tr>";
            $grand = 0.0;
            foreach ($items as $it) {
                $sub = (float)$it['quantity'] * (float)$it['price_each'];
                $grand += $sub;
                $productDisplay = htmlspecialchars($it['product_name']) . (isset($it['brand_name']) && !empty($it['brand_name']) ? ' (' . htmlspecialchars($it['brand_name']) . ')' : '');
                $html .= "<tr><td style='padding:8px;border-bottom:1px solid #f1f1f1'>" . $productDisplay . "</td><td style='padding:8px;border-bottom:1px solid #f1f1f1;text-align:right'>" . (int)$it['quantity'] . "</td><td style='padding:8px;border-bottom:1px solid #f1f1f1;text-align:right'>₱" . number_format((float)$it['price_each'],2) . "</td><td style='padding:8px;border-bottom:1px solid #f1f1f1;text-align:right'>₱" . number_format($sub,2) . "</td></tr>";
            }
            $html .= "<tr><td colspan='3' style='padding:8px;text-align:right'><strong>Total</strong></td><td style='padding:8px;text-align:right'><strong>₱" . number_format($grand,2) . "</strong></td></tr>";
            $html .= "</table>";
            $html .= "<p>If you have any questions reply to this email or contact our support.</p>";

            $sent = smtp_send_mail($cust['email'], $cust['fullname'], "Order #{$order_id} Confirmation", $html);
            if (!$sent) {
                error_log('Failed to send order confirmation email for order ' . $order_id);
            }
        }

    if (!empty($_SESSION['cart_products'])) {
            $remaining_cart = [];
            foreach ($_SESSION['cart_products'] as $item) {
                if (!in_array((int)$item['item_id'], $selected_ids)) {
                    $remaining_cart[] = $item;
                }
            }
            if (count($remaining_cart) > 0) {
                $_SESSION['cart_products'] = $remaining_cart;
            } else {
                unset($_SESSION['cart_products']);
            }
        }
        
        $_SESSION['success'] = 'Order placed successfully.';
        $checkout_success = true;
        $redirect_url = '/essence_db/cart/order_success.php?id=' . $order_id;
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = 'Checkout failed: ' . $e->getMessage();
    $redirect_url = '/essence_db/cart/view_cart.php';
}

if ($redirect_url !== null) {
    header('Location: ' . $redirect_url);
    exit();
}

include('../includes/header.php');
?>
