<?php
// Admin sidebar - shared across all admin pages
$isAdminPage = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
$currentPage = basename($_SERVER['REQUEST_URI'], '.php');
?>
<?php if ($isAdminPage): ?>
<aside class="admin-sidebar-shared">
  <div class="list-group">
    <a href="/essence_db/admin/dashboard.php" class="list-group-item list-group-item-action <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
      <i class="fas fa-chart-line me-2"></i>Dashboard
    </a>
    <a href="/essence_db/admin/products.php" class="list-group-item list-group-item-action <?= $currentPage === 'products' ? 'active' : '' ?>">
      <i class="fas fa-box me-2"></i>Products
    </a>
    <a href="/essence_db/admin/orders.php" class="list-group-item list-group-item-action <?= $currentPage === 'orders' ? 'active' : '' ?>">
      <i class="fas fa-receipt me-2"></i>Orders
    </a>
    <a href="/essence_db/admin/users_manage.php" class="list-group-item list-group-item-action <?= $currentPage === 'users_manage' ? 'active' : '' ?>">
      <i class="fas fa-users me-2"></i>Customers
    </a>
    <a href="/essence_db/admin/brands.php" class="list-group-item list-group-item-action <?= $currentPage === 'brands' ? 'active' : '' ?>">
      <i class="fas fa-tag me-2"></i>Brands
    </a>
    <a href="/essence_db/admin/settings.php" class="list-group-item list-group-item-action <?= $currentPage === 'settings' ? 'active' : '' ?>">
      <i class="fas fa-cog me-2"></i>Settings
    </a>
  </div>
</aside>
<?php endif; ?>
