<?php
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';
include __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$allowed = ['pending','processing','shipped','completed','cancelled'];
if ($order_id <= 0 || !in_array($status, $allowed)) {
    $_SESSION['message'] = 'Invalid input.';
    header('Location: orders.php');
    exit();
}

// Check payment method to determine if payment_status should be updated
$paymentMethod = '';
$paymentMethodQuery = mysqli_prepare($conn, 'SELECT payment_method FROM payments WHERE order_id = ? LIMIT 1');
if ($paymentMethodQuery) {
    mysqli_stmt_bind_param($paymentMethodQuery, 'i', $order_id);
    mysqli_stmt_execute($paymentMethodQuery);
    $paymentResult = mysqli_stmt_get_result($paymentMethodQuery);
    $paymentData = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;
    $paymentMethod = $paymentData ? trim($paymentData['payment_method']) : '';
    error_log("Order {$order_id} - Payment Method: '{$paymentMethod}'");
}

// Only auto-mark as paid if status is "completed" AND payment method is "Cash on Delivery"
$updateQuery = 'UPDATE orders SET status = ?';
if ($status === 'completed' && trim($paymentMethod) === 'Cash on Delivery') {
    $updateQuery .= ', payment_status = "paid"';
    error_log("Order {$order_id} - Marking as PAID (COD + Completed)");
} else {
    error_log("Order {$order_id} - NOT marking as paid. Status={$status}, PaymentMethod={$paymentMethod}");
}
$updateQuery .= ' WHERE order_id = ?';

$stmt = mysqli_prepare($conn, $updateQuery);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $status, $order_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Order status updated.';
        // after updating status, send notification email to customer with order details
        include_once __DIR__ . '/../includes/mail.php';
        // fetch order and customer email
        $s = mysqli_prepare($conn, 'SELECT o.order_id, o.total_amount, o.status, o.payment_status, c.email, c.fullname FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = ? LIMIT 1');
        if ($s) {
            mysqli_stmt_bind_param($s, 'i', $order_id);
            mysqli_stmt_execute($s);
            $res = mysqli_stmt_get_result($s);
            $ord = $res ? mysqli_fetch_assoc($res) : null;
            if ($ord && !empty($ord['email'])) {
                // fetch order items
                $items = [];
                $si = mysqli_prepare($conn, 'SELECT oi.product_id, oi.quantity, oi.price_each, p.brand_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?');
                if ($si) {
                    mysqli_stmt_bind_param($si, 'i', $order_id);
                    mysqli_stmt_execute($si);
                    $rsi = mysqli_stmt_get_result($si);
                    if ($rsi) {
                        while ($row = mysqli_fetch_assoc($rsi)) $items[] = $row;
                    }
                }

                // build email HTML
                $paymentStatus = ucfirst($ord['payment_status']);
                $html = "<h3>Order #{$order_id} - Status updated to " . htmlspecialchars(ucfirst($status));
                if ($status === 'completed') {
                    $html .= " (Payment: " . htmlspecialchars($paymentStatus) . ")";
                }
                $html .= "</h3>";
                $html .= "<p>Hi " . htmlspecialchars($ord['fullname']) . ",</p>";
                $html .= "<p>Your order status has been updated. Below are the order details:</p>";
                $html .= "<table style='width:100%;border-collapse:collapse'>";
                $html .= "<tr><th style='text-align:left;border-bottom:1px solid #ddd;padding:8px'>Product</th><th style='text-align:right;border-bottom:1px solid #ddd;padding:8px'>Qty</th><th style='text-align:right;border-bottom:1px solid #ddd;padding:8px'>Price</th><th style='text-align:right;border-bottom:1px solid #ddd;padding:8px'>Subtotal</th></tr>";
                $grand = 0.0;
                foreach ($items as $it) {
                    $sub = (float)$it['quantity'] * (float)$it['price_each'];
                    $grand += $sub;
                    $html .= "<tr><td style='padding:8px;border-bottom:1px solid #f1f1f1'>" . htmlspecialchars($it['brand_name']) . "</td><td style='padding:8px;border-bottom:1px solid #f1f1f1;text-align:right'>" . (int)$it['quantity'] . "</td><td style='padding:8px;border-bottom:1px solid #f1f1f1;text-align:right'>₱" . number_format((float)$it['price_each'],2) . "</td><td style='padding:8px;border-bottom:1px solid #f1f1f1;text-align:right'>₱" . number_format($sub,2) . "</td></tr>";
                }
                $html .= "<tr><td colspan='3' style='padding:8px;text-align:right'><strong>Total</strong></td><td style='padding:8px;text-align:right'><strong>₱" . number_format($grand,2) . "</strong></td></tr>";
                $html .= "</table>";
                $html .= "<p>If you have any questions, reply to this email or contact our support.</p>";

                // send email
                $sent = smtp_send_mail($ord['email'], $ord['fullname'], "Order #{$order_id} status updated", $html);
                if (!$sent) {
                    error_log('Failed to send order update email for order ' . $order_id);
                }
            }
        }
    } else {
        $_SESSION['message'] = 'Failed to update order.';
    }
} else {
    $_SESSION['message'] = 'Failed to prepare statement.';
}

header('Location: orders.php');
exit();

?>
