<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please login to continue.';
    header('Location: /essence_db/users/login.php');
    exit();
}

return true;

?>
