<?php 
$db_host = "localhost:3306";
$db_username = "root";
$db_passwd = "";

$conn = mysqli_connect($db_host, $db_username, $db_passwd) or die("Could not connect!\n");

$db_name = "essence_db";
mysqli_select_db($conn, $db_name) or die("Could not select the database $dbname!\n". mysqli_error($conn));
if (file_exists(__DIR__ . '/config.local.php')) {
	include __DIR__ . '/config.local.php';
}

// --- Mail configuration (for Mailtrap) ---
if (!defined('MAIL_HOST')) define('MAIL_HOST', 'smtp.mailtrap.io');
if (!defined('MAIL_PORT')) define('MAIL_PORT', 2525); 
if (!defined('MAIL_USERNAME')) define('MAIL_USERNAME', 'b8290cf40811e8');
if (!defined('MAIL_PASSWORD')) define('MAIL_PASSWORD', '633fb029128043');
if (!defined('MAIL_FROM_ADDRESS')) define('MAIL_FROM_ADDRESS', 'lessenthera@gmail.com');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'L\'Essence');
if (!defined('MAIL_USE_TLS')) define('MAIL_USE_TLS', false);

?>