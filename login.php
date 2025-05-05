<?php
session_start();
// CELAH: Session Fixation - tidak ada regenerasi session ID setelah login
// CELAH: Tidak ada session timeout yang dikonfigurasi

require_once 'config/database.php';
require_once 'functions/utilities.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$errors = [];
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email']; // Tidak ada sanitasi
    $password = $_POST['password']; // Tidak ada sanitasi

    if (empty($email) || empty($password)) {
        $errors[] = "Login failed";
    } else {
        $is_sql_injection = false;

        if (strpos($email, ';') !== false ||
            strpos($email, "'") !== false ||
            strpos($email, "=") !== false ||
            strpos($email, "--") !== false ||
            strpos($email, "/*") !== false ||
            strpos($email, "*/") !== false ||
            strpos(strtoupper($email), "OR") !== false ||
            strpos(strtoupper($email), "UNION") !== false) {
            $is_sql_injection = true;
        }

        if (strpos($email, ';') !== false) {
            $query = "SELECT id, name, email, password, role FROM users WHERE email = '$email'";
            $result = false;
            $user = null;

            try {
                $result = $conn->multi_query($query);

                if ($result) {
                    $res = $conn->store_result();
                    if ($res) {
                        if ($res->num_rows > 0) {
                            $user = $res->fetch_assoc();
                        }
                        $res->free();
                    }

                    while ($conn->more_results()) {
                        try {
                            $conn->next_result();
                            $res = $conn->store_result();
                            if ($res) {
                                $res->free();
                            }
                        } catch (mysqli_sql_exception $e) {
                            break;
                        }
                    }
                }
		//if($user && password_verify($password, $user['password'])) {
                if ($user && $password === $user['password']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    $redirect = $_SESSION['role'] === 'admin' ? 'admin/index.php' : 'index.php';
                    header("Location: $redirect");
                    exit;
                }
            } catch (mysqli_sql_exception $e) {
                // Silent fail - blind SQLi behavior
            }
        } else {
            $query = "SELECT id, name, email, password, role FROM users WHERE email = '$email'";
            try {
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0) {
                    $user = $result->fetch_assoc();

                    //if ($user && password_verify($password, $user['password'])) {
		      if ($user && $password === $user['password']) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];

                        $redirect = $_SESSION['role'] === 'admin' ? 'admin/index.php' : 'index.php';
                        header("Location: $redirect");
                        exit;
                    }
                }
            } catch (mysqli_sql_exception $e) {
                // Silent fail - blind SQLi behavior
            }
        }

        $errors[] = "Login failed";
    }
}

if (isset($_POST['remember']) && isset($_SESSION['user_id'])) {
    setcookie('user_id', $_SESSION['user_id'], time() + (86400 * 30), "/");
    setcookie('user_email', $_SESSION['user_email'], time() + (86400 * 30), "/");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        function checkPassword() {
            var password = document.getElementById('password').value;
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login to Your Account</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <!-- CELAH: XSS - output error tanpa escape -->
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo $_SERVER["PHP_SELF"] . (isset($_GET['redirect']) ? '?redirect=' . $_GET['redirect'] : ''); ?>" method="POST" onsubmit="return checkPassword()">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <!-- CELAH: XSS - nilai email tidak di-escape -->
                            <input type="text" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="password-strength mt-1">
                                <small>Password strength: <span id="password-strength">Not entered</span></small>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                        <input type="hidden" name="source" value="login_form">
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Don't have an account? <a href="register.php">Register</a></p>
                    <p class="mt-2">Admin? <a href="admin/login.php" class="text-danger">Admin Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
