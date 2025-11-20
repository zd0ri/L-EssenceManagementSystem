<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - L'Essence</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/essence_db/includes/style/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-wrapper">
            <div class="login-form-section">
                <div class="login-form-inner">
                    <?php include __DIR__ . '/../includes/alert.php'; ?>
                    <h2 class="mb-1">L'Essence</h2>
                    <p class="text-muted mb-4">The essence of you, bottled beautifully.</p>
                    
                    <form method="POST" action="store.php">
                        <div class="mb-3">
                            <input type="text" class="form-control form-control-sm" id="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="Password">
                        </div>

                        <div class="mb-3">
                            <input type="password" class="form-control form-control-sm" id="confirmPass" name="confirmPass" placeholder="Confirm password">
                        </div>

                        <button type="submit" class="btn btn-primary btn-rounded w-100 mb-4">Create Account</button>
                    </form>

                    <div class="text-center small text-muted">
                        <span>Already have an account? <a href="login.php" class="text-decoration-none fw-semibold text-primary">Login</a></span>
                    </div>
                </div>
            </div>

            <!-- Right: Carousel -->
            <div class="login-carousel-section">
                <?php
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
                <div id="registerCarousel" class="carousel slide login-carousel" data-bs-ride="carousel">
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
                            <button type="button" data-bs-target="#registerCarousel" data-bs-slide-to="<?php echo $i; ?>" <?php echo $i === 0 ? 'class="active"' : ''; ?>></button>
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
</body>
</html>
