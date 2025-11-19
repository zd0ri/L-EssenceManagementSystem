<?php
session_start();
require_once(__DIR__ . '/../includes/admin_auth.php');
include("../includes/config.php");
include("../includes/header.php");

$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$orderId = isset($_SESSION['orderId']) ? (int)$_SESSION['orderId'] : 0;

if ($orderId <= 0 || empty($status)) {
    $_SESSION['message'] = 'Invalid order or status.';
    header("Location: orders.php");
    exit();
}

// check payment method 
$paymentMethod = '';
$paymentMethodQuery = mysqli_prepare($conn, 'SELECT payment_method FROM payments WHERE order_id = ? LIMIT 1');
if ($paymentMethodQuery) {
    mysqli_stmt_bind_param($paymentMethodQuery, 'i', $orderId);
    mysqli_stmt_execute($paymentMethodQuery);
    $paymentResult = mysqli_stmt_get_result($paymentMethodQuery);
    $paymentData = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;
    $paymentMethod = $paymentData ? trim($paymentData['payment_method']) : '';
    error_log("Order {$orderId} - Payment Method: '{$paymentMethod}'");
}

$sql = "UPDATE orders SET status = '{$status}'";
if ($status === 'completed' && trim($paymentMethod) === 'Cash on Delivery') {
    $sql .= ", payment_status = 'paid'";
    error_log("Order {$orderId} - Marking as PAID (COD + Completed)");
} else {
    error_log("Order {$orderId} - NOT marking as paid. Status={$status}, PaymentMethod={$paymentMethod}");
}
$sql .= " WHERE order_id = {$orderId}";

$result = mysqli_query($conn, $sql);
if ($result) {
    $_SESSION['message'] = 'order updated';
    header("Location: orders.php");
    exit();
} else {
    $_SESSION['message'] = 'Could not update order: ' . mysqli_error($conn);
    header("Location: orders.php");
    exit();
}
