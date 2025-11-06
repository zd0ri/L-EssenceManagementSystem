<?php
session_start();
include('../includes/adminHeader.php');
include('../includes/config.php');
print_r($_SESSION);
// if (!isset($_SESSION['user_id'])) {
//     $_SESSION['message'] = "please Login to access the page";
//     header("Location: ../user/login.php" );
// }
// echo $_GET['search'];
if(isset($_GET['search'])) {
    $keyword = strtolower(trim($_GET['search']));
}
else {
    $keyword = '';
}


if ($keyword) {
    $sql = "SELECT p.*, i.quantity FROM products p LEFT JOIN inventory i ON p.product_id = i.product_id WHERE LOWER(p.brand_name) LIKE '%{$keyword}%' OR LOWER(p.description) LIKE '%{$keyword}%'";
    $result = mysqli_query($conn, $sql);
} else {
    $sql = "SELECT p.*, i.quantity FROM products p LEFT JOIN inventory i ON p.product_id = i.product_id";
    $result = mysqli_query($conn, $sql);
}

$itemCount = mysqli_num_rows($result);
?>


<body>
    <a href="create.php" class="btn btn-primary btn-lg " role="button" aria-disabled="true">Add Item</a></p>
    <h2>number of items <?=$itemCount ?> </h2>
    <table class="table table-striped table-bordered">
        <?php
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            $img = htmlspecialchars($row['image'] ?? '');
            echo "<td><img src='{$img}' width='150' height='150' /> </td>";
            echo "<td>{$row['product_id']}</td>";
            echo "<td>" . htmlspecialchars($row['brand_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['description'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['price'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['quantity'] ?? 0) . "</td>";

            echo "<td><a href='edit.php?id={$row['product_id']}'><i class='fa-regular fa-pen-to-square' style='color: blue'></i></a><a href='delete.php?id={$row['product_id']}'><i class='fa-solid fa-trash' style='color: red'></i></a></td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>
<?php
include('../includes/footer.php');

