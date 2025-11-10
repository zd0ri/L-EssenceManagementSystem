<?php
session_start();
// require admin before any output
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/config.php');

// store form inputs temporarily
$_SESSION['brand'] = trim($_POST['brand_name']);
$_SESSION['scent'] = trim($_POST['scent_type']);
$_SESSION['size'] = trim($_POST['size']);
$_SESSION['price'] = trim($_POST['price']);
$_SESSION['desc'] = trim($_POST['description']);
$_SESSION['qty'] = $_POST['quantity'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = trim($_POST['brand_name']);
    $scent = trim($_POST['scent_type']);
    $size = trim($_POST['size']);
    $price = trim($_POST['price']);
    $desc = trim($_POST['description']);
    $qty = $_POST['quantity'];

    // --- Validation ---
    if (empty($brand)) {
        $_SESSION['brandError'] = 'Please enter brand name.';
        header("Location: create.php");
        exit();
    }

    if (empty($price) || !is_numeric($price)) {
        $_SESSION['priceError'] = 'Invalid price format.';
        header("Location: create.php");
        exit();
    }

    // Allow 0 (zero) quantity. Note: PHP considers the string '0' as empty(), so check explicitly.
    if ($qty === '' || !is_numeric($qty) || (int)$qty < 0) {
        $_SESSION['qtyError'] = 'Invalid quantity. Use 0 or a positive integer.';
        header("Location: create.php");
        exit();
    }

    // --- Handle image upload ---
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

    // --- Insert into products table ---
    $sql = "
        INSERT INTO products (brand_name, scent_type, size, price, description, image, status)
        VALUES ('{$brand}', '{$scent}', '{$size}', '{$price}', '{$desc}', '{$target}', 'available')
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die('Product insert error: ' . mysqli_error($conn));
    }

    // --- Get the new product_id ---
    $product_id = mysqli_insert_id($conn);

    // --- Insert into inventory table ---
    $updated_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL';
    $q_inventory = "
        INSERT INTO inventory (product_id, quantity, restock_date, updated_by)
        VALUES ('{$product_id}', '{$qty}', NOW(), {$updated_by})
    ";

    $result2 = mysqli_query($conn, $q_inventory);

    if ($result && $result2) {
        // create product_images table if not exists
        $createImagesTable = "CREATE TABLE IF NOT EXISTS product_images (
            product_image_id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            path VARCHAR(255) NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($conn, $createImagesTable);

        // handle multiple images upload (input name images[])
        $uploadDir = __DIR__ . '/../uploads/products';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $inserted = 0;
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
                        $inserted++;
                    } else {
                        $_SESSION['imageError'][] = 'DB insert failed for ' . $relPath . ': ' . mysqli_error($conn);
                    }
                } else {
                    $_SESSION['imageError'][] = "Failed to move uploaded file for index {$i}.";
                }
            }
            if ($inserted === 0 && !empty($_SESSION['imageError'])) {
                $_SESSION['message'] = 'Image upload failed: ' . implode(' | ', $_SESSION['imageError']);
                header("Location: create.php");
                exit();
            }
        } elseif (isset($target) && $target) {
            // backward compatibility: single image saved previously
            $relPath = $target;
            $relEsc = mysqli_real_escape_string($conn, $relPath);
            mysqli_query($conn, "INSERT INTO product_images (product_id, path) VALUES ({$product_id}, '{$relEsc}')");
        }

        header("Location: index.php");
        exit();
    } else {
        echo "Inventory insert error: " . mysqli_error($conn);
    }
}
?>
