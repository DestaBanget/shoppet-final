<?php
session_start();
require_once 'config/database.php';
require_once 'functions/utilities.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get product details
$productQuery = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($productQuery);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$productResult = $stmt->get_result();

// Check if product exists
if ($productResult->num_rows == 0) {
    redirectWithMessage('products.php', 'Product not found', 'danger');
}

$product = $productResult->fetch_assoc();
$stmt->close();

// Handle add to cart action
if (isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Validate quantity
    if ($quantity <= 0) {
        $quantity = 1;
    }
    
    // Check stock availability
    if ($quantity > $product['stock']) {
        $message = "Sorry, only {$product['stock']} units available in stock";
        $message_type = "danger";
    } else {
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        
        // Check if product already in cart
        if (isset($_SESSION['cart'][$product_id])) {
            // Update quantity if already in cart
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            // Add new product to cart
            $_SESSION['cart'][$product_id] = array(
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => $product['image']
            );
        }
        
        $message = "{$product['name']} added to cart successfully";
        $message_type = "success";
    }
    
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
    
    // Redirect to prevent form resubmission
    header("Location: product.php?id=$product_id");
    exit;
}

// Get product reviews
$reviewsQuery = "SELECT r.*, u.name as user_name FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ? 
                ORDER BY r.created_at DESC";
$stmt = $conn->prepare($reviewsQuery);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviewsResult = $stmt->get_result();
$stmt->close();

// Handle review submission
if (isset($_POST['submit_review']) && isLoggedIn()) {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? sanitizeInput($_POST['comment']) : '';
    $user_id = $_SESSION['user_id'];
    
    // Validate input
    if ($rating < 1 || $rating > 5) {
        $_SESSION['review_error'] = "Please select a rating between 1 and 5";
    } elseif (empty($comment)) {
        $_SESSION['review_error'] = "Please enter a comment";
    } else {
        // Check if user has already reviewed this product
        $checkReviewQuery = "SELECT id FROM reviews WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($checkReviewQuery);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $checkResult = $stmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Update existing review
            $updateReviewQuery = "UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($updateReviewQuery);
            $stmt->bind_param("isii", $rating, $comment, $user_id, $product_id);
            
            if ($stmt->execute()) {
                $_SESSION['review_success'] = "Your review has been updated successfully";
            } else {
                $_SESSION['review_error'] = "Error updating review: " . $conn->error;
            }
        } else {
            // Insert new review
            $insertReviewQuery = "INSERT INTO reviews (product_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insertReviewQuery);
            $stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);
            
            if ($stmt->execute()) {
                $_SESSION['review_success'] = "Your review has been submitted successfully";
            } else {
                $_SESSION['review_error'] = "Error submitting review: " . $conn->error;
            }
        }
        
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header("Location: product.php?id=$product_id");
        exit;
    }
}

// Calculate average rating
$avgRating = getAverageRating($product_id, $conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - ShopPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category']; ?>"><?php echo $product['category']; ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $product['name']; ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Product Image -->
            <div class="col-md-6 mb-4">
                <img src="<?php echo !empty($product['image']) ? $product['image'] : 'assets/images/product-placeholder.jpg'; ?>" alt="<?php echo $product['name']; ?>" class="img-fluid rounded product-detail-img">
            </div>
            
            <!-- Product Details -->
            <div class="col-md-6">
                <h1 class="mb-3"><?php echo $product['name']; ?></h1>
                
                <div class="mb-3">
                    <div class="d-flex align-items-center">
                        <div class="star-rating me-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $avgRating): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i <= $avgRating + 0.5): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="text-muted">(<?php echo $reviewsResult->num_rows; ?> reviews)</span>
                    </div>
                </div>
                
                <h2 class="price mb-4">$<?php echo number_format($product['price'], 2); ?></h2>
                
                <div class="mb-4">
                    <p><?php echo nl2br($product['description']); ?></p>
                </div>
                
                <div class="mb-4">
                    <p class="<?php echo $product['stock'] > 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php if ($product['stock'] > 0): ?>
                            <i class="fas fa-check-circle me-1"></i> In Stock (<?php echo $product['stock']; ?> available)
                        <?php else: ?>
                            <i class="fas fa-times-circle me-1"></i> Out of Stock
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                    <form action="product.php?id=<?php echo $product_id; ?>" method="POST">
                        <div class="mb-4">
                            <label for="quantity" class="form-label">Quantity</label>
                            <div class="input-group" style="width: 150px;">
                                <button type="button" class="btn btn-outline-secondary" id="decrement">-</button>
                                <input type="number" class="form-control text-center" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                <button type="button" class="btn btn-outline-secondary" id="increment">+</button>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-block">
                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        <p class="mb-0">This product is currently out of stock. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="mt-5">
            <h3>Customer Reviews</h3>
            <hr>
            
            <!-- Review Form -->
            <?php if (isLoggedIn()): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Write a Review</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['review_error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['review_error']; ?>
                                <?php unset($_SESSION['review_error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['review_success'])): ?>
                            <div class="alert alert-success">
                                <?php echo $_SESSION['review_success']; ?>
                                <?php unset($_SESSION['review_success']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="product.php?id=<?php echo $product_id; ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <div class="star-rating mb-3">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <label class="rating-label me-2">
                                            <input type="radio" name="rating" value="<?php echo $i; ?>" class="rating-input d-none">
                                            <i class="far fa-star fa-lg"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="comment" class="form-label">Your Review</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                            </div>
                            
                            <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-4">
                    <p class="mb-0">Please <a href="login.php">login</a> to leave a review.</p>
                </div>
            <?php endif; ?>
            
            <!-- Reviews List -->
            <?php if ($reviewsResult->num_rows > 0): ?>
                <?php while($review = $reviewsResult->fetch_assoc()): ?>
                    <div class="card mb-3 review-card">
                        <div class="card-body">
                            <div class="d-flex mb-3">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo $review['user_name']; ?></h5>
                                    <div class="text-muted small">
                                        <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                    </div>
                                    <div class="star-rating mt-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <p class="mb-0"><?php echo nl2br($review['comment']); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-light">
                    <p class="mb-0">No reviews yet. Be the first to review this product!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
