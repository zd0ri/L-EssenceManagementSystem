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

//ensure at least one active admin remains
$roleQ = mysqli_query($conn, "SELECT role FROM users WHERE user_id = {$id} LIMIT 1");
$roleRow = $roleQ ? mysqli_fetch_assoc($roleQ) : null;
if ($new === 'inactive' && $roleRow && ($roleRow['role'] === 'admin')) {
    $adm = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'admin' AND status = 'active'");
    $admRow = $adm ? mysqli_fetch_assoc($adm) : null;
    if ($admRow && (int)$admRow['cnt'] <= 1) {
        $_SESSION['message'] = 'Cannot deactivate the last active admin.';
        header('Location: users_manage.php');
        exit();
    }
}

$updateSql = "UPDATE users SET status = ? WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $updateSql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $new, $id);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        $_SESSION['message'] = 'User status updated.';
    } else {
        $_SESSION['message'] = 'Failed to update status: ' . mysqli_stmt_error($stmt);
    }
} else {
    $_SESSION['message'] = 'Failed to prepare update.';
}
header('Location: users_manage.php');
exit();

?>
