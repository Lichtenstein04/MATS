-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 21, 2026 at 11:14 AM
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
-- Database: `mats_queue_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `queue_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('Queue','Payment','System') NOT NULL DEFAULT 'System',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `student_id`, `queue_id`, `title`, `message`, `notification_type`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'Queue Number Issued', 'Your queue number is A001. Please wait until your number is called.', 'Queue', 0, '2026-10-17 04:26:26'),
(2, 1, 2, 'Queue Number Issued', 'Your queue number is A002. Please wait until your number is called.', 'Queue', 0, '2026-10-17 04:36:54'),
(3, 1, 3, 'Queue Number Issued', 'Your queue number is A003. Please wait until your number is called.', 'Queue', 0, '2026-10-17 04:37:07'),
(4, 1, 3, 'Payment Status Updated', 'Your demonstration payment status has been marked as Paid.', 'Payment', 0, '2026-10-17 04:38:31'),
(5, 1, 4, 'Queue Number Issued', 'Your queue number is A004. Please wait until your number is called.', 'Queue', 0, '2026-10-17 04:39:28'),
(6, 1, 5, 'Queue Number Issued', 'Your queue number is A005. Please wait until your number is called.', 'Queue', 0, '2026-10-17 04:41:16'),
(7, 1, 5, 'Payment Status Updated', 'Your demonstration payment status has been marked as Paid.', 'Payment', 0, '2026-10-17 04:42:22');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `queue_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('Pending','Partially Paid','Paid','Cancelled') NOT NULL DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `student_id`, `queue_id`, `amount`, `payment_status`, `payment_method`, `reference_number`, `paid_at`, `created_at`) VALUES
(1, 1, 3, 0.00, 'Paid', 'Cash', NULL, '2026-10-17 12:38:31', '2026-10-17 04:37:07'),
(2, 1, 4, 0.00, 'Pending', NULL, NULL, NULL, '2026-10-17 04:39:28'),
(3, 1, 5, 0.00, 'Paid', 'Cash', NULL, '2026-10-17 12:42:22', '2026-10-17 04:41:16'),
(4, 1, 5, 0.00, 'Pending', NULL, NULL, NULL, '2026-10-17 04:41:16');

-- --------------------------------------------------------

--
-- Table structure for table `queue_counters`
--

CREATE TABLE `queue_counters` (
  `queue_date` date NOT NULL,
  `last_number` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue_counters`
--

INSERT INTO `queue_counters` (`queue_date`, `last_number`, `updated_at`) VALUES
('2026-10-17', 5, '2026-10-17 04:41:16');

-- --------------------------------------------------------

--
-- Table structure for table `queue_transactions`
--

CREATE TABLE `queue_transactions` (
  `queue_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `queue_number` varchar(20) NOT NULL,
  `transaction_type` varchar(100) NOT NULL,
  `status` enum('Waiting','Serving','Completed','Cancelled') NOT NULL DEFAULT 'Waiting',
  `queue_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue_transactions`
--

INSERT INTO `queue_transactions` (`queue_id`, `student_id`, `queue_number`, `transaction_type`, `status`, `queue_date`, `created_at`) VALUES
(1, 1, 'A001', 'Examination Payment', 'Completed', '2026-10-17', '2026-10-17 04:26:26'),
(2, 1, 'A002', 'Other Student Service', 'Completed', '2026-10-17', '2026-10-17 04:36:54'),
(3, 1, 'A003', 'Examination Payment', 'Serving', '2026-10-17', '2026-10-17 04:37:07'),
(4, 1, 'A004', 'Examination Payment', 'Waiting', '2026-10-17', '2026-10-17 04:39:28'),
(5, 1, 'A005', 'Examination Payment', 'Waiting', '2026-10-17', '2026-10-17 04:41:16'),
(6, 2, 'A001', 'Other Student Service', 'Waiting', '2026-10-18', '2026-10-18 03:21:17'),
(12, 1, 'A002', 'Examination Payment', 'Waiting', '2026-10-18', '2026-10-18 03:52:22'),
(13, 4, 'A003', 'Examination Payment', 'Waiting', '2026-10-18', '2026-10-18 03:56:21'),
(20, 5, 'A004', 'Examination Payment', 'Waiting', '2026-10-18', '2026-10-18 03:59:29'),
(21, NULL, 'A005', 'Examination Payment', 'Waiting', '2026-10-18', '2026-10-18 04:15:10'),
(22, NULL, 'A006', 'Other Student Service', 'Waiting', '2026-10-18', '2026-10-18 04:15:13'),
(23, NULL, 'A007', 'Examination Payment', 'Waiting', '2026-10-18', '2026-10-18 04:15:14'),
(24, NULL, 'A008', 'Other Student Service', 'Waiting', '2026-10-18', '2026-10-18 04:15:14'),
(25, NULL, 'A009', 'Examination Payment', 'Waiting', '2026-10-18', '2026-10-18 04:15:15'),
(26, NULL, 'A010', 'Other Student Service', 'Waiting', '2026-10-18', '2026-10-18 04:15:15'),
(27, NULL, 'A011', 'Examination Payment', 'Waiting', '2026-10-18', '2026-10-18 04:15:16'),
(28, NULL, 'A012', 'Examination Payment', 'Waiting', '2026-10-18', '2026-10-18 04:18:39');

-- --------------------------------------------------------

--
-- Table structure for table `staff_accounts`
--

CREATE TABLE `staff_accounts` (
  `staff_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_accounts`
--

INSERT INTO `staff_accounts` (`staff_id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'staffadmin', '$2y$10$T7LlkjawEL.5RDwzwnnvIe9ERuznIBbIDjVmIPp/YPFkkyFhCpKbG', '2026-10-17 04:47:17');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `student_number` varchar(30) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_number`, `first_name`, `last_name`, `course`, `year_level`, `created_at`) VALUES
(1, '169765', 'Antonio', 'Cenon', 'BSIT', '1st Year', '2026-10-16 01:33:20'),
(2, '123456789', 'Antonio', 'Cenon', 'BSEE', '4th Year', '2026-10-18 03:19:22'),
(3, '15679', 'Kat', 'Alfornon', 'BSBA', '3rd Year', '2026-10-18 03:55:54'),
(4, '101305', 'Kat', 'Alfornon', 'BSBA', '3rd Year', '2026-10-18 03:56:12'),
(5, '1234567', 'KAT', 'ALFORNON', 'BSBA', '3rd Year', '2026-10-18 03:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'institution_name', 'MATS College of Technology', 'Name of the institution', '2026-10-16 01:19:09'),
(2, 'queue_prefix', 'A', 'Prefix used for queue ticket numbers', '2026-10-16 01:19:09'),
(3, 'examination_fee', '0.00', 'Placeholder until the official fee is confirmed', '2026-10-16 01:19:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notification_student` (`student_id`),
  ADD KEY `fk_notification_queue` (`queue_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_student` (`student_id`),
  ADD KEY `fk_payment_queue` (`queue_id`);

--
-- Indexes for table `queue_counters`
--
ALTER TABLE `queue_counters`
  ADD PRIMARY KEY (`queue_date`);

--
-- Indexes for table `queue_transactions`
--
ALTER TABLE `queue_transactions`
  ADD PRIMARY KEY (`queue_id`),
  ADD UNIQUE KEY `unique_daily_queue` (`queue_date`,`queue_number`),
  ADD UNIQUE KEY `uq_queue_date_number` (`queue_date`,`queue_number`),
  ADD KEY `fk_queue_student` (`student_id`);

--
-- Indexes for table `staff_accounts`
--
ALTER TABLE `staff_accounts`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_number` (`student_number`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `queue_transactions`
--
ALTER TABLE `queue_transactions`
  MODIFY `queue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `staff_accounts`
--
ALTER TABLE `staff_accounts`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_queue` FOREIGN KEY (`queue_id`) REFERENCES `queue_transactions` (`queue_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notification_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_queue` FOREIGN KEY (`queue_id`) REFERENCES `queue_transactions` (`queue_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON UPDATE CASCADE;

--
-- Constraints for table `queue_transactions`
--
ALTER TABLE `queue_transactions`
  ADD CONSTRAINT `fk_queue_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
