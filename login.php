<?php
session_start();
// CELAH: Session Fixation - tidak ada regenerasi session ID setelah login
// CELAH: Tidak ada session timeout yang dikonfigurasi

require_once 'config/database.php';
require_once 'functions/utilities.php';

// Redirect if already logged in
if (isLoggedIn()) {
    // Redirect based on role
    if (isAdmin()) {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$errors = [];
$email = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CELAH: Tidak ada CSRF protection
    
    // CELAH: Sanitasi input yang tidak memadai
    $email = $_POST['email']; // Tidak ada sanitasi email
    $password = $_POST['password']; // Password tidak disanitasi
    
    // CELAH: Validasi yang tidak memadai
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // CELAH: Tidak ada pembatasan percobaan login (brute force)
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        // CELAH: SQL Injection - menggunakan variabel langsung dalam query
        $query = "SELECT id, name, email, password, role FROM users WHERE email = '$email'";
        $result = $conn->query($query);
        
        // CELAH: Timing attack - memberikan informasi berbeda berdasarkan apakah email ada atau tidak
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // CELAH: Tidak ada pembatasan waktu verifikasi password (potensi timing attack)
            if (password_verify($password, $user['password'])) {
                // CELAH: Tidak ada regenerasi session ID setelah login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // CELAH: Log yang tidak aman - menyimpan informasi sensitif
                error_log("User {$user['email']} logged in from IP: {$_SERVER['REMOTE_ADDR']}");
                
                // CELAH: Insecure Direct Object Reference - tidak ada validasi role
                // CELAH: Privilege Escalation - tidak ada validasi role yang tepat
                
                // CELAH: Insecure redirect - menggunakan parameter GET tanpa validasi
                if (isset($_GET['redirect'])) {
                    header("Location: " . $_GET['redirect']);
                    exit;
                }
                
                // Redirect based on role
                if ($_SESSION['role'] === 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                // CELAH: Information Disclosure - memberikan informasi spesifik tentang kesalahan
                $errors[] = "Incorrect password for email: $email";
            }
        } else {
            // CELAH: Information Disclosure - memberikan informasi spesifik tentang kesalahan
            $errors[] = "Email not registered: $email";
        }
    }
    
    // CELAH: Tidak ada logging untuk percobaan login yang gagal
}

// CELAH: Insecure "Remember Me" functionality
if (isset($_POST['remember']) && isset($_SESSION['user_id'])) {
    // CELAH: Cookie tanpa flag HttpOnly dan Secure
    // CELAH: Menyimpan ID pengguna dalam cookie tanpa enkripsi
    setcookie('user_id', $_SESSION['user_id'], time() + (86400 * 30), "/");
    
    // CELAH: Menyimpan kredensial dalam cookie
    setcookie('user_email', $_SESSION['user_email'], time() + (86400 * 30), "/");
}

// CELAH: Debug mode yang terekspos
$debug = isset($_GET['debug']) && $_GET['debug'] == 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ShopPet</title>
    <!-- CELAH: Tidak ada Content Security Policy -->
    <!-- CELAH: Tidak ada X-Frame-Options -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- CELAH: JavaScript yang tidak aman -->
    <script>
        function checkPassword() {
            // CELAH: Validasi password di sisi klien yang dapat dibypass
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
    <?php 
    // CELAH: File Inclusion Vulnerability
    include 'includes/header.php'; 
    ?>

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
                                <!-- //<input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required> -->
                                <input type="text" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <!-- CELAH: Password strength meter yang tidak aman -->
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
                            
                            <!-- CELAH: Hidden field yang dapat dimanipulasi -->
                            <input type="hidden" name="source" value="login_form">
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">Don't have an account? <a href="register.php">Register</a></p>
                        <p class="mt-2">Admin? <a href="admin/login.php" class="text-danger">Admin Login</a></p>
                        <!-- CELAH: Password reset yang tidak aman -->
                        <p class="mt-2"><a href="reset_password.php?email=<?php echo urlencode($email); ?>">Forgot Password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <!-- CELAH: JavaScript dari CDN tanpa Subresource Integrity -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- CELAH: JavaScript yang tidak aman untuk password strength meter -->
    <script>
        document.getElementById('password').addEventListener('input', function() {
            var password = this.value;
            var strength = 'Weak';
            
            if (password.length > 8) {
                strength = 'Medium';
            }
            
            if (password.length > 12 && /[A-Z]/.test(password) && /[0-9]/.test(password)) {
                strength = 'Strong';
            }
            
            document.getElementById('password-strength').textContent = strength;
            
            // CELAH: Menyimpan password di localStorage
            localStorage.setItem('last_password_strength', strength);
        });
    </script>
    
    <?php if ($debug): ?>
    <!-- CELAH: Debug information yang terekspos -->
    <div class="container mt-5 p-3 bg-light">
        <h3>Debug Information</h3>
        <pre><?php print_r($_SERVER); ?></pre>
        <pre><?php print_r($_SESSION); ?></pre>
        <pre><?php print_r($_COOKIE); ?></pre>
        <h4>Database Configuration</h4>
        <pre><?php print_r($conn); ?></pre>
    </div>
    <?php endif; ?>
</body>
</html>