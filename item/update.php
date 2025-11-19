<?php
session_start();
// require admin before any output
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$brand_id = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;
$product_name = trim($_POST['product_name'] ?? '');
$scent = trim($_POST['scent_type'] ?? '');
$size = trim($_POST['size'] ?? '');
$price = trim($_POST['price'] ?? '');
$desc = trim($_POST['description'] ?? '');
$qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($product_id <= 0) {
    $_SESSION['message'] = 'Invalid product id.';
    header('Location: index.php');
    exit();
}

if ($brand_id <= 0 || $product_name === '' || $price === '' || !is_numeric($price)) {
    $_SESSION['message'] = 'Invalid product data.';
    header('Location: edit.php?id=' . $product_id);
    exit();
}

$target = null;
$createImagesTable = "CREATE TABLE IF NOT EXISTS product_images (
    product_image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $createImagesTable);

$uploadDir = __DIR__ . '/../uploads/products';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $hasNewUpload = false;
    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        if (isset($_FILES['images']['error'][$i]) && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $hasNewUpload = true;
            break;
        }
    }

    if ($hasNewUpload) {
        $existing = mysqli_query($conn, "SELECT product_image_id, path FROM product_images WHERE product_id = {$product_id}");
        if ($existing) {
            while ($row = mysqli_fetch_assoc($existing)) {
                $stored = $row['path'];
                $filePath = __DIR__ . '/../' . str_replace('/', DIRECTORY_SEPARATOR, ltrim($stored, '/'));
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            mysqli_query($conn, "DELETE FROM product_images WHERE product_id = {$product_id}");
        }

        $resImg = mysqli_query($conn, "SELECT image FROM products WHERE product_id = {$product_id} LIMIT 1");
        if ($resImg && mysqli_num_rows($resImg) > 0) {
            $rowImg = mysqli_fetch_assoc($resImg);
            if (!empty($rowImg['image'])) {
                $legacyPath = __DIR__ . '/../' . str_replace('/', DIRECTORY_SEPARATOR, ltrim($rowImg['image'], '/'));
                if (file_exists($legacyPath)) {
                    @unlink($legacyPath);
                }
                mysqli_query($conn, "UPDATE products SET image = '' WHERE product_id = {$product_id}");
            }
        }
    }

    $insertedCount = 0;
    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        if (!isset($_FILES['images']['error'][$i])) continue;
        $err = $_FILES['images']['error'][$i];
        if ($err !== UPLOAD_ERR_OK) {
            if ($err !== UPLOAD_ERR_NO_FILE) {
                $_SESSION['imageError'][] = "Upload error for file index {$i}: code {$err}";
            }
            continue;
        }
        $tmp = $_FILES['images']['tmp_name'][$i];
        $type = mime_content_type($tmp);
        if (!in_array($type, ['image/jpeg','image/png','image/jpg'])) {
            $_SESSION['imageError'][] = "Unsupported file type for file index {$i}: {$type}";
            continue;
        }
        $basename = uniqid('prod_' . $product_id . '_') . '.' . (strpos($type,'png')!==false ? 'png' : 'jpg');
        $targetPath = $uploadDir . '/' . $basename;
        if (move_uploaded_file($tmp, $targetPath)) {
            $relPath = 'uploads/products/' . $basename;
            $relEsc = mysqli_real_escape_string($conn, $relPath);
            if (mysqli_query($conn, "INSERT INTO product_images (product_id, path) VALUES ({$product_id}, '{$relEsc}')")) {
                $insertedCount++;
            } else {
                $_SESSION['imageError'][] = 'DB insert failed for ' . $relPath . ': ' . mysqli_error($conn);
            }
        } else {
            $_SESSION['imageError'][] = "Failed to move uploaded file for index {$i}.";
        }
    }
    if ($hasNewUpload && $insertedCount === 0) {
        if (!empty($_SESSION['imageError'])) {
            $_SESSION['message'] = 'Image upload failed: ' . implode(' | ', $_SESSION['imageError']);
        } else {
            $_SESSION['message'] = 'Image upload failed for unknown reason.';
        }
        header('Location: edit.php?id=' . $product_id);
        exit();
    }
}

$product_name_esc = mysqli_real_escape_string($conn, $product_name);
$scent_esc = mysqli_real_escape_string($conn, $scent);
$size_esc = mysqli_real_escape_string($conn, $size);
$price_esc = mysqli_real_escape_string($conn, $price);
$desc_esc = mysqli_real_escape_string($conn, $desc);

$sql = "UPDATE products SET brand_id={$brand_id}, product_name='{$product_name_esc}', scent_type='{$scent_esc}', size='{$size_esc}', price='{$price_esc}', description='{$desc_esc}'";
if ($target !== null) {
    $target_esc = mysqli_real_escape_string($conn, $target);
    $sql .= ", image='{$target_esc}'";
}
$sql .= " WHERE product_id = {$product_id}";

if (!mysqli_query($conn, $sql)) {
    $_SESSION['message'] = 'Failed to update product: ' . mysqli_error($conn);
    header('Location: edit.php?id=' . $product_id);
    exit();
}
$qty = (int)$qty;
$check = mysqli_query($conn, "SELECT inventory_id FROM inventory WHERE product_id = {$product_id} LIMIT 1");
if ($check && mysqli_num_rows($check) > 0) {
    mysqli_query($conn, "UPDATE inventory SET quantity = {$qty}, restock_date = NOW() WHERE product_id = {$product_id}");
} else {
    $updated_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL';
    mysqli_query($conn, "INSERT INTO inventory (product_id, quantity, restock_date, updated_by) VALUES ({$product_id}, {$qty}, NOW(), {$updated_by})");
}

$_SESSION['success'] = 'Product updated.';
$redirect = 'index.php';
if (isset($_POST['return']) && $_POST['return'] === 'dashboard') {
    $redirect = '/essence_db/admin/dashboard.php';
}
header('Location: ' . $redirect);
exit();
