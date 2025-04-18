<?php
session_start();
require_once 'config/database.php';
require_once 'functions/utilities.php';

// Cek login
if (!isLoggedIn()) {
    redirectWithMessage('login.php', 'Please login to view your orders', 'info');
}

$user_id = $_SESSION['user_id'];

function getOrderStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'processing':
            return 'bg-info text-dark';
        case 'shipped':
            return 'bg-primary';
        case 'delivered':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Ambil semua pesanan user
$ordersQuery = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
$ordersResult = $conn->query($ordersQuery);
$orders = $ordersResult->fetch_all(MYSQLI_ASSOC);

// Optional: pastikan function getCartItemCount() dan isLoggedIn() tersedia sebelum header dipanggil
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - ShopPet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Style tambahan jika ada -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-paw me-2 text-primary"></i>
                ShopPet
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="products.php?category=dogs">Dogs</a></li>
                            <li><a class="dropdown-item" href="products.php?category=cats">Cats</a></li>
                            <li><a class="dropdown-item" href="products.php?category=birds">Birds</a></li>
                            <li><a class="dropdown-item" href="products.php?category=fish">Fish</a></li>
                        </ul>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <form class="d-flex me-2" action="search.php" method="GET">
                        <input class="form-control me-2" type="search" name="query" placeholder="Search" aria-label="Search">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <a href="cart.php" class="btn btn-outline-primary position-relative me-2">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (function_exists('getCartItemCount') && getCartItemCount() > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo getCartItemCount(); ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if (isLoggedIn()): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-secondary me-2">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <?php if (isset($_SESSION['message'])): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    endif; 
    ?>
</header>

<div class="container my-5">
    <h2 class="mb-4">My Orders</h2>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            You haven't placed any orders yet. <a href="index.php" class="alert-link">Shop now</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <!-- <th>Action</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge <?php echo getOrderStatusBadgeClass($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <!-- <td>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td> -->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
