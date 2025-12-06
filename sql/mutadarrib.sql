-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2025 at 09:34 PM
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
(10, 26, 1, 'عمان', '$2y$10$QjzUou/jsvmvSHZ2VHYS6OUHf5jfnKaAr148QrNvEUEExQBQ/VC/G', 1, '2025-11-29 17:26:12', '2025-11-29 17:26:12', 'محمد احمد محمد المحامي', 'محمد', 'احمد', 'محمد', 'المحامي', '1111111111', '0790000000', 'lawyer1@example.com', 'عمان', NULL, NULL, '', 'لا', 'دكتوراه', NULL),
(11, 28, 9, 'إربد – وسط البلد', '$2y$10$uL0QwjUnJ740uHlwjiExauOvmuZj/ljri.K/AtHAp/WnafkmUTgTy', 1, '2025-11-29 20:37:07', '2025-11-29 20:37:07', 'يوسف محمود علي الديري', 'يوسف', 'محمود', 'علي', 'الديري', '1000000002', '0790000002', 'lawyer2@test.com', 'عمان', NULL, NULL, '', 'نعم', 'بكالوريوس', NULL),
(18, 27, 8, NULL, '$2y$10$KiNjGQHAmt4wCVQPzEZlAODYuM0tRreeUdbZ9.aSkz/u9R.nhiV0a', 0, '2025-12-06 22:49:58', '2025-12-06 22:49:58', 'محمد أحمد مصطفى الخطيب', 'محمد', 'أحمد', 'مصطفى', 'الخطيب', '1000000001', '0790000001', 'lawyer1@test.com', 'عمان', NULL, NULL, 'نعم', 'نعم', 'بكالوريوس', '1000000001');

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
(8, 'محمد أحمد الخطيب', '1000000001', 'عمّان – جبل الحسين', '0790000001', 'lawyer1@test.com', NULL, '2025-11-29 19:50:19', 'محمد أحمد مصطفى الخطيب', 'محمد', 'أحمد', 'مصطفى', 'الخطيب', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '1000000001'),
(9, 'يوسف محمود الديري', '1000000002', 'إربد – وسط البلد', '0790000002', 'lawyer2@test.com', NULL, '2025-11-29 19:50:19', 'يوسف محمود علي الديري', 'يوسف', 'محمود', 'علي', 'الديري', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '1000000002'),
(10, 'خالد صالح الرواشدة', '1000000003', 'الزرقاء – الجديدة', '0790000003', 'lawyer3@test.com', NULL, '2025-11-29 19:50:19', 'خالد صالح محمد الرواشدة', 'خالد', 'صالح', 'محمد', 'الرواشدة', 'نعم', 'ماجستير', NULL, NULL, 'نعم', '1000000003'),
(11, 'أنس فواز الطراونة', '1000000004', 'الكرك – وسط المدينة', '0790000004', 'lawyer4@test.com', NULL, '2025-11-29 19:50:19', 'أنس فواز عادل الطراونة', 'أنس', 'فواز', 'عادل', 'الطراونة', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '1000000004'),
(12, 'رامي حسن الخليفات', '1000000005', 'مأدبا – البلد', '0790000005', 'lawyer5@test.com', NULL, '2025-11-29 19:50:19', 'رامي حسن فهد الخليفات', 'رامي', 'حسن', 'فهد', 'الخليفات', 'نعم', 'ماجستير', NULL, NULL, 'نعم', '1000000005'),
(13, 'طارق أمين الزعبي', '1000000006', 'إربد – الحصن', '0790000006', 'lawyer6@test.com', NULL, '2025-11-29 19:50:19', 'طارق أمين شاكر الزعبي', 'طارق', 'أمين', 'شاكر', 'الزعبي', 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL),
(14, 'علي وائل المناصير', '1000000007', 'عمّان – خلدا', '0790000007', 'lawyer7@test.com', NULL, '2025-11-29 19:50:19', 'علي وائل كريم المناصير', 'علي', 'وائل', 'كريم', 'المناصير', 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL),
(15, 'سامر يوسف المحادين', '1000000008', 'السلط – المدينة', '0790000008', 'lawyer8@test.com', NULL, '2025-11-29 19:50:19', 'سامر يوسف جابر المحادين', 'سامر', 'يوسف', 'جابر', 'المحادين', 'نعم', 'ماجستير', NULL, NULL, 'لا', NULL),
(16, 'سامي عارف الكساسبة', '1000000009', 'العقبة – الجنوب', '0790000009', 'lawyer9@test.com', NULL, '2025-11-29 19:50:19', 'سامي عارف سالم الكساسبة', 'سامي', 'عارف', 'سالم', 'الكساسبة', 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL),
(17, 'بهاء ناصر العجارمة', '1000000010', 'عمّان – تلاع العلي', '0790000010', 'lawyer10@test.com', NULL, '2025-11-29 19:50:19', 'بهاء ناصر طلال العجارمة', 'بهاء', 'ناصر', 'طلال', 'العجارمة', 'نعم', 'دكتوراه', NULL, NULL, 'لا', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `trainees`
--

INSERT INTO `trainees` (`trainee_id`, `user_id`, `highschool_certificate`, `university_degree`, `no_conviction_doc`, `good_conduct_doc`, `social_security`, `social_security_number`, `created_at`, `updated_at`, `full_name`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `national_id`, `phone`, `email`, `home_address`) VALUES
(2, 12, 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL, '2025-11-14 14:05:13', '2025-11-14 15:06:16', 'ريم', 'ريم', NULL, NULL, NULL, '0505050505', '0780505050', 'reem@example.com', 'عمان'),
(0, 27, 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '1000000001', '2025-11-29 19:53:21', '2025-11-29 19:53:21', 'محمد أحمد مصطفى الخطيب', 'محمد', 'أحمد', 'مصطفى', 'الخطيب', '1000000001', '0790000001', 'lawyer1@test.com', 'عمان');

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

--
-- Dumping data for table `trainings`
--

INSERT INTO `trainings` (`training_id`, `lawyer_id`, `title`, `description`, `duration_months`, `location`, `start_date`, `end_date`, `status`, `seats`, `created_at`, `updated_at`) VALUES
(1, 10, 'تدريب قانوني في القضايا المدنية', 'تدريب عملي في مكتب محاماة', 3, 'عمان', NULL, NULL, 'open', 1, '2025-11-29 22:04:44', '2025-12-06 22:49:57'),
(2, 26, 'تدريب تيست', 'تدريب تيست تدريب تيست', 6, 'Amman', '2025-12-01', '2026-05-30', 'open', 3, '2025-11-29 22:32:32', '2025-11-29 22:32:32'),
(3, 28, 'تدريب تيست', 'تدريب تيست تدريب تيست', 12, 'Amman', '2025-12-01', '2026-12-30', 'open', 3, '2025-11-29 22:34:00', '2025-11-29 22:34:00');

-- --------------------------------------------------------

--
-- Table structure for table `training_applications`
--

CREATE TABLE `training_applications` (
  `application_id` int(11) NOT NULL,
  `trainee_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected','completed') NOT NULL DEFAULT 'pending',
  `trainee_seen` tinyint(1) NOT NULL DEFAULT 0,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `syndicate_notified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `training_applications`
--

INSERT INTO `training_applications` (`application_id`, `trainee_id`, `training_id`, `status`, `trainee_seen`, `applied_at`, `reviewed_at`, `notes`, `syndicate_notified`) VALUES
(10, 0, 1, 'completed', 0, '2025-12-06 22:49:42', '2025-12-06 22:49:58', NULL, 0);

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
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('trainee','lawyer','admin') NOT NULL DEFAULT 'trainee',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `national_id`, `phone`, `email`, `address`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '0000000000', '0790000000', 'admin@example.com', 'Head Office', '$2y$10$btXC0u5ep0CjLg87dlFAjOzJHipd54ijGMDmFsEGGQetAtgx6ObDi', 'admin', '2025-11-12 00:28:19', '2025-11-14 01:19:45'),
(26, 'محمد احمد محمد المحامي', '1111111111', '0790000000', 'lawyer1@example.com', 'عمان', '$2y$10$QjzUou/jsvmvSHZ2VHYS6OUHf5jfnKaAr148QrNvEUEExQBQ/VC/G', 'lawyer', '2025-11-29 17:26:12', '2025-11-29 17:26:12'),
(27, 'محمد أحمد مصطفى الخطيب', '1000000001', '0790000001', 'lawyer1@test.com', 'عمان', '$2y$10$KiNjGQHAmt4wCVQPzEZlAODYuM0tRreeUdbZ9.aSkz/u9R.nhiV0a', 'lawyer', '2025-11-29 19:53:21', '2025-12-06 22:49:58'),
(28, 'يوسف محمود علي الديري', '1000000002', '0790000002', 'lawyer2@test.com', 'عمان', '$2y$10$uL0QwjUnJ740uHlwjiExauOvmuZj/ljri.K/AtHAp/WnafkmUTgTy', 'lawyer', '2025-11-29 20:37:07', '2025-11-29 20:37:07');

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
  ADD PRIMARY KEY (`lawyer_id`);

--
-- Indexes for table `lawyers_syndicate`
--
ALTER TABLE `lawyers_syndicate`
  ADD PRIMARY KEY (`syndicate_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`training_id`);

--
-- Indexes for table `training_applications`
--
ALTER TABLE `training_applications`
  ADD PRIMARY KEY (`application_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `national_id` (`national_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lawyers`
--
ALTER TABLE `lawyers`
  MODIFY `lawyer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `lawyers_syndicate`
--
ALTER TABLE `lawyers_syndicate`
  MODIFY `syndicate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `training_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `training_applications`
--
ALTER TABLE `training_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
