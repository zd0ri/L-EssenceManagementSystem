<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../users/my_orders.php');
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    $_SESSION['message'] = 'Invalid order.';
    header('Location: ../users/my_orders.php');
    exit();
}

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$custStmt = mysqli_prepare($conn, 'SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($custStmt, 'i', $current_user_id);
mysqli_stmt_execute($custStmt);
$cres = mysqli_stmt_get_result($custStmt);
$crow = $cres ? mysqli_fetch_assoc($cres) : null;
$customer_id = $crow ? (int)$crow['customer_id'] : 0;
if (!$customer_id) {
    $_SESSION['message'] = 'Customer profile not found.';
    header('Location: ../users/profile.php');
    exit();
}

$s = mysqli_prepare($conn, 'SELECT status FROM orders WHERE order_id = ? AND customer_id = ? LIMIT 1');
mysqli_stmt_bind_param($s, 'ii', $order_id, $customer_id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$ord = $r ? mysqli_fetch_assoc($r) : null;
if (!$ord) {
    $_SESSION['message'] = 'Order not found.';
    header('Location: ../users/my_orders.php');
    exit();
}

$curStatus = $ord['status'];
$allowed = ['pending','processing'];
if (!in_array($curStatus, $allowed)) {
    $_SESSION['message'] = 'This order cannot be cancelled at this stage.';
    header('Location: ../users/order_view.php?id=' . $order_id);
    exit();
}

$pmq = mysqli_prepare($conn, 'SELECT payment_method FROM payments WHERE order_id = ? LIMIT 1');
mysqli_stmt_bind_param($pmq, 'i', $order_id);
mysqli_stmt_execute($pmq);
$pmr = mysqli_stmt_get_result($pmq);
$pmd = $pmr ? mysqli_fetch_assoc($pmr) : null;
$paymentMethod = $pmd ? trim($pmd['payment_method']) : '';

mysqli_begin_transaction($conn);
try {
    $upd = mysqli_prepare($conn, 'UPDATE orders SET status = ? WHERE order_id = ?');
    $status = 'cancelled';
    mysqli_stmt_bind_param($upd, 'si', $status, $order_id);
    mysqli_stmt_execute($upd);

    if ($paymentMethod !== 'Cash on Delivery' && $paymentMethod !== '') {
        mysqli_query($conn, "UPDATE orders SET payment_status = 'refunded' WHERE order_id = {$order_id}");
        
        mysqli_query($conn, "UPDATE payments SET reference_no = CONCAT(IFNULL(reference_no,''), '|REFUND'), amount_paid = 0 WHERE order_id = {$order_id}");
    }

    mysqli_commit($conn);

    include_once __DIR__ . '/../includes/mail.php';
    $s2 = mysqli_prepare($conn, 'SELECT c.email, c.fullname FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = ? LIMIT 1');
    if ($s2) {
        mysqli_stmt_bind_param($s2, 'i', $order_id);
        mysqli_stmt_execute($s2);
        $res2 = mysqli_stmt_get_result($s2);
        $info = $res2 ? mysqli_fetch_assoc($res2) : null;
        if ($info && !empty($info['email'])) {
            $html = "<p>Hi " . htmlspecialchars($info['fullname']) . ",</p>";
            $html .= "<p>Your order #{$order_id} has been cancelled as requested. ";
            if ($paymentMethod !== 'Cash on Delivery' && $paymentMethod !== '') {
                $html .= "A refund has been processed. Please allow a few business days for the refund to reflect in your account.";
            } else {
                $html .= "Since you chose Cash on Delivery, no online refund was necessary.";
            }
            $html .= "</p><p>If you have questions contact support.</p>";
            smtp_send_mail($info['email'], $info['fullname'], "Order #{$order_id} Cancelled", $html);
        }
    }

    $_SESSION['success'] = 'Order cancelled.';
    header('Location: ../users/my_orders.php');
    exit();
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log('Cancel order failed: ' . $e->getMessage());
    $_SESSION['message'] = 'Failed to cancel order.';
    header('Location: ../users/order_view.php?id=' . $order_id);
    exit();
}

?>
