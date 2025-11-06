<?php
session_start();
include("../includes/config.php");
include("../includes/header.php");

$email = trim($_POST['email']);
$password = trim($_POST['password']);
$confirmPass = trim($_POST['confirmPass']);
if ($password !== $confirmPass) {
    $_SESSION['message'] = 'passwords do not match';
    header("Location: register.php");
    exit();
}
$password = sha1($password);

// derive a username from the email (part before @) and escape inputs
$username = mysqli_real_escape_string($conn, strstr($email, '@', true) ?: $email);
$email_esc = mysqli_real_escape_string($conn, $email);
$password_esc = mysqli_real_escape_string($conn, $password);

// users table requires username, password, role, email
$role = 'customer';

$sql = "INSERT INTO users (username, password, role, email) VALUES ('{$username}', '{$password_esc}', '{$role}', '{$email_esc}')";
$result = mysqli_query($conn, $sql);
if ($result) {
    $_SESSION['user_id'] = mysqli_insert_id($conn);
    $_SESSION['email'] = $email_esc;
    header("Location: profile.php");
    exit();
} else {
    $_SESSION['message'] = 'Registration failed: ' . mysqli_error($conn);
    header("Location: register.php");
    exit();
}
