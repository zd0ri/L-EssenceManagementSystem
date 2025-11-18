<?php
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/mail.php';

?>

<div class="admin-page">
    <div class="admin-card">
        <h2>Mailtrap Email Test</h2>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
            $testEmail = 'test@example.com'; // Mailtrap catches all emails
            $testSubject = 'L\'Essence - Test Email ' . date('Y-m-d H:i:s');
            $testHtml = '
                <h2>Test Email from L\'Essence</h2>
                <p>This is a test email to verify Mailtrap integration.</p>
                <h3>Order Details Example</h3>
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
            
            echo '<div class="alert alert-info">Sending test email to Mailtrap...</div>';
            $result = smtp_send_mail($testEmail, 'Test User', $testSubject, $testHtml);
            
            if ($result) {
                echo '<div class="alert alert-success"><strong>Success!</strong> Email sent to Mailtrap sandbox. Check your Mailtrap inbox: <a href="https://mailtrap.io/inboxes" target="_blank">https://mailtrap.io/inboxes</a></div>';
            } else {
                echo '<div class="alert alert-danger"><strong>Failed!</strong> Email was not sent. Check PHP error logs for details.</div>';
            }
        }
        ?>
        
        <form method="POST">
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Mailtrap Configuration</strong>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Host:</strong> <code><?php echo defined('MAIL_HOST') ? MAIL_HOST : 'NOT SET'; ?></code>
                    </div>
                    <div class="mb-2">
                        <strong>Port:</strong> <code><?php echo defined('MAIL_PORT') ? MAIL_PORT : 'NOT SET'; ?></code>
                    </div>
                    <div class="mb-2">
                        <strong>Username:</strong> <code><?php echo defined('MAIL_USERNAME') ? MAIL_USERNAME : 'NOT SET'; ?></code>
                    </div>
                    <div class="mb-2">
                        <strong>Password:</strong> <code><?php echo defined('MAIL_PASSWORD') ? '***' . substr(MAIL_PASSWORD, -3) : 'NOT SET'; ?></code>
                    </div>
                    <div class="mb-2">
                        <strong>From Address:</strong> <code><?php echo defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'NOT SET'; ?></code>
                    </div>
                    <div class="mb-2">
                        <strong>From Name:</strong> <code><?php echo defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'NOT SET'; ?></code>
                    </div>
                    <div class="mb-2">
                        <strong>Use TLS:</strong> <code><?php echo defined('MAIL_USE_TLS') ? (MAIL_USE_TLS ? 'Yes' : 'No') : 'NOT SET'; ?></code>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="send_test" class="btn btn-primary">Send Test Email to Mailtrap</button>
        </form>
        
        <hr />
        
        <div class="card mt-4">
            <div class="card-header">
                <strong>Instructions</strong>
            </div>
            <div class="card-body">
                <ol>
                    <li>Click "Send Test Email to Mailtrap" button above</li>
                    <li>If successful, you'll see a success message</li>
                    <li>Visit <a href="https://mailtrap.io/inboxes" target="_blank">Mailtrap Inbox</a> to view the email</li>
                    <li>If you get an error, check the PHP error logs for connection details</li>
                    <li>Once test email works, all order status notifications should send automatically</li>
                </ol>
                
                <h5 style="margin-top:20px">Troubleshooting:</h5>
                <ul>
                    <li>Make sure Mailtrap credentials are correct in <code>includes/config.php</code></li>
                    <li>Ensure your firewall allows outbound connections to <code>smtp.mailtrap.io:2525</code></li>
                    <li>Check PHP error logs at <code>C:\xampp\apache\logs</code></li>
                </ul>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
