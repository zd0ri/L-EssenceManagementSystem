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
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($user_id <= 0 || !in_array($status, ['active','inactive'], true)) {
    $_SESSION['message'] = 'Invalid input.';
    header('Location: users_manage.php');
    exit();
}

if ($user_id === (int)$_SESSION['user_id']) {
    $_SESSION['message'] = 'You cannot change your own status.';
    header('Location: users_manage.php');
    exit();
}

$q = mysqli_prepare($conn, "SELECT role, status FROM users WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($q, 'i', $user_id);
mysqli_stmt_execute($q);
mysqli_stmt_store_result($q);
if (mysqli_stmt_num_rows($q) === 0) {
    $_SESSION['message'] = 'User not found.';
    header('Location: users_manage.php');
    exit();
}
mysqli_stmt_bind_result($q, $role, $curStatus);
mysqli_stmt_fetch($q);

//  ensure at least one active admin remains
if ($status === 'inactive' && $role === 'admin') {
    $adm = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'admin' AND status = 'active'");
    $admRow = $adm ? mysqli_fetch_assoc($adm) : null;
    if ($admRow && (int)$admRow['cnt'] <= 1) {
        $_SESSION['message'] = 'Cannot deactivate the last active admin.';
        header('Location: users_manage.php');
        exit();
    }
}

if ($curStatus === $status) {
    $_SESSION['message'] = 'No change.';
    header('Location: users_manage.php');
    exit();
}

$updateSql = "UPDATE users SET status = ? WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $updateSql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $status, $user_id);
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
