<?php
session_start();
require_once 'config/database.php';
require_once 'functions/utilities.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Handle quantity updates
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = intval($quantity);
        
        if ($quantity > 0) {
            // Check stock availability
            if (isProductInStock($product_id, $quantity, $conn)) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            } else {
                // Set to maximum available stock
                $query = "SELECT stock FROM products WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                $_SESSION['cart'][$product_id]['quantity'] = $row['stock'];
                $_SESSION['message'] = "Quantity adjusted to available stock for some products";
                $_SESSION['message_type'] = "warning";
            }
        } else {
            // Remove product if quantity is 0
            unset($_SESSION['cart'][$product_id]);
        }
    }
    
    header("Location: cart.php");
    exit;
}

// Handle Add to Cart (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = intval($_POST['product_id']);

    // Ambil detail produk dari database
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        // Jika sudah ada di cart, tambahkan quantity-nya
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += 1;
        } else {
            // Tambah produk baru ke cart
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => 1
            ];
        }

        $_SESSION['message'] = "Product added to cart!";
        $_SESSION['message_type'] = "success";
    }

    // Redirect kembali ke halaman sebelumnya
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}


// Handle remove item
if (isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    $_SESSION['message'] = "Item removed from cart";
    $_SESSION['message_type'] = "success";
    header("Location: cart.php");
    exit;
}

// Calculate subtotal
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Calculate tax and total
$tax = $subtotal * 0.10; // 10% tax
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <h1 class="mb-4">Shopping Cart</h1>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="alert alert-info">
                <h4 class="alert-heading">Your cart is empty!</h4>
                <p>Browse our products and add items to your cart.</p>
                <hr>
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Cart Items (<?php echo count($_SESSION['cart']); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <form action="cart.php" method="POST">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 80px;"></th>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th style="width: 150px;">Quantity</th>
                                                <th class="text-end">Subtotal</th>
                                                <th style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                                <tr>
                                                    <td>
                                                        <img src="<?php echo !empty($item['image']) ? $item['image'] : 'assets/images/product-placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                                    </td>
                                                    <td>
                                                        <a href="product.php?id=<?php echo $product_id; ?>" class="text-decoration-none">
                                                            <?php echo $item['name']; ?>
                                                        </a>
                                                    </td>
                                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                    <td>
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" name="quantity[<?php echo $product_id; ?>]" value="<?php echo $item['quantity']; ?>" min="0" max="99">
                                                        </div>
                                                    </td>
                                                    <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                    <td>
                                                        <a href="cart.php?remove=<?php echo $product_id; ?>" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-3">
                                    <a href="products.php" class="btn btn-outline-primary">
                                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                    </a>
                                    <button type="submit" name="update_cart" class="btn btn-primary">
                                        <i class="fas fa-sync-alt me-2"></i>Update Cart
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card checkout-summary">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (10%)</span>
                                <span>$<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total</strong>
                                <strong>$<?php echo number_format($total, 2); ?></strong>
                            </div>
                            
                            <div class="d-grid">
                                <a href="checkout.php" class="btn btn-lg btn-primary">
                                    Proceed to Checkout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
