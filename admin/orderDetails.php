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
$sql = "SELECT o.order_id, o.status, c.fullname, c.address, c.contact FROM orders o INNER JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = {$orderId} LIMIT 1";
$result = mysqli_query($conn, $sql);
$customer = mysqli_fetch_assoc($result);

$sql = "SELECT p.brand_name AS description, oi.quantity, oi.price_each AS sell_price FROM order_items oi INNER JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = {$orderId}";
$items = mysqli_query($conn, $sql);

?>
<h2><?= $customer['order_id'] ?> </h2>
<h3><?php echo htmlspecialchars($customer['fullname'] ?? ''); ?></h3>
<p><?php echo htmlspecialchars(($customer['address'] ?? '') . ' ' . ($customer['contact'] ?? '')); ?></p>
<table class="table table-striped table-bordered">
    <thead>
        <th>item name</th>
        <th>quantity</th>
        <th>price</th>
        <th>total</th>
    </thead>

    <?php
    $grandTotal = 0;
    while ($row = mysqli_fetch_assoc($items)) {
        $total = $row['sell_price'] * $row['quantity'];
        $grandTotal += $total;
        echo "<tr>";

        echo "<td>{$row['description']}</td>";
        echo "<td>{$row['quantity']} </td>";
        echo "<td>{$row['sell_price']}</td>";

        echo "<td>{$total}</td>";



        echo "</tr>";
    }
    ?>
</table>
<h4><?= number_format($grandTotal, 2) ?></h4>
<form action="updateorder.php" method="POST">
<select class="form-select form-control" aria-label="Order status" name="status">
    <option value="pending" <?= ($customer['status'] == 'pending') ? 'selected' : '' ?>>pending</option>
    <option value="processing" <?= ($customer['status'] == 'processing') ? 'selected' : '' ?>>processing</option>
    <option value="shipped" <?= ($customer['status'] == 'shipped') ? 'selected' : '' ?>>shipped</option>
    <option value="completed" <?= ($customer['status'] == 'completed') ? 'selected' : '' ?>>completed</option>
    <option value="cancelled" <?= ($customer['status'] == 'cancelled') ? 'selected' : '' ?>>cancelled</option>
</select>
<button type="submit" class="btn btn-primary">update order</button>
</form>

<?php

include('../includes/footer.php');
?>