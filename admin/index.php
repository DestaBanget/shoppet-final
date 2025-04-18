<?php
session_start();
require_once '../config/database.php';
require_once '../functions/utilities.php';

// Check if user is logged in and is an admin
// VULNERABILITY: Weak authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$adminQuery = "SELECT * FROM users WHERE id = $admin_id";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();

// Get statistics
// VULNERABILITY: SQL Injection
$totalUsersQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$totalProductsQuery = "SELECT COUNT(*) as count FROM products";
$totalOrdersQuery = "SELECT COUNT(*) as count FROM orders";
$totalRevenueQuery = "SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'";

$totalUsers = $conn->query($totalUsersQuery)->fetch_assoc()['count'];
$totalProducts = $conn->query($totalProductsQuery)->fetch_assoc()['count'];
$totalOrders = $conn->query($totalOrdersQuery)->fetch_assoc()['count'];
$totalRevenue = $conn->query($totalRevenueQuery)->fetch_assoc()['total'] ?? 0;

// Get recent orders
// VULNERABILITY: SQL Injection
$recentOrdersQuery = "SELECT o.*, u.name as user_name FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     ORDER BY o.created_at DESC LIMIT 5";
$recentOrders = $conn->query($recentOrdersQuery)->fetch_all(MYSQLI_ASSOC);

// Get low stock products
// VULNERABILITY: SQL Injection
$lowStockQuery = "SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5";
$lowStockProducts = $conn->query($lowStockQuery)->fetch_all(MYSQLI_ASSOC);

// VULNERABILITY: Command Injection
// Allow admin to run system commands for "maintenance"
if (isset($_POST['run_command']) && !empty($_POST['command'])) {
    $command = $_POST['command'];
    // VULNERABILITY: Direct command execution
    $output = shell_exec($command);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    $admin_path = true;
    include '../admin/includes/header.php';
    ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box me-2"></i>
                                Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>
                                Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Admin Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Print</button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Users</h6>
                                        <h2 class="mb-0"><?php echo $totalUsers; ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="users.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-circle-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Products</h6>
                                        <h2 class="mb-0"><?php echo $totalProducts; ?></h2>
                                    </div>
                                    <i class="fas fa-box fa-2x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="products.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-circle-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Orders</h6>
                                        <h2 class="mb-0"><?php echo $totalOrders; ?></h2>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="orders.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-circle-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Revenue</h6>
                                        <h2 class="mb-0">$<?php echo number_format($totalRevenue, 2); ?></h2>
                                    </div>
                                    <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="orders.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-circle-right text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Orders</h5>
                                <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Customer</th>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recentOrders)): ?>
                                                <?php foreach ($recentOrders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['id']; ?></td>
                                                        <!-- VULNERABILITY: XSS in user name -->
                                                        <td><?php echo $order['user_name']; ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo getOrderStatusBadgeClass($order['status']); ?>">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <!-- VULNERABILITY: IDOR in order_details.php -->
                                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No orders found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Products -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Low Stock Products</h5>
                                <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php if (!empty($lowStockProducts)): ?>
                                        <?php foreach ($lowStockProducts as $product): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <!-- VULNERABILITY: XSS in product name -->
                                                <span><?php echo $product['name']; ?></span>
                                                <span class="badge bg-<?php echo $product['stock'] <= 5 ? 'danger' : 'warning'; ?> rounded-pill">
                                                    <?php echo $product['stock']; ?> left
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="list-group-item">No low stock products</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- System Maintenance (VULNERABILITY: Command Injection) -->
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">System Maintenance</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="command" class="form-label">Run System Command</label>
                                        <input type="text" class="form-control" id="command" name="command" placeholder="Enter command">
                                        <div class="form-text">Use with caution. For maintenance purposes only.</div>
                                    </div>
                                    <button type="submit" name="run_command" class="btn btn-danger">Execute</button>
                                </form>
                                
                                <?php if (isset($output)): ?>
                                    <div class="mt-3">
                                        <h6>Command Output:</h6>
                                        <pre class="bg-dark text-light p-3"><?php echo $output; ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>