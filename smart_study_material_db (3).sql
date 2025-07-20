-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 20, 2025 at 05:33 AM
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
-- Database: `smart_study_material_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'dev', 'dev@gmail.com', 'dev', '2025-07-02 03:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `downloads`
--

CREATE TABLE `downloads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `material_id` int(11) DEFAULT NULL,
  `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `downloads_summary`
--

CREATE TABLE `downloads_summary` (
  `id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_reviews`
--

CREATE TABLE `file_reviews` (
  `id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_types`
--

CREATE TABLE `material_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `material_types`
--

INSERT INTO `material_types` (`id`, `type_name`) VALUES
(3, 'ass'),
(4, 'journal');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `name`) VALUES
(3, 'sem 1');

-- --------------------------------------------------------

--
-- Table structure for table `study_materials`
--

CREATE TABLE `study_materials` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `material_type_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visible_to_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `study_materials`
--

INSERT INTO `study_materials` (`id`, `title`, `semester_id`, `subject_id`, `material_type_id`, `file_name`, `uploaded_by`, `uploaded_at`, `visible_to_user_id`) VALUES
(4, 'ass 1', 3, 3, 3, '68652b6b8ab8e8.79211978.pdf', 1, '2025-07-02 12:51:55', NULL),
(5, 'php', 3, 3, 4, '68652be2c75f37.13709560.pdf', 1, '2025-07-02 12:53:54', NULL),
(6, 'prg 1-10', 3, 3, 4, '68652c1363bf64.45302995.pdf', 1, '2025-07-02 12:54:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `semester_id`, `name`) VALUES
(3, 3, 'ds');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `image`, `last_login`, `updated_at`, `created_at`) VALUES
(4, 'DEVKUMAR KIRITBHAI PATEL', 'dev@gmail.com', '$2y$10$7JKLQxaUHxvbVSkP4H7HKeCZsSRtXAqm7qEb3F4BDsJY4dWJfWP0m', '68652b944cd1d.png', '2025-07-02 18:22:38', '2025-07-02 12:52:38', '2025-07-02 12:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `activity_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`id`, `user_id`, `activity_type`, `title`, `description`, `activity_time`) VALUES
(27, 4, 'login', NULL, 'User logged in', '2025-07-02 12:52:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `downloads`
--
ALTER TABLE `downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `downloads_summary`
--
ALTER TABLE `downloads_summary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `file_reviews`
--
ALTER TABLE `file_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `material_types`
--
ALTER TABLE `material_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `material_type_id` (`material_type_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semester_id` (`semester_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `downloads`
--
ALTER TABLE `downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `downloads_summary`
--
ALTER TABLE `downloads_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `file_reviews`
--
ALTER TABLE `file_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `material_types`
--
ALTER TABLE `material_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `study_materials`
--
ALTER TABLE `study_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `downloads`
--
ALTER TABLE `downloads`
  ADD CONSTRAINT `downloads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `downloads_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `study_materials` (`id`);

--
-- Constraints for table `downloads_summary`
--
ALTER TABLE `downloads_summary`
  ADD CONSTRAINT `downloads_summary_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `study_materials` (`id`);

--
-- Constraints for table `file_reviews`
--
ALTER TABLE `file_reviews`
  ADD CONSTRAINT `file_reviews_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `study_materials` (`id`),
  ADD CONSTRAINT `file_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD CONSTRAINT `study_materials_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  ADD CONSTRAINT `study_materials_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `study_materials_ibfk_3` FOREIGN KEY (`material_type_id`) REFERENCES `material_types` (`id`),
  ADD CONSTRAINT `study_materials_ibfk_4` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
