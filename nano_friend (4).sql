-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2025 at 05:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nano_friend`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `target_type`, `target_id`, `description`, `ip_address`, `created_at`) VALUES
(112, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-06-08 12:27:28'),
(113, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-06-08 17:42:14'),
(114, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-06-10 10:58:54'),
(115, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-06-11 06:17:46'),
(116, 22, 'create_shop', 'shop', 31, 'สร้างร้านค้า ดีไอวาย (NNF-1027)', '::1', '2025-06-11 19:51:12'),
(117, 22, 'create_shop', 'shop', 32, 'สร้างร้านค้า บ้านนาดีมือถือ (NNF-1032)', '::1', '2025-06-11 19:51:48');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) DEFAULT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `log_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `amortization_schedule`
--

CREATE TABLE `amortization_schedule` (
  `id` int(11) NOT NULL,
  `formula` enum('fixed','floating','custom') NOT NULL,
  `months` tinyint(3) UNSIGNED NOT NULL,
  `installment` smallint(5) UNSIGNED NOT NULL,
  `due_date` date NOT NULL,
  `principal` decimal(12,2) NOT NULL,
  `interest` decimal(12,2) NOT NULL,
  `balance` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `apple_ids`
--

CREATE TABLE `apple_ids` (
  `id` int(11) NOT NULL,
  `apple_id` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `pincode` char(4) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `contracts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`contracts`)),
  `history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`history`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `apple_ids`
--

INSERT INTO `apple_ids` (`id`, `apple_id`, `password`, `pincode`, `is_active`, `contracts`, `history`, `created_at`, `updated_at`) VALUES
(3, 'chute.251@icloud.com', 'MiyuiTHAI5645', '9120', 1, '[]', '[{\"changed_at\":\"2025-06-08T15:19:39+07:00\",\"old_password\":\"0D*Fec)7*t4u\",\"old_pincode\":\"2139\",\"changed_by\":22},{\"changed_at\":\"2025-06-08T17:26:35+07:00\",\"old_password\":\"Google12345\",\"old_pincode\":\"0001\",\"changed_by\":22}]', '2025-06-08 15:11:49', '2025-06-08 17:26:35'),
(9, 'Yommana888@hotmail.com', 'Yommana564', '8520', 1, '[]', '[]', '2025-06-08 17:40:28', '2025-06-08 17:40:28'),
(10, 'comzero11@gmail.com', 'updad85244', '9547', 1, '[]', '[{\"changed_at\":\"2025-06-10T11:54:40+07:00\",\"old_password\":\"updad8520\",\"old_pincode\":\"9546\",\"changed_by\":22}]', '2025-06-10 11:42:42', '2025-06-10 11:54:40'),
(11, 'Puridech12311@icloud.com', 'ldldlldld', '8520', 1, '[]', '[]', '2025-06-10 11:54:22', '2025-06-10 11:54:22');

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_address` text NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `payment_methods` text NOT NULL,
  `line_qr_path` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `line_id` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `company_name`, `company_address`, `branch_name`, `payment_methods`, `line_qr_path`, `logo_path`, `updated_at`, `line_id`) VALUES
(1, 'บริษัท นาโนเฟรนด์ จำกัด', '61 ถนนอรุณอมรินทร์<br>\r\nแขวงอรุณอมรินทร์<br>เขตบางกอกน้อย<br>\r\nกรุงเทพมหานคร 10700<br>', 'สมหวัง', 'โอนชำระที่\r\nธนาคารทหารไทย สาขาอรุณอมรินทร์\r\nชื่อบัญชี: ธนวรรณณ์ วีระกิฎธารา\r\nเลขที่บัญชี: 922-9-63374-9\r\nโทรศัพท์: 080-559-3431\r\nLine: @nanopay', 'uploads/settings/line_qr_1748881133.png', 'uploads/settings/company_logo_1748881554.png', '2025-06-02 23:25:54', '');

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `contract_no_shop` varchar(50) DEFAULT NULL,
  `contract_no_approved` varchar(50) DEFAULT NULL,
  `shop_id` int(11) NOT NULL,
  `customer_firstname` varchar(100) DEFAULT NULL,
  `customer_lastname` varchar(100) DEFAULT NULL,
  `customer_id_card` varchar(20) DEFAULT NULL,
  `customer_birth_date` date DEFAULT NULL,
  `customer_moo` varchar(20) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_line` varchar(100) DEFAULT NULL,
  `customer_facebook` varchar(100) DEFAULT NULL,
  `loan_amount` decimal(10,2) DEFAULT NULL,
  `installment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `device_brand` varchar(50) DEFAULT NULL,
  `device_model` varchar(50) DEFAULT NULL,
  `device_capacity` varchar(50) DEFAULT NULL,
  `device_color` varchar(50) DEFAULT NULL,
  `device_imei` varchar(20) DEFAULT NULL,
  `device_serial_no` varchar(50) DEFAULT NULL,
  `lock_type` enum('mdm','icloud') DEFAULT NULL,
  `mdm_reference` varchar(255) DEFAULT NULL,
  `icloud_email` varchar(255) DEFAULT NULL,
  `icloud_password` varchar(255) DEFAULT NULL,
  `icloud_pin` varchar(50) DEFAULT NULL,
  `manual_group_id` int(10) UNSIGNED DEFAULT NULL,
  `contract_type` enum('monthly','floating_interest','fixed_interest','custom','manual') NOT NULL DEFAULT 'monthly',
  `period_months` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reject_reason` text DEFAULT NULL,
  `allow_resubmit` tinyint(1) NOT NULL DEFAULT 0,
  `contract_update_at` datetime DEFAULT NULL,
  `approval_at` datetime DEFAULT current_timestamp(),
  `commission_status` enum('commission_pending','commission_transferred','commission_cancelled') DEFAULT 'commission_pending',
  `commission_slip` varchar(255) DEFAULT NULL,
  `commission_at` datetime DEFAULT current_timestamp(),
  `start_date` datetime NOT NULL,
  `end_date` date DEFAULT NULL,
  `province_id` int(10) UNSIGNED NOT NULL,
  `amphur_id` int(10) UNSIGNED NOT NULL,
  `district_id` int(10) UNSIGNED NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `house_number` varchar(100) NOT NULL,
  `moo` varchar(50) DEFAULT NULL,
  `soi` varchar(100) DEFAULT NULL,
  `other_address` varchar(255) DEFAULT NULL,
  `branch_name` varchar(100) NOT NULL DEFAULT 'สมหวัง',
  `created_at` datetime DEFAULT current_timestamp(),
  `commission_amount` decimal(10,2) DEFAULT NULL COMMENT 'ยอดคอมมิชชั่นที่โอนให้',
  `commission_transferred_at` datetime DEFAULT NULL COMMENT 'วันที่-เวลาโอนคอมมิชชั่น',
  `commission_slip_path` varchar(255) DEFAULT NULL COMMENT 'ไฟล์สลิปคอมมิชชั่น',
  `disbursement_cost` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'ต้นทุนปล่อยสินเชื่อ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contracts`
--

INSERT INTO `contracts` (`id`, `contract_no_shop`, `contract_no_approved`, `shop_id`, `customer_firstname`, `customer_lastname`, `customer_id_card`, `customer_birth_date`, `customer_moo`, `customer_phone`, `customer_line`, `customer_facebook`, `loan_amount`, `installment_amount`, `device_brand`, `device_model`, `device_capacity`, `device_color`, `device_imei`, `device_serial_no`, `lock_type`, `mdm_reference`, `icloud_email`, `icloud_password`, `icloud_pin`, `manual_group_id`, `contract_type`, `period_months`, `approval_status`, `reject_reason`, `allow_resubmit`, `contract_update_at`, `approval_at`, `commission_status`, `commission_slip`, `commission_at`, `start_date`, `end_date`, `province_id`, `amphur_id`, `district_id`, `postal_code`, `house_number`, `moo`, `soi`, `other_address`, `branch_name`, `created_at`, `commission_amount`, `commission_transferred_at`, `commission_slip_path`, `disbursement_cost`) VALUES
(49, 'TEST-05301', NULL, 26, 'นายทดสอบ', 'งานดี', '3584546546546', '1446-03-07', NULL, '0959487652', 'https://line.me/R/ti/p/@387mkssg', 'https://facebook.com/example', 5000.00, 208.33, 'Apple', 'iPhone 16 Pro', '128 GB', 'ดำ', '354564654616132', 'dsd54da54s', 'icloud', NULL, 'test2@gmail.com', 'Test21231', '8520', NULL, 'fixed_interest', 1, 'approved', NULL, 1, '2025-06-08 12:52:34', '2025-06-08 12:48:48', 'commission_transferred', NULL, '2025-06-08 12:48:48', '2025-06-09 12:46:00', '2025-07-09', 14, 1416, 141601, '', '95/70 เดอะวิลล์', '', 'นิมติใหม่ 6', '', 'สมหวัง', '2025-06-08 12:48:48', 500.00, '2025-06-08 12:53:00', 'uploads/commission_slips/contract_49_20250608125325_29dda977.jpg', 0.00),
(50, 'TEST-05302', NULL, 26, 'นางสาว ย่านผี', 'แล่นหนีไป', '3564879879879', '1448-12-05', NULL, '0959487652', 'https://line.me/R/ti/p/@387mkssg', 'https://facebook.com/example', 1.00, 0.04, 'Apple', 'iPhone 11', '128 GB', 'ดำ', '354564654616132', 'dsd54da54s', 'icloud', NULL, 'comzero11@gmail.com', 'updad8520', '9546', NULL, 'fixed_interest', 1, 'approved', NULL, 0, '2025-06-08 15:03:21', '2025-06-08 15:03:07', 'commission_pending', NULL, '2025-06-08 15:03:07', '2025-06-08 15:01:00', '2025-07-08', 11, 1102, 110201, '10560', '95/70 เดอะวิลล์', '', 'นิมติใหม่ 6', '', 'สมหวัง', '2025-06-08 15:03:07', NULL, NULL, NULL, 0.00),
(51, 'TEST-05303', NULL, 26, 'นายหลอกผี', 'หนีไม่ไกล', '3456416513621', '1443-01-01', NULL, '0959487652', 'https://line.me/R/ti/p/@387mkssg', 'https://facebook.com/example', 5000.00, 208.33, 'Apple', 'iPhone 15 Pro Max', '128 GB', 'ดำ', '354564654616132', 'dsd54da54s', 'icloud', NULL, 'chute.251@icloud.com', 'wTp4MJbDFfdy', '7894', NULL, 'fixed_interest', 1, 'approved', NULL, 0, '2025-06-10 12:46:15', '2025-06-08 16:17:16', 'commission_transferred', NULL, '2025-06-08 16:17:16', '2025-06-08 16:16:00', '2025-07-08', 11, 1104, 110415, '10130', '95/70 เดอะวิลล์', '', 'นิมติใหม่ 6', '', 'สมหวัง', '2025-06-08 16:17:16', 800.00, '2025-06-10 12:47:00', 'uploads/commission_slips/contract_51_20250610124734_3ebabc61.jpg', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `contract_evidence`
--

CREATE TABLE `contract_evidence` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `evidence_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) NOT NULL COMMENT 'shop user id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contract_evidence`
--

INSERT INTO `contract_evidence` (`id`, `contract_id`, `evidence_type`, `file_path`, `uploaded_at`, `uploaded_by`) VALUES
(124, 49, 'signed_contract', 'uploads/evidence/49/1749361844_signed_contract_0_33.png', '2025-06-08 12:50:44', 26),
(125, 49, 'signed_contract', 'uploads/evidence/49/1749361844_signed_contract_1_22.png', '2025-06-08 12:50:44', 26),
(126, 49, 'signed_contract', 'uploads/evidence/49/1749361844_signed_contract_2_11.png', '2025-06-08 12:50:44', 26),
(127, 49, 'customer_photo', 'uploads/evidence/49/1749361844_customer_photo_Dtbezn3nNUxytg04aveWSnrR1RCDwnfybCAthlNq7TDyhr.jpg', '2025-06-08 12:50:44', 26),
(128, 49, 'device_photo', 'uploads/evidence/49/1749361844_device_photo_S__5161032_0.jpg', '2025-06-08 12:50:44', 26),
(129, 49, 'imei_photo', 'uploads/evidence/49/1749361844_imei_photo_471436635_10233336104734438_6060860188770727772_n.jpg', '2025-06-08 12:50:44', 26),
(130, 49, 'lock_screen_photo', 'uploads/evidence/49/1749361844_lock_screen_photo_476610613_10233574981786215_5343288641761439254_n.jpg', '2025-06-08 12:50:44', 26);

-- --------------------------------------------------------

--
-- Table structure for table `contract_number_settings`
--

CREATE TABLE `contract_number_settings` (
  `id` int(11) NOT NULL,
  `prefix` varchar(20) NOT NULL DEFAULT '',
  `pattern` enum('sequential','random') NOT NULL DEFAULT 'sequential',
  `include_date` tinyint(1) NOT NULL DEFAULT 0,
  `date_format` varchar(20) NOT NULL DEFAULT 'Ymd',
  `random_length` smallint(5) UNSIGNED NOT NULL DEFAULT 6,
  `seq_length` smallint(5) UNSIGNED NOT NULL DEFAULT 4,
  `next_sequence` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contract_number_settings`
--

INSERT INTO `contract_number_settings` (`id`, `prefix`, `pattern`, `include_date`, `date_format`, `random_length`, `seq_length`, `next_sequence`, `is_active`, `updated_at`) VALUES
(1, 'TEST-', 'sequential', 0, 'Ymd', 6, 5, 5304, 1, '2025-06-08 09:16:01');

-- --------------------------------------------------------

--
-- Table structure for table `contract_templates`
--

CREATE TABLE `contract_templates` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `contract_title` varchar(255) DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `payment_period` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_interest_entries`
--

CREATE TABLE `custom_interest_entries` (
  `id` int(10) UNSIGNED NOT NULL,
  `formula_id` tinyint(3) UNSIGNED NOT NULL,
  `months` tinyint(3) UNSIGNED NOT NULL,
  `value` decimal(8,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_interest_entries`
--

INSERT INTO `custom_interest_entries` (`id`, `formula_id`, `months`, `value`) VALUES
(107, 1, 1, 0.1000),
(108, 1, 2, 0.1000),
(109, 1, 3, 0.1000),
(110, 1, 4, 0.1000),
(111, 1, 5, 0.1000),
(112, 1, 6, 0.1000),
(113, 1, 7, 0.1000),
(114, 1, 8, 0.1000),
(115, 1, 9, 0.1000),
(116, 1, 10, 0.1000);

-- --------------------------------------------------------

--
-- Table structure for table `custom_interest_formulas`
--

CREATE TABLE `custom_interest_formulas` (
  `formula_id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `interval_days` smallint(5) UNSIGNED NOT NULL DEFAULT 15,
  `is_active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_interest_formulas`
--

INSERT INTO `custom_interest_formulas` (`formula_id`, `name`, `description`, `interval_days`, `is_active`) VALUES
(1, '3. ค่าบริการคิดเป็นเปอร์เซนต์', 'บวกได้ตามใจชอบ สามารถบวกเปอร์เซนต์ได้ตามที่ต้องการ', 30, 1);

-- --------------------------------------------------------

--
-- Table structure for table `device_models`
--

CREATE TABLE `device_models` (
  `id` int(11) NOT NULL,
  `brand` varchar(100) NOT NULL COMMENT 'ยี่ห้อ',
  `model_name` varchar(100) NOT NULL COMMENT 'ชื่อรุ่น',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_models`
--

INSERT INTO `device_models` (`id`, `brand`, `model_name`, `created_at`) VALUES
(1, 'Apple', 'iPhone 11', '2025-05-25 14:46:14'),
(2, 'Apple', 'iPhone 11 Pro', '2025-05-25 14:51:19'),
(3, 'Apple', 'iPhone 11 Pro Max', '2025-05-25 14:51:34'),
(4, 'Apple', 'iPhone 12', '2025-05-25 14:51:48'),
(5, 'Apple', 'iPhone 12 Pro', '2025-05-25 14:51:56'),
(6, 'Apple', 'iPhone 12 Pro Max', '2025-05-25 17:44:30'),
(7, 'Apple', 'iPhone 13', '2025-05-26 10:32:10'),
(8, 'Apple', 'iPhone 13 Pro', '2025-05-26 10:32:33'),
(9, 'Apple', 'iPhone 13 Pro Max', '2025-05-26 10:32:42'),
(10, 'Apple', 'iPhone 14', '2025-05-26 10:32:50'),
(11, 'Apple', 'iPhone 14 Pro', '2025-05-26 10:33:01'),
(12, 'Apple', 'iPhone 14 Pro Max', '2025-05-26 10:36:07'),
(13, 'Apple', 'iPhone 15', '2025-05-26 10:36:29'),
(14, 'Apple', 'iPhone 15 Pro', '2025-05-26 10:36:35'),
(15, 'Apple', 'iPhone 15 Pro Max', '2025-05-26 10:36:40'),
(16, 'Apple', 'iPhone 16', '2025-05-26 10:36:51'),
(17, 'Apple', 'iPhone 16 Pro', '2025-05-26 10:36:56'),
(18, 'Apple', 'iPhone 16 Pro Max', '2025-05-26 10:37:00');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `expense_type` enum('commission','lock_program','disbursement','other') NOT NULL COMMENT 'ประเภทค่าใช้จ่าย',
  `amount` decimal(10,2) NOT NULL COMMENT 'จำนวนเงิน',
  `note` varchar(255) DEFAULT NULL COMMENT 'หมายเหตุ (ถ้ามี)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่-เวลาที่บันทึก'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='เก็บค่าใช้จ่ายของแต่ละสัญญา';

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `contract_id`, `expense_type`, `amount`, `note`, `created_at`) VALUES
(21, 49, 'lock_program', 220.00, 'ร้าน Nano ล็อคด้วย Apple id', '2025-06-08 12:55:00'),
(22, 49, 'other', 100.00, 'ค่าส่งสินค้า', '2025-06-08 12:55:26'),
(23, 51, 'lock_program', 220.00, '', '2025-06-10 12:46:51'),
(24, 51, 'other', 100.00, 'ค่าส่งของให้ลูกค้า', '2025-06-10 12:47:06');

-- --------------------------------------------------------

--
-- Table structure for table `fixed_interest_multipliers`
--

CREATE TABLE `fixed_interest_multipliers` (
  `months` tinyint(3) UNSIGNED NOT NULL,
  `multiplier` decimal(8,4) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fixed_interest_multipliers`
--

INSERT INTO `fixed_interest_multipliers` (`months`, `multiplier`, `is_active`) VALUES
(1, 1.2500, 1),
(2, 1.3500, 1),
(3, 1.4500, 1),
(4, 1.5500, 1),
(5, 1.6500, 1),
(6, 1.7500, 1),
(7, 1.8500, 1),
(8, 1.9500, 1),
(9, 2.0000, 1),
(10, 2.0500, 1);

-- --------------------------------------------------------

--
-- Table structure for table `floating_interest_settings`
--

CREATE TABLE `floating_interest_settings` (
  `months` tinyint(3) UNSIGNED NOT NULL COMMENT 'จำนวนเดือน (n)',
  `rate` decimal(5,4) NOT NULL COMMENT 'อัตราดอกลอย (เช่น 0.30 = 30%)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=ใช้งาน, 0=ปิดใช้งาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตั้งค่าดอกลอย: เดือน, อัตราดอกเบี้ย, สถานะใช้งาน';

--
-- Dumping data for table `floating_interest_settings`
--

INSERT INTO `floating_interest_settings` (`months`, `rate`, `is_active`) VALUES
(1, 0.3000, 1),
(2, 0.3000, 1),
(3, 0.3000, 1),
(4, 0.3000, 1),
(5, 0.3000, 1),
(6, 0.3000, 1),
(7, 0.3000, 1),
(8, 0.3000, 1),
(9, 0.3000, 1),
(10, 0.3000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `guarantors`
--

CREATE TABLE `guarantors` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manual_interest_entries`
--

CREATE TABLE `manual_interest_entries` (
  `id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `month_idx` tinyint(3) UNSIGNED NOT NULL,
  `repayment` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manual_interest_entries`
--

INSERT INTO `manual_interest_entries` (`id`, `group_id`, `month_idx`, `repayment`, `is_active`) VALUES
(1063, 379, 1, 2500.00, 1),
(1064, 379, 2, 1500.00, 1),
(1065, 379, 3, 1150.00, 1),
(1066, 379, 4, 1000.00, 1),
(1067, 379, 5, 800.00, 1),
(1068, 379, 6, 500.00, 1),
(1069, 379, 7, 300.00, 1),
(1070, 379, 8, 100.00, 1),
(1071, 379, 9, 50.00, 1),
(1072, 379, 10, 20.00, 1),
(1073, 380, 1, 3750.00, 1),
(1074, 380, 2, 2250.00, 1),
(1075, 380, 3, 1750.00, 1),
(1076, 380, 4, 1500.00, 1),
(1077, 380, 5, 1200.00, 1),
(1078, 380, 6, 1000.00, 1),
(1079, 380, 7, 800.00, 1),
(1080, 380, 8, 600.00, 1),
(1081, 380, 9, 400.00, 1),
(1082, 380, 10, 200.00, 1),
(1083, 381, 1, 5000.00, 1),
(1084, 381, 2, 3000.00, 1),
(1085, 381, 3, 2330.00, 1),
(1086, 381, 4, 2000.00, 1),
(1087, 381, 5, 1500.00, 1),
(1088, 381, 6, 1300.00, 1),
(1089, 381, 7, 1100.00, 1),
(1090, 381, 8, 900.00, 1),
(1091, 381, 9, 700.00, 1),
(1092, 381, 10, 500.00, 1),
(1093, 382, 1, 6250.00, 1),
(1094, 382, 2, 3750.00, 1),
(1095, 382, 3, 2900.00, 1),
(1096, 382, 4, 2500.00, 1),
(1097, 382, 5, 2000.00, 1),
(1098, 382, 6, 1700.00, 1),
(1099, 382, 7, 1400.00, 1),
(1100, 382, 8, 1100.00, 1),
(1101, 382, 9, 700.00, 1),
(1102, 382, 10, 500.00, 1),
(1103, 383, 1, 7500.00, 1),
(1104, 383, 2, 4500.00, 1),
(1105, 383, 3, 3500.00, 1),
(1106, 383, 4, 3000.00, 1),
(1107, 383, 5, 2500.00, 1),
(1108, 383, 6, 2000.00, 1),
(1109, 383, 7, 1700.00, 1),
(1110, 383, 8, 1400.00, 1),
(1111, 383, 9, 1000.00, 1),
(1112, 383, 10, 500.00, 1),
(1113, 384, 1, 8750.00, 1),
(1114, 384, 2, 5250.00, 1),
(1115, 384, 3, 4000.00, 1),
(1116, 384, 4, 3250.00, 1),
(1117, 384, 5, 2500.00, 1),
(1118, 384, 6, 2100.00, 1),
(1119, 384, 7, 1700.00, 1),
(1120, 384, 8, 1400.00, 1),
(1121, 384, 9, 1000.00, 1),
(1122, 384, 10, 500.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `manual_interest_formulas`
--

CREATE TABLE `manual_interest_formulas` (
  `formula_id` tinyint(3) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manual_interest_formulas`
--

INSERT INTO `manual_interest_formulas` (`formula_id`, `is_active`) VALUES
(4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `manual_interest_groups`
--

CREATE TABLE `manual_interest_groups` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `formula_id` tinyint(3) UNSIGNED NOT NULL,
  `principal` decimal(12,2) NOT NULL,
  `group_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manual_interest_groups`
--

INSERT INTO `manual_interest_groups` (`group_id`, `formula_id`, `principal`, `group_name`) VALUES
(379, 4, 2000.00, 'กลุ่ม 1 กู้ = 2,000.-'),
(380, 4, 3000.00, 'กลุ่ม 2 กู้ = 3,000.-'),
(381, 4, 4000.00, 'กลุ่ม 3 กู้ = 4,000.-'),
(382, 4, 5000.00, 'กลุ่ม 4 กู้ = 5,000.-'),
(383, 4, 6000.00, 'กลุ่ม 5 กู้ = 6,000.-'),
(384, 4, 7000.00, 'กลุ่ม 6 กู้ = 7,000.-');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` char(32) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `pay_no` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_date` date DEFAULT NULL,
  `amount_due` decimal(10,2) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `slip_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `late_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `penalty_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fee_unlock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fee_document` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fee_other` decimal(12,2) NOT NULL DEFAULT 0.00,
  `slip_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`slip_paths`)),
  `is_closed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `contract_id`, `pay_no`, `due_date`, `amount`, `paid_date`, `amount_due`, `amount_paid`, `payment_method`, `note`, `slip_path`, `status`, `paid_at`, `created_by`, `created_at`, `updated_at`, `late_fee`, `penalty_amount`, `fee_unlock`, `fee_document`, `fee_other`, `slip_paths`, `is_closed`) VALUES
(280, 49, 1, '2025-06-10', 0.00, NULL, 208.33, 208.33, NULL, '', NULL, 'paid', '2025-06-10 15:01:05', NULL, '2025-06-08 12:52:27', '2025-06-10 15:01:05', 0.00, 100.00, 0.00, 0.00, 0.00, '[\"/uploads/slips/280/slip_68452607b19387.59950720.jpg\"]', 0),
(281, 49, 2, '2025-06-11', 0.00, NULL, 208.33, 208.33, NULL, 'จ่ายแล้ว', NULL, 'paid', '2025-06-11 07:14:59', NULL, '2025-06-08 12:52:27', '2025-06-11 07:14:59', 0.00, 0.00, 0.00, 0.00, 0.00, '[\"/uploads/slips/281/slip_6848ca834e5029.07237117.jpg\"]', 0),
(282, 49, 3, '2025-06-12', 0.00, NULL, 208.33, 100.00, NULL, '', NULL, 'paid', '2025-06-11 10:10:29', NULL, '2025-06-08 12:52:27', '2025-06-11 10:10:29', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(283, 49, 4, '2025-06-13', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(284, 49, 5, '2025-06-14', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(285, 49, 6, '2025-06-15', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(286, 49, 7, '2025-06-16', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(287, 49, 8, '2025-06-17', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(288, 49, 9, '2025-06-18', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(289, 49, 10, '2025-06-19', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(290, 49, 11, '2025-06-20', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(291, 49, 12, '2025-06-21', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(292, 49, 13, '2025-06-22', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(293, 49, 14, '2025-06-23', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(294, 49, 15, '2025-06-24', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(295, 49, 16, '2025-06-25', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(296, 49, 17, '2025-06-26', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(297, 49, 18, '2025-06-27', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(298, 49, 19, '2025-06-28', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(299, 49, 20, '2025-06-29', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(300, 49, 21, '2025-06-30', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(301, 49, 22, '2025-07-01', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(302, 49, 23, '2025-07-02', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(303, 49, 24, '2025-07-03', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(304, 49, 25, '2025-07-04', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(305, 49, 26, '2025-07-05', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(306, 49, 27, '2025-07-06', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(307, 49, 28, '2025-07-07', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(308, 49, 29, '2025-07-08', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(309, 49, 30, '2025-07-09', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 12:52:27', '2025-06-11 07:12:55', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(310, 50, 1, '2025-06-09', 0.00, NULL, 0.04, 0.04, NULL, '', NULL, 'paid', '2025-06-10 13:55:21', NULL, '2025-06-08 15:03:07', '2025-06-10 13:55:21', 0.00, 100.00, 0.00, 0.00, 0.00, '[\"/uploads/slips/310/slip_6847c71b086ae1.66520961.jpg\"]', 0),
(311, 50, 2, '2025-06-10', 0.00, NULL, 0.04, 0.04, NULL, '', NULL, 'paid', '2025-06-10 12:49:02', NULL, '2025-06-08 15:03:07', '2025-06-10 12:49:02', 0.00, 0.00, 0.00, 0.00, 0.00, '[\"/uploads/slips/311/slip_6847c74ef08ed8.99517907.jpg\"]', 0),
(312, 50, 3, '2025-06-11', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(313, 50, 4, '2025-06-12', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(314, 50, 5, '2025-06-13', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(315, 50, 6, '2025-06-14', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(316, 50, 7, '2025-06-15', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(317, 50, 8, '2025-06-16', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(318, 50, 9, '2025-06-17', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(319, 50, 10, '2025-06-18', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(320, 50, 11, '2025-06-19', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(321, 50, 12, '2025-06-20', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(322, 50, 13, '2025-06-21', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(323, 50, 14, '2025-06-22', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(324, 50, 15, '2025-06-23', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(325, 50, 16, '2025-06-24', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(326, 50, 17, '2025-06-25', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(327, 50, 18, '2025-06-26', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(328, 50, 19, '2025-06-27', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(329, 50, 20, '2025-06-28', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(330, 50, 21, '2025-06-29', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(331, 50, 22, '2025-06-30', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(332, 50, 23, '2025-07-01', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(333, 50, 24, '2025-07-02', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(334, 50, 25, '2025-07-03', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(335, 50, 26, '2025-07-04', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(336, 50, 27, '2025-07-05', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(337, 50, 28, '2025-07-06', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(338, 50, 29, '2025-07-07', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(339, 50, 30, '2025-07-08', 0.00, NULL, 0.04, NULL, NULL, NULL, NULL, '', NULL, NULL, '2025-06-08 15:03:07', '2025-06-11 09:18:00', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(340, 51, 1, '2025-06-09', 0.00, NULL, 208.33, 208.33, NULL, '', NULL, 'paid', '2025-06-11 06:23:48', NULL, '2025-06-08 16:17:16', '2025-06-11 06:23:48', 0.00, 100.00, 0.00, 0.00, 0.00, '[\"/uploads/slips/340/slip_6848be844bff51.12374848.jpg\"]', 0),
(341, 51, 2, '2025-06-10', 0.00, NULL, 208.33, 100.00, NULL, '', NULL, 'paid', '2025-06-11 17:36:48', NULL, '2025-06-08 16:17:16', '2025-06-11 17:36:48', 0.00, 100.00, 0.00, 0.00, 0.00, '[\"/uploads/slips/341/slip_68495c40c5cb51.25565718.jpg\"]', 0),
(342, 51, 3, '2025-06-11', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(343, 51, 4, '2025-06-12', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(344, 51, 5, '2025-06-13', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(345, 51, 6, '2025-06-14', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(346, 51, 7, '2025-06-15', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(347, 51, 8, '2025-06-16', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(348, 51, 9, '2025-06-17', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(349, 51, 10, '2025-06-18', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(350, 51, 11, '2025-06-19', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(351, 51, 12, '2025-06-20', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(352, 51, 13, '2025-06-21', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(353, 51, 14, '2025-06-22', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(354, 51, 15, '2025-06-23', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(355, 51, 16, '2025-06-24', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(356, 51, 17, '2025-06-25', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(357, 51, 18, '2025-06-26', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(358, 51, 19, '2025-06-27', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(359, 51, 20, '2025-06-28', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(360, 51, 21, '2025-06-29', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(361, 51, 22, '2025-06-30', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(362, 51, 23, '2025-07-01', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(363, 51, 24, '2025-07-02', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(364, 51, 25, '2025-07-03', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(365, 51, 26, '2025-07-04', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(366, 51, 27, '2025-07-05', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(367, 51, 28, '2025-07-06', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(368, 51, 29, '2025-07-07', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0),
(369, 51, 30, '2025-07-08', 0.00, NULL, 208.33, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, '2025-06-08 16:17:16', '2025-06-08 16:17:16', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payment_frequency`
--

CREATE TABLE `payment_frequency` (
  `formula` enum('fixed','floating','manual') NOT NULL COMMENT 'สูตรดอกเบี้ย fixed หรือ floating',
  `interval_days` smallint(5) UNSIGNED NOT NULL DEFAULT 15 COMMENT 'จำนวนวันระหว่างงวด',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=เปิดใช้งาน,0=ปิดใช้งาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตั้งค่าความถี่การชำระแยกตามสูตร';

--
-- Dumping data for table `payment_frequency`
--

INSERT INTO `payment_frequency` (`formula`, `interval_days`, `is_active`) VALUES
('', 30, 1),
('fixed', 1, 1),
('floating', 15, 1),
('manual', 30, 1);

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_fee_formulas`
--

CREATE TABLE `service_fee_formulas` (
  `formula_key` varchar(32) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_fee_formulas`
--

INSERT INTO `service_fee_formulas` (`formula_key`, `display_name`, `is_active`) VALUES
('custom', '3. ค่าบริการคิดเป็นเปอร์เซนต์', 1),
('fixed_interest', '1. ชำระค่าบริการแบบรายเดือน (ทบต้น)', 1),
('floating_interest', '2. แบบชำระค่าบริการรายงวด (จนกว่าจะคืนต้น)', 1),
('manual', '4. ค่าบริการไฟแนนซ์กำหนดเอง', 1);

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_name` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shop_orders`
--

CREATE TABLE `shop_orders` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shop_profits`
--

CREATE TABLE `shop_profits` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL COMMENT 'FK → users.id',
  `contract_id` int(11) NOT NULL COMMENT 'FK → contracts.id',
  `profit_amount` decimal(10,2) NOT NULL,
  `transferred_at` datetime NOT NULL COMMENT 'วันที่และเวลาที่ admin โอนให้',
  `slip_path` varchar(255) DEFAULT NULL COMMENT 'ไฟล์สลิป (relative path)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'ID ของคนสร้างเรคอร์ด (admin)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `tax_id` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `line_id` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','shop') NOT NULL DEFAULT 'shop',
  `is_active` tinyint(1) DEFAULT 1,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `theme` varchar(10) NOT NULL DEFAULT 'dark',
  `permissions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `address`, `phone`, `tax_id`, `website`, `line_id`, `email`, `username`, `password`, `role`, `is_active`, `latitude`, `longitude`, `created_at`, `theme`, `permissions`) VALUES
(22, 'Super Admin', NULL, NULL, NULL, NULL, NULL, 'kubu99@hotmail.com', 'kubu99', '$2y$10$WHso8d0p/Dl55RQ29rAc8uc2lD2/mBJRFivTzqNbEW11w/NosrIsS', 'superadmin', 1, NULL, NULL, '2025-05-24 09:55:38', 'dark', NULL),
(26, 'ใจดีโฟน', '88/50 เดอะวิลล์, นิมิตใหม่', '0808168803', '', 'https://creditgsm.com', 'utelecom', 'romihave@gmail.com', 'test2', '$2y$10$MOwggKjdaav5v4BagYU2.uzMXDfwqgrevg4nHCuc6eT9JdtSXAoxO', 'shop', 1, 13.8140899, 100.7281736, '2025-05-24 13:07:26', 'light', NULL),
(28, NULL, NULL, NULL, NULL, NULL, NULL, 'testmodeshop@gmail.com', 'testmodeshop', '$2y$10$T9S9yo72fBMqtnJOA3S5auUL4u9oeDQzE98Zh1Y14c2YHXzX3ZHli', 'superadmin', 1, NULL, NULL, '2025-05-30 05:24:14', 'dark', '[]'),
(31, 'ดีไอวาย', NULL, NULL, NULL, NULL, NULL, 'diyonbox@gmail.com', 'diyonbox', '$2y$10$AX/H/pvcGa2pIBe4uM21oej2Q7qH6/14HWqW.EHK3EMYshtjds9Vq', 'shop', 1, 15.0880355, 102.2195999, '2025-06-11 19:51:12', 'dark', NULL),
(32, 'บ้านนาดีมือถือ', NULL, NULL, NULL, NULL, NULL, 'banneedeeu@gmail.com', 'banneedee', '$2y$10$pUywPlLwkiyyV5FVT0DMQuKZf0bE5sFfGRG9MjaiYkdlPGIV7.p..', 'shop', 1, 15.6653542, 99.9036270, '2025-06-11 19:51:48', 'dark', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_al_user` (`user_id`),
  ADD KEY `idx_al_created` (`created_at`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `amortization_schedule`
--
ALTER TABLE `amortization_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `apple_ids`
--
ALTER TABLE `apple_ids`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `apple_id` (`apple_id`),
  ADD UNIQUE KEY `uq_apple_id` (`apple_id`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `contract_evidence`
--
ALTER TABLE `contract_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `contract_number_settings`
--
ALTER TABLE `contract_number_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contract_templates`
--
ALTER TABLE `contract_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `custom_interest_entries`
--
ALTER TABLE `custom_interest_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `formula_id` (`formula_id`);

--
-- Indexes for table `custom_interest_formulas`
--
ALTER TABLE `custom_interest_formulas`
  ADD PRIMARY KEY (`formula_id`);

--
-- Indexes for table `device_models`
--
ALTER TABLE `device_models`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contract` (`contract_id`);

--
-- Indexes for table `fixed_interest_multipliers`
--
ALTER TABLE `fixed_interest_multipliers`
  ADD PRIMARY KEY (`months`);

--
-- Indexes for table `floating_interest_settings`
--
ALTER TABLE `floating_interest_settings`
  ADD PRIMARY KEY (`months`);

--
-- Indexes for table `guarantors`
--
ALTER TABLE `guarantors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `manual_interest_entries`
--
ALTER TABLE `manual_interest_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_group` (`group_id`);

--
-- Indexes for table `manual_interest_formulas`
--
ALTER TABLE `manual_interest_formulas`
  ADD PRIMARY KEY (`formula_id`);

--
-- Indexes for table `manual_interest_groups`
--
ALTER TABLE `manual_interest_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `idx_formula` (`formula_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payment_frequency`
--
ALTER TABLE `payment_frequency`
  ADD PRIMARY KEY (`formula`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token_hash` (`token_hash`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `service_fee_formulas`
--
ALTER TABLE `service_fee_formulas`
  ADD PRIMARY KEY (`formula_key`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `shop_orders`
--
ALTER TABLE `shop_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `shop_profits`
--
ALTER TABLE `shop_profits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `ux_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `amortization_schedule`
--
ALTER TABLE `amortization_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `apple_ids`
--
ALTER TABLE `apple_ids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `contract_evidence`
--
ALTER TABLE `contract_evidence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `contract_number_settings`
--
ALTER TABLE `contract_number_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contract_templates`
--
ALTER TABLE `contract_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_interest_entries`
--
ALTER TABLE `custom_interest_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `device_models`
--
ALTER TABLE `device_models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `guarantors`
--
ALTER TABLE `guarantors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `manual_interest_entries`
--
ALTER TABLE `manual_interest_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1123;

--
-- AUTO_INCREMENT for table `manual_interest_groups`
--
ALTER TABLE `manual_interest_groups`
  MODIFY `group_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=385;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=370;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shop_orders`
--
ALTER TABLE `shop_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shop_profits`
--
ALTER TABLE `shop_profits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_al_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `admin_logs_ibfk_2` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- Constraints for table `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contract_evidence`
--
ALTER TABLE `contract_evidence`
  ADD CONSTRAINT `contract_evidence_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contract_templates`
--
ALTER TABLE `contract_templates`
  ADD CONSTRAINT `contract_templates_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_interest_entries`
--
ALTER TABLE `custom_interest_entries`
  ADD CONSTRAINT `custom_interest_entries_ibfk_1` FOREIGN KEY (`formula_id`) REFERENCES `custom_interest_formulas` (`formula_id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `guarantors`
--
ALTER TABLE `guarantors`
  ADD CONSTRAINT `guarantors_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `manual_interest_entries`
--
ALTER TABLE `manual_interest_entries`
  ADD CONSTRAINT `manual_interest_entries_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `manual_interest_groups` (`group_id`) ON DELETE CASCADE;

--
-- Constraints for table `manual_interest_groups`
--
ALTER TABLE `manual_interest_groups`
  ADD CONSTRAINT `manual_interest_groups_ibfk_1` FOREIGN KEY (`formula_id`) REFERENCES `manual_interest_formulas` (`formula_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_profits`
--
ALTER TABLE `shop_profits`
  ADD CONSTRAINT `fk_shop_profits_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_shop_profits_shop` FOREIGN KEY (`shop_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
