<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Handle POST (login)
if (isset($_POST['submit'])) {
        $email_raw = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password_raw = isset($_POST['password']) ? trim($_POST['password']) : '';
        $errors = [];

        if ($email_raw === '') {
                $errors[] = 'Email is required';
        } elseif (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
        }
        if ($password_raw === '') {
                $errors[] = 'Password is required';
        }

        if (!empty($errors)) {
                $_SESSION['message'] = implode('<br>', $errors);
        } else {
                $email = strtolower($email_raw);
                $pass = sha1($password_raw);
                $sql = "SELECT user_id, email, role FROM users WHERE email=? AND password=? LIMIT 1";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'ss', $email, $pass);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_store_result($stmt);
                        mysqli_stmt_bind_result($stmt, $user_id, $db_email, $role);
                        if (mysqli_stmt_num_rows($stmt) === 1) {
                                mysqli_stmt_fetch($stmt);
                                $_SESSION['email'] = $db_email;
                                $_SESSION['user_id'] = $user_id;
                                $_SESSION['role'] = $role;
                                // Redirect admins to dashboard, regular users to home
                                if ($role === 'admin') {
                                        header("Location: ../admin/dashboard.php");
                                } else {
                                        header("Location: ../index.php");
                                }
                                exit();
                        } else {
                                $_SESSION['message'] = 'Wrong email or password';
                        }
                } else {
                        $_SESSION['message'] = 'An internal error occurred';
                }
        }
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - L'Essence</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/essence_db/includes/style/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-wrapper">
            <!-- Left: Login Form -->
            <div class="login-form-section">
                <div class="login-form-inner">
                    <?php include __DIR__ . '/../includes/alert.php'; ?>
                    <h2 class="mb-1">L'Essence</h2>
                    <p class="text-muted mb-4">The essence of you, bottled beautifully.</p>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="mb-3">
                            <input type="text" class="form-control form-control-sm" id="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="Password">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3 small">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me 30 days</label>
                            </div>
                            <div><a href="#" class="text-decoration-none">Forgot Password?</a></div>
                        </div>

                        <button type="submit" name="submit" class="btn btn-primary btn-rounded w-100 mb-4">Login</button>

                    <div class="text-center small text-muted">
                        <span>New User? <a href="register.php" class="text-decoration-none fw-semibold text-primary">Create Account</a></span>
                    </div>
                </div>
            </div>

            <!-- Right: Carousel -->
            <div class="login-carousel-section">
                <?php
                // load a few product images for the carousel
                $carouselImages = [];
                $q = "SELECT COALESCE(pi.path, p.image) AS img_path, p.brand_name, p.scent_type
                      FROM products p
                      LEFT JOIN product_images pi ON p.product_id = pi.product_id
                      WHERE p.status = 'available' AND (pi.path IS NOT NULL OR p.image IS NOT NULL)
                      GROUP BY COALESCE(pi.path, p.image)
                      ORDER BY p.product_id DESC
                      LIMIT 6";
                $res = mysqli_query($conn, $q);
                if ($res && mysqli_num_rows($res) > 0) {
                    while ($r = mysqli_fetch_assoc($res)) {
                        $carouselImages[] = $r;
                    }
                }

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/essence_db/';

                if (count($carouselImages) > 0) {
                ?>
                <div id="loginCarousel" class="carousel slide login-carousel" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($carouselImages as $i => $imgData):
                            $p = str_replace('\\', '/', $imgData['img_path']);
                            $imgUrl = preg_match('#^https?://#i', $p) ? $p : $baseUrl . ltrim($p, '/');
                            $active = $i === 0 ? ' active' : ''; ?>
                            <div class="carousel-item<?php echo $active; ?>">
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" class="d-block w-100 carousel-img" alt="product">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-indicators">
                        <?php foreach ($carouselImages as $i => $img): ?>
                            <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="<?php echo $i; ?>" <?php echo $i === 0 ? 'class="active"' : ''; ?>></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php } else { ?>
                <div class="login-placeholder">
                    <h2>L'Essence</h2>
                    <p>Discover curated scents</p>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
