<?php
session_start();
include("../includes/config.php");
$email_raw = isset($_POST['email']) ? trim($_POST['email']) : '';
$password_raw = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPass = isset($_POST['confirmPass']) ? $_POST['confirmPass'] : '';
$errors = [];

if ($email_raw === '') {
    $errors[] = 'Email is required';
} elseif (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if ($password_raw === '') {
    $errors[] = 'Password is required';
} elseif (strlen($password_raw) < 8) {
    $errors[] = 'Password must be at least 8 characters';
}

if ($password_raw !== $confirmPass) {
    $errors[] = 'Passwords do not match';
}

if (!empty($errors)) {
    $_SESSION['message'] = implode('<br>', $errors);
    header("Location: register.php");
    exit();
}

$email = strtolower($email_raw);

$checkSql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $checkSql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['message'] = 'An account with that email already exists';
        header("Location: register.php");
        exit();
    }
} else {
    $_SESSION['message'] = 'An internal error occurred';
    header("Location: register.php");
    exit();
}

$username_raw = strstr($email, '@', true) ?: $email;
$username = substr(preg_replace('/[^A-Za-z0-9_\-\.]/', '', $username_raw), 0, 50);


$password_hash = sha1($password_raw);

$role = 'customer';

$insertSql = "INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insertSql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ssss', $username, $password_hash, $role, $email);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        $_SESSION['user_id'] = mysqli_insert_id($conn);
        $_SESSION['email'] = $email;
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['message'] = 'Registration failed: ' . mysqli_stmt_error($stmt);
        header("Location: register.php");
        exit();
    }
} else {
    $_SESSION['message'] = 'An internal error occurred';
    header("Location: register.php");
    exit();
}
