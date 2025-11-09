<?php
session_start();
include('../includes/config.php');

// only admin allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['message'] = 'Access denied.';
    header('Location: ../users/login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'Invalid user id.';
    header('Location: users_manage.php');
    exit();
}

$id = (int)$_GET['id'];

// prevent admin from toggling themselves
if ($id === (int)$_SESSION['user_id']) {
    $_SESSION['message'] = 'You cannot change your own status.';
    header('Location: users.php');
    exit();
}

$q = mysqli_query($conn, "SELECT status FROM users WHERE user_id = {$id} LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    $_SESSION['message'] = 'User not found.';
    header('Location: users_manage.php');
    exit();
}
$r = mysqli_fetch_assoc($q);
$new = ($r['status'] === 'active') ? 'inactive' : 'active';

if (mysqli_query($conn, "UPDATE users SET status = '{$new}' WHERE user_id = {$id}")) {
    $_SESSION['message'] = 'User status updated.';
} else {
    $_SESSION['message'] = 'Failed to update status: ' . mysqli_error($conn);
}
header('Location: users_manage.php');
exit();

?>
