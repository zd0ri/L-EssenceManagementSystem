<?php 
$db_host = "localhost:3306";
$db_username = "root";
$db_passwd = "";

$conn = mysqli_connect($db_host, $db_username, $db_passwd) or die("Could not connect!\n");

// echo "Connection established.\n";
$db_name = "essence_db";
mysqli_select_db($conn, $db_name) or die("Could not select the database $dbname!\n". mysqli_error($conn));
// --- Mail configuration (for Mailtrap) ---
// Fill these with your Mailtrap SMTP credentials
define('MAIL_HOST', 'smtp.mailtrap.io');
define('MAIL_PORT', 2525); // 2525 often works with Mailtrap
define('MAIL_USERNAME', 'your_mailtrap_username');
define('MAIL_PASSWORD', 'your_mailtrap_password');
define('MAIL_FROM_ADDRESS', 'no-reply@example.com');
define('MAIL_FROM_NAME', 'L\'Essence');
define('MAIL_USE_TLS', false); // set true if using port 587 and STARTTLS

?>