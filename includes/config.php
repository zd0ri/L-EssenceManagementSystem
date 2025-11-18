<?php 
$db_host = "localhost:3306";
$db_username = "root";
$db_passwd = "";

$conn = mysqli_connect($db_host, $db_username, $db_passwd) or die("Could not connect!\n");

// echo "Connection established.\n";
$db_name = "essence_db";
mysqli_select_db($conn, $db_name) or die("Could not select the database $dbname!\n". mysqli_error($conn));

// Load local config (keeps SMTP credentials out of VCS)
if (file_exists(__DIR__ . '/config.local.php')) {
	include __DIR__ . '/config.local.php';
}

// --- Mail configuration (for Mailtrap) ---
// Define defaults only if not provided by config.local.php
if (!defined('MAIL_HOST')) define('MAIL_HOST', 'smtp.mailtrap.io');
if (!defined('MAIL_PORT')) define('MAIL_PORT', 2525); // 2525 often works with Mailtrap
if (!defined('MAIL_USERNAME')) define('MAIL_USERNAME', 'b8290cf40811e8');
if (!defined('MAIL_PASSWORD')) define('MAIL_PASSWORD', '633fb029128043');
if (!defined('MAIL_FROM_ADDRESS')) define('MAIL_FROM_ADDRESS', 'lessenthera@gmail.com');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'L\'Essence');
if (!defined('MAIL_USE_TLS')) define('MAIL_USE_TLS', false); // set true if using port 587 and STARTTLS

// NOTE: Send API support removed. Use Mailtrap Inbox (SMTP) credentials above
// to send test emails to the Mailtrap sandbox/inbox. Keep MAIL_USE_TLS as
// needed (false for port 2525, true if using 587 with STARTTLS).

?>