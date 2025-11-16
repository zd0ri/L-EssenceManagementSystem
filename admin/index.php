<?php
session_start();
include('../includes/config.php');
include('../includes/header.php');

// admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['message'] = 'Access denied.';
    header('Location: ../users/login.php');
    exit();
}

?>
<div class="admin-page">
    <div class="admin-card">
        <h1 class="mb-4">Admin Dashboard</h1>

    <?php include('../includes/alert.php'); ?>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Items</h5>
                    <p class="card-text small">Manage products — create, edit, delete, and manage images.</p>
                    <a href="/essence_db/item/index.php" class="btn btn-primary">Manage Items</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Orders</h5>
                    <p class="card-text small">View and update order statuses.</p>
                    <a href="/essence_db/admin/orders.php" class="btn btn-primary">Manage Orders</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Users</h5>
                    <p class="card-text small">Activate/deactivate users, update roles, impersonate, delete.</p>
                    <a href="/essence_db/admin/users_manage.php" class="btn btn-primary">Manage Users</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Public Site</h5>
                    <p class="card-text small">Open the public storefront as admin.</p>
                    <a href="/essence_db/index.php" class="btn btn-outline-secondary">View Site</a>
                </div>
            </div>
        </div>
    </div>

    </div>
</div>

<?php include('../includes/footer.php'); ?>
