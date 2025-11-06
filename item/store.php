<?php
session_start();
include('../includes/config.php');

// store form inputs temporarily
$_SESSION['brand'] = trim($_POST['brand_name']);
$_SESSION['scent'] = trim($_POST['scent_type']);
$_SESSION['size'] = trim($_POST['size']);
$_SESSION['price'] = trim($_POST['price']);
$_SESSION['desc'] = trim($_POST['description']);
$_SESSION['qty'] = $_POST['quantity'];

if (isset($_POST['submit'])) {
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

    if (empty($qty) || !is_numeric($qty)) {
        $_SESSION['qtyError'] = 'Invalid quantity.';
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
        header("Location: index.php");
        exit();
    } else {
        echo "Inventory insert error: " . mysqli_error($conn);
    }
}
?>
