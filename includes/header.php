<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- Elegant/classy fonts for the site (headings + body) -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="/essence_db/includes/style/style.css" rel="stylesheet">
  <?php
  // Load admin theme CSS if on an admin page
  $isAdminPage = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
  if ($isAdminPage) {
    echo '<link href="/essence_db/includes/style/admin.css" rel="stylesheet">' . "\n";
  }
  ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
  </script>
  <title>Shop</title>
  <script>
    // Detect if on an admin page and add admin-mode class
    if (window.location.pathname.includes('/admin/')) {
      document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('admin-mode');
      });
    }
  </script>
</head>

<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">L'Essence</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php
          // Show Dashboard for admins, Home for regular users
          if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
              echo '<li class="nav-item">';
              echo '<a class="nav-link active" aria-current="page" href="/essence_db/admin/dashboard.php">Dashboard</a>';
              echo '</li>';
          } else {
              echo '<li class="nav-item">';
              echo '<a class="nav-link active" aria-current="page" href="/essence_db/index.php">Home</a>';
              echo '</li>';
          }
          ?>
          <li class="nav-item">
            <a class="nav-link" href="/essence_db/about.php">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/essence_db/brands.php">Brands</a>
          </li>
          <?php
          // Show admin dropdown only for admins. For regular users show Profile/My Orders links directly.
          if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
              echo '<li class="nav-item dropdown">';
              echo '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin</a>';
              echo '<ul class="dropdown-menu">';
              echo "<li><a class='dropdown-item' href='/essence_db/admin/products.php'>Products</a></li>";
              echo "<li><a class='dropdown-item' href='/essence_db/admin/orders.php'>Orders</a></li>";
              echo "<li><a class='dropdown-item' href='/essence_db/admin/users_manage.php'>Customers</a></li>";
              echo "<li><a class='dropdown-item' href='/essence_db/admin/brands.php'>Brands</a></li>";
              echo "<li><a class='dropdown-item' href='/essence_db/admin/settings.php'>Settings</a></li>";
              echo '</ul>';
              echo '</li>';
          } elseif (isset($_SESSION['user_id'])) {
              // regular logged-in user: show Profile and My Orders as normal nav items
              echo '<li class="nav-item"><a class="nav-link" href="/essence_db/users/profile.php">Profile</a></li>';
              echo '<li class="nav-item"><a class="nav-link" href="/essence_db/users/my_orders.php">My Orders</a></li>';
          }
          ?>
        </ul>
        
        <!-- Search bar (single across site) -->
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="d-flex me-3">
          <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
          <button class="btn btn-outline-success" type="submit">
            <i class="fa-solid fa-magnifying-glass"></i>
          </button>
        </form>

        <!-- Icons section -->
        <div class="d-flex align-items-center">
          <a href="/essence_db/users/profile.php" class="text-dark me-3">
            <i class="fa-solid fa-user fa-lg"></i>
          </a>
          <a href="/essence_db/cart/view_cart.php" class="text-dark me-3">
            <i class="fa-solid fa-cart-shopping fa-lg"></i>
          </a>

          <?php
          if (!isset($_SESSION['user_id'])) {
            echo "<a href='/essence_db/users/login.php' class='btn btn-outline-primary'>Login</a>";
          } else {
            echo "<span class='me-2'>{$_SESSION['email']}</span>";
            echo "<a href='/essence_db/users/logout.php' class='btn btn-outline-danger'>Logout</a>";
          }
          ?>
        </div>
      </div>
    </div>
  </nav>
  <?php include_once __DIR__ . '/alert.php'; ?>
