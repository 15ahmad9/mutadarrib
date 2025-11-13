-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2025 at 12:46 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mutadarrib`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` bigint(20) NOT NULL,
  `entity` varchar(100) DEFAULT NULL,
  `entity_id` varchar(100) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `lawyers`
--

CREATE TABLE `lawyers` (
  `lawyer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `master_id` int(11) DEFAULT NULL,
  `office_address` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `lawyers`
--

INSERT INTO `lawyers` (`lawyer_id`, `user_id`, `master_id`, `office_address`, `password`, `verified`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'عمان', '$2y$10$j3ACPUVlqG8KtWvMaDVtWeXbNHvoSkmSFC9KbtBBMUHoTGtvIocCm', 1, '2025-11-12 00:22:59', '2025-11-12 00:22:59');

-- --------------------------------------------------------

--
-- Table structure for table `lawyers_master`
--

CREATE TABLE `lawyers_master` (
  `master_id` int(11) NOT NULL,
  `lawyer_name` varchar(200) NOT NULL,
  `national_id` varchar(30) NOT NULL,
  `office_address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `lawyers_master`
--

INSERT INTO `lawyers_master` (`master_id`, `lawyer_name`, `national_id`, `office_address`, `phone`, `email`, `notes`, `created_at`) VALUES
(1, 'محمد المحامي', '1111111111', 'عمان', '0790000000', 'lawyer1@example.com', 'مسجل لدى النقابة', '2025-11-12 00:18:50');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `highschool_certificate` enum('نعم','لا') NOT NULL DEFAULT 'لا',
  `university_degree` enum('بكالوريوس','ماجستير','دكتوراه') DEFAULT NULL,
  `no_conviction_doc` varchar(255) DEFAULT NULL,
  `good_conduct_doc` varchar(255) DEFAULT NULL,
  `social_security` enum('نعم','لا') NOT NULL DEFAULT 'لا',
  `social_security_number` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `trainings`
--

CREATE TABLE `trainings` (
  `training_id` int(11) NOT NULL,
  `lawyer_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_months` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `seats` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `training_applications`
--

CREATE TABLE `training_applications` (
  `application_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected','completed') NOT NULL DEFAULT 'pending',
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Triggers `training_applications`
--
DELIMITER $$
CREATE TRIGGER `trg_after_app_completed` AFTER UPDATE ON `training_applications` FOR EACH ROW BEGIN
  DECLARE v_user_id INT DEFAULT NULL;
  DECLARE v_exists INT DEFAULT 0;
  -- نتحقق أن الحالة تحولت إلى 'completed' من حالة أخرى
  IF (NEW.status = 'completed' AND OLD.status <> 'completed') THEN
    -- نحصل على user_id من جدول students
    SELECT user_id INTO v_user_id FROM students WHERE student_id = NEW.student_id LIMIT 1;
    IF v_user_id IS NOT NULL THEN
      -- نحدّد ما إذا كان المستخدم موجودًا بالفعل كمزاول
      SELECT COUNT(*) INTO v_exists FROM lawyers WHERE user_id = v_user_id;
      IF v_exists = 0 THEN
        -- نعيد تحديث دور المستخدم إلى 'lawyer'
        UPDATE users SET role = 'lawyer', updated_at = NOW() WHERE user_id = v_user_id AND role <> 'lawyer';
        -- ندرج سجل في جدول lawyers (مع ترك master_id فارغًا - يمكن ربطه لاحقًا بالتحقق)
        INSERT INTO lawyers (user_id, office_address, verified, created_at) VALUES (v_user_id, NULL, 0, NOW());
      ELSE
        -- لو كان موجودًا كـ lawyer بالفعل، نضمن بأن الدور في users هو 'lawyer'
        UPDATE users SET role = 'lawyer', updated_at = NOW() WHERE user_id = v_user_id AND role <> 'lawyer';
      END IF;
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `national_id` varchar(30) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','lawyer','admin') NOT NULL DEFAULT 'student',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `national_id`, `phone`, `email`, `address`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '0000000000', '0790000000', 'admin@example.com', 'Head Office', '$2y$10$btXC0u5ep0CjLg87dlFAjOzJHipd54ijGMDmFsEGGQetAtgx6ObDi', 'admin', '2025-11-12 00:28:19', '2025-11-14 01:19:45'),
(3, 'محمد المحامي', '1111111111', '0790000000', 'mohammad@example.com', 'عمان', '$2y$10$OH8WwE9dJabllN0kT.ynEOHomQxBRHn4cih49dQLb1/0LRztCTGwC', 'lawyer', '2025-11-12 00:22:59', '2025-11-14 01:19:42'),
(5, 'أحمد الطالب', '2222222222', '0791111111', 'ahmad@example.com', 'عمان', '$2y$10$z/87SIISLj0ME4KbA2h5/ez7pggFS/r/7m0ms1narI5/uorrnA4Oq', 'student', '2025-11-12 00:35:59', '2025-11-14 01:19:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_audit_user` (`performed_by`);

--
-- Indexes for table `lawyers`
--
ALTER TABLE `lawyers`
  ADD PRIMARY KEY (`lawyer_id`),
  ADD UNIQUE KEY `ux_lawyers_user_id` (`user_id`),
  ADD KEY `fk_lawyers_master` (`master_id`);

--
-- Indexes for table `lawyers_master`
--
ALTER TABLE `lawyers_master`
  ADD PRIMARY KEY (`master_id`),
  ADD UNIQUE KEY `ux_lm_national_id` (`national_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `ux_students_user_id` (`user_id`);

--
-- Indexes for table `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`training_id`),
  ADD KEY `ix_trainings_status` (`status`),
  ADD KEY `ix_trainings_lawyer` (`lawyer_id`);

--
-- Indexes for table `training_applications`
--
ALTER TABLE `training_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `ix_apps_status` (`status`),
  ADD KEY `ix_apps_student` (`student_id`),
  ADD KEY `ix_apps_training` (`training_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `ux_users_national_id` (`national_id`),
  ADD UNIQUE KEY `ux_users_email` (`email`),
  ADD KEY `ix_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lawyers`
--
ALTER TABLE `lawyers`
  MODIFY `lawyer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lawyers_master`
--
ALTER TABLE `lawyers_master`
  MODIFY `master_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `training_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_applications`
--
ALTER TABLE `training_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `lawyers`
--
ALTER TABLE `lawyers`
  ADD CONSTRAINT `fk_lawyers_master` FOREIGN KEY (`master_id`) REFERENCES `lawyers_master` (`master_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lawyers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trainings`
--
ALTER TABLE `trainings`
  ADD CONSTRAINT `fk_trainings_lawyer` FOREIGN KEY (`lawyer_id`) REFERENCES `lawyers` (`lawyer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `training_applications`
--
ALTER TABLE `training_applications`
  ADD CONSTRAINT `fk_apps_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_apps_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`training_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
