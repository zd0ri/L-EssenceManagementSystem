<?php
require __DIR__ . '/config.php';
require __DIR__ . '/mail.php';

$to = 'any@example.com';
$res = smtp_send_mail($to, 'Test Recipient', 'Mailtrap SMTP test', '<h3>Mailtrap SMTP test</h3><p>This is a test.</p>');
echo $res ? 'sent' : 'failed - check php error log';