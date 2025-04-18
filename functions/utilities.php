<?php
// Function to sanitize user input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to calculate average rating for a product
function getAverageRating($productId, $conn) {
    $query = "SELECT AVG(rating) as average FROM reviews WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return round($row['average'] ?? 0, 1);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function logUserActivity($userId, $action, $description = '') {
    global $conn;

    $userId = intval($userId);
    $action = mysqli_real_escape_string($conn, $action);
    $description = mysqli_real_escape_string($conn, $description);

    $query = "INSERT INTO user_logs (user_id, action, description, created_at) 
              VALUES ($userId, '$action', '$description', NOW())";

    $conn->query($query);
}


// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to redirect with a message
function redirectWithMessage($location, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $location");
    exit;
}

// Function to get cart item count
function getCartItemCount() {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
    return 0;
}

// Function to check if a product is in stock
function isProductInStock($productId, $quantity, $conn) {
    $query = "SELECT stock FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return ($row && $row['stock'] >= $quantity);
}
