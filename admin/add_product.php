<?php
session_start();
require_once '../config/database.php';
require_once '../functions/utilities.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category = trim($_POST['category']);
    
    // Validate name
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    
    // Validate price
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero";
    }
    
    // Validate stock
    if ($stock < 0) {
        $errors[] = "Stock cannot be negative";
    }
    
    // Validate category
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } else {
            $upload_dir = '../uploads/products/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/products/' . $file_name;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }
    
    // If no errors, insert product into database
    if (empty($errors)) {
        $query = "INSERT INTO products (name, description, price, stock, category, image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdiis", $name, $description, $price, $stock, $category, $image_path);
        
        if ($stmt->execute()) {
            $success = true;
            $_SESSION['success_message'] = "Product added successfully!";
            header("Location: products.php");
            exit();
        } else {
            $errors[] = "Error adding product: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin-top: 50px;
        }
        .card {
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <?php 
        $admin_path = true;
        include '../admin/includes/header.php'; 
    ?>

<div class="container">
    <div class="card">
        <div class="card-body">
            <h3 class="card-title mb-4">Add New Product</h3>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">Product added successfully!</div>
            <?php endif; ?>

            <form action="product_add.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="price">Price ($) *</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="stock">Stock *</label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <input type="text" class="form-control" id="category" name="category" required>
                </div>

                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" class="form-control-file" id="image" name="image">
                    <small class="form-text text-muted">Supported formats: JPG, PNG, GIF</small>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Add Product</button>
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

</body>
</html>