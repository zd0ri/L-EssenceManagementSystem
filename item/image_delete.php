<?php
session_start();
// require admin before any output
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/header.php');
include('../includes/config.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'Invalid image id.';
    header('Location: index.php');
    exit();
}

$imgId = (int)$_GET['id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

$q = mysqli_query($conn, "SELECT path FROM product_images WHERE product_image_id = {$imgId} LIMIT 1");
if ($q && mysqli_num_rows($q) > 0) {
    $row = mysqli_fetch_assoc($q);
    $path = $row['path'];
    $full = __DIR__ . '/../' . $path;
    if (file_exists($full)) @unlink($full);
    mysqli_query($conn, "DELETE FROM product_images WHERE product_image_id = {$imgId}");
}

$_SESSION['success'] = 'Image deleted';
if ($product_id) header('Location: ../edit.php?id=' . $product_id);
else header('Location: index.php');
exit();
