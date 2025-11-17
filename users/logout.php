<?php
session_start();
// set a flash message so the header alert can show confirmation
$_SESSION['success'] = 'You have been logged out.';
// remove authentication/session-identifying values but keep session for flash
unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['role']);
header("Location: login.php");
exit;