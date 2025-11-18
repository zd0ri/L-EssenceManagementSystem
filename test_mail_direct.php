<?php
// Direct test - no admin required
require_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/mail.php';

echo "Testing Mailtrap Configuration...\n\n";

echo "MAIL_HOST: " . (defined('MAIL_HOST') ? MAIL_HOST : 'NOT SET') . "\n";
echo "MAIL_PORT: " . (defined('MAIL_PORT') ? MAIL_PORT : 'NOT SET') . "\n";
echo "MAIL_USERNAME: " . (defined('MAIL_USERNAME') ? MAIL_USERNAME : 'NOT SET') . "\n";
echo "MAIL_PASSWORD: " . (defined('MAIL_PASSWORD') ? '***' . substr(MAIL_PASSWORD, -3) : 'NOT SET') . "\n";
echo "MAIL_FROM_ADDRESS: " . (defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'NOT SET') . "\n";
echo "MAIL_FROM_NAME: " . (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'NOT SET') . "\n";
echo "MAIL_USE_TLS: " . (defined('MAIL_USE_TLS') ? (MAIL_USE_TLS ? 'true' : 'false') : 'NOT SET') . "\n\n";

echo "Attempting to send test email...\n\n";

$testHtml = '
<h2>Test Email - Order Details</h2>
<p>Order #12345 - Status: Shipped</p>
<table style="width:100%;border-collapse:collapse;margin-top:20px;">
    <tr>
        <th style="text-align:left;border-bottom:1px solid #ddd;padding:8px">Product</th>
        <th style="text-align:right;border-bottom:1px solid #ddd;padding:8px">Qty</th>
        <th style="text-align:right;border-bottom:1px solid #ddd;padding:8px">Price</th>
        <th style="text-align:right;border-bottom:1px solid #ddd;padding:8px">Subtotal</th>
    </tr>
    <tr>
        <td style="padding:8px;border-bottom:1px solid #f1f1f1">Rose Perfume (Essence)</td>
        <td style="padding:8px;border-bottom:1px solid #f1f1f1;text-align:right">2</td>
        <td style="padding:8px;border-bottom:1px solid #f1f1f1;text-align:right">₱1,500.00</td>
        <td style="padding:8px;border-bottom:1px solid #f1f1f1;text-align:right">₱3,000.00</td>
    </tr>
    <tr>
        <td style="padding:8px;border-bottom:1px solid #f1f1f1">Lavender Spray (Essence)</td>
        <td style="padding:8px;border-bottom:1px solid #f1f1f1;text-align:right">1</td>
        <td style="padding:8px;border-bottom:1px solid #f1f1f1;text-align:right">₱999.00</td>
        <td style="padding:8px;border-bottom:1px solid #f1f1f1;text-align:right">₱999.00</td>
    </tr>
    <tr>
        <td colspan="3" style="padding:8px;text-align:right"><strong>Total</strong></td>
        <td style="padding:8px;text-align:right"><strong>₱3,999.00</strong></td>
    </tr>
</table>
<p style="margin-top:20px">Thank you for your order!</p>
';

$result = smtp_send_mail('test@example.com', 'Test Customer', 'Test Order #12345', $testHtml);

if ($result) {
    echo "✓ Email sent successfully!\n";
    echo "Check your Mailtrap inbox: https://mailtrap.io/inboxes\n";
} else {
    echo "✗ Email failed to send.\n";
    echo "Check PHP error logs for details.\n";
}
?>
