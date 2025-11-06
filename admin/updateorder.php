<?php
session_start();
include("../includes/config.php");
include("../includes/header.php");

$status =$_POST['status'];



$orderId = isset($_SESSION['orderId']) ? (int)$_SESSION['orderId'] : 0;
$sql = "UPDATE orders SET status = '{$status}' WHERE order_id = {$orderId}";
$result = mysqli_query($conn, $sql);
if ($result ) {
    $_SESSION['message'] = 'order updated';
    header("Location: orders.php");
    exit();
} else {
    $_SESSION['message'] = 'Could not update order: ' . mysqli_error($conn);
    header("Location: orders.php");
    exit();
}
