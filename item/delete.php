<?php
session_start();
include('../includes/config.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	$_SESSION['message'] = 'Invalid product id.';
	header('Location: index.php');
	exit();
}

$id = (int)$_GET['id'];

// fetch product to remove image file
$q = mysqli_query($conn, "SELECT image FROM products WHERE product_id = {$id} LIMIT 1");
if ($q && mysqli_num_rows($q) > 0) {
	$row = mysqli_fetch_assoc($q);
	$img = $row['image'];
	if (!empty($img) && file_exists(__DIR__ . '/' . $img)) {
		@unlink(__DIR__ . '/' . $img);
	}
}

// remove inventory then product
mysqli_query($conn, "DELETE FROM inventory WHERE product_id = {$id}");
mysqli_query($conn, "DELETE FROM products WHERE product_id = {$id}");

$_SESSION['success'] = 'Product deleted.';
header('Location: index.php');
exit();
