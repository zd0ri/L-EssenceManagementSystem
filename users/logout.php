<?php
session_start();
$_SESSION['success'] = 'You have been logged out.';
unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['role']);
header("Location: login.php");
exit;