-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2025 at 09:28 PM
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
  `syndicate_id` int(11) DEFAULT NULL,
  `office_address` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `full_name` varchar(200) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `grandfather_name` varchar(100) DEFAULT NULL,
  `family_name` varchar(100) DEFAULT NULL,
  `national_id` varchar(30) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `no_conviction_doc` varchar(255) DEFAULT NULL,
  `good_conduct_doc` varchar(255) DEFAULT NULL,
  `social_security` enum('نعم','لا') DEFAULT 'لا',
  `highschool_certificate` varchar(10) DEFAULT 'لا',
  `university_degree` varchar(10) DEFAULT 'لا',
  `social_security_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `lawyers`
--

INSERT INTO `lawyers` (`lawyer_id`, `user_id`, `syndicate_id`, `office_address`, `password`, `verified`, `created_at`, `updated_at`, `full_name`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `national_id`, `phone`, `email`, `home_address`, `no_conviction_doc`, `good_conduct_doc`, `social_security`, `highschool_certificate`, `university_degree`, `social_security_number`) VALUES
(1, 3, 1, 'عمان', '$2y$10$j3ACPUVlqG8KtWvMaDVtWeXbNHvoSkmSFC9KbtBBMUHoTGtvIocCm', 1, '2025-11-12 00:22:59', '2025-11-14 16:18:20', 'محمد احمد محمد المحامي', 'محمد', 'احمد', 'محمد', 'المحامي', '1111111111', '0790000000', 'lawyer1@example.com', '', NULL, NULL, 'لا', 'لا', 'لا', NULL),
(9, 24, 6, 'عمان', '$2y$10$RnYhv5X6LSTC0SK1qh.Gdef66TtFxFonlqlo5gJiy5Q7yN57MytR2', 1, '2025-11-14 21:21:18', '2025-11-14 21:21:18', 'سارة علي محمد المحامية', 'سارة', 'علي', 'محمد', 'المحامية', '7878787878', '0787878787', 'sara@example.com', 'عمان', '', '', 'نعم', 'نعم', 'ماجستير', '7878787878');

-- --------------------------------------------------------

--
-- Table structure for table `lawyers_syndicate`
--

CREATE TABLE `lawyers_syndicate` (
  `syndicate_id` int(11) NOT NULL,
  `lawyer_name` varchar(200) NOT NULL,
  `national_id` varchar(30) NOT NULL,
  `office_address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(200) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `grandfather_name` varchar(100) DEFAULT NULL,
  `family_name` varchar(100) DEFAULT NULL,
  `highschool_certificate` enum('نعم','لا') DEFAULT 'لا',
  `university_degree` enum('بكالوريوس','ماجستير','دكتوراه') DEFAULT NULL,
  `no_conviction_doc` varchar(255) DEFAULT NULL,
  `good_conduct_doc` varchar(255) DEFAULT NULL,
  `social_security` enum('نعم','لا') DEFAULT 'لا',
  `social_security_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `lawyers_syndicate`
--

INSERT INTO `lawyers_syndicate` (`syndicate_id`, `lawyer_name`, `national_id`, `office_address`, `phone`, `email`, `notes`, `created_at`, `full_name`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `highschool_certificate`, `university_degree`, `no_conviction_doc`, `good_conduct_doc`, `social_security`, `social_security_number`) VALUES
(1, 'محمد المحامي', '1111111111', 'عمان', '0790000000', 'lawyer1@example.com', 'مسجل لدى النقابة', '2025-11-12 00:18:50', 'محمد احمد محمد المحامي', 'محمد', 'احمد', 'محمد', 'المحامي', 'لا', '', '', '', 'نعم', '1111111111'),
(6, '', '7878787878', 'عمان', '0787878787', 'sara@example.com', NULL, '2025-11-14 21:21:18', 'سارة علي محمد المحامية', 'سارة', 'علي', 'محمد', 'المحامية', 'نعم', 'ماجستير', '', '', 'نعم', '7878787878'),
(7, '', '5455445544', 'عمان', '0787778889', 'bilal@example.com', NULL, '2025-11-14 21:25:24', 'بلال علي لؤي المحامي', 'بلال', 'علي', 'لؤي', 'المحامي', 'نعم', 'ماجستير', '', '', 'نعم', '5455445544');

-- --------------------------------------------------------

--
-- Table structure for table `trainees`
--

CREATE TABLE `trainees` (
  `trainee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `highschool_certificate` enum('نعم','لا') NOT NULL DEFAULT 'لا',
  `university_degree` enum('بكالوريوس','ماجستير','دكتوراه') DEFAULT NULL,
  `no_conviction_doc` varchar(255) DEFAULT NULL,
  `good_conduct_doc` varchar(255) DEFAULT NULL,
  `social_security` enum('نعم','لا') NOT NULL DEFAULT 'لا',
  `social_security_number` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `full_name` varchar(200) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `grandfather_name` varchar(100) DEFAULT NULL,
  `family_name` varchar(100) DEFAULT NULL,
  `national_id` varchar(30) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `home_address` varchar(255) DEFAULT NULL
) ;

--
-- Dumping data for table `trainees`
--

INSERT INTO `trainees` (`trainee_id`, `user_id`, `highschool_certificate`, `university_degree`, `no_conviction_doc`, `good_conduct_doc`, `social_security`, `social_security_number`, `created_at`, `updated_at`, `full_name`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `national_id`, `phone`, `email`, `home_address`) VALUES
(2, 12, 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL, '2025-11-14 14:05:13', '2025-11-14 15:06:16', 'ريم', 'ريم', NULL, NULL, NULL, '0505050505', '0780505050', 'reem@example.com', 'عمان');

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
  `trainee_id` int(11) NOT NULL,
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
    -- نحصل على user_id من جدول trainees
    SELECT user_id INTO v_user_id FROM trainees WHERE trainee_id = NEW.trainee_id LIMIT 1;
    IF v_user_id IS NOT NULL THEN
      -- نحدّد ما إذا كان المستخدم موجودًا بالفعل كمزاول
      SELECT COUNT(*) INTO v_exists FROM lawyers WHERE user_id = v_user_id;
      IF v_exists = 0 THEN
        -- نعيد تحديث دور المستخدم إلى 'lawyer'
        UPDATE users SET role = 'lawyer', updated_at = NOW() WHERE user_id = v_user_id AND role <> 'lawyer';
        -- ندرج سجل في جدول lawyers (مع ترك syndicate_id فارغًا - يمكن ربطه لاحقًا بالتحقق)
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
  `role` enum('trainee','lawyer','admin') NOT NULL DEFAULT 'trainee',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `first_name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `grandfather_name` varchar(100) DEFAULT NULL,
  `family_name` varchar(100) DEFAULT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `office_address` varchar(255) DEFAULT NULL,
  `highschool_certificate` enum('نعم','لا') DEFAULT 'لا',
  `university_degree` enum('بكالوريوس','ماجستير','دكتوراه') DEFAULT NULL,
  `no_conviction_doc` varchar(255) DEFAULT NULL,
  `good_conduct_doc` varchar(255) DEFAULT NULL,
  `social_security` enum('نعم','لا') DEFAULT 'لا',
  `social_security_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `national_id`, `phone`, `email`, `address`, `password`, `role`, `created_at`, `updated_at`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `home_address`, `office_address`, `highschool_certificate`, `university_degree`, `no_conviction_doc`, `good_conduct_doc`, `social_security`, `social_security_number`) VALUES
(1, 'admin', '0000000000', '0790000000', 'admin@example.com', 'Head Office', '$2y$10$btXC0u5ep0CjLg87dlFAjOzJHipd54ijGMDmFsEGGQetAtgx6ObDi', 'admin', '2025-11-12 00:28:19', '2025-11-14 01:19:45', NULL, NULL, NULL, NULL, NULL, NULL, 'لا', NULL, NULL, NULL, 'لا', NULL),
(3, 'محمد احمد محمد المحامي', '1111111111', '0790000000', 'lawyer1@example.com', 'عمان', '$2y$10$OH8WwE9dJabllN0kT.ynEOHomQxBRHn4cih49dQLb1/0LRztCTGwC', 'lawyer', '2025-11-12 00:22:59', '2025-11-14 16:23:20', 'محمد', 'احمد', 'محمد', 'المحامي', '', 'عمان', 'لا', '', '', '', 'نعم', '1111111111'),
(5, 'أحمد الطالب', '2222222222', '0791111111', 'ahmad@example.com', 'عمان', '$2y$10$z/87SIISLj0ME4KbA2h5/ez7pggFS/r/7m0ms1narI5/uorrnA4Oq', 'trainee', '2025-11-12 00:35:59', '2025-11-14 01:19:39', NULL, NULL, NULL, NULL, NULL, NULL, 'لا', NULL, NULL, NULL, 'لا', NULL),
(12, 'ريم', '0505050505', '0780505050', 'reem@example.com', 'عمان', '$2y$10$nr/n4S58QGWNHzwI9d11jOgnFXKVbQqTh3QZ1J0SD5h6alMQR6WLi', 'trainee', '2025-11-14 14:05:13', '2025-11-14 14:05:13', NULL, NULL, NULL, NULL, NULL, NULL, 'لا', NULL, NULL, NULL, 'لا', NULL),
(24, 'سارة علي محمد المحامية', '7878787878', '0787878787', 'sara@example.com', NULL, '$2y$10$RnYhv5X6LSTC0SK1qh.Gdef66TtFxFonlqlo5gJiy5Q7yN57MytR2', 'lawyer', '2025-11-14 21:21:18', '2025-11-14 21:21:18', 'سارة', 'علي', 'محمد', 'المحامية', 'عمان', 'عمان', 'نعم', 'ماجستير', '', '', 'نعم', '7878787878'),
(25, 'بلال علي لؤي المحامي', '5455445544', '0787778889', 'bilal@example.com', NULL, '$2y$10$C5/InYK1mecXyS1en.L6wurbtjiSYLeTgg9K3QYPoj2L05/uXOXpe', 'lawyer', '2025-11-14 21:25:24', '2025-11-14 21:25:24', 'بلال', 'علي', 'لؤي', 'المحامي', 'عمان', 'عمان', 'نعم', 'ماجستير', '', '', 'نعم', '5455445544');

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
  ADD KEY `fk_lawyers_syndicate` (`syndicate_id`);

--
-- Indexes for table `lawyers_syndicate`
--
ALTER TABLE `lawyers_syndicate`
  ADD PRIMARY KEY (`syndicate_id`),
  ADD UNIQUE KEY `ux_lm_national_id` (`national_id`);

--
-- Indexes for table `trainees`
--
ALTER TABLE `trainees`
  ADD PRIMARY KEY (`trainee_id`),
  ADD UNIQUE KEY `ux_trainees_user_id` (`user_id`);

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
  ADD KEY `ix_apps_trainee` (`trainee_id`),
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
  MODIFY `lawyer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `lawyers_syndicate`
--
ALTER TABLE `lawyers_syndicate`
  MODIFY `syndicate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `trainees`
--
ALTER TABLE `trainees`
  MODIFY `trainee_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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
  ADD CONSTRAINT `fk_lawyers_syndicate` FOREIGN KEY (`syndicate_id`) REFERENCES `lawyers_syndicate` (`syndicate_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lawyers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trainees`
--
ALTER TABLE `trainees`
  ADD CONSTRAINT `fk_trainees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trainings`
--
ALTER TABLE `trainings`
  ADD CONSTRAINT `fk_trainings_lawyer` FOREIGN KEY (`lawyer_id`) REFERENCES `lawyers` (`lawyer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `training_applications`
--
ALTER TABLE `training_applications`
  ADD CONSTRAINT `fk_apps_trainee` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`trainee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_apps_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`training_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
