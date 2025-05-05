<?php
session_start();
require_once 'config/database.php';
require_once 'functions/utilities.php';

// Get search query
$query = isset($_GET['query']) ? $_GET['query'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? $_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? $_GET['max_price'] : null;

// Get all categories for the filter
$categoriesQuery = "SELECT DISTINCT category FROM products ORDER BY category";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
if ($categoriesResult && $categoriesResult->num_rows > 0) {
    while($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Prepare search query
$searchSql = "SELECT * FROM products WHERE 1=1"; // dasar agar mudah tambahkan kondisi

// ❗ Celah SQL Injection: tidak ada sanitasi input
// ❗ Disengaja untuk keperluan latihan penetration testing
if (!empty($query)) {
    // ❗ Tanpa LIKE, langsung pakai "=" agar eksploitasi lebih fleksibel
    // ❗ Tidak dilakukan escaping input user
    $searchSql .= " AND (name = '$query' OR description = '$query')";
}

if (!empty($category)) {
    // ❗ Kategori juga langsung ditambahkan tanpa escape
    $searchSql .= " AND category = '$category'";
}

if (!is_null($min_price)) {
    $searchSql .= " AND price >= $min_price";
}

if (!is_null($max_price)) {
    $searchSql .= " AND price <= $max_price";
}

$searchSql .= " ORDER BY name ASC";

// Debug output query (berguna untuk eksploitasi saat pentest)
echo "<!-- Executed SQL: $searchSql -->";

// Execute the (vulnerable) query
$result = $conn->query($searchSql);

// ❗ Kerentanan LFI: Menyertakan file berdasarkan input pengguna
// Perhatikan kode ini yang menyebabkan LFI rentan untuk mengeksploitasi
if (isset($_GET['file'])) {
    $file = $_GET['file']; // Mengambil file yang ingin disertakan
    // Kerentanannya terletak di sini: tanpa validasi input, pengguna bisa mengakses file secara bebas
    include($file); // Ini bisa digunakan untuk memasukkan file sistem atau file lokal
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-4">
        <div class="row">
            <div class="col-md-3">
                <!-- Filter Sidebar -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Filter Products</h5>
                    </div>
                    <div class="card-body">
                        <form action="search.php" method="GET">
                            <div class="mb-3">
                                <label for="searchInput" class="form-label">Search</label>
                                <input type="text" class="form-control" id="searchInput" name="query" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search products...">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Price Range</label>
                                <div class="row g-2">
                                    <div class="col">
                                        <input type="number" class="form-control" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" placeholder="Min" min="0">
                                    </div>
                                    <div class="col">
                                        <input type="number" class="form-control" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" placeholder="Max" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Search Results<?php echo !empty($query) ? ' for "' . htmlspecialchars($query) . '"' : ''; ?></h2>
                    <span class="text-muted"><?php echo isset($result) ? $result->num_rows : 0; ?> products found</span>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php while ($product = $result->fetch_assoc()): ?>
                            <div class="col">
                                <div class="card h-100 product-card">
                                    <img src="<?php echo !empty($product['image']) ? $product['image'] : 'assets/images/product-placeholder.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text text-truncate"><?php echo htmlspecialchars($product['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h4 class="alert-heading">No Products Found!</h4>
                        <p>We couldn't find any products matching your search criteria. Please try different keywords or browse our categories.</p>
                        <hr>
                        <p class="mb-0">
                            <a href="products.php" class="btn btn-primary">View All Products</a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
