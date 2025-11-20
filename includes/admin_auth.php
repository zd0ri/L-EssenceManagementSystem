<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();


if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please login to access that page.';
    
    header('Location: /essence_db/users/login.php');
    exit();
}

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'Admin access required to view that page.';
    header('Location: /essence_db/index.php');
    exit();
}
return true;

?>
