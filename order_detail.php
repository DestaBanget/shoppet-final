<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Database connection
$host = 'localhost';
$port = '3306';
$dbname = 'shoppet_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if $pdo is valid
if (!$pdo) {
    die("Failed to connect to the database.");
}

// Fetch order details
$stmt = $pdo->prepare("SELECT o.* FROM orders o WHERE o.id = :order_id AND o.user_id = :user_id");
$stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Check if order exists
if ($stmt->rowCount() > 0) {
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header("Location: orders.php");
    exit();
}

// Fetch order items
$stmt = $pdo->prepare("SELECT oi.*, p.id AS product_id, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id");
$stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
$stmt->execute();
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate order totals
$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$pageTitle = "Order #" . $order_id . " Details";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopPet - <?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $pageTitle; ?></h1>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Orders
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="row mb-3 pb-3 border-bottom">
                                <div class="col-md-2 col-sm-3">
                                    <img src="<?php echo $item['image'] ?? 'assets/images/product-placeholder.jpg'; ?>" 
                                         alt="<?php echo $item['name']; ?>" 
                                         class="img-fluid rounded">
                                </div>
                                <div class="col-md-6 col-sm-5">
                                    <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                                    <p class="text-muted small mb-1">
                                        Price: $<?php echo number_format($item['price'], 2); ?>
                                    </p>
                                    <p class="text-muted small mb-0">
                                        Quantity: <?php echo $item['quantity']; ?>
                                    </p>
                                </div>
                                <div class="col-md-4 col-sm-4 text-end">
                                    <p class="price mb-0">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Order Date:</span>
                            <span><?php echo isset($order['created_at']) ? date('M d, Y', strtotime($order['created_at'])) : 'N/A'; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Order Status:</span>
                            <span>
                                <?php 
                                switch($order['status']) {
                                    case 'pending': echo '<span class="badge bg-warning text-dark">Pending</span>'; break;
                                    case 'processing': echo '<span class="badge bg-info text-dark">Processing</span>'; break;
                                    case 'shipped': echo '<span class="badge bg-primary">Shipped</span>'; break;
                                    case 'delivered': echo '<span class="badge bg-success">Delivered</span>'; break;
                                    case 'cancelled': echo '<span class="badge bg-danger">Cancelled</span>'; break;
                                    default: echo '<span class="badge bg-secondary">Unknown</span>';
                                }
                                ?>
                            </span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>$<?php echo number_format($order['shipping_fee'] ?? 0, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax:</span>
                            <span>$<?php echo number_format($order['tax'] ?? 0, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-0">
                            <span class="fw-bold">Total:</span>
                            <span class="fw-bold price">$<?php echo number_format($subtotal + ($order['shipping_fee'] ?? 0) + ($order['tax'] ?? 0), 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Shipping Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong><?php echo isset($order['shipping_address']) ? $order['shipping_address'] : 'N/A'; ?></strong></p>
                        <p class="mb-0"><?php echo isset($order['shipping_city']) ? $order['shipping_city'] : 'N/A'; ?>, 
                                         <?php echo isset($order['shipping_state']) ? $order['shipping_state'] : 'N/A'; ?> 
                                         <?php echo isset($order['shipping_zip']) ? $order['shipping_zip'] : 'N/A'; ?></p>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">Payment Method: <strong><?php echo $order['payment_method']; ?></strong></p>
                        <p class="mb-0">Payment Status: 
                            <?php if (isset($order['payment_status']) && $order['payment_status'] == 'paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
