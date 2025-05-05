<?php
session_start();
require_once 'config/database.php';
require_once 'functions/utilities.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('login.php', 'Please login to edit your profile', 'info');
}

// Get user information - VULNERABILITY: SQL Injection
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE id = " . $user_id;
$userResult = $conn->query($userQuery);
$user = $userResult->fetch_assoc();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABILITY: No CSRF protection
    $name = $_POST['name']; // Removed sanitizeInput
    $email = $_POST['email']; // Removed sanitizeInput
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic checks only (for functionality)
    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    }

    if ($email !== $user['email']) {
        $checkEmailQuery = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
        $emailResult = $conn->query($checkEmailQuery);

        if ($emailResult->num_rows > 0) {
            $errors[] = "Email is already in use by another account";
        }
    }

    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }

        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }

    $profile_image = $user['profile_image'];

    if (isset($_POST['remove_image'])) {
        if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
            unlink($user['profile_image']);
        }
        $profile_image = null;
    } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $filename = $_FILES['profile_image']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) {
            $upload_dir = 'uploads/profile_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image = $upload_path;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        } else {
            $errors[] = "Invalid file type. Please upload a JPG, PNG, or GIF image.";
        }
    }

    if (empty($errors)) {
        if (!empty($current_password) && !empty($new_password)) {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

            $updateQuery = "UPDATE users SET 
                            name = '$name', 
                            email = '$email', 
                            password = '$hashedPassword', 
                            profile_image = '$profile_image' 
                            WHERE id = $user_id";

            if ($conn->query($updateQuery)) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $success = true;
            } else {
                $errors[] = "Error updating profile: " . $conn->error;
            }
        } else {
            $updateQuery = "UPDATE users SET 
                            name = '$name', 
                            email = '$email', 
                            profile_image = '$profile_image' 
                            WHERE id = $user_id";

            if ($conn->query($updateQuery)) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $success = true;
            } else {
                $errors[] = "Error updating profile: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container my-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px;">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?php echo $user['profile_image']; ?>" alt="<?php echo $user['name']; ?>" class="img-fluid rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-1"><?php echo $user['name']; ?></h5>
                    <p class="text-muted"><?php echo $user['email']; ?></p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="profile.php" class="list-group-item list-group-item-action">My Profile</a>
                    <a href="orders.php" class="list-group-item list-group-item-action">My Orders</a>
                    <a href="edit_profile.php" class="list-group-item list-group-item-action active">Edit Profile</a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">Your profile has been updated successfully!</div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo $user['name']; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>">
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Profile Photo</h5>
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Upload Profile Photo</label>
                            <input type="file" class="form-control" name="profile_image">
                            <?php if (!empty($user['profile_image'])): ?>
                                <div class="mt-2">
                                    <img src="<?php echo $user['profile_image']; ?>" alt="Current Profile Photo" style="width: 100px;">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image">
                                        <label class="form-check-label" for="remove_image">Remove current image</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Change Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password">
                        </div>

                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
