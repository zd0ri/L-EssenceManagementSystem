<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['message'] = 'Access denied.';
    header('Location: ../users/login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'Invalid user id.';
    header('Location: users.php');
    exit();
}

$target = (int)$_GET['id'];

if ($target === (int)$_SESSION['user_id']) {
    $_SESSION['message'] = 'Already this user.';
    header('Location: users.php');
    exit();
}

$q = mysqli_query($conn, "SELECT user_id, email, role, status FROM users WHERE user_id = {$target} LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    $_SESSION['message'] = 'User not found.';
    header('Location: users.php');
    exit();
}
$r = mysqli_fetch_assoc($q);
if ($r['status'] !== 'active') {
    $_SESSION['message'] = 'Cannot impersonate an inactive user.';
    header('Location: users.php');
    exit();
}

$_SESSION['impersonator_id'] = $_SESSION['user_id'];
$_SESSION['user_id'] = (int)$r['user_id'];
$_SESSION['email'] = $r['email'];
$_SESSION['role'] = $r['role'];

$_SESSION['message'] = 'You are now impersonating ' . $r['email'] . '. To return, logout and login back as admin.';
header('Location: ../index.php');
exit();

?>
