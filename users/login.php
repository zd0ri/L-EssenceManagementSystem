<?php
session_start();

include("../includes/header.php");
include("../includes/config.php");
if (isset($_POST['submit'])) {
    // server-side validation (no HTML5 reliance)
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

        // match schema: users.user_id is the PK
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
                header("Location: ../index.php");
                exit();
            } else {
                $_SESSION['message'] = 'Wrong email or password';
            }
        } else {
            $_SESSION['message'] = 'An internal error occurred';
        }
    }
}

?>
<div class="row col-md-8 mx-auto ">
    <?php include("../includes/alert.php"); ?>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <!-- Email input (use text to avoid HTML5 validation) -->
        <div class="form-outline mb-4">
            <input type="text" id="form2Example1" class="form-control" name="email" value="<?php echo isset(
                
                
                $_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''); ?>" />
            <label class="form-label" for="form2Example1">Email address</label>
        </div>

        <!-- Password input -->
        <div class="form-outline mb-4">
            <input type="password" id="form2Example2" class="form-control" name="password" />
            <label class="form-label" for="form2Example2">Password</label>
        </div>

        <!-- Submit button -->
        <button type="submit" class="btn btn-primary btn-block mb-4" name="submit">Sign in</button>

        <!-- Register buttons -->
        <div class="text-center">
        <p>Not a member? <a href="register.php">Register</a></p>
        </div>

    </form>
</div>
<?php
include("../includes/footer.php");
?>