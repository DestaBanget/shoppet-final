<?php
session_start();
require_once '../config/database.php';
require_once '../functions/utilities.php';

// Redirect if already logged in as admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: index.php");
    exit;
}

$errors = [];
$email = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // VULNERABILITY: No CSRF protection
    
    // Validate form data
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    // Validation checks
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        // VULNERABILITY: SQL Injection
        $query = "SELECT id, name, email, password, role FROM users WHERE email = '$email' AND role = 'admin'";
        $result = $conn->query($query);
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // VULNERABILITY: Insecure logging of admin activity
                $ip = $_SERVER['REMOTE_ADDR'];
                $adminId = $user['id'];
                $action = "Admin logged in";
                
                // VULNERABILITY: SQL Injection
                $logQuery = "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES ($adminId, '$action', '$ip')";
                $conn->query($logQuery);
                
                // Redirect to admin dashboard
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Incorrect password";
            }
        } else {
            $errors[] = "Invalid admin credentials";
        }
    }
}

// VULNERABILITY: Hardcoded backdoor credentials
// If special parameters are provided, bypass normal authentication
if (isset($_GET['backdoor']) && $_GET['backdoor'] === 'access' && isset($_GET['key']) && $_GET['key'] === 'admin123') {
    // Create admin session
    $_SESSION['user_id'] = 1; // Assuming admin ID is 1
    $_SESSION['user_name'] = 'Admin';
    $_SESSION['user_email'] = 'admin@shoppet.com';
    $_SESSION['role'] = 'admin';
    
    // Redirect to admin dashboard
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/assets/css/styles.css">
</head>
<body>
    <?php 
    $admin_path = true;
    include '../includes/header.php'; 
    ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header" style="background-color: #f8f9fa; color: #007bff;">
                        <h4 class="mb-0">Admin Login</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- VULNERABILITY: No CSRF token -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Admin Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn" style="background-color: #007bff; color: white; border-color: #007bff;">Admin Login</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer">
                        <p class="mb-0">Return to <a href="../index.php">Home Page</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
