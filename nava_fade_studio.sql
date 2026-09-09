-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2026 at 02:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nava_fade_studio`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service` varchar(255) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `customer_id`, `service`, `appointment_date`, `appointment_time`, `notes`, `status`, `created_at`) VALUES
(1, 1, 'Hair Cut, Facial & Wash', '2027-07-16', '08:48:00', 'hii po', 'Confirmed', '2026-09-06 16:49:24'),
(2, 2, 'Facial & Wash', '2026-09-09', '02:58:00', '', 'Completed', '2026-09-06 16:58:50'),
(3, 2, 'Facial & Wash, Clean Shave', '2026-09-24', '16:24:00', '', 'Confirmed', '2026-09-07 06:23:57'),
(6, 3, 'Hair Cut, Hair Wash, Clean Shave', '2026-09-12', '10:00:00', 'same as usual', 'Completed', '2026-09-08 22:58:39');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `contact_number`, `password`, `created_at`) VALUES
(1, 'Ferlyn Acebes', 'ferlyn@gmail.com', '0994678890', '$2y$10$r/R69rBCxRP5tg1OSS2NJOV.nXqXL7Gfwi1Vbk56rvbDH37EcflUm', '2026-09-06 16:40:56'),
(2, 'Ferlyn Dela Pena', 'tabudi@gmail.com', '0994678890', '$2y$10$TNzUQvFpqXJ8gw8NOUi6uOvzvoaa8Ct0awXigvmj83cRswiNpn5FS', '2026-09-06 16:57:00'),
(3, 'Archilles Navarro', 'agnavarro.student@asiancollege.edu.ph', '09694074629', '$2y$10$gQ30mqoMQXla2f91HgvD5uROVKo6/5a9MLdJD79/19SRY11wnfcya', '2026-09-07 21:13:22'),
(4, 'lorins', 'lorins@pornhub.com', '0912345678', '$2y$10$kc73zA8sFqLzyZj7GLzQ5Oi3spDMJW5/pErgV837NkLg4TVnCUyDS', '2026-09-08 01:01:11');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Confirmed','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `total_amount`, `status`, `created_at`) VALUES
(3, 3, 1400.00, 'Confirmed', '2026-09-08 21:49:47'),
(4, 3, 1750.00, 'Pending', '2026-09-08 23:22:17');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(3, 3, 2, 5, 280.00, 1400.00),
(4, 4, 1, 5, 350.00, 1750.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `payment_method` enum('Cash','GCash') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Paid','Failed') NOT NULL DEFAULT 'Pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `customer_id`, `order_id`, `appointment_id`, `payment_method`, `reference_number`, `receipt_image`, `amount`, `status`, `paid_at`, `created_at`) VALUES
(1, 3, 3, NULL, 'GCash', 'DDS213468124', NULL, 1400.00, 'Paid', '2026-09-09 07:02:08', '2026-09-08 21:49:47'),
(2, 3, NULL, 6, 'GCash', 'DDS213468124', NULL, 400.00, 'Paid', '2026-09-09 07:00:31', '2026-09-08 22:58:39'),
(3, 3, 4, NULL, 'GCash', '6854FD2426', NULL, 1750.00, 'Paid', '2026-09-09 07:23:51', '2026-09-08 23:22:17');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `description`, `price`, `stock`, `image`, `status`, `created_at`) VALUES
(1, 'NAVA Shampoo', 'NAVA professional shampoo for a clean, fresh, and well-maintained hair.', 350.00, 5, 'shampoo.png', 'Active', '2026-09-06 16:39:00'),
(2, 'NAVA Hair Clay', 'NAVA hair clay for styling and creating a strong, clean hairstyle.', 280.00, 13, 'hair-clay.png', 'Active', '2026-09-06 16:39:00'),
(3, 'NAVA Hair Spray', 'NAVA hair spray that helps keep your hairstyle in place throughout the day.', 300.00, 10, 'hair-spray.png', 'Active', '2026-09-06 16:39:00');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text NOT NULL,
  `status` enum('Pending','Approved','Hidden') NOT NULL DEFAULT 'Pending',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `customer_id`, `rating`, `comment`, `status`, `is_featured`, `created_at`) VALUES
(1, 3, 4, 'I love my new haircut yesterday, I\'d love to come in your barbershop next time!', 'Approved', 0, '2026-09-08 08:52:22');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `description`, `price`, `duration`, `image`, `created_at`) VALUES
(1, 'Hair Cut', 'Professional haircut tailored to your preferred style and look.', 100.00, '30 minutes', 'hair-cut.png', '2026-09-06 16:38:17'),
(2, 'Facial & Wash', 'Refreshing facial and hair wash treatment for a clean and fresh feeling.', 550.00, '30 to 60 minutes', 'facial.png', '2026-09-06 16:38:17'),
(3, 'Beard Trimming', 'Clean and precise beard trimming to maintain a sharp and well-groomed appearance.', 150.00, '15 to 30 minutes', 'beard-trim.png', '2026-09-06 16:38:17'),
(4, 'Hair Wash', 'Quick and refreshing hair wash to keep your hair clean and fresh.', 50.00, '5 to 15 minutes', 'hair-wash.png', '2026-09-06 16:38:17'),
(5, 'Clean Shave', 'Professional clean shave for a smooth and well-groomed finish.', 250.00, '20 to 30 minutes', 'clean-shave.png', '2026-09-06 16:38:17'),
(6, 'Hot Towel Treatment', 'Relaxing hot towel treatment designed to refresh and soften the skin.', 150.00, '15 minutes', 'hot-towel.png', '2026-09-06 16:38:17'),
(7, 'Hair Styling', 'Professional hair styling for a polished look suitable for any occasion.', 300.00, '30 minutes', 'hair-styling.png', '2026-09-06 16:38:17'),
(8, 'Hair Treatment', 'Deep hair treatment designed to improve hair condition and maintain healthy-looking hair.', 800.00, '30 to 60 minutes', 'hair-treatment.png', '2026-09-06 16:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'studio_name', 'NAVA Fade Studio', '2026-09-06 16:21:57'),
(2, 'email', 'navafadestudio@gmail.com', '2026-09-06 16:21:57'),
(3, 'phone', '0969 407 4629 / 0316-073', '2026-09-06 16:21:57'),
(4, 'address', 'Jugno, Amlan, Negros Oriental, Philippines', '2026-09-06 16:21:57'),
(5, 'weekday_hours', '9:00 AM - 8:00 PM', '2026-09-06 16:21:57'),
(6, 'saturday_hours', '10:00 AM - 7:00 PM', '2026-09-06 16:21:57'),
(7, 'sunday_hours', '10:00 AM - 7:00 PM', '2026-09-06 16:21:57'),
(8, 'discount', '20', '2026-09-06 16:21:57'),
(9, 'description', 'NAVA Fade Studio is dedicated to delivering clean, modern, and personalized grooming experiences. From sharp haircuts and beard trims to relaxing treatments, our goal is to help every client look fresh, feel confident, and leave with a style they will love.', '2026-09-06 16:21:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_appointments_customer` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_customer` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_appointment_id` (`appointment_id`),
  ADD KEY `idx_payment_status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reviews_customer` (`customer_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
