<?php
session_start();
require_once(__DIR__ . '/../includes/auth.php');
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_orders.php');
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    $_SESSION['message'] = 'Invalid order.';
    header('Location: my_orders.php');
    exit();
}

// verify the order belongs to the current user
$current_user_id = (int)($_SESSION['user_id'] ?? 0);
$s = mysqli_prepare($conn, 'SELECT o.order_id FROM orders o JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = ? AND c.user_id = ? LIMIT 1');
mysqli_stmt_bind_param($s, 'ii', $order_id, $current_user_id);
mysqli_stmt_execute($s);
$res = mysqli_stmt_get_result($s);
if (!$res || mysqli_num_rows($res) === 0) {
    $_SESSION['message'] = 'Order not found or not permitted.';
    header('Location: my_orders.php');
    exit();
}

// fetch order items
$items = [];
$si = mysqli_prepare($conn, 'SELECT oi.product_id, oi.quantity, oi.price_each, p.product_name, b.name as brand_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id LEFT JOIN brands b ON p.brand_id = b.brand_id WHERE oi.order_id = ?');
mysqli_stmt_bind_param($si, 'i', $order_id);
mysqli_stmt_execute($si);
$rsi = mysqli_stmt_get_result($si);
if ($rsi) {
    while ($row = mysqli_fetch_assoc($rsi)) $items[] = $row;
}

if (empty($items)) {
    $_SESSION['message'] = 'No items found for this order.';
    header('Location: my_orders.php');
    exit();
}

if (!isset($_SESSION['cart_products']) || !is_array($_SESSION['cart_products'])) $_SESSION['cart_products'] = [];

// For each item, add to cart: if exists increment qty, else set to ordered qty.
foreach ($items as $it) {
    $pid = (int)$it['product_id'];
    $qty = (int)$it['quantity'];

    // check current inventory to avoid adding more than available
    $ci = mysqli_prepare($conn, 'SELECT quantity FROM inventory WHERE product_id = ? LIMIT 1');
    mysqli_stmt_bind_param($ci, 'i', $pid);
    mysqli_stmt_execute($ci);
    $rci = mysqli_stmt_get_result($ci);
    $avail = 0;
    if ($rci && ($rr = mysqli_fetch_assoc($rci))) $avail = (int)$rr['quantity'];

    // fetch product info (price, name)
    $pstmt = mysqli_prepare($conn, 'SELECT product_name, price, image FROM products WHERE product_id = ? LIMIT 1');
    mysqli_stmt_bind_param($pstmt, 'i', $pid);
    mysqli_stmt_execute($pstmt);
    $pres = mysqli_stmt_get_result($pstmt);
    $prow = $pres ? mysqli_fetch_assoc($pres) : null;
    $name = $prow['product_name'] ?? ('Product ' . $pid);
    $price = isset($prow['price']) ? (float)$prow['price'] : (float)$it['price_each'];

    $existingQty = isset($_SESSION['cart_products'][$pid]) ? (int)$_SESSION['cart_products'][$pid]['item_qty'] : 0;
    $desired = $existingQty + $qty;
    // clamp by available inventory (if inventory row absent, allow desired)
    $finalQty = $avail > 0 ? min($desired, $avail) : $desired;

    $_SESSION['cart_products'][$pid] = [
        'item_id' => $pid,
        'item_qty' => $finalQty,
        'item_name' => $name,
        'item_price' => $price,
        'max_qty' => $avail
    ];
}

$_SESSION['success'] = 'Items from order added to your cart.';
header('Location: ../cart/view_cart.php');
exit();
?>