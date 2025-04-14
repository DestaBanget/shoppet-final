-- Create the database
CREATE DATABASE IF NOT EXISTS shoppet_db;
USE shoppet_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    category VARCHAR(50) NULL,
    image VARCHAR(255) NULL,
    featured BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_address JSON NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (name, email, password, role, created_at)
VALUES ('Admin User', 'admin@shoppet.com', '$2y$10$Og.QO6F1LUSc8IU0YO5hWedDcG5RVY6NOxEfaPJoMf3/NZJ3XWZ0a', 'admin', NOW());

-- Insert sample products
INSERT INTO products (name, description, price, stock, category, image, featured, created_at) VALUES
('Premium Dog Food', 'High-quality dog food with essential nutrients for your furry friend.', 29.99, 50, 'dogs', 'assets/images/dog-food.jpg', TRUE, NOW()),
('Cat Tree House', 'Multi-level cat tree with scratching posts and cozy hideaways.', 79.99, 20, 'cats', 'assets/images/cat-tree.jpg', TRUE, NOW()),
('Bird Cage', 'Spacious bird cage with multiple perches and feeding stations.', 49.99, 15, 'birds', 'assets/images/bird-cage.jpg', FALSE, NOW()),
('Fish Tank Filter', 'Advanced filtration system for clean and healthy aquarium water.', 34.99, 30, 'fish', 'assets/images/fish-filter.jpg', FALSE, NOW()),
('Dog Collar', 'Adjustable collar made of durable and comfortable material.', 12.99, 100, 'dogs', 'assets/images/dog-collar.jpg', FALSE, NOW()),
('Cat Toy Set', 'Set of interactive toys to keep your cat entertained.', 15.99, 40, 'cats', 'assets/images/cat-toys.jpg', TRUE, NOW()),
('Bird Food', 'Nutritious seed mix for all types of pet birds.', 9.99, 60, 'birds', 'assets/images/bird-food.jpg', FALSE, NOW()),
('Aquarium Plants', 'Natural-looking artificial plants for fish tanks.', 19.99, 25, 'fish', 'assets/images/aquarium-plants.jpg', TRUE, NOW());
