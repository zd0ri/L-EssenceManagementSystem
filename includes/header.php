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
 
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="/essence_db/includes/style/style.css" rel="stylesheet">
  <?php
 
  $isAdminPage = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
  if ($isAdminPage) {
    echo '<link href="/essence_db/includes/style/admin.css" rel="stylesheet">' . "\n";
  }
  ?>
  <style>
    
    .admin-sidebar-shared {
      position: static;
      float: left;
      width: 220px;
      background: #fff;
      border-right: 1px solid #dfe6e9;
      padding: 20px 0;
      z-index: 1000;
    }

    body.admin-mode .admin-sidebar-shared .list-group-item {
      background: transparent;
      border: none;
      padding: 12px 20px;
      border-radius: 0;
      margin: 0;
      color: #5A4939;
      font-weight: 500;
      transition: all 0.2s ease;
      cursor: pointer;
      border-left: 3px solid transparent;
    }

    body.admin-mode .admin-sidebar-shared .list-group-item:hover {
      background: #f0e9ff;
      color: #5A4939;
      border-left-color: #5A4939;
    }

    body.admin-mode .admin-sidebar-shared .list-group-item.active {
      background: #f0e9ff;
      color: #5A4939;;
      border-left-color: #5A4939;
    }

   
    .admin-main-content {
      margin-left: 240px;
      padding: 28px;
      min-height: calc(100vh - 80px);
      overflow: visible;
    }

    @media (max-width: 992px) {
      .admin-sidebar-shared {
        float: none;
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #dfe6e9;
      }

      .admin-main-content {
        margin-left: 0;
      }
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous" >
  </script>
  <title>Shop</title>
  <script>
   
    if (window.location.pathname.includes('/admin/')) {
      document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('admin-mode');
      });
    }
  </script>
</head>

<body>
  <nav class="navbar navbar-expand-lg" >
    <div class="container-fluid">
      <a class="navbar-brand" href="/essence_db/index.php">
        <img src="/essence_db/uploads/logo.png" alt="L'Essence" style="height:40px; object-fit:contain;">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent" style="color #white">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php
         
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
          
          $isAdminPage = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
          $isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
          
          if (isset($_SESSION['user_id']) && !$isAdmin && !$isAdminPage) {
              
              echo '<li class="nav-item"><a class="nav-link" href="/essence_db/users/profile.php">Profile</a></li>';
              echo '<li class="nav-item"><a class="nav-link" href="/essence_db/users/my_orders.php">My Orders</a></li>';
          }
          ?>
        </ul>
        
      
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="d-flex me-3">
          <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
          <button class="btn btn-outline-success" type="submit">
            <i class="fa-solid fa-magnifying-glass"></i>
          </button>
        </form>

        
        <div class="d-flex align-items-center">
          <?php if (!isset($isAdmin) || !$isAdmin): ?>
            <a href="/essence_db/users/profile.php" class="text-dark me-3">
              <i class="fa-solid fa-user fa-lg"></i>
            </a>
            <a href="/essence_db/cart/view_cart.php" class="text-dark me-3">
              <i class="fa-solid fa-cart-shopping fa-lg"></i>
            </a>
          <?php endif; ?>

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
  <?php include_once __DIR__ . '/admin_sidebar.php'; ?>
