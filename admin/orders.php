<?php
session_start();
// require admin before output
require_once(__DIR__ . '/../includes/admin_auth.php');
include('../includes/header.php');
include('../includes/config.php');
//CREATE VIEW  salesPerOrder as SELECT o.orderinfo_id, SUM(i.sell_price * ol.quantity), o.status FROM orderinfo o INNER JOIN orderline ol using (orderinfo_id) INNER JOIN item i USING (item_id)
// GROUP BY o.orderinfo_id;
// $sql = "SELECT o.orderinfo_id as orderId, SUM(i.sell_price * ol.quantity) as total FROM orderinfo o INNER JOIN orderline ol using (orderinfo_id) INNER JOIN item i USING (item_id)
// GROUP BY o.orderinfo_id";

//order details


// fetch orders with customer info
$sql = "SELECT o.order_id, o.total_amount AS total, o.status, o.payment_status, c.fullname, c.email, o.order_date FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id ORDER BY o.order_date DESC";
$result = mysqli_query($conn, $sql);
$itemCount = $result ? mysqli_num_rows($result) : 0;

?>
<div class="admin-page">
    <div class="admin-card">
        <h2 style="color: #5A4939">Number of Orders: <?= $itemCount ?></h2>
        <?php include("../includes/alert.php"); ?>
        <table class="table table-striped table-bordered admin-table">
    <?php
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['order_id']}</td>";
        echo "<td>₱" . number_format((float)$row['total'],2) . "</td>";
        echo "<td>" . htmlspecialchars($row['fullname'] ?? 'Guest') . "</td>";
        echo "<td>" . htmlspecialchars($row['email'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars(ucfirst($row['payment_status'])) . "</td>";
        // status with color
        $color = $row['status'] === 'completed' ? 'green' : 'red';
        echo "<td style='color: {$color}'>" . htmlspecialchars(ucfirst($row['status'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['order_date']) . "</td>";

        // actions: view details and change status (form)
        echo "<td>";
        echo "<a href='orderDetails.php?id={$row['order_id']}' class='btn btn-sm btn-outline-primary me-2'>View</a>";
        echo "<form method='POST' action='update_order_status.php' style='display:inline-block'>";
        echo "<input type='hidden' name='order_id' value='" . (int)$row['order_id'] . "' />";
        echo "<select name='status' class='form-select form-select-sm d-inline-block' style='width:140px; display:inline-block; margin-right:6px;'>";
        $statuses = ['pending','processing','shipped','completed','cancelled'];
        foreach ($statuses as $s) {
            $sel = $s === $row['status'] ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($s) . "' {$sel}>" . ucfirst($s) . "</option>";
        }
        echo "</select>";
        echo "<button type='submit' class='btn btn-sm btn-success'>Save</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";


        
    }
    ?>
        </table>
    </div>
</div>
<?php
include('../includes/footer.php');
?>