-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2025 at 07:15 PM
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
-- Database: `webgnis_users`
--

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `session_id`, `created_at`, `updated_at`) VALUES
(7, 1, 'guest_1747815353369_yjne75n0yec', '2025-05-31 12:01:01', '2025-06-01 17:13:26'),
(8, 2, 'guest_1748692891984_psi30pq43jj', '2025-05-31 12:01:24', '2025-05-31 12:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `station_id` varchar(50) NOT NULL,
  `station_name` varchar(100) DEFAULT NULL,
  `station_type` enum('horizontal','vertical','gravity') NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`item_id`, `cart_id`, `station_id`, `station_name`, `station_type`, `added_at`) VALUES
(207, 7, '1944', 'MMA-5207 (BBM-62)', 'vertical', '2025-06-01 17:01:45'),
(208, 7, '1397', 'MMA-4231', 'vertical', '2025-06-01 17:13:26');

-- --------------------------------------------------------

--
-- Table structure for table `company_details`
--

CREATE TABLE `company_details` (
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `company_address` text NOT NULL,
  `sector_id` int(11) NOT NULL,
  `authorized_representative` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `individual_details`
--

CREATE TABLE `individual_details` (
  `individual_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `individual_details`
--

INSERT INTO `individual_details` (`individual_id`, `user_id`, `full_name`, `address`) VALUES
(1, 2, 'Sample Name', 'Sample Address'),
(2, 3, 'Sample Name', 'Sample Address'),
(3, 4, 'Sample Name', 'Sample Address');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `payment_method_id` int(11) NOT NULL,
  `method_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`payment_method_id`, `method_name`, `is_active`, `display_order`) VALUES
(1, 'Link Biz', 1, 1),
(2, 'Bank Transfer', 1, 2),
(3, 'Cash Deposit', 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `exp_date` timestamp NULL DEFAULT NULL,
  `transaction_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`request_id`, `user_id`, `request_date`, `status_id`, `total_amount`, `remarks`, `exp_date`, `transaction_code`) VALUES
(57, 2, '2025-05-31 12:08:13', 1, 1200.00, NULL, '2025-06-15 12:08:13', 'CSUMGB-20250531-2-001'),
(63, 2, '2025-05-31 12:21:17', 2, 900.00, NULL, '2025-06-15 12:21:17', 'CSUMGB-20250531-2-001'),
(65, 1, '2025-05-31 12:24:22', 2, 900.00, NULL, '2025-06-15 12:24:22', 'CSUMGB-20250531-1-001'),
(66, 1, '2025-05-31 12:26:18', 1, 600.00, NULL, '2025-06-15 12:26:18', 'CSUMGB-20250531-1-002'),
(67, 1, '2025-05-31 12:26:46', 2, 1200.00, NULL, '2025-06-15 12:26:46', 'CSUMGB-20250531-1-002');

-- --------------------------------------------------------

--
-- Table structure for table `request_items`
--

CREATE TABLE `request_items` (
  `item_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `station_id` varchar(50) NOT NULL,
  `station_name` varchar(100) DEFAULT NULL,
  `station_type` enum('horizontal','vertical','gravity') NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_items`
--

INSERT INTO `request_items` (`item_id`, `request_id`, `station_id`, `station_name`, `station_type`, `price`) VALUES
(184, 57, '1', 'MMA-4269 (GM-3HA)', 'vertical', 300.00),
(185, 57, '2', 'MM-604', 'vertical', 300.00),
(186, 57, '3', 'MM-603 (MMA-3857)', 'vertical', 300.00),
(187, 57, '4', 'MM-519 (MMA-4440)', 'vertical', 300.00),
(188, 63, '4', 'MM-519 (MMA-4440)', 'vertical', 300.00),
(189, 63, '5', 'MM-518', 'vertical', 300.00),
(190, 63, '6', 'MM-10A', 'vertical', 300.00),
(191, 65, '1', 'MMA-4269 (GM-3HA)', 'vertical', 300.00),
(192, 65, '2', 'MM-604', 'vertical', 300.00),
(193, 65, '3', 'MM-603 (MMA-3857)', 'vertical', 300.00),
(194, 66, '2', 'MM-604', 'vertical', 300.00),
(195, 66, '3', 'MM-603 (MMA-3857)', 'vertical', 300.00),
(196, 67, '5', 'MM-518', 'vertical', 300.00),
(197, 67, '6', 'MM-10A', 'vertical', 300.00),
(198, 67, '7', 'MM-554', 'vertical', 300.00),
(199, 67, '8', 'MM-262', 'vertical', 300.00);

-- --------------------------------------------------------

--
-- Table structure for table `request_statuses`
--

CREATE TABLE `request_statuses` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `color_code` varchar(10) NOT NULL DEFAULT '#777777'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_statuses`
--

INSERT INTO `request_statuses` (`status_id`, `status_name`, `description`, `color_code`) VALUES
(1, 'Not Paid', 'User has not yet paid the requests', '#dc3545'),
(2, 'Paid', 'User has paid the requests', '#ffc107'),
(3, 'Pending', 'User has paid, action is on NAMRIA', '#fd7e14'),
(4, 'Approved', 'NAMRIA has approved the request, ready for download', '#28a745'),
(5, 'Not Approved', 'NAMRIA denied the request', '#6c757d'),
(6, 'Expired', 'Not paid requests and no payment received after 15 days', '#343a40');

-- --------------------------------------------------------

--
-- Table structure for table `sectors`
--

CREATE TABLE `sectors` (
  `id` int(11) NOT NULL,
  `sector_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`id`, `sector_name`) VALUES
(1, 'National Government (ENR)'),
(2, 'National Government (Others)'),
(3, 'Local Government'),
(4, 'Government Controlled Corp.'),
(5, 'Private (Company)'),
(6, 'Private (Individual)'),
(7, 'Foreign'),
(8, 'N.G.O.'),
(9, 'Academia'),
(10, 'Legislative'),
(11, 'Judiciary');

-- --------------------------------------------------------

--
-- Table structure for table `sexes`
--

CREATE TABLE `sexes` (
  `id` int(11) NOT NULL,
  `sex_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sexes`
--

INSERT INTO `sexes` (`id`, `sex_name`) VALUES
(1, 'Male'),
(2, 'Female');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `transaction_code` varchar(50) NOT NULL,
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_proof_file` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_date` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `transaction_code`, `request_id`, `user_id`, `status_id`, `payment_method_id`, `payment_amount`, `paid_amount`, `payment_reference`, `payment_proof_file`, `payment_date`, `verified`, `verified_by`, `verified_date`, `remarks`) VALUES
(27, 'CSUMGB-20250531-2-001', 63, 2, 2, 1, 900.00, 900.00, 'aa', 'payment_proof_63_683af45420ee0.jpg', '2025-05-31 12:21:40', 0, NULL, NULL, NULL),
(28, 'CSUMGB-20250531-1-001', 65, 1, 2, 1, 900.00, 900.00, 'aa', 'payment_proof_65_683af51003786.jpg', '2025-05-31 12:24:48', 0, NULL, NULL, NULL),
(29, 'CSUMGB-20250531-1-002', 67, 1, 2, 1, 1200.00, 1200.00, 'aa', 'payment_proof_67_683af586deebb.jpg', '2025-05-31 12:26:46', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `user_type` enum('individual','company','admin') NOT NULL,
  `sex_id` int(11) DEFAULT NULL,
  `name_on_certificate` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `contact_number`, `user_type`, `sex_id`, `name_on_certificate`, `created_at`, `updated_at`, `is_active`, `last_login`) VALUES
(1, 'admin', '$2y$10$l4MkyBztTrYXY.xVCx2rAeo8hSZkqC7enS0sgi3TTBeLzydcjhFHe', 'admin@webgnis.gov.ph', '09123456789', 'admin', NULL, NULL, '2025-05-17 07:45:19', '2025-05-17 08:05:32', 1, NULL),
(2, 'sample', '$2y$10$l4MkyBztTrYXY.xVCx2rAeo8hSZkqC7enS0sgi3TTBeLzydcjhFHe', 'sample@email.com', '09393939393', 'individual', 1, 'Sample', '2025-05-17 07:59:41', '2025-05-17 07:59:41', 1, NULL),
(3, 'sample2', '$2y$10$mmgFIjA2tkAwbDnv88m8p.uya9cUNcEmZakXQi9uomI.ko6.PlUwK', 'sample2@email.com', '09393939393', 'individual', 1, 'Sample', '2025-05-17 08:00:30', '2025-05-17 08:00:30', 1, NULL),
(4, 'sample3', '$2y$10$Mb2iZmsFbEnD9zq4xbUd1Ojg7PCH9XPZEppX6fAuVKGLf0sffWEtK', 'sample3@email.com', '09393939393', 'individual', 1, 'Sample', '2025-05-17 08:05:14', '2025-05-17 08:05:14', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `cart_id` (`cart_id`);

--
-- Indexes for table `company_details`
--
ALTER TABLE `company_details`
  ADD PRIMARY KEY (`company_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_company_details_user_id` (`user_id`),
  ADD KEY `idx_company_details_sector_id` (`sector_id`);

--
-- Indexes for table `individual_details`
--
ALTER TABLE `individual_details`
  ADD PRIMARY KEY (`individual_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_individual_details_user_id` (`user_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`payment_method_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `request_date` (`request_date`),
  ADD KEY `exp_date` (`exp_date`);

--
-- Indexes for table `request_items`
--
ALTER TABLE `request_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `request_statuses`
--
ALTER TABLE `request_statuses`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sexes`
--
ALTER TABLE `sexes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `payment_date` (`payment_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `sex_id` (`sex_id`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_user_type` (`user_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=209;

--
-- AUTO_INCREMENT for table `company_details`
--
ALTER TABLE `company_details`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `individual_details`
--
ALTER TABLE `individual_details`
  MODIFY `individual_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `payment_method_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `request_items`
--
ALTER TABLE `request_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `request_statuses`
--
ALTER TABLE `request_statuses`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sexes`
--
ALTER TABLE `sexes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE;

--
-- Constraints for table `company_details`
--
ALTER TABLE `company_details`
  ADD CONSTRAINT `company_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_details_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`);

--
-- Constraints for table `individual_details`
--
ALTER TABLE `individual_details`
  ADD CONSTRAINT `individual_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `request_statuses` (`status_id`);

--
-- Constraints for table `request_items`
--
ALTER TABLE `request_items`
  ADD CONSTRAINT `request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `request_statuses` (`status_id`),
  ADD CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`),
  ADD CONSTRAINT `transactions_ibfk_5` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`sex_id`) REFERENCES `sexes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
