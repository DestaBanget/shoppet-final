<?php
session_start();
require_once 'config/database.php';
require_once 'functions/utilities.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopPet - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Hero Banner -->
            <div class="col-12 mb-4">
                <div class="card bg-light">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h1 class="display-5 fw-bold">Welcome to ShopPet</h1>
                                <p class="lead">Find the best products for your furry friends.</p>
                                <form action="search.php" method="GET" class="d-flex mt-4">
                                    <input class="form-control me-2" type="search" name="query" placeholder="Search for products" aria-label="Search">
                                    <button class="btn btn-primary" type="submit">Search</button>
                                </form>
                            </div>
                            <div class="col-md-5 text-center">
                                <img src="assets/images/pets-banner.jpg" class="img-fluid rounded" alt="Pets" style="max-height: 300px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Categories -->
            <div class="col-12 mb-4">
                <h2>Shop by Category</h2>
                <div class="row g-4 mt-2">
                    <div class="col-6 col-md-3">
                        <a href="products.php?category=dogs" class="text-decoration-none">
                            <div class="card h-100 text-center category-card">
                                <div class="card-body">
                                    <i class="fas fa-dog fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title">Dogs</h5>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="products.php?category=cats" class="text-decoration-none">
                            <div class="card h-100 text-center category-card">
                                <div class="card-body">
                                    <i class="fas fa-cat fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title">Cats</h5>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="products.php?category=birds" class="text-decoration-none">
                            <div class="card h-100 text-center category-card">
                                <div class="card-body">
                                    <i class="fas fa-dove fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title">Birds</h5>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="products.php?category=fish" class="text-decoration-none">
                            <div class="card h-100 text-center category-card">
                                <div class="card-body">
                                    <i class="fas fa-fish fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title">Fish</h5>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Featured Products -->
            <div class="col-12 mb-4">
                <h2>Featured Products</h2>
                <div class="row g-4 mt-2">
                    <?php
                    // Getting featured products
                    $featuredQuery = "SELECT * FROM products WHERE featured = 1 LIMIT 4";
                    $featuredResult = $conn->query($featuredQuery);

                    if ($featuredResult && $featuredResult->num_rows > 0) {
                        while ($product = $featuredResult->fetch_assoc()) {
                            ?>
                            <div class="col-md-3 col-6">
                                <div class="card h-100 product-card">
                                    <img src="<?php echo !empty($product['image']) ? $product['image'] : 'assets/images/product-placeholder.jpg'; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                        <p class="card-text text-truncate"><?php echo $product['description']; ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="col-12"><p class="text-center">No featured products available.</p></div>';
                    }
                    ?>
                </div>
                <div class="text-center mt-4">
                    <a href="products.php" class="btn btn-primary">View All Products</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
