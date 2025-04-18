<?php
session_start();
require_once 'config/database.php';
require_once 'functions/utilities.php';

// Get category filter
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : null;

// Get search query
$searchQuery = isset($_GET['search']) ? $_GET['search'] : null;

// Build the SQL query
$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";

$params = [];
$types = "";

// Add category filter if provided
if ($categoryFilter) {
    $sql .= " AND c.slug = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

// Add search filter if provided
if ($searchQuery) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$sql .= " ORDER BY p.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

// Get all categories for the sidebar
$categoriesQuery = "SELECT * FROM categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);
$categories = $categoriesResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $categoryFilter ? ucfirst($categoryFilter) . ' Products' : 'All Products'; ?> - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <div class="row">
            <!-- Sidebar with categories -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Categories</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="products.php" class="list-group-item list-group-item-action <?php echo !$categoryFilter ? 'active' : ''; ?>">
                            All Products
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="products.php?category=<?php echo $category['slug']; ?>" class="list-group-item list-group-item-action <?php echo $categoryFilter === $category['slug'] ? 'active' : ''; ?>">
                                <?php echo $category['name']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="col-lg-9">
                <!-- Search and filter bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="products.php" method="GET" class="row g-3">
                            <?php if ($categoryFilter): ?>
                                <input type="hidden" name="category" value="<?php echo $categoryFilter; ?>">
                            <?php endif; ?>
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search products..." name="search" value="<?php echo $searchQuery; ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="sort">
                                    <option value="newest">Newest First</option>
                                    <option value="price_low">Price: Low to High</option>
                                    <option value="price_high">Price: High to Low</option>
                                    <option value="name_asc">Name: A to Z</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Products grid -->
                <div class="row">
                    <?php if (empty($products)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <p class="mb-0">No products found. Please try a different search or category.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 product-card">
                                    <div class="product-image">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text text-muted small"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                                        <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($product['description']), 0, 100) . '...'; ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <span class="price fw-bold">$<?php echo number_format($product['price'], 2); ?></span>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-top-0">
                                        <form action="cart.php" method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="action" value="add">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>