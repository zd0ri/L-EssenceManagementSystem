<?php
// Ensure there's an active session
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// If user is not logged in, redirect to login with message
if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please login to access that page.';
    // attempt to determine login URL relative to this include
    header('Location: /essence_db/users/login.php');
    exit();
}

// If user is logged in but not admin, show an access denied message and redirect back
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'Admin access required to view that page.';
    header('Location: /essence_db/index.php');
    exit();
}

// Passed checks: user is authenticated and is an admin
return true;

?>
