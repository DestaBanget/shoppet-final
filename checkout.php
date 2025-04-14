<?php
session_start();
require_once 'config/database.php';
require_once 'functions/utilities.php';

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirectWithMessage('cart.php', 'Your cart is empty', 'info');
}

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    redirectWithMessage('login.php', 'Please login to continue with checkout', 'info');
}

// Get user information
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

// Calculate cart totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.10; // 10% tax
$shipping = 10.00; // Flat shipping rate
$total = $subtotal + $tax + $shipping;

// Handle checkout process
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $errors = [];
    
    $fullname = sanitizeInput($_POST['fullname']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $city = sanitizeInput($_POST['city']);
    $state = sanitizeInput($_POST['state']);
    $zip = sanitizeInput($_POST['zip']);
    $payment_method = sanitizeInput($_POST['payment_method']);
    
    if (empty($fullname)) $errors[] = "Full name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State is required";
    if (empty($zip)) $errors[] = "ZIP code is required";
    if (empty($payment_method)) $errors[] = "Payment method is required";
    
    // Verify stock availability again
    foreach ($_SESSION['cart'] as $product_id => $item) {
        if (!isProductInStock($product_id, $item['quantity'], $conn)) {
            $errors[] = "Sorry, {$item['name']} is no longer available in the requested quantity";
        }
    }
    
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $orderQuery = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status, created_at) 
                          VALUES (?, ?, ?, ?, 'pending', NOW())";
            $shipping_address = json_encode([
                'fullname' => $fullname,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip' => $zip
            ]);
            
            $stmt = $conn->prepare($orderQuery);
            $stmt->bind_param("idss", $user_id, $total, $shipping_address, $payment_method);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();
            
            // Add order items
            $orderItemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($orderItemQuery);
            
            foreach ($_SESSION['cart'] as $product_id => $item) {
                $stmt->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
                $stmt->execute();
                
                // Update product stock
                $updateStockQuery = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateStockQuery);
                $updateStmt->bind_param("ii", $item['quantity'], $product_id);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Clear the cart
            $_SESSION['cart'] = array();
            
            // Redirect to confirmation page
            redirectWithMessage("order_confirmation.php?id={$order_id}", "Order placed successfully!", "success");
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = "Error processing your order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <h1 class="mb-4">Checkout</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="checkout.php" method="POST">
            <div class="row">
                <div class="col-md-8">
                    <!-- Shipping Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="fullname" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo $user['name'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="col-12">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>
                                
                                <div class="col-md-5">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state" name="state" required>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="zip" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" id="zip" name="zip" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="creditCard" value="credit_card" checked>
                                <label class="form-check-label" for="creditCard">
                                    <i class="fas fa-credit-card me-2"></i>Credit Card
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                <label class="form-check-label" for="paypal">
                                    <i class="fab fa-paypal me-2"></i>PayPal
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="bankTransfer" value="bank_transfer">
                                <label class="form-check-label" for="bankTransfer">
                                    <i class="fas fa-university me-2"></i>Bank Transfer
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Order Summary -->
                    <div class="card checkout-summary">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <?php foreach ($_SESSION['cart'] as $item): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>
                                            <?php echo $item['name']; ?> 
                                            <small class="text-muted">x<?php echo $item['quantity']; ?></small>
                                        </span>
                                        <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (10%)</span>
                                <span>$<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span>
                                <span>$<?php echo number_format($shipping, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total</strong>
                                <strong>$<?php echo number_format($total, 2); ?></strong>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-lg btn-primary">
                                    <i class="fas fa-lock me-2"></i>Place Order
                                </button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="cart.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i>Return to Cart
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
