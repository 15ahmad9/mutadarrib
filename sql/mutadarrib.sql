-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2026 at 09:08 PM
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
-- Database: `mutadarrib`
--

-- --------------------------------------------------------

--
-- Table structure for table `allied_medical_providers`
--

CREATE TABLE `allied_medical_providers` (
  `provider_id` int(11) NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `allied_medical_trainees`
--

CREATE TABLE `allied_medical_trainees` (
  `trainee_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `university` varchar(160) DEFAULT NULL,
  `major` varchar(160) DEFAULT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `cv_file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `architecture_design_providers`
--

CREATE TABLE `architecture_design_providers` (
  `provider_id` int(11) NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `architecture_design_trainees`
--

CREATE TABLE `architecture_design_trainees` (
  `trainee_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `university` varchar(160) DEFAULT NULL,
  `major` varchar(160) DEFAULT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `cv_file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_providers`
--

CREATE TABLE `business_providers` (
  `provider_id` int(11) NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_trainees`
--

CREATE TABLE `business_trainees` (
  `trainee_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `university` varchar(160) DEFAULT NULL,
  `skills` varchar(255) DEFAULT NULL,
  `major` varchar(255) DEFAULT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `cv_file_path` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('task','event') NOT NULL DEFAULT 'task',
  `reminder_minutes` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','closed') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`message_id`, `user_id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES
(8001, 201, 'ليث الشريدة', 'laith@gmail.com', '0795566001', 'استفسار عن التدريب', 'هل التدريب يتطلب دوام كامل؟', 'new', '2026-01-03 13:05:00'),
(8002, NULL, 'زائر', 'visitor@gmail.com', '0790000000', 'معلومة عامة', 'هل يمكن التسجيل لغير المحامين؟', 'read', '2026-01-03 13:10:00'),
(8003, 103, 'سامي الطراونة', 'sami@gmail.com', '0794433221', 'تحديث بيانات', 'أرجو تحديث عنوان المكتب في النظام.', 'closed', '2026-01-03 13:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `it_applications`
--

CREATE TABLE `it_applications` (
  `application_id` int(11) NOT NULL,
  `internship_id` int(11) NOT NULL,
  `trainee_user_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `cv_file_path` varchar(255) DEFAULT NULL,
  `status` enum('submitted','in_review','shortlisted','accepted','rejected','withdrawn') NOT NULL DEFAULT 'submitted',
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `trainee_seen` tinyint(1) NOT NULL DEFAULT 0,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_internships`
--

CREATE TABLE `it_internships` (
  `internship_id` int(11) NOT NULL,
  `provider_user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `field` varchar(100) DEFAULT NULL,
  `internship_type` enum('onsite','remote','hybrid') NOT NULL DEFAULT 'onsite',
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `duration_weeks` smallint(6) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `required_skills` text DEFAULT NULL,
  `seats` smallint(6) DEFAULT 1,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'published',
  `published_at` datetime DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `it_internships`
--

INSERT INTO `it_internships` (`internship_id`, `provider_user_id`, `title`, `description`, `field`, `internship_type`, `city`, `country`, `duration_weeks`, `start_date`, `end_date`, `required_skills`, `seats`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(11, 207, 'تدريب مطور ويب (PHP/Laravel)', 'فرصة تدريب عملي لتطوير مواقع باستخدام PHP وLaravel، تشمل بناء CRUD، التعامل مع قواعد البيانات، وواجهات بسيطة.', NULL, 'hybrid', 'عمان', 'الأردن', 8, '2026-03-01', '2026-04-30', 'PHP, Laravel, MySQL, HTML, CSS, Git', 5, 'published', '2026-02-02 23:02:53', '2026-02-02 23:02:53', '2026-02-02 23:02:53'),
(12, 207, 'تدريب Front-End (HTML/CSS/JS)', 'تدريب للمبتدئين لتطوير واجهات مستخدم متجاوبة باستخدام HTML/CSS وJavaScript، مع مشاريع تطبيقية.', NULL, 'remote', 'عمان', 'الأردن', 6, '2026-03-08', '2026-04-19', 'HTML, CSS, JavaScript, Responsive Design', 3, 'published', '2026-02-02 23:04:15', '2026-02-02 23:04:15', '2026-02-02 23:04:15'),
(13, 207, 'تدريب تحليل بيانات (Excel + Power BI)', 'فرصة تدريب في تحليل البيانات وبناء Dashboards باستخدام Excel وPower BI، مع مهام حقيقية.', NULL, 'onsite', 'عمان', 'الأردن', 10, '2026-04-01', '2026-06-16', 'Excel, Power BI, Data Analysis, Reporting', 4, 'published', '2026-02-02 23:05:37', '2026-02-02 23:05:37', '2026-02-02 23:05:37'),
(14, 207, 'تدريب دعم فني IT (Help Desk)', 'تدريب ضمن فريق الدعم الفني يشمل معالجة الأعطال، إعداد الأجهزة، صيانة الشبكات، وكتابة تقارير.', NULL, 'onsite', 'عمان', 'الأردن', 12, '2026-04-01', '2026-06-30', 'Windows, Networking Basics, Troubleshooting, Customer Support', 3, 'published', '2026-02-02 23:06:45', '2026-02-02 23:06:45', '2026-02-02 23:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `it_providers`
--

CREATE TABLE `it_providers` (
  `user_id` int(11) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `company_registration_no` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `it_providers`
--

INSERT INTO `it_providers` (`user_id`, `company_name`, `company_registration_no`, `website`, `description`, `city`, `country`, `created_at`, `updated_at`) VALUES
(207, 'company', NULL, NULL, NULL, NULL, 'الأردن', '2026-01-26 21:12:39', '2026-01-26 21:12:39'),
(209, '2company', NULL, NULL, NULL, NULL, 'الأردن', '2026-01-26 22:17:55', '2026-01-26 22:17:55');

-- --------------------------------------------------------

--
-- Table structure for table `it_trainees`
--

CREATE TABLE `it_trainees` (
  `user_id` int(11) NOT NULL,
  `university` varchar(200) DEFAULT NULL,
  `major` varchar(150) DEFAULT NULL,
  `graduation_year` smallint(6) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `cv_file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `it_trainees`
--

INSERT INTO `it_trainees` (`user_id`, `university`, `major`, `graduation_year`, `skills`, `github_url`, `linkedin_url`, `cv_file_path`, `created_at`, `updated_at`) VALUES
(208, 'zuj', 'se', 2025, 'php', NULL, NULL, NULL, '2026-01-26 21:47:50', '2026-01-26 21:47:50'),
(212, 'zuj', 'علم بيانات', NULL, 'php', 'https://github/a', 'https://LinkedIn.com', NULL, '2026-02-02 22:29:06', '2026-02-02 22:29:06');

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
  `identity_front` varchar(255) DEFAULT NULL,
  `identity_back` varchar(255) DEFAULT NULL,
  `no_conviction_doc` varchar(255) DEFAULT NULL,
  `good_conduct_doc` varchar(255) DEFAULT NULL,
  `social_security` enum('نعم','لا') DEFAULT 'لا',
  `highschool_certificate` varchar(10) DEFAULT 'لا',
  `university_degree` varchar(10) DEFAULT 'لا',
  `social_security_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lawyers`
--

INSERT INTO `lawyers` (`lawyer_id`, `user_id`, `syndicate_id`, `office_address`, `password`, `verified`, `created_at`, `updated_at`, `full_name`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `national_id`, `phone`, `email`, `home_address`, `identity_front`, `identity_back`, `no_conviction_doc`, `good_conduct_doc`, `social_security`, `highschool_certificate`, `university_degree`, `social_security_number`) VALUES
(1, 101, 1, 'عمان - الشميساني', '$2y$10$ZJfx/jrxCJTcGFBTPjDCbORecVF4tzK2L1yqmxer2P7Hrfia3Segq', 1, '2026-01-02 10:00:00', '2026-01-04 23:57:39', 'خالد محمود أحمد الزعبي', 'خالد', 'محمود', 'أحمد', 'الزعبي', '7012945836', '0791122334', 'khaled@gmail.com', 'عمان - تلاع العلي', NULL, NULL, NULL, NULL, 'نعم', 'نعم', 'بكالوريوس', '7012945836'),
(2, 102, 1, 'إربد - الحي الشرقي', '$2y$10$o7lnMn3PqaTAL6gXyZUVyO6zyESvR53qkO/oeE4ZZKwWo2Y72w04i', 1, '2026-01-02 10:05:00', '2026-01-04 23:57:39', 'أحمد عادل يوسف العموش', 'أحمد', 'عادل', 'يوسف', 'العموش', '8391054721', '0786655443', 'ahmad@gmail.com', 'إربد - شارع الجامعة', NULL, NULL, NULL, NULL, 'لا', 'نعم', 'ماجستير', NULL),
(3, 103, 1, 'الكرك - وسط البلد', '$2y$10$gM8dJ0/Cs2QUzcVsJXaPju8m/0gegtEOtIfIbRlt6t0nNby7rB1UC', 1, '2026-01-02 10:10:00', '2026-01-04 23:57:39', 'سامي جهاد حسن الطراونة', 'سامي', 'جهاد', 'حسن', 'الطراونة', '6158739204', '0794433221', 'sami@gmail.com', 'الكرك - المرج', NULL, NULL, NULL, NULL, 'نعم', 'نعم', 'بكالوريوس', '6158739204'),
(4, 104, 1, 'مادبا - قرب المحكمة', '$2y$10$ir7kSa/RPUmB.zAjXTYZIO0LbyyUB0wjBeJyF0MnYC2q0OEiF3J9C', 0, '2026-01-02 10:12:00', '2026-01-04 23:57:39', 'يوسف فادي محمود المجالي', 'يوسف', 'فادي', 'محمود', 'المجالي', '9024763158', '0779988776', 'yousef@gmail.com', 'مادبا - وسط البلد', NULL, NULL, NULL, NULL, 'لا', 'نعم', 'بكالوريوس', NULL),
(5, 105, 1, 'الزرقاء - الجديدة', '$2y$10$RSQELcoXo8niHBz19LxS9.GMpF0AL4Avm2irrOxmk213P2M/OlM16', 1, '2026-01-02 10:15:00', '2026-01-04 23:57:39', 'محمود رائد سليمان بني ياسين', 'محمود', 'رائد', 'سليمان', 'بني ياسين', '7483629105', '0792211345', 'mahmoud@gmail.com', 'الزرقاء - حي الأمير حسن', NULL, NULL, NULL, NULL, 'نعم', 'نعم', 'دكتوراه', ''),
(6, 106, 1, 'عمان - جبل الحسين', '$2y$10$/o5XXUBi7P3R6Zb.cUVA0OBEV8AJhbAdklbZ3SMCKuMu3kKwkMTF2', 0, '2026-01-02 10:20:00', '2026-01-04 23:57:50', 'عمر ناصر محمد الحراحشة', 'عمر', 'ناصر', 'محمد', 'الحراحشة', '5630198472', '0783344556', 'omar@gmail.com', 'عمان - جبل التاج', NULL, NULL, NULL, NULL, 'لا', 'نعم', 'بكالوريوس', NULL),
(10, 26, 1, 'عمان', '$2y$10$QjzUou/jsvmvSHZ2VHYS6OUHf5jfnKaAr148QrNvEUEExQBQ/VC/G', 1, '2025-11-29 17:26:12', '2025-12-22 20:57:59', 'محمد احمد محمد المحامي', 'محمد', 'احمد', 'محمد', 'المحامي', '1111111111', '0790000000', 'lawyer1@example.com', 'عمان', NULL, NULL, '', '', '', 'لا', 'دكتوراه', NULL);

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
  `identity_front` varchar(255) DEFAULT NULL,
  `identity_back` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `role` enum('trainee','lawyer') DEFAULT 'lawyer',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lawyers_syndicate`
--

INSERT INTO `lawyers_syndicate` (`syndicate_id`, `lawyer_name`, `national_id`, `office_address`, `phone`, `email`, `identity_front`, `identity_back`, `notes`, `role`, `created_at`, `full_name`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `highschool_certificate`, `university_degree`, `no_conviction_doc`, `good_conduct_doc`, `social_security`, `social_security_number`) VALUES
(1, 'محمد المحامي', '1111111111', 'عمان', '0790000000', 'lawyer1@example.com', NULL, NULL, 'مسجل لدى النقابة', 'lawyer', '2025-11-12 00:18:50', 'محمد احمد محمد المحامي', 'محمد', 'احمد', 'محمد', 'المحامي', 'لا', '', '', '', 'نعم', '1111111111'),
(2, 'محمد أحمد الخطيب', '1000000001', 'عمّان – جبل الحسين', '0790000001', 'lawyer1@test.com', NULL, NULL, NULL, 'lawyer', '2025-11-29 19:50:19', 'محمد أحمد مصطفى الخطيب', 'محمد', 'أحمد', 'مصطفى', 'الخطيب', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '1000000001'),
(3, 'خالد الزعبي', '7012945836', 'عمان - الشميساني', '0791122334', 'khaled@gmail.com', NULL, NULL, 'مسجل لدى النقابة', 'lawyer', '2025-12-10 09:12:21', 'خالد محمود أحمد الزعبي', 'خالد', 'محمود', 'أحمد', 'الزعبي', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '7012945836'),
(4, 'أحمد العموش', '8391054721', 'إربد - الحي الشرقي', '0786655443', 'ahmad@gmail.com', NULL, NULL, 'خبرة 6 سنوات', 'lawyer', '2025-12-11 14:35:10', 'أحمد عادل يوسف العموش', 'أحمد', 'عادل', 'يوسف', 'العموش', 'نعم', 'ماجستير', NULL, NULL, 'لا', NULL),
(5, 'سامي الطراونة', '6158739204', 'الكرك - وسط البلد', '0794433221', 'sami@gmail.com', NULL, NULL, 'مكتب تدريب معتمد', 'lawyer', '2025-12-12 11:03:44', 'سامي جهاد حسن الطراونة', 'سامي', 'جهاد', 'حسن', 'الطراونة', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '6158739204'),
(6, 'يوسف المجالي', '9024763158', 'مادبا - قرب المحكمة', '0779988776', 'yousef@gmail.com', NULL, NULL, 'يقبل متدربين', 'lawyer', '2025-12-13 16:22:05', 'يوسف فادي محمود المجالي', 'يوسف', 'فادي', 'محمود', 'المجالي', 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL),
(7, 'محمود بني ياسين', '7483629105', 'الزرقاء - الجديدة', '0792211345', 'mahmoud@gmail.com', NULL, NULL, 'متاح للتدريب المسائي', 'lawyer', '2025-12-14 10:47:59', 'محمود رائد سليمان بني ياسين', 'محمود', 'رائد', 'سليمان', 'بني ياسين', 'نعم', 'دكتوراه', NULL, NULL, 'نعم', '7483629105'),
(8, 'عمر الحراحشة', '5630198472', 'عمان - جبل الحسين', '0783344556', 'omar@gmail.com', NULL, NULL, 'اختصاص مدني وتجاري', 'lawyer', '2025-12-15 13:19:33', 'عمر ناصر محمد الحراحشة', 'عمر', 'ناصر', 'محمد', 'الحراحشة', 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL),
(9, 'ليث الشريدة', '3109754628', 'البلقاء - الفحيص', '0795566001', 'laith@gmail.com', NULL, NULL, 'خريج حديث', 'trainee', '2025-12-20 09:05:02', 'ليث إبراهيم صالح الشريدة', 'ليث', 'إبراهيم', 'صالح', 'الشريدة', 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL),
(10, 'يحيى العدوان', '4286019735', 'عمان - طبربور', '0782233002', 'yahya@gmail.com', NULL, NULL, 'يرغب بتدريب مكتب تجاري', 'trainee', '2025-12-21 18:40:11', 'يحيى فؤاد عبدالكريم العدوان', 'يحيى', 'فؤاد', 'عبدالكريم', 'العدوان', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '4286019735'),
(11, 'رامي العناسوة', '1567430298', 'إربد - الحصن', '0797788003', 'rami@gmail.com', NULL, NULL, 'يبحث عن تدريب قريب', 'trainee', '2025-12-22 12:14:28', 'رامي جمال أمين العناسوة', 'رامي', 'جمال', 'أمين', 'العناسوة', 'نعم', 'ماجستير', NULL, NULL, 'لا', NULL),
(12, 'تامر الرواشدة', '9073146251', 'الكرك - المرج', '0774455004', 'tamer@gmail.com', NULL, NULL, 'خريج حقوق', 'trainee', '2025-12-23 08:27:40', 'تامر مازن خليل الرواشدة', 'تامر', 'مازن', 'خليل', 'الرواشدة', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '9073146251'),
(13, 'سيف الجازي', '6642087319', 'الزرقاء - الغويرية', '0793344005', 'saif@gmail.com', NULL, NULL, 'يفضل تدريب صباحي', 'trainee', '2025-12-24 10:58:06', 'سيف سامر يوسف الجازي', 'سيف', 'سامر', 'يوسف', 'الجازي', 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL),
(14, 'مالك الخلايلة', '9073146251', 'مادبا - مليح', '0789900116', 'malek@gmail.com', NULL, NULL, 'جاهز للمقابلة', 'trainee', '2025-12-25 15:09:52', 'مالك عيسى طلال الخلايلة', 'مالك', 'عيسى', 'طلال', 'الخلايلة', 'نعم', 'دكتوراه', NULL, NULL, 'نعم', '2751908643'),
(15, 'خالد الزعبي', '7012945836', 'عمان - الشميساني', '0791122334', 'خالد@gmail.com', NULL, NULL, 'مسجل لدى النقابة', 'lawyer', '2025-10-03 09:12:21', 'خالد محمود أحمد الزعبي', 'خالد', 'محمود', 'أحمد', 'الزعبي', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '7012945836'),
(16, 'أحمد العموش', '8391054721', 'إربد - الحي الشرقي', '0786655443', 'أحمد@gmail.com', NULL, NULL, 'خبرة 6 سنوات', 'lawyer', '2025-10-07 14:35:10', 'أحمد عادل يوسف العموش', 'أحمد', 'عادل', 'يوسف', 'العموش', 'نعم', 'ماجستير', NULL, NULL, 'لا', NULL),
(17, 'سامي الطراونة', '6158739204', 'الكرك - وسط البلد', '0794433221', 'سامي@gmail.com', NULL, NULL, 'مكتب تدريب معتمد', 'lawyer', '2025-10-12 11:03:44', 'سامي جهاد حسن الطراونة', 'سامي', 'جهاد', 'حسن', 'الطراونة', 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '6158739204'),
(18, 'يوسف المجالي', '9024763158', 'مادبا - قرب المحكمة', '0779988776', 'يوسف@gmail.com', NULL, NULL, 'يقبل متدربين', 'lawyer', '2025-10-18 16:22:05', 'يوسف فادي محمود المجالي', 'يوسف', 'فادي', 'محمود', 'المجالي', 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL),
(19, 'محمود بني ياسين', '7483629105', 'الزرقاء - الجديدة', '0792211345', 'محمود@gmail.com', NULL, NULL, 'متاح للتدريب المسائي', 'lawyer', '2025-10-22 10:47:59', 'محمود رائد سليمان بني ياسين', 'محمود', 'رائد', 'سليمان', 'بني ياسين', 'نعم', 'دكتوراه', NULL, NULL, 'نعم', '7483629105'),
(20, 'علي المشاقبة', '2579031486', 'المفرق - شارع الجامعة', '0774455667', 'علي@gmail.com', NULL, NULL, 'متاح لاستقبال 2 متدربين', 'lawyer', '2025-11-13 08:27:40', 'علي مازن خليل المشاقبة', 'علي', 'مازن', 'خليل', 'المشاقبة', 'نعم', NULL, NULL, NULL, 'لا', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `literature_providers`
--

CREATE TABLE `literature_providers` (
  `provider_id` int(11) NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `literature_trainees`
--

CREATE TABLE `literature_trainees` (
  `trainee_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `university` varchar(160) DEFAULT NULL,
  `major` varchar(160) DEFAULT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `cv_file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership_requests`
--

CREATE TABLE `membership_requests` (
  `request_id` int(11) NOT NULL,
  `public_code` varchar(12) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` enum('trainee','lawyer') NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `syndicate_id` int(11) DEFAULT NULL,
  `identity_front` varchar(255) NOT NULL,
  `identity_back` varchar(255) NOT NULL,
  `no_conviction_doc` varchar(255) DEFAULT NULL,
  `good_conduct_doc` varchar(255) DEFAULT NULL,
  `lawyer_name` varchar(200) NOT NULL,
  `national_id` varchar(30) NOT NULL,
  `office_address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `grandfather_name` varchar(100) DEFAULT NULL,
  `family_name` varchar(100) DEFAULT NULL,
  `highschool_certificate` enum('نعم','لا') DEFAULT 'لا',
  `university_degree` enum('بكالوريوس','ماجستير','دكتوراه') DEFAULT NULL,
  `social_security` enum('نعم','لا') DEFAULT 'لا',
  `social_security_number` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_syndicate_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_requests`
--

INSERT INTO `membership_requests` (`request_id`, `public_code`, `user_id`, `role`, `status`, `syndicate_id`, `identity_front`, `identity_back`, `no_conviction_doc`, `good_conduct_doc`, `lawyer_name`, `national_id`, `office_address`, `phone`, `email`, `notes`, `full_name`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `highschool_certificate`, `university_degree`, `social_security`, `social_security_number`, `created_at`, `reviewed_at`, `reviewed_by`, `rejection_reason`, `approved_syndicate_id`) VALUES
(6001, 'AB12CD34EF56', 104, 'lawyer', 'pending', 1, 'uploads/ids/104_front.jpg', 'uploads/ids/104_back.jpg', NULL, NULL, 'يوسف المجالي', '9024763158', 'مادبا - قرب المحكمة', '0779988776', 'yousef@gmail.com', 'بانتظار المراجعة', 'يوسف فادي محمود المجالي', 'يوسف', 'فادي', 'محمود', 'المجالي', 'نعم', 'بكالوريوس', 'لا', NULL, '2026-01-02 12:30:00', NULL, NULL, NULL, NULL),
(6002, 'ZX98YU76TR54', 203, 'trainee', 'approved', 1, 'uploads/ids/203_front.jpg', 'uploads/ids/203_back.jpg', NULL, NULL, 'رامي العناسوة', '1567430298', 'إربد - الحصن', '0797788003', 'rami@gmail.com', 'تمت الموافقة', 'رامي جمال أمين العناسوة', 'رامي', 'جمال', 'أمين', 'العناسوة', 'نعم', 'ماجستير', 'لا', NULL, '2026-01-02 12:40:00', '2026-01-03 09:00:00', 999, NULL, 1),
(6010, 'LM12NP34QR56', NULL, 'lawyer', 'pending', 1, 'uploads/ids/6010_front.jpg', 'uploads/ids/6010_back.jpg', 'uploads/docs/6010_ncv.pdf', 'uploads/docs/6010_gc.pdf', 'زياد القضاة', '3347619208', 'عمان - عبدون', '0771122899', 'ziad@gmail.com', 'طلب تسجيل محامي جديد', 'زياد فؤاد عبدالكريم القضاة', 'زياد', 'فؤاد', 'عبدالكريم', 'القضاة', 'نعم', 'بكالوريوس', 'لا', NULL, '2026-01-03 10:10:00', NULL, NULL, NULL, NULL),
(6011, 'GH78JK90MN12', NULL, 'trainee', 'pending', 1, 'uploads/ids/6011_front.jpg', 'uploads/ids/6011_back.jpg', NULL, NULL, 'نادر السرحان', '5196082743', 'عمان - ماركا', '0798877665', 'nader@gmail.com', 'خريج جديد ويطلب اعتماد', 'نادر محمود أحمد السرحان', 'نادر', 'محمود', 'أحمد', 'السرحان', 'نعم', 'بكالوريوس', 'نعم', '5196082743', '2026-01-03 10:20:00', NULL, NULL, NULL, NULL),
(6012, 'AA11BB22CC33', NULL, 'trainee', 'rejected', 1, 'uploads/ids/6012_front.jpg', 'uploads/ids/6012_back.jpg', NULL, NULL, 'معتز الزوايدة', '6802519734', 'إربد - شارع الجامعة', '0786677001', 'moataz@gmail.com', 'نواقص وثائق', 'معتز سالم حسن الزوايدة', 'معتز', 'سالم', 'حسن', 'الزوايدة', 'لا', 'بكالوريوس', 'لا', NULL, '2026-01-03 10:30:00', '2026-01-03 18:05:00', 999, 'يرجى إرفاق شهادة الثانوية', 0),
(6013, 'DD44EE55FF66', NULL, 'lawyer', 'approved', 1, 'uploads/ids/6013_front.jpg', 'uploads/ids/6013_back.jpg', 'uploads/docs/6013_ncv.pdf', 'uploads/docs/6013_gc.pdf', 'فراس العبادي', '9851734062', 'السلط - السرو', '0797788990', 'feras@gmail.com', 'مكتب تدريب جديد', 'فراس جمال أمين العبادي', 'فراس', 'جمال', 'أمين', 'العبادي', 'نعم', 'ماجستير', 'نعم', '9851734062', '2026-01-03 10:40:00', '2026-01-04 09:00:00', 999, NULL, 1),
(6014, 'HH77II88JJ99', NULL, 'lawyer', 'rejected', 1, 'uploads/ids/6014_front.jpg', 'uploads/ids/6014_back.jpg', 'uploads/docs/6014_ncv.pdf', NULL, 'رائد العجارمة', '8065129473', 'عمان - تلاع العلي', '0793344991', 'raed@gmail.com', 'وثائق غير مكتملة', 'رائد سامر يوسف العجارمة', 'رائد', 'سامر', 'يوسف', 'العجارمة', 'نعم', 'بكالوريوس', 'نعم', '8065129473', '2026-01-03 10:50:00', '2026-01-03 19:10:00', 999, 'يرجى إرفاق شهادة حسن السيرة والسلوك', NULL),
(6015, 'KK10LL20MM30', NULL, 'trainee', 'approved', 1, 'uploads/ids/6015_front.jpg', 'uploads/ids/6015_back.jpg', NULL, NULL, 'عمار الحياري', '7420193856', 'الزرقاء - الجديدة', '0791122007', 'ammar@gmail.com', 'تم استكمال المتطلبات', 'عمار نزار خليل الحياري', 'عمار', 'نزار', 'خليل', 'الحياري', 'نعم', 'بكالوريوس', 'لا', NULL, '2026-01-03 11:05:00', '2026-01-04 09:30:00', 999, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `syndicate_exam_requests`
--

CREATE TABLE `syndicate_exam_requests` (
  `request_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `trainee_id` int(11) NOT NULL,
  `lawyer_id` int(11) NOT NULL,
  `status` enum('waiting_exam','scheduled','passed','failed') NOT NULL DEFAULT 'waiting_exam',
  `exam_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `syndicate_exam_requests`
--

INSERT INTO `syndicate_exam_requests` (`request_id`, `application_id`, `trainee_id`, `lawyer_id`, `status`, `exam_date`, `created_at`) VALUES
(9001, 5002, 12, 1, 'scheduled', '2026-01-18', '2026-01-04 09:00:00'),
(9002, 5004, 14, 3, 'waiting_exam', NULL, '2026-01-04 09:05:00'),
(9003, 5006, 16, 5, 'scheduled', '2026-01-22', '2026-01-04 09:10:00'),
(9005, 5004, 14, 3, 'scheduled', '2026-02-08', '2026-01-05 09:15:00'),
(9007, 5002, 12, 1, 'passed', '2026-01-18', '2026-01-18 12:00:00'),
(9008, 5004, 14, 3, 'failed', '2026-02-08', '2026-02-08 15:30:00'),
(9009, 5006, 16, 5, 'scheduled', '2026-02-12', '2026-01-05 09:25:00');

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
  `home_address` varchar(255) DEFAULT NULL,
  `identity_front` varchar(255) DEFAULT NULL,
  `identity_back` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainees`
--

INSERT INTO `trainees` (`trainee_id`, `user_id`, `highschool_certificate`, `university_degree`, `no_conviction_doc`, `good_conduct_doc`, `social_security`, `social_security_number`, `created_at`, `updated_at`, `full_name`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `national_id`, `phone`, `email`, `home_address`, `identity_front`, `identity_back`, `is_archived`, `archived_at`) VALUES
(3, 27, 'نعم', 'بكالوريوس', '', '', 'نعم', '1000000001', '2025-11-29 19:53:21', '2025-12-22 00:33:39', 'محمد أحمد مصطفى الخطيب', 'محمد', 'أحمد', 'مصطفى', 'الخطيب', '1000000001', '0790000001', 'lawyer1@test.com', 'عمان', NULL, NULL, 0, '2025-12-12 17:47:48'),
(11, 201, 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL, '2026-01-02 11:10:00', '2026-01-02 11:35:00', 'ليث إبراهيم صالح الشريدة', 'ليث', 'إبراهيم', 'صالح', 'الشريدة', '3109754628', '0795566001', 'laith@gmail.com', 'البلقاء - الفحيص', NULL, NULL, 0, NULL),
(12, 202, 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '4286019735', '2026-01-02 11:15:00', '2026-01-02 11:40:00', 'يحيى فؤاد عبدالكريم العدوان', 'يحيى', 'فؤاد', 'عبدالكريم', 'العدوان', '4286019735', '0782233002', 'yahya@gmail.com', 'عمان - طبربور', NULL, NULL, 0, NULL),
(13, 203, 'نعم', 'ماجستير', NULL, NULL, 'لا', NULL, '2026-01-02 11:20:00', '2026-01-02 11:20:00', 'رامي جمال أمين العناسوة', 'رامي', 'جمال', 'أمين', 'العناسوة', '1567430298', '0797788003', 'rami@gmail.com', 'إربد - الحصن', NULL, NULL, 0, NULL),
(14, 204, 'نعم', 'بكالوريوس', NULL, NULL, 'نعم', '9073146251', '2026-01-02 11:22:00', '2026-01-02 11:55:00', 'تامر مازن خليل الرواشدة', 'تامر', 'مازن', 'خليل', 'الرواشدة', '9073146251', '0774455004', 'tamer@gmail.com', 'الكرك - المرج', NULL, NULL, 0, NULL),
(15, 205, 'نعم', 'بكالوريوس', NULL, NULL, 'لا', NULL, '2026-01-02 11:25:00', '2026-01-02 11:25:00', 'سيف سامر يوسف الجازي', 'سيف', 'سامر', 'يوسف', 'الجازي', '6642087319', '0793344005', 'saif@gmail.com', 'الزرقاء - الغويرية', NULL, NULL, 0, NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainings`
--

INSERT INTO `trainings` (`training_id`, `lawyer_id`, `title`, `description`, `duration_months`, `location`, `start_date`, `end_date`, `status`, `seats`, `created_at`, `updated_at`) VALUES
(1001, 1, 'تدريب محاماة مدني وتجاري', 'برنامج تدريب عملي على الدعاوى المدنية وصياغة اللوائح والمرافعات.', 6, 'عمان - الشميساني', '2026-01-15', '2026-07-15', 'open', 2, '2026-01-03 09:00:00', '2026-01-03 09:00:00'),
(1002, 2, 'تدريب قضايا أحوال شخصية', 'جلسات عملية على لوائح الأحوال الشخصية وإجراءات المحاكم الشرعية.', 4, 'إربد - الحي الشرقي', '2026-01-20', '2026-05-20', 'open', 1, '2026-01-03 09:10:00', '2026-01-03 09:10:00'),
(1003, 3, 'تدريب إجراءات جزائية ومرافعات', 'تطبيقات على ملفات جزائية ومحاضر ضبط ومرافعات.', 5, 'الكرك - وسط البلد', '2026-02-01', '2026-07-01', 'open', 2, '2026-01-03 09:20:00', '2026-01-03 09:20:00'),
(1004, 5, 'تدريب صياغة العقود', 'صياغة عقود بيع/إيجار/شراكة + مراجعة مخاطر قانونية.', 3, 'الزرقاء - الجديدة', '2026-01-25', '2026-04-25', 'open', 3, '2026-01-03 09:30:00', '2026-01-03 09:30:00'),
(1005, 4, 'تدريب مبتدئين - مكتب عام', 'مقدمة عملية لإدارة ملف العميل والمراسلات والمتابعة.', 3, 'مادبا - قرب المحكمة', '2026-02-10', '2026-05-10', 'open', 1, '2026-01-03 09:40:00', '2026-01-03 09:40:00');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_applications`
--

INSERT INTO `training_applications` (`application_id`, `trainee_id`, `training_id`, `status`, `trainee_seen`, `applied_at`, `reviewed_at`, `notes`, `syndicate_notified`) VALUES
(5001, 11, 1001, 'pending', 1, '2026-01-03 12:00:00', NULL, 'يفضل دوام صباحي', 0),
(5002, 12, 1001, 'accepted', 1, '2026-01-03 12:10:00', '2026-01-03 16:00:00', 'تم القبول - يرجى إحضار السيرة الذاتية', 1),
(5003, 13, 1002, 'rejected', 1, '2026-01-03 12:20:00', '2026-01-03 17:10:00', 'اكتمال المقاعد', 1),
(5004, 14, 1003, 'accepted', 0, '2026-01-03 12:30:00', '2026-01-03 18:00:00', 'موعد مقابلة قبل البدء', 1),
(5005, 15, 1004, 'pending', 0, '2026-01-03 12:40:00', NULL, 'قريب من مكان السكن', 0),
(5006, 16, 1004, 'accepted', 1, '2026-01-03 12:50:00', '2026-01-03 18:30:00', 'قبول مبدئي', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `national_id` varchar(10) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('trainee','lawyer','admin','syndicate_admin','IT_Provider','IT_Trainee') NOT NULL DEFAULT 'trainee',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_completed` tinyint(1) NOT NULL DEFAULT 0,
  `profile_completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `national_id`, `phone`, `email`, `address`, `password`, `role`, `created_at`, `updated_at`, `profile_completed`, `profile_completed_at`) VALUES
(1, 'admin', '0000000000', '0790000000', 'admin@example.com', 'Head Office', '$2y$10$btXC0u5ep0CjLg87dlFAjOzJHipd54ijGMDmFsEGGQetAtgx6ObDi', 'admin', '2025-11-12 00:28:19', '2025-11-14 01:19:45', 0, NULL),
(3, 'موظف نقابة رقم 1', '9999999999', '0799999999', 'syndicate_admin@example.com', 'النقابة', '$2y$10$Azlsa2XqFhNZHWBkRwlT9.JlDKgcX/BrgpM5d/YgGZ1L1iTZVfAFe', 'syndicate_admin', '2025-12-11 23:35:03', '2025-12-12 17:48:52', 0, NULL),
(26, 'محمد احمد محمد المحامي', '1111111111', '0790000000', 'lawyer1@example.com', 'عمان', '$2y$10$QjzUou/jsvmvSHZ2VHYS6OUHf5jfnKaAr148QrNvEUEExQBQ/VC/G', 'lawyer', '2025-11-29 17:26:12', '2025-12-22 20:58:22', 0, '0000-00-00 00:00:00'),
(27, 'محمد أحمد مصطفى الخطيب', '1000000001', '0790000001', 'lawyer1@test.com', 'عمان', '$2y$10$KiNjGQHAmt4wCVQPzEZlAODYuM0tRreeUdbZ9.aSkz/u9R.nhiV0a', 'trainee', '2025-11-29 19:53:21', '2025-12-22 00:14:50', 0, '0000-00-00 00:00:00'),
(101, 'خالد محمود أحمد الزعبي', '7012945836', '0791122334', 'khaled@gmail.com', 'عمان - الشميساني', '$2y$10$vP2Jzy0wojTXzqtTaB9p1ufYkVICqYSOAWrm32CGi7BCRkLTODnMS', 'lawyer', '2026-01-02 10:00:00', '2026-01-04 23:49:24', 1, '2026-01-02 10:30:00'),
(102, 'أحمد عادل يوسف العموش', '8391054721', '0786655443', 'ahmad@gmail.com', 'إربد - الحي الشرقي', '$2y$10$BheuoAYgnih9H1ybAG76WuXExIWnPa3GwwNYsWE.Z.xWXxAkAaeJy', 'lawyer', '2026-01-02 10:05:00', '2026-01-04 23:49:24', 1, '2026-01-02 10:40:00'),
(103, 'سامي جهاد حسن الطراونة', '6158739204', '0794433221', 'sami@gmail.com', 'الكرك - وسط البلد', '$2y$10$4YnmA7sab2X9VoWuEzYAM.fdpNbCb9UH8u77rGqpvgL2vTC3Bxx/W', 'lawyer', '2026-01-02 10:10:00', '2026-01-04 23:49:24', 1, '2026-01-02 10:45:00'),
(104, 'يوسف فادي محمود المجالي', '9024763158', '0779988776', 'yousef@gmail.com', 'مادبا - قرب المحكمة', '$2y$10$fQBYqVfqjveiR1w7DMN9oODdfaNCX3pgnEz/hHtcecOXveZGYSHBK', 'lawyer', '2026-01-02 10:12:00', '2026-01-04 23:49:24', 0, NULL),
(105, 'محمود رائد سليمان بني ياسين', '7483629105', '0792211345', 'mahmoud@gmail.com', 'الزرقاء - الجديدة', '$2y$10$dsiL/wEQYAoYPJS3HsORou11vUYBLy99kqcd5Mlp1wroHYwHWqRF.', 'lawyer', '2026-01-02 10:15:00', '2026-01-04 23:49:24', 1, '2026-01-02 11:00:00'),
(106, 'عمر ناصر محمد الحراحشة', '5630198472', '0783344556', 'omar@gmail.com', 'عمان - جبل الحسين', '$2y$10$51lOPSTWAyMweEcgYtRj5u6g1wu7Kv66B76DqXM5GUMPYOAdhNoga', 'lawyer', '2026-01-02 10:20:00', '2026-01-04 23:49:24', 0, NULL),
(201, 'ليث إبراهيم صالح الشريدة', '3109754628', '0795566001', 'laith@gmail.com', 'البلقاء - الفحيص', '$2y$10$DfRwwfjilYSflJhmrEYMB.nhBgGMmtkUcLhW3O7twFkXfALed0bXm', 'trainee', '2026-01-02 11:10:00', '2026-01-04 23:49:24', 1, '2026-01-02 11:35:00'),
(202, 'يحيى فؤاد عبدالكريم العدوان', '4286019735', '0782233002', 'yahya@gmail.com', 'عمان - طبربور', '$2y$10$ldvzQBOh9nYIlxzum0.XS.fVcyiPkOunn5tRZpqDBtMjrjCE2/dE.', 'trainee', '2026-01-02 11:15:00', '2026-01-04 23:49:24', 1, '2026-01-02 11:40:00'),
(203, 'رامي جمال أمين العناسوة', '1567430298', '0797788003', 'rami@gmail.com', 'إربد - الحصن', '$2y$10$EDFtDxdUmv/fif7g/g3DweO67GgC6duNGMKxAZPQguiCHY4KtTg1.', 'trainee', '2026-01-02 11:20:00', '2026-01-04 23:49:24', 0, NULL),
(204, 'تامر مازن خليل الرواشدة', '9073146251', '0774455004', 'tamer@gmail.com', 'الكرك - المرج', '$2y$10$H6YaTTrqWcesPRBEsfbGZOqFunOM1xulh/5fXCl93YHIn0t/NSKV6', 'trainee', '2026-01-02 11:22:00', '2026-01-04 23:49:24', 1, '2026-01-02 11:55:00'),
(205, 'سيف سامر يوسف الجازي', '6642087319', '0793344005', 'saif@gmail.com', 'الزرقاء - الغويرية', '$2y$10$OKQD73la/ZuoUDdeFeNBtOdqo3X6ISanXyT/6MmGECSVIAoOipyDK', 'trainee', '2026-01-02 11:25:00', '2026-01-04 23:49:24', 0, NULL),
(207, 'company', NULL, '0784564566', 'company@example.com', 'amman', '$2y$10$QnHL0feBKB8hwdamzNbwPezQO7x1gVlq5Vy8wzNcUQC.HTqK4sBSW', 'IT_Provider', '2026-01-26 21:12:39', '2026-01-26 21:12:39', 0, NULL),
(208, 'محمد احمد لؤي تيست', '1010101010', '0780000001', 'moh@example.com', 'amman', '$2y$10$hDK.EbimjfqYSFnfeNJm0eISKbmfm86DBnYFRCJIprnbs9UoRZnLK', 'IT_Trainee', '2026-01-26 21:47:50', '2026-01-26 21:47:50', 0, NULL),
(209, '2company', NULL, '0784564556', 'company2@example.com', 'amman', '$2y$10$pE3msYYhe.X7Kpuw1tYBdOEmJao4vVT5H8Y19G6WvMHME3dsuuVG6', 'IT_Provider', '2026-01-26 22:17:55', '2026-01-26 22:17:55', 0, NULL),
(212, 'سيد علي لؤي المتدرب', NULL, '0780123456', 'saed@example.com', 'عمان', '$2y$10$cZoY/Fkr8sTi7z9ixQPvgupVk0ylJgXRUP0UVRtT2O0SPcsBGModO', 'trainee', '2026-02-02 22:29:06', '2026-02-02 22:29:06', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allied_medical_providers`
--
ALTER TABLE `allied_medical_providers`
  ADD PRIMARY KEY (`provider_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `allied_medical_trainees`
--
ALTER TABLE `allied_medical_trainees`
  ADD PRIMARY KEY (`trainee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `architecture_design_providers`
--
ALTER TABLE `architecture_design_providers`
  ADD PRIMARY KEY (`provider_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `architecture_design_trainees`
--
ALTER TABLE `architecture_design_trainees`
  ADD PRIMARY KEY (`trainee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `business_providers`
--
ALTER TABLE `business_providers`
  ADD PRIMARY KEY (`provider_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `business_trainees`
--
ALTER TABLE `business_trainees`
  ADD PRIMARY KEY (`trainee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_calendar_user_start` (`user_id`,`start_at`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `it_applications`
--
ALTER TABLE `it_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `uq_application_unique` (`internship_id`,`trainee_user_id`),
  ADD KEY `idx_it_applications_internship` (`internship_id`),
  ADD KEY `idx_it_applications_trainee` (`trainee_user_id`),
  ADD KEY `idx_it_applications_status` (`status`),
  ADD KEY `idx_it_applications_applied_at` (`applied_at`);

--
-- Indexes for table `it_internships`
--
ALTER TABLE `it_internships`
  ADD PRIMARY KEY (`internship_id`),
  ADD KEY `idx_it_internships_provider` (`provider_user_id`),
  ADD KEY `idx_it_internships_status` (`status`),
  ADD KEY `idx_it_internships_city` (`city`),
  ADD KEY `idx_it_internships_published_at` (`published_at`);

--
-- Indexes for table `it_providers`
--
ALTER TABLE `it_providers`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_it_providers_company_name` (`company_name`);

--
-- Indexes for table `it_trainees`
--
ALTER TABLE `it_trainees`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_it_trainees_graduation_year` (`graduation_year`);

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
-- Indexes for table `literature_providers`
--
ALTER TABLE `literature_providers`
  ADD PRIMARY KEY (`provider_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `literature_trainees`
--
ALTER TABLE `literature_trainees`
  ADD PRIMARY KEY (`trainee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `membership_requests`
--
ALTER TABLE `membership_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_membership_nat_status` (`national_id`,`status`);

--
-- Indexes for table `syndicate_exam_requests`
--
ALTER TABLE `syndicate_exam_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `trainees`
--
ALTER TABLE `trainees`
  ADD PRIMARY KEY (`trainee_id`);

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
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD UNIQUE KEY `uq_users_national_id` (`national_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allied_medical_providers`
--
ALTER TABLE `allied_medical_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `allied_medical_trainees`
--
ALTER TABLE `allied_medical_trainees`
  MODIFY `trainee_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `architecture_design_providers`
--
ALTER TABLE `architecture_design_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `architecture_design_trainees`
--
ALTER TABLE `architecture_design_trainees`
  MODIFY `trainee_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_providers`
--
ALTER TABLE `business_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_trainees`
--
ALTER TABLE `business_trainees`
  MODIFY `trainee_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8004;

--
-- AUTO_INCREMENT for table `it_applications`
--
ALTER TABLE `it_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `it_internships`
--
ALTER TABLE `it_internships`
  MODIFY `internship_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `lawyers`
--
ALTER TABLE `lawyers`
  MODIFY `lawyer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `lawyers_syndicate`
--
ALTER TABLE `lawyers_syndicate`
  MODIFY `syndicate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `literature_providers`
--
ALTER TABLE `literature_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `literature_trainees`
--
ALTER TABLE `literature_trainees`
  MODIFY `trainee_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `membership_requests`
--
ALTER TABLE `membership_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6016;

--
-- AUTO_INCREMENT for table `syndicate_exam_requests`
--
ALTER TABLE `syndicate_exam_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9010;

--
-- AUTO_INCREMENT for table `trainees`
--
ALTER TABLE `trainees`
  MODIFY `trainee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `training_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1006;

--
-- AUTO_INCREMENT for table `training_applications`
--
ALTER TABLE `training_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5007;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=213;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `fk_calendar_events_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `it_applications`
--
ALTER TABLE `it_applications`
  ADD CONSTRAINT `fk_it_applications_internship` FOREIGN KEY (`internship_id`) REFERENCES `it_internships` (`internship_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_it_applications_trainee` FOREIGN KEY (`trainee_user_id`) REFERENCES `it_trainees` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `it_internships`
--
ALTER TABLE `it_internships`
  ADD CONSTRAINT `fk_it_internships_provider` FOREIGN KEY (`provider_user_id`) REFERENCES `it_providers` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `it_providers`
--
ALTER TABLE `it_providers`
  ADD CONSTRAINT `fk_it_providers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `it_trainees`
--
ALTER TABLE `it_trainees`
  ADD CONSTRAINT `fk_it_trainees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
