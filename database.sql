-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 18 Apr 2025 pada 09.48
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shoppet_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(50) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `ip_address`, `timestamp`) VALUES
(1, 3, 'Admin logged in', '::1', '2025-04-14 20:29:26'),
(2, 3, 'Admin logged in', '::1', '2025-04-14 20:43:01'),
(3, 3, 'Admin logged in', '::1', '2025-04-14 21:03:58'),
(4, 3, 'Admin logged in', '::1', '2025-04-14 22:15:20'),
(5, 3, 'Admin logged in', '::1', '2025-04-15 02:49:38'),
(6, 3, 'Admin logged in', '::1', '2025-04-15 04:01:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(3, 7, 9, 1, '2025-04-14 21:54:24'),
(4, 7, 10, 1, '2025-04-14 21:56:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `created_at`) VALUES
(1, 'Dogs', 'dogs', '2025-04-14 21:39:11'),
(2, 'Cats', 'cats', '2025-04-14 21:39:11'),
(3, 'Birds', 'birds', '2025-04-14 21:39:11'),
(4, 'Fish', 'fish', '2025-04-14 21:39:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`shipping_address`)),
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `shipping_address`, `payment_method`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 97.99, '{\"fullname\":\"fauzann\",\"address\":\"Perum Loka Pratama blok A-17, RT 12, RW 04, Tambakasri, Tajinan\",\"city\":\"KABUPATEN MALANG\",\"state\":\"JAWA TIMUR\",\"zip\":\"68111\"}', 'credit_card', 'processing', '2025-04-14 20:25:59', NULL),
(2, 8, 42.99, '{\"fullname\":\"user1\",\"address\":\"aa\",\"city\":\"a\",\"state\":\"a\",\"zip\":\"a\"}', 'credit_card', 'pending', '2025-04-15 03:11:43', NULL),
(3, 8, 42.99, '{\"fullname\":\"user1\",\"address\":\"aa\",\"city\":\"a\",\"state\":\"a\",\"zip\":\"a\"}', 'credit_card', 'pending', '2025-04-15 03:13:48', NULL),
(4, 8, 53.96, '{\"fullname\":\"user1\",\"address\":\"aa\",\"city\":\"a\",\"state\":\"a\",\"zip\":\"a\"}', 'credit_card', 'pending', '2025-04-15 03:29:30', NULL),
(5, 8, 108.97, '{\"fullname\":\"user1\",\"address\":\"aa\",\"city\":\"a\",\"state\":\"a\",\"zip\":\"a\"}', 'credit_card', 'pending', '2025-04-15 05:16:46', NULL),
(6, 8, 31.98, '{\"fullname\":\"user1\",\"address\":\"aa\",\"city\":\"a\",\"state\":\"a\",\"zip\":\"a\"}', 'credit_card', 'pending', '2025-04-15 05:23:52', NULL),
(7, 8, 24.29, '{\"fullname\":\"user1\",\"address\":\"aa\",\"city\":\"a\",\"state\":\"a\",\"zip\":\"a\"}', 'paypal', 'pending', '2025-04-15 05:29:27', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 2, 1, 79.99),
(2, 2, 1, 1, 29.99),
(3, 3, 1, 1, 29.99),
(4, 4, 15, 4, 9.99),
(5, 5, 1, 3, 29.99),
(6, 6, 15, 2, 9.99),
(7, 7, 13, 1, 12.99);

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `payment_status` varchar(50) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `category`, `image`, `featured`, `created_at`, `updated_at`, `category_id`) VALUES
(1, 'Premium Dog Food', 'High-quality dog food with essential nutrients for your furry friend.', 29.99, 45, 'dogs', 'assets/images/dog-food.jpg', 1, '2025-04-14 06:20:17', NULL, 1),
(2, 'Cat Tree House', 'Multi-level cat tree with scratching posts and cozy hideaways.', 79.99, 19, 'cats', 'assets/images/cat-tree.jpg', 1, '2025-04-14 06:20:17', NULL, 2),
(3, 'Bird Cage', 'Spacious bird cage with multiple perches and feeding stations.', 49.99, 15, 'birds', 'assets/images/bird-cage.jpg', 0, '2025-04-14 06:20:17', NULL, 3),
(4, 'Fish Tank Filter', 'Advanced filtration system for clean and healthy aquarium water.', 34.99, 30, 'fish', 'assets/images/fish-filter.jpg', 0, '2025-04-14 06:20:17', NULL, 4),
(5, 'Dog Collar', 'Adjustable collar made of durable and comfortable material.', 12.99, 100, 'dogs', 'assets/images/dog-collar.jpg', 0, '2025-04-14 06:20:17', NULL, 1),
(6, 'Cat Toy Set', 'Set of interactive toys to keep your cat entertained.', 15.99, 40, 'cats', 'assets/images/cat-toys.jpg', 1, '2025-04-14 06:20:17', NULL, 2),
(7, 'Bird Food', 'Nutritious seed mix for all types of pet birds.', 9.99, 60, 'birds', 'assets/images/bird-food.jpg', 0, '2025-04-14 06:20:17', NULL, 3),
(8, 'Aquarium Plants', 'Natural-looking artificial plants for fish tanks.', 19.99, 25, 'fish', 'assets/images/aquarium-plants.jpg', 1, '2025-04-14 06:20:17', NULL, 4),
(9, 'Premium Dog Food', 'High-quality dog food with essential nutrients for your furry friend.', 29.99, 50, 'dogs', 'assets/images/dog-food.jpg', 1, '2025-04-14 20:24:10', NULL, 1),
(10, 'Cat Tree House', 'Multi-level cat tree with scratching posts and cozy hideaways.', 79.99, 20, 'cats', 'assets/images/cat-tree.jpg', 1, '2025-04-14 20:24:10', NULL, 2),
(11, 'Bird Cage', 'Spacious bird cage with multiple perches and feeding stations.', 49.99, 15, 'birds', 'assets/images/bird-cage.jpg', 0, '2025-04-14 20:24:10', NULL, 3),
(12, 'Fish Tank Filter', 'Advanced filtration system for clean and healthy aquarium water.', 34.99, 30, 'fish', 'assets/images/fish-filter.jpg', 0, '2025-04-14 20:24:10', NULL, 4),
(13, 'Dog Collar', 'Adjustable collar made of durable and comfortable material.', 12.99, 99, 'dogs', 'assets/images/dog-collar.jpg', 0, '2025-04-14 20:24:10', NULL, 1),
(14, 'Cat Toy Set', 'Set of interactive toys to keep your cat entertained.', 15.99, 40, 'cats', 'assets/images/cat-toys.jpg', 1, '2025-04-14 20:24:10', NULL, 2),
(15, 'Bird Food', 'Nutritious seed mix for all types of pet birds.', 9.99, 54, 'birds', 'assets/images/bird-food.jpg', 0, '2025-04-14 20:24:10', NULL, 3),
(16, 'Aquarium Plants', 'Natural-looking artificial plants for fish tanks.', 19.99, 25, 'fish', 'assets/images/aquarium-plants.jpg', 1, '2025-04-14 20:24:10', NULL, 4),
(17, 'Fish Tank', 'fish fish tank tank!', 30.00, 5, '0', 'uploads/products/1744638282_fishtank.jpeg', 0, '0000-00-00 00:00:00', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 15, 8, 4, 'bagus', '2025-04-15 05:23:43', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`, `profile_image`) VALUES
(1, 'Admin User', 'admin@shoppet.com', '$2y$10$Og.QO6F1LUSc8IU0YO5hWedDcG5RVY6NOxEfaPJoMf3/NZJ3XWZ0a', 'admin', '2025-04-14 06:20:17', NULL, NULL),
(2, 'fauzan', 'fauzan@gmail.com', '$2y$10$BVz7LSUNcunARoSu63dU.OovwNbVog7dLN0Lf4SDKH75ipKtu2d2q', 'admin', '2025-04-14 06:20:48', NULL, NULL),
(3, 'ini admin', 'iniadmin@shoppet.com', '$2y$10$UvLH257xaQKZgH1FD2XHi.4JKKcVTe6/kWuew9o6w58Nwnj8VK44O', 'admin', '2025-04-14 20:19:45', NULL, NULL),
(5, 'fauzann', 'fauzann@shoppet.com', '$2y$10$BWccgmlJ5boqFupzLLF4S.HvbKlptgT3iAH/sAtxoUaeCvIV.d6JC', 'admin', '2025-04-14 20:25:03', NULL, NULL),
(6, 'mozaik', 'mozaik@shoppet.com', '$2y$10$jk/QA1HjaJwqbK.VWqlBAO8SrF4sjLJ.ssNnoAS6iqZ6lvF4Up0XW', 'user', '2025-04-14 20:59:10', NULL, NULL),
(7, 'desta', 'desta@shoppet.com', '$2y$10$wsy9OyMH2J.X2MkLalfmo.a.1mvjZ123ITYBzwjzlQ4JXwKRjRpuu', 'user', '2025-04-14 21:43:54', NULL, NULL),
(8, 'user1', 'user1@shoppet.com', '$2y$10$Fq6isWdILw96ui5xQbTP6efU5rxmDY.1BrO/1YhyPJEN/i0MXjSAi', 'user', '2025-04-15 02:51:36', NULL, 'uploads/profile_images/Admin Logout.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 2, 'login', 'User logged in', '::1', '2025-04-13 23:21:02'),
(2, 5, 'login', 'User logged in', '::1', '2025-04-14 13:25:36'),
(3, 5, 'login', 'User logged in', '::1', '2025-04-14 13:39:37'),
(4, 6, 'login', 'User logged in', '::1', '2025-04-14 13:59:23'),
(5, 7, 'login', 'User logged in', '::1', '2025-04-14 14:44:05'),
(6, 7, 'login', 'User logged in', '::1', '2025-04-14 14:46:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_logs`
--

CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_logs`
--

INSERT INTO `user_logs` (`id`, `user_id`, `action`, `description`, `created_at`) VALUES
(1, 8, 'login', 'User logged in', '2025-04-15 04:10:24'),
(2, 8, 'login', 'User logged in', '2025-04-15 04:12:00'),
(3, 8, 'login', 'User logged in', '2025-04-15 05:22:53'),
(4, 8, 'login', 'User logged in', '2025-04-15 08:42:08'),
(5, 8, 'login', 'User logged in', '2025-04-15 09:15:29'),
(6, 8, 'login', 'User logged in', '2025-04-15 09:36:21');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Ketidakleluasaan untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Ketidakleluasaan untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
