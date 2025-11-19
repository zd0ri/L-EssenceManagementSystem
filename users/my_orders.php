<?php
session_start();
require_once(__DIR__ . '/../includes/auth.php');
include('../includes/header.php');
include('../includes/config.php');

$current_user_id = (int)($_SESSION['user_id'] ?? 0);
$stmt = mysqli_prepare($conn, 'SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $current_user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$c = $res ? mysqli_fetch_assoc($res) : null;
$customer_id = $c ? (int)$c['customer_id'] : null;

if (!$customer_id) {
    $_SESSION['message'] = 'Customer profile not found. Please complete your profile.';
    header('Location: profile.php');
    exit();
}

$sql = 'SELECT o.order_id, o.total_amount, o.status, o.payment_status, o.order_date FROM orders o WHERE o.customer_id = ? ORDER BY o.order_date DESC';
$stmt2 = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt2, 'i', $customer_id);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);

?>
<div class="container mt-4">
    <?php include('../includes/alert.php'); ?>
    <h2>My Orders</h2>
    <p>Below are the orders you've placed. Click "View" to see details, or "Buy Again" to add all items from that order into your cart.</p>
    <table class="table table-striped">
        <thead>
            <tr><th>Order</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php
        if ($res2 && mysqli_num_rows($res2) > 0) {
            while ($row = mysqli_fetch_assoc($res2)) {
                echo '<tr>';
                echo '<td>' . (int)$row['order_id'] . '</td>';
                echo '<td>' . htmlspecialchars($row['order_date']) . '</td>';
                echo '<td>₱' . number_format((float)$row['total_amount'],2) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($row['payment_status'])) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($row['status'])) . '</td>';
                echo '<td>';
                echo "<a class='btn btn-sm btn-outline-primary me-2' href='order_view.php?id=" . (int)$row['order_id'] . "'>View</a>";
                // Buy Again form
                echo "<form method='POST' action='buy_again.php' style='display:inline-block'>";
                echo "<input type='hidden' name='order_id' value='" . (int)$row['order_id'] . "' />";
                echo "<button class='btn btn-sm btn-success' type='submit'>Buy Again</button>";
                echo "</form>";
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">You have no orders yet.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>
<?php include('../includes/footer.php'); ?>
