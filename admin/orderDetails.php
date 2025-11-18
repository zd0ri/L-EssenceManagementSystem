<?php
// CREATE VIEW orderdetails AS SELECT o.orderinfo_id, c.lname, c.fname, c.addressline, c.town, c.zipcode, c.phone,  i.sell_price, ol.quantity, i.description, o.status FROM customer c INNER JOIN orderinfo o using(customer_id) INNER JOIN orderline ol USING (orderinfo_id) INNER JOIN item i USING(item_id);

session_start();
// require admin before output
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/header.php');
include('../includes/config.php');

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$_SESSION['orderId'] = $orderId;

// fetch order and customer info using current schema
$sql = "SELECT o.order_id, o.status, o.remarks, o.delivery_method, o.payment_status, o.total_amount, c.fullname, c.address, c.contact FROM orders o INNER JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = {$orderId} LIMIT 1";
$result = mysqli_query($conn, $sql);
$customer = mysqli_fetch_assoc($result);

$sql = "SELECT p.brand_name AS description, oi.quantity, oi.price_each AS sell_price, COALESCE((SELECT path FROM product_images WHERE product_id = oi.product_id ORDER BY product_image_id ASC LIMIT 1), p.image) AS image_path FROM order_items oi INNER JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = {$orderId}";
$items = mysqli_query($conn, $sql);

?>
<div class="admin-page">
    <div class="admin-card">
        <h2>Order #<?= htmlspecialchars($customer['order_id']) ?> </h2>
        <h3><?php echo htmlspecialchars($customer['fullname'] ?? ''); ?></h3>
        <p><?php echo htmlspecialchars(($customer['address'] ?? '') . ' ' . ($customer['contact'] ?? '')); ?></p>

        <div class="mb-3">
            <strong>Delivery method:</strong> <?php echo htmlspecialchars($customer['delivery_method'] ?? ''); ?>
            &nbsp;|&nbsp;
            <strong>Payment status:</strong> <?php echo htmlspecialchars(ucfirst($customer['payment_status'] ?? '')); ?>
            &nbsp;|&nbsp;
            <strong>Order status:</strong> <?php echo htmlspecialchars(ucfirst($customer['status'] ?? '')); ?>
        </div>

<?php if (!empty($customer['remarks'])): ?>
    <div class="card mb-3">
        <div class="card-header">Customer Remarks / Notes</div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($customer['remarks'])); ?></p>
        </div>
    </div>
<?php endif; ?>
<table class="table table-striped table-bordered admin-table">
    <thead>
        <th>Item Name</th>
        <th>Quantity</th>
        <th>Price</th>
        <th>Total</th>
    </thead>

    <?php
    $grandTotal = 0;
    while ($row = mysqli_fetch_assoc($items)) {
        $total = $row['sell_price'] * $row['quantity'];
        $grandTotal += $total;
        echo "<tr>";

        $imgHtml = '';
        if (!empty($row['image_path'])) {
            $p = str_replace('\\', '/', $row['image_path']);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';
            $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
            $imgHtml = '<img src="' . htmlspecialchars($imgUrl) . '" style="width:64px;height:64px;object-fit:cover;border-radius:6px;margin-right:8px;">';
        }

        echo "<td>" . $imgHtml . htmlspecialchars($row['description']) . "</td>";
        echo "<td>" . (int)$row['quantity'] . "</td>";
        echo "<td>₱" . number_format((float)$row['sell_price'],2) . "</td>";

        echo "<td>₱" . number_format((float)$total,2) . "</td>";

        echo "</tr>";
    }
    ?>
</table>
<h4>Total: ₱<?= number_format($grandTotal, 2) ?></h4>
<?php if (!empty($customer['total_amount'])): ?>
    <p><small>Recorded order total: ₱<?= number_format((float)$customer['total_amount'],2) ?></small></p>
<?php endif; ?>
<form action="updateorder.php" method="POST">
<select class="form-select form-control" aria-label="Order status" name="status">
    <option style="color: #5A4939" value="pending" <?= ($customer['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
    <option style="color: #5A4939" value="processing" <?= ($customer['status'] == 'processing') ? 'selected' : '' ?>>Processing</option>
    <option style="color: #5A4939" value="shipped" <?= ($customer['status'] == 'shipped') ? 'selected' : '' ?>>Shipped</option>
    <option style="color: #5A4939" value="completed" <?= ($customer['status'] == 'completed') ? 'selected' : '' ?>>Completed</option>
    <option style="color: #5A4939" value="cancelled" <?= ($customer['status'] == 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
</select>
<button type="submit" class="btn btn-primary">update order</button>
</form>

    </div>
</div>

<?php

include('../includes/footer.php');
?>