<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['message'] = 'Access denied.';
    header('Location: ../users/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users_manage.php');
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$role = isset($_POST['role']) ? trim($_POST['role']) : '';

// only allow 'admin' or 'customer'
$allowedRoles = ['admin', 'customer'];
if ($user_id <= 0 || !in_array($role, $allowedRoles, true)) {
    $_SESSION['message'] = 'Invalid input.';
    header('Location: users_manage.php');
    exit();
}

// only update role for active users
$q = mysqli_query($conn, "SELECT status FROM users WHERE user_id = {$user_id} LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    $_SESSION['message'] = 'User not found.';
    header('Location: users_manage.php');
    exit();
}
$row = mysqli_fetch_assoc($q);
if ($row['status'] !== 'active') {
    $_SESSION['message'] = 'Can only update role for active users.';
    header('Location: users_manage.php');
    exit();
}

// prevent demoting last admin: ensure at least one admin remains
if ($role !== 'admin') {
    $adm = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'admin' AND status = 'active'");
    $admRow = mysqli_fetch_assoc($adm);
    if ($admRow && (int)$admRow['cnt'] <= 1) {
        $_SESSION['message'] = 'Cannot remove admin role — at least one active admin required.';
        header('Location: users_manage.php');
        exit();
    }
}

// perform update using prepared statement
$updateSql = "UPDATE users SET role = ? WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $updateSql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $role, $user_id);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        $_SESSION['message'] = 'User role updated.';
    } else {
        $_SESSION['message'] = 'Failed to update role: ' . mysqli_stmt_error($stmt);
    }
} else {
    $_SESSION['message'] = 'Failed to prepare update statement.';
}

header('Location: users_manage.php');
exit();

?>
