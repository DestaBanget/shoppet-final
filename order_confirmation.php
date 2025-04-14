<?php
session_start();
require_once 'config/database.php';
require_once 'functions/utilities.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Get order details
$orderQuery = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$orderResult = $stmt->get_result();

// Check if order exists
if ($orderResult->num_rows == 0) {
    redirectWithMessage('index.php', 'Order not found', 'danger');
}

$order = $orderResult->fetch_assoc();
$stmt->close();

// Get order items
$itemsQuery = "SELECT oi.*, p.name, p.image 
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = ?";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$itemsResult = $stmt->get_result();
$items = $itemsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate order summary
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.10; // 10% tax
$shipping = 10.00; // Flat shipping rate
$total = $subtotal + $tax + $shipping;

// Parse shipping address
$shipping_address = json_decode($order['shipping_address'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <div class="display-1 text-success mb-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="mb-3">Order Confirmed!</h1>
                    <p class="lead">Thank you for your purchase. Your order has been received and is being processed.</p>
                    <div class="mt-4">
                        <p class="mb-1">
                            <strong>Order Number:</strong> #<?php echo $order_id; ?>
                        </p>
                        <p>
                            <strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo !empty($item['image']) ? $item['image'] : 'assets/images/product-placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>" class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <div>
                                                        <?php echo $item['name']; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                            <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end">Subtotal</td>
                                        <td class="text-end">$<?php echo number_format($subtotal, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Tax (10%)</td>
                                        <td class="text-end">$<?php echo number_format($tax, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Shipping</td>
                                        <td class="text-end">$<?php echo number_format($shipping, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total</strong></td>
                                        <td class="text-end"><strong>$<?php echo number_format($total, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Shipping Address</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">
                                    <strong><?php echo $shipping_address['fullname']; ?></strong><br>
                                    <?php echo $shipping_address['address']; ?><br>
                                    <?php echo $shipping_address['city']; ?>, <?php echo $shipping_address['state']; ?> <?php echo $shipping_address['zip']; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Payment Information</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">
                                    <strong>Payment Method:</strong><br>
                                    <?php 
                                    switch($order['payment_method']) {
                                        case 'credit_card':
                                            echo '<i class="fas fa-credit-card me-2"></i>Credit Card';
                                            break;
                                        case 'paypal':
                                            echo '<i class="fab fa-paypal me-2"></i>PayPal';
                                            break;
                                        case 'bank_transfer':
                                            echo '<i class="fas fa-university me-2"></i>Bank Transfer';
                                            break;
                                        default:
                                            echo $order['payment_method'];
                                    }
                                    ?>
                                </p>
                                <p class="mb-0 mt-2">
                                    <strong>Status:</strong><br>
                                    <span class="badge bg-success">Paid</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mb-5">
                    <p>A confirmation email has been sent to your email address.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Return to Home
                        </a>
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-user me-2"></i>My Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
