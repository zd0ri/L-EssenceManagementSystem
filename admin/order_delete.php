<?php
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';
include __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    $_SESSION['message'] = 'Invalid order id.';
    header('Location: orders.php');
    exit();
}

// prevent deleting shipped/completed orders
$check = mysqli_prepare($conn, 'SELECT status FROM orders WHERE order_id = ? LIMIT 1');
if ($check) {
    mysqli_stmt_bind_param($check, 'i', $order_id);
    mysqli_stmt_execute($check);
    $res = mysqli_stmt_get_result($check);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if ($row && in_array($row['status'], ['shipped','completed'])) {
        $_SESSION['message'] = 'Cannot delete an order that has already been shipped or completed.';
        header('Location: orders.php');
        exit();
    }
}

$del = mysqli_prepare($conn, 'DELETE FROM orders WHERE order_id = ?');
if ($del) {
    mysqli_stmt_bind_param($del, 'i', $order_id);
    if (mysqli_stmt_execute($del)) {
        $_SESSION['success'] = 'Order deleted.';
        
        if (function_exists('error_log')) {
            error_log('Admin ' . ($_SESSION['user_id'] ?? 'unknown') . ' deleted order ' . $order_id);
        }
    } else {
        $_SESSION['message'] = 'Failed to delete order.';
    }
} else {
    $_SESSION['message'] = 'Failed to prepare delete statement.';
}

header('Location: orders.php');
exit();
?>