<?php
session_start();
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/config.php');

$_SESSION['brand_id'] = trim($_POST['brand_id']);
$_SESSION['product_name'] = trim($_POST['product_name']);
$_SESSION['scent'] = trim($_POST['scent_type']);
$_SESSION['size'] = trim($_POST['size']);
$_SESSION['price'] = trim($_POST['price']);
$_SESSION['desc'] = trim($_POST['description']);
$_SESSION['qty'] = $_POST['quantity'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand_id = trim($_POST['brand_id']);
    $product_name = trim($_POST['product_name']);
    $scent = trim($_POST['scent_type']);
    $size = trim($_POST['size']);
    $price = trim($_POST['price']);
    $desc = trim($_POST['description']);
    $rawQty = isset($_POST['quantity']) ? trim($_POST['quantity']) : '';
    if ($rawQty === '') {
        $qty = 1;
    } elseif (is_numeric($rawQty) && (int)$rawQty >= 0) {
        $qty = (int)$rawQty;
    } else {
        $_SESSION['qtyError'] = 'Invalid quantity. Use 0 or a positive integer.';
        header("Location: create.php");
        exit();
    }

    if (empty($brand_id)) {
        $_SESSION['brandError'] = 'Please select a brand.';
        header("Location: create.php");
        exit();
    }

    if (empty($product_name)) {
        $_SESSION['productNameError'] = 'Please enter product name.';
        header("Location: create.php");
        exit();
    }

    if (empty($price) || !is_numeric($price)) {
        $_SESSION['priceError'] = 'Invalid price format.';
        header("Location: create.php");
        exit();
    }

    $target = null;
    if (isset($_FILES['img_path']) && $_FILES['img_path']['error'] == 0) {
        $fileType = $_FILES['img_path']['type'];
        if (in_array($fileType, ["image/jpeg", "image/jpg", "image/png"])) {
            $source = $_FILES['img_path']['tmp_name'];
            $target = 'images/' . basename($_FILES['img_path']['name']);
            move_uploaded_file($source, $target) or die("Couldn't copy image.");
        } else {
            $_SESSION['imageError'] = "Invalid file type — only JPG, JPEG, PNG allowed.";
            header("Location: create.php");
            exit();
        }
    }

    $sql = "
        INSERT INTO products (brand_id, product_name, scent_type, size, price, description, image, status)
        VALUES ({$brand_id}, '" . mysqli_real_escape_string($conn, $product_name) . "', '" . mysqli_real_escape_string($conn, $scent) . "', '" . mysqli_real_escape_string($conn, $size) . "', '{$price}', '" . mysqli_real_escape_string($conn, $desc) . "', '{$target}', 'available')
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die('Product insert error: ' . mysqli_error($conn));
    }

    $product_id = mysqli_insert_id($conn);

    $updated_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL';
    $q_inventory = "INSERT INTO inventory (product_id, quantity, restock_date, updated_by) VALUES ({$product_id}, {$qty}, NOW(), {$updated_by})";

    $result2 = mysqli_query($conn, $q_inventory);

    if ($result && $result2) {
        $createImagesTable = "CREATE TABLE IF NOT EXISTS product_images (
            product_image_id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            path VARCHAR(255) NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($conn, $createImagesTable);

        $uploadDir = __DIR__ . '/../uploads/products';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $imageErrors = [];
        
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $inserted = 0;
            $totalFiles = count($_FILES['images']['name']);
            
            for ($i = 0; $i < $totalFiles; $i++) {
                if (empty($_FILES['images']['name'][$i])) {
                    continue;
                }
                
                $err = $_FILES['images']['error'][$i];
                
                if ($err === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                if ($err !== UPLOAD_ERR_OK) {
                    $imageErrors[] = "Upload error for file {$i}: code {$err}";
                    continue;
                }
                
                $tmp = $_FILES['images']['tmp_name'][$i];
                $filename = $_FILES['images']['name'][$i];
                
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $imageErrors[] = "Unsupported file type for {$filename}: only JPG, JPEG, PNG allowed";
                    continue;
                }
                
                $basename = uniqid('prod_' . $product_id . '_') . '.' . $ext;
                $targetPath = $uploadDir . '/' . $basename;
                
                if (move_uploaded_file($tmp, $targetPath)) {
                    $relPath = 'uploads/products/' . $basename;
                    $relEsc = mysqli_real_escape_string($conn, $relPath);
                    
                    if (mysqli_query($conn, "INSERT INTO product_images (product_id, path) VALUES ({$product_id}, '{$relEsc}')")) {
                        $inserted++;
                    } else {
                        $imageErrors[] = 'DB insert failed for ' . $relPath . ': ' . mysqli_error($conn);
                    }
                } else {
                    $imageErrors[] = "Failed to upload {$filename}";
                }
            }
            
            if ($inserted === 0 && !empty($imageErrors)) {
                $_SESSION['message'] = 'Image upload failed: ' . implode(' | ', $imageErrors);
                header("Location: create.php");
                exit();
            }
        } elseif (isset($target) && $target) {
            $relPath = $target;
            $relEsc = mysqli_real_escape_string($conn, $relPath);
            mysqli_query($conn, "INSERT INTO product_images (product_id, path) VALUES ({$product_id}, '{$relEsc}')");
        }
        unset($_SESSION['brand_id'], $_SESSION['product_name'], $_SESSION['scent'], $_SESSION['size'], $_SESSION['price'], $_SESSION['desc'], $_SESSION['qty']);

        $redirect = 'index.php';
        if (isset($_POST['return']) && $_POST['return'] === 'dashboard') {
            $redirect = '/essence_db/admin/dashboard.php';
        }
        header("Location: " . $redirect);
        exit();
    } else {
        echo "Inventory insert error: " . mysqli_error($conn);
    }

}
?>
