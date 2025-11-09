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
    header('Location: users_manage.php');
    exit();
}

$id = (int)$_GET['id'];

// prevent deleting self
if ($id === (int)$_SESSION['user_id']) {
    $_SESSION['message'] = 'You cannot delete your own account.';
    header('Location: users_manage.php');
    exit();
}

// if user is admin, ensure we don't delete the last active admin
$q = mysqli_query($conn, "SELECT role, status FROM users WHERE user_id = {$id} LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    $_SESSION['message'] = 'User not found.';
    header('Location: users_manage.php');
    exit();
}
$row = mysqli_fetch_assoc($q);
if ($row['role'] === 'admin' && $row['status'] === 'active') {
    $adm = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'admin' AND status = 'active'");
    $admRow = mysqli_fetch_assoc($adm);
    if ($admRow && (int)$admRow['cnt'] <= 1) {
        $_SESSION['message'] = 'Cannot delete the last active admin.';
        header('Location: users_manage.php');
        exit();
    }
}

// perform delete (customers will cascade)
if (mysqli_query($conn, "DELETE FROM users WHERE user_id = {$id}")) {
    $_SESSION['message'] = 'User deleted.';
} else {
    $_SESSION['message'] = 'Failed to delete user: ' . mysqli_error($conn);
}

header('Location: users_manage.php');
exit();

?>
