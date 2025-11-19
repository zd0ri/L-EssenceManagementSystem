<?php
session_start();
// require login before sending any output
require_once(__DIR__ . '/../includes/auth.php');
// Load config (DB connection) before processing POST, but delay header output until after redirects
include(__DIR__ . '/../includes/config.php');
// after auth, we have a user id
$current_user_id = (int)$_SESSION['user_id'];

// Handle POST (single form: profile fields + optional image)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $posted_contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $posted_address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $posted_email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($posted_fullname)) {
        $_SESSION['message'] = 'Full name is required.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // check email uniqueness if changed
    if (!empty($posted_email) && isset($_SESSION['email']) && $posted_email !== $_SESSION['email']) {
        $posted_email_esc = mysqli_real_escape_string($conn, $posted_email);
        $chk = mysqli_query($conn, "SELECT user_id FROM users WHERE email = '{$posted_email_esc}' AND user_id != {$current_user_id} LIMIT 1");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $_SESSION['message'] = 'Email already in use by another account.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // process uploaded image if present
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['profile_image'];
        $allowed = ["image/jpeg" => 'jpg', "image/jpg" => 'jpg', "image/png" => 'png'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['message'] = 'Error uploading file.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!isset($allowed[$mime]) || $file['size'] > 5 * 1024 * 1024) {
            $_SESSION['message'] = 'Invalid image. Use JPG/PNG up to 5MB.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
        $ext = $allowed[$mime];
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $target = $uploadDir . "/profile_{$current_user_id}.{$ext}";
        // remove old variants
        foreach (['png','jpg','jpeg'] as $e) {
            $old = $uploadDir . "/profile_{$current_user_id}.{$e}";
            if (file_exists($old) && pathinfo($old, PATHINFO_EXTENSION) !== $ext) @unlink($old);
        }
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $_SESSION['message'] = 'Failed to save uploaded image.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // update users.email if changed
    if (!empty($posted_email) && (!isset($_SESSION['email']) || $posted_email !== $_SESSION['email'])) {
        $posted_email_esc = mysqli_real_escape_string($conn, $posted_email);
        $upd = mysqli_query($conn, "UPDATE users SET email = '{$posted_email_esc}' WHERE user_id = {$current_user_id}");
        if (!$upd) {
            $_SESSION['message'] = 'Failed to update email: ' . mysqli_error($conn);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
        $_SESSION['email'] = $posted_email;
    }

    // insert or update customers
    $fullname_esc = mysqli_real_escape_string($conn, $posted_fullname);
    $contact_esc = mysqli_real_escape_string($conn, $posted_contact);
    $address_esc = mysqli_real_escape_string($conn, $posted_address);
    $email_for_customers = mysqli_real_escape_string($conn, $posted_email ?: ($_SESSION['email'] ?? ''));
    $check = mysqli_query($conn, "SELECT customer_id FROM customers WHERE user_id = {$current_user_id} LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        $r = mysqli_fetch_assoc($check);
        $custId = (int)$r['customer_id'];
        $sql = "UPDATE customers SET fullname='{$fullname_esc}', contact='{$contact_esc}', address='{$address_esc}', email='{$email_for_customers}' WHERE customer_id={$custId}";
    } else {
        $sql = "INSERT INTO customers (user_id, fullname, contact, address, email) VALUES ({$current_user_id}, '{$fullname_esc}', '{$contact_esc}', '{$address_esc}', '{$email_for_customers}')";
    }
    if (!mysqli_query($conn, $sql)) {
        $_SESSION['message'] = 'Failed to save profile: ' . mysqli_error($conn);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    $_SESSION['success'] = 'Profile updated successfully.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// load latest customer data to prefill form
$fullname = '';
$contact = '';
$address = '';
$email_input = $_SESSION['email'] ?? '';
$q = "SELECT fullname, contact, address, email FROM customers WHERE user_id = {$current_user_id} LIMIT 1";
$r = mysqli_query($conn, $q);
if ($r && mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    $fullname = $row['fullname'] ?? '';
    $contact = $row['contact'] ?? '';
    $address = $row['address'] ?? '';
    if (empty($email_input) && !empty($row['email'])) $email_input = $row['email'];
}

// determine avatar URL
$avatarUrl = '/essence_db/uploads/default-avatar.png';
$uploadDir = __DIR__ . '/../uploads';
foreach (['png','jpg','jpeg'] as $e) {
    $candidate = $uploadDir . "/profile_{$current_user_id}.{$e}";
    if (file_exists($candidate)) {
        $avatarUrl = '/essence_db/uploads/profile_' . $current_user_id . '.' . $e . '?t=' . filemtime($candidate);
        break;
    }
}

?>
<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="container-xl px-4 mt-4">
    <?php include(__DIR__ . "/../includes/alert.php"); ?>
    <!-- Profile page is informational; top navigation provides links to Profile and My Orders -->

    <form id="update-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-xl-4">
                <div class="card mb-4 mb-xl-0">
                    <div class="card-header">Change Profile Picture</div>
                    <div class="card-body text-center">
                        <img class="img-account-profile rounded-circle mb-2" src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Profile image" style="width:120px;height:120px;object-fit:cover;" />
                        <div class="small font-italic text-muted mb-4">JPG or PNG no larger than 5 MB</div>
                        <div class="mb-3">
                            <input class="form-control" id="profile_image" type="file" name="profile_image" accept="image/png,image/jpeg">
                        </div>
                        <div class="small text-muted">Upload will replace existing image.</div>

                        <!-- Inline account details shown under avatar (read-only) -->
                        <hr />
                        <div class="text-start">
                            <p class="mb-1"><strong class="me-1">Full name:</strong> <?php echo htmlspecialchars($fullname); ?></p>
                            <p class="mb-1"><strong class="me-1">Email:</strong> <?php echo htmlspecialchars($email_input ?? ($_SESSION['email'] ?? '')); ?></p>
                            <p class="mb-1"><strong class="me-1">Contact:</strong> <?php echo htmlspecialchars($contact); ?></p>
                            <p class="mb-0"><strong class="me-1">Address:</strong> <?php echo htmlspecialchars($address); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header">Update Information</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="fullname">Full name</label>
                            <input class="form-control" id="fullname" type="text" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="address">Address</label>
                            <input class="form-control" id="address" type="text" name="address" value="<?php echo htmlspecialchars($address); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="contact">Contact (phone)</label>
                            <input class="form-control" id="contact" type="tel" name="contact" value="<?php echo htmlspecialchars($contact); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" id="email" type="email" name="email" value="<?php echo htmlspecialchars($email_input); ?>">
                        </div>

                        <button class="btn btn-primary" type="submit" name="submit">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
    
    <!-- My Orders moved to standalone page: users/my_orders.php -->
<?php
// End of file - single handler and form are above. Removed duplicated block that caused parse errors.