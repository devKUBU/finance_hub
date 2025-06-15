-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 27, 2025 at 12:59 AM
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
(1, 22, 'login', 'system', NULL, 'ล็อกอินเข้าสู่ระบบ', '::1', '2025-05-24 10:04:15'),
(2, 22, 'create_admin', 'admin', 23, 'สร้างแอดมิน username=sale.upservice, email=sale.upservice@gmail.com', '::1', '2025-05-24 10:04:51'),
(3, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-24 10:08:04'),
(4, 22, 'edit_admin', 'admin', 23, 'แก้ไขแอดมิน id=23, email=sale.upservice@gmail.com, is_active=1', '::1', '2025-05-24 10:08:21'),
(5, 22, 'edit_admin', 'admin', 23, 'แก้ไขแอดมิน id=23, email=sale.upservice@gmail.com, is_active=0', '::1', '2025-05-24 10:08:34'),
(6, 22, 'edit_admin', 'admin', 23, 'แก้ไขแอดมิน id=23, email=sale.upservice@gmail.com, is_active=1', '::1', '2025-05-24 10:08:45'),
(7, 22, 'create_shop', 'shop', 24, 'สร้างร้านค้า อัปเดตโฟน สาขา 2 (NNF-1001)', '::1', '2025-05-24 10:58:17'),
(8, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-24 12:38:50'),
(9, 22, 'create_admin', 'admin', 25, 'สร้างแอดมิน username=กฟไกฟ, email=donateicloud@gmail.com', '::1', '2025-05-24 12:58:56'),
(10, 22, 'delete_admin', 'admin', 25, 'ลบแอดมิน username=กฟไกฟ, email=donateicloud@gmail.com', '::1', '2025-05-24 12:59:05'),
(11, 22, 'create_shop', 'shop', 26, 'สร้างร้านค้า ใจดีโฟน (NNF-1025)', '::1', '2025-05-24 13:07:26'),
(12, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-24 13:18:20'),
(13, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-24 13:18:51'),
(14, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-24 14:33:43'),
(15, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-24 14:42:36'),
(16, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-24 17:58:35'),
(17, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-24 17:59:08'),
(18, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 01:27:39'),
(19, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 01:32:21'),
(20, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 01:41:08'),
(21, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 02:09:26'),
(22, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 12:35:00'),
(23, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 14:31:01'),
(24, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 14:32:02'),
(25, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 14:52:30'),
(26, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 17:39:29'),
(27, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-25 17:44:50'),
(28, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '49.49.238.53', '2025-05-25 12:43:10'),
(29, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '49.49.238.53', '2025-05-25 12:43:39'),
(30, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '27.55.68.131', '2025-05-25 12:47:24'),
(31, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '124.120.205.164', '2025-05-25 12:48:55'),
(32, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '124.120.205.164', '2025-05-25 12:51:36'),
(33, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-26 08:45:25'),
(34, 22, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-26 08:51:55'),
(35, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '127.0.0.1', '2025-05-26 13:15:48'),
(36, 22, 'edit_admin', 'admin', 23, 'แก้ไขแอดมิน id=23, email=sale.upservice@gmail.com, is_active=0', '::1', '2025-05-26 13:50:38'),
(37, 22, 'edit_admin', 'admin', 23, 'แก้ไขแอดมิน id=23, email=sale.upservice@gmail.com, is_active=1', '::1', '2025-05-26 13:50:46'),
(38, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-26 14:41:26'),
(39, 26, 'login', 'system', NULL, 'ล็อกอินสำเร็จ', '::1', '2025-05-27 03:45:18');

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
  `contract_type` enum('monthly','floating_interest') NOT NULL,
  `period_months` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
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
  `commission_slip_path` varchar(255) DEFAULT NULL COMMENT 'ไฟล์สลิปคอมมิชชั่น'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contracts`
--

INSERT INTO `contracts` (`id`, `contract_no_shop`, `contract_no_approved`, `shop_id`, `customer_firstname`, `customer_lastname`, `customer_id_card`, `customer_moo`, `customer_phone`, `customer_line`, `customer_facebook`, `loan_amount`, `installment_amount`, `device_brand`, `device_model`, `device_capacity`, `device_color`, `device_imei`, `device_serial_no`, `contract_type`, `period_months`, `approval_status`, `approval_at`, `commission_status`, `commission_slip`, `commission_at`, `start_date`, `end_date`, `province_id`, `amphur_id`, `district_id`, `postal_code`, `house_number`, `moo`, `soi`, `other_address`, `branch_name`, `created_at`, `commission_amount`, `commission_transferred_at`, `commission_slip_path`) VALUES
(16, 'NN-83848D', NULL, 26, 'พงษ์พัฒน์', 'นิยมบล', '335987546521111', NULL, '085844777', 'utelegram', 'https://www.facebook.com/tonton.chanwit', 5000.00, 750.00, 'Apple', 'iPhone 11', '128', 'ดำ', '356812112974306', 'DX3DD2U2N7', 'floating_interest', 3, 'approved', '2025-05-26 17:48:19', 'commission_transferred', NULL, '2025-05-26 17:48:19', '2025-05-26 17:48:19', '2025-08-26', 10, 1010, 101001, '', '88/50', '8', 'นิมิมิตใหม่ 6', '', 'สมหวัง', '2025-05-26 17:48:19', 1500000.00, '2025-05-27 04:47:47', NULL),
(17, 'NN-A8568F', NULL, 26, 'ดารากร', 'ซ้ายดี', '35652541254785465', NULL, '0808168803', 'iiiisssss', 'https://www.facebook.com/tonton.chanwit', 10000.00, 1500.00, 'Apple', 'iPhone 16', '128', 'ฟ้า', '352051699869480', 'XJYYX90R4K', 'floating_interest', 6, 'approved', '2025-05-26 18:30:37', 'commission_pending', NULL, '2025-05-26 18:30:37', '2025-05-26 00:00:00', '2025-11-26', 25, 2501, 250113, '25000', '88/50', '8', 'สุวินทวงค์ 8', '', 'สมหวัง', '2025-05-26 18:30:37', NULL, NULL, NULL),
(18, 'NN-14539E', NULL, 26, 'สมชาย', 'ใจดี', '35652541254785465', NULL, '0808168803', 'utelegram', 'https://www.facebook.com/tonton.chanwit', 5000.00, 750.00, 'Apple', 'iPhone 11', '128', 'ฟ้า', '352515454654654', 'FGDFGDFGDF', 'floating_interest', 6, 'pending', '2025-05-26 19:39:51', 'commission_pending', NULL, '2025-05-26 19:39:51', '2025-05-26 00:00:00', '2025-11-26', 10, 1014, 101401, '', '88/50', '8', 'สุวินทวงค์ 8', '', 'สมหวัง', '2025-05-26 19:39:51', NULL, NULL, NULL),
(19, 'NN-9A2AB8', NULL, 26, 'นายรัชฏา', 'สากรเกตุ', '35652541254785465', NULL, '085844777', 'Utelecom', 'https://www.facebook.com/tonton.chanwit', 12000.00, 1800.00, 'Apple', 'iPhone 16 Pro Max', '128', 'ดำ', '356812112974306', 'XJYYX90R4K', 'floating_interest', 6, 'rejected', '2025-05-27 03:49:52', 'commission_cancelled', NULL, '2025-05-27 03:49:52', '2025-05-26 00:00:00', '2025-11-26', 18, 1803, 180302, '17120', '399/88', '8', 'วนาทิพย์', '', 'สมหวัง', '2025-05-27 03:49:52', NULL, NULL, NULL),
(20, 'NN-059B9E', NULL, 26, 'นางสาวใหม่สด', 'กลุพันธ์', '35652541254785465', NULL, '085844777', 'iiiisssss', 'https://www.facebook.com/tonton.chanwit', 5000.00, 750.00, 'Apple', 'iPhone 11 Pro', '128', 'ฟ้า', '352515454654654', 'FGDFGDFGDF', 'floating_interest', 6, 'pending', '2025-05-27 04:28:32', 'commission_pending', NULL, '2025-05-27 04:28:32', '2025-05-26 17:48:19', '2025-11-26', 26, 2602, 260202, '26130', '95/70', '8', 'สุวินทวงค์ 8', '', 'สมหวัง', '2025-05-27 04:28:32', NULL, NULL, NULL),
(21, 'NN-E950B8', NULL, 26, 'ทดสอบ', 'เวลา', '35652541254785465', NULL, '0808168803', 'utelegram', 'https://www.facebook.com/tonton.chanwit', 7000.00, 1050.00, 'Apple', 'iPhone 16 Pro', '256', 'ดำ', '352515454654654', 'DAW54DAWD1', 'floating_interest', 3, 'pending', '2025-05-27 05:57:15', 'commission_pending', NULL, '2025-05-27 05:57:15', '2025-05-27 05:56:00', '2025-08-27', 27, 2702, 270201, '27260', '88/50', '8', 'สุวินทวงค์ 8', '', 'สมหวัง', '2025-05-27 05:57:15', NULL, NULL, NULL);

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
(18, 16, 'signed_contract', 'uploads/evidence/16/1748256919_signed_contract_scrnli_atJ3zvXFdVv5r0.png', '2025-05-26 17:55:19', 26),
(19, 16, 'customer_photo', 'uploads/evidence/16/1748256919_customer_photo_494357652_579364978101005_7053065893791379512_n.jpg', '2025-05-26 17:55:19', 26),
(20, 16, 'device_photo', 'uploads/evidence/16/1748256919_device_photo_494828629_1216901513261311_5628891889941817508_n.jpg', '2025-05-26 17:55:19', 26),
(21, 16, 'imei_photo', 'uploads/evidence/16/1748256919_imei_photo_494828629_1216901513261311_5628891889941817508_n__1_.jpg', '2025-05-26 17:55:19', 26),
(22, 16, 'lock_screen_photo', 'uploads/evidence/16/1748256919_lock_screen_photo_494359625_1734606957493392_3734328647896483536_n.jpg', '2025-05-26 17:55:19', 26),
(23, 16, 'signed_contract', 'uploads/evidence/16/1748256954_signed_contract_scrnli_WYyhwI8ZBPrxah.png', '2025-05-26 17:55:54', 26),
(24, 17, 'signed_contract', 'uploads/evidence/17/1748259089_signed_contract_scrnli_rgcGr52NVLQeMP.png', '2025-05-26 18:31:29', 26),
(25, 17, 'customer_photo', 'uploads/evidence/17/1748259089_customer_photo_494357652_579364978101005_7053065893791379512_n.jpg', '2025-05-26 18:31:29', 26),
(26, 17, 'device_photo', 'uploads/evidence/17/1748259089_device_photo_494828629_1216901513261311_5628891889941817508_n__1_.jpg', '2025-05-26 18:31:29', 26),
(27, 17, 'imei_photo', 'uploads/evidence/17/1748259089_imei_photo_494828629_1216901513261311_5628891889941817508_n.jpg', '2025-05-26 18:31:29', 26),
(28, 17, 'lock_screen_photo', 'uploads/evidence/17/1748259089_lock_screen_photo_494359625_1734606957493392_3734328647896483536_n.jpg', '2025-05-26 18:31:29', 26),
(33, 19, 'signed_contract', 'uploads/evidence/19/1748293816_signed_contract_0_494357652_579364978101005_7053065893791379512_n.jpg', '2025-05-27 04:10:16', 26),
(34, 19, 'signed_contract', 'uploads/evidence/19/1748293816_signed_contract_1_494359625_1734606957493392_3734328647896483536_n.jpg', '2025-05-27 04:10:16', 26),
(35, 19, 'signed_contract', 'uploads/evidence/19/1748293816_signed_contract_2_494828629_1216901513261311_5628891889941817508_n.jpg', '2025-05-27 04:10:16', 26),
(36, 19, 'customer_photo', 'uploads/evidence/19/1748293849_customer_photo_scrnli_5VKO6T7e06akuk.png', '2025-05-27 04:10:49', 26),
(38, 19, 'imei_photo', 'uploads/evidence/19/1748293849_imei_photo_scrnli_uR1X8c4w14h1FY.png', '2025-05-27 04:10:49', 26),
(39, 19, 'lock_screen_photo', 'uploads/evidence/19/1748293849_lock_screen_photo_494359625_1734606957493392_3734328647896483536_n.jpg', '2025-05-27 04:10:49', 26),
(40, 21, 'device_photo', 'uploads/evidence/21/1748300271_device_photo_494357652_579364978101005_7053065893791379512_n.jpg', '2025-05-27 05:57:51', 26),
(41, 21, 'signed_contract', 'uploads/evidence/21/1748300283_signed_contract_0_494359625_1734606957493392_3734328647896483536_n.jpg', '2025-05-27 05:58:03', 26),
(42, 21, 'signed_contract', 'uploads/evidence/21/1748300283_signed_contract_1_494828629_1216901513261311_5628891889941817508_n.jpg', '2025-05-27 05:58:03', 26),
(43, 21, 'signed_contract', 'uploads/evidence/21/1748300283_signed_contract_2_scrnli_BzH07Xn8aSS1LL.png', '2025-05-27 05:58:03', 26);

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
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` char(32) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `created_at`) VALUES
(1, 'admin@demo.com', '39a77432c8f647e4288a3723df59ef31', '2025-05-22 08:14:50'),
(2, 'admin@demo.com', '49c660d52d392a0140a280f5208c761e', '2025-05-22 08:15:50'),
(3, 'admin@demo.com', 'ac9ff159219f3d91f8b2cf9a48d7edc9', '2025-05-22 08:18:02'),
(4, 'admin@demo.com', 'a2e9e059a24b3bb6c3604f1897364ddf', '2025-05-22 08:19:02'),
(5, 'admin@demo.com', '9f88c3134b515e71bdeae61ecd5f1033', '2025-05-22 08:23:51'),
(6, 'admin@demo.com', 'e35e5a93d0380b7e2e12fdc1f045b36f', '2025-05-22 08:33:23'),
(7, 'admin@demo.com', '14df0a3845cc47b19aa7cd9c66115eb1', '2025-05-22 08:36:09'),
(8, 'admin@demo.com', '76db0d2fe8c97ed7e84944ed28bd58e8', '2025-05-22 08:36:18'),
(9, 'admin@demo.com', '292955b29104612898069bc179ab32f8', '2025-05-22 08:36:21'),
(10, 'admin@demo.com', 'fb0973da4da6fe81514b874f712034ae', '2025-05-22 08:41:08'),
(11, 'admin@demo.com', '444edb1756dddac4c6faa1aa9949afc0', '2025-05-22 08:43:03'),
(12, 'admin@demo.com', 'c98bb938c9fbeb26ff6488b2cecbbf16', '2025-05-22 08:47:34'),
(13, 'admin@demo.com', '3b3eb117aeece76cd10658ec75a437ea', '2025-05-22 08:48:53'),
(14, 'admin@demo.com', '83023c144fdd12362237a61ab5196a22', '2025-05-22 09:08:48'),
(15, 'admin@demo.com', '088d65e4d426f80d1650ab290946721f', '2025-05-22 13:36:11');

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
  `slip_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `contract_id`, `pay_no`, `due_date`, `amount`, `paid_date`, `amount_due`, `amount_paid`, `slip_path`, `status`, `created_by`, `created_at`) VALUES
(51, 16, 1, '2025-06-10', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 17:48:19'),
(52, 16, 2, '2025-06-25', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 17:48:19'),
(53, 16, 3, '2025-07-10', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 17:48:19'),
(54, 16, 4, '2025-07-25', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 17:48:19'),
(55, 16, 5, '2025-08-09', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 17:48:19'),
(56, 16, 6, '2025-08-24', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 17:48:19'),
(57, 17, 1, '2025-06-10', 0.00, NULL, 1500.00, NULL, NULL, 'pending', NULL, '2025-05-26 18:30:37'),
(58, 17, 2, '2025-06-25', 0.00, NULL, 1500.00, NULL, NULL, 'pending', NULL, '2025-05-26 18:30:37'),
(59, 17, 3, '2025-07-10', 0.00, NULL, 1500.00, NULL, NULL, 'pending', NULL, '2025-05-26 18:30:37'),
(60, 17, 4, '2025-07-25', 0.00, NULL, 1500.00, NULL, NULL, 'pending', NULL, '2025-05-26 18:30:37'),
(61, 17, 5, '2025-08-09', 0.00, NULL, 1500.00, NULL, NULL, 'pending', NULL, '2025-05-26 18:30:37'),
(62, 17, 6, '2025-08-24', 0.00, NULL, 1500.00, NULL, NULL, 'pending', NULL, '2025-05-26 18:30:37'),
(63, 18, 1, '2025-06-10', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(64, 18, 2, '2025-06-25', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(65, 18, 3, '2025-07-10', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(66, 18, 4, '2025-07-25', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(67, 18, 5, '2025-08-09', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(68, 18, 6, '2025-08-24', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(69, 18, 7, '2025-09-08', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(70, 18, 8, '2025-09-23', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(71, 18, 9, '2025-10-08', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(72, 18, 10, '2025-10-23', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(73, 18, 11, '2025-11-07', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(74, 18, 12, '2025-11-22', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-26 19:39:51'),
(75, 19, 1, '2025-06-10', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(76, 19, 2, '2025-06-25', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(77, 19, 3, '2025-07-10', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(78, 19, 4, '2025-07-25', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(79, 19, 5, '2025-08-09', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(80, 19, 6, '2025-08-24', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(81, 19, 7, '2025-09-08', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(82, 19, 8, '2025-09-23', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(83, 19, 9, '2025-10-08', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(84, 19, 10, '2025-10-23', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(85, 19, 11, '2025-11-07', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(86, 19, 12, '2025-11-22', 0.00, NULL, 1800.00, NULL, NULL, 'pending', NULL, '2025-05-27 03:49:52'),
(87, 20, 1, '2025-06-10', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(88, 20, 2, '2025-06-25', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(89, 20, 3, '2025-07-10', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(90, 20, 4, '2025-07-25', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(91, 20, 5, '2025-08-09', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(92, 20, 6, '2025-08-24', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(93, 20, 7, '2025-09-08', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(94, 20, 8, '2025-09-23', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(95, 20, 9, '2025-10-08', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(96, 20, 10, '2025-10-23', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(97, 20, 11, '2025-11-07', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(98, 20, 12, '2025-11-22', 0.00, NULL, 750.00, NULL, NULL, 'pending', NULL, '2025-05-27 04:28:32'),
(99, 21, 1, '2025-06-11', 0.00, NULL, 1050.00, NULL, NULL, 'pending', NULL, '2025-05-27 05:57:15'),
(100, 21, 2, '2025-06-26', 0.00, NULL, 1050.00, NULL, NULL, 'pending', NULL, '2025-05-27 05:57:15'),
(101, 21, 3, '2025-07-11', 0.00, NULL, 1050.00, NULL, NULL, 'pending', NULL, '2025-05-27 05:57:15'),
(102, 21, 4, '2025-07-26', 0.00, NULL, 1050.00, NULL, NULL, 'pending', NULL, '2025-05-27 05:57:15'),
(103, 21, 5, '2025-08-10', 0.00, NULL, 1050.00, NULL, NULL, 'pending', NULL, '2025-05-27 05:57:15'),
(104, 21, 6, '2025-08-25', 0.00, NULL, 1050.00, NULL, NULL, 'pending', NULL, '2025-05-27 05:57:15');

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
(22, 'Super Admin', NULL, NULL, NULL, NULL, NULL, 'admin@nano-friend.com', 'kubu99', '$2y$10$WHso8d0p/Dl55RQ29rAc8uc2lD2/mBJRFivTzqNbEW11w/NosrIsS', 'superadmin', 1, NULL, NULL, '2025-05-24 09:55:38', 'dark', NULL),
(23, NULL, NULL, NULL, NULL, NULL, NULL, 'sale.upservice@gmail.com', 'sale.upservice', '$2y$10$NCdJ492nLSKbInMz8KBU9uNUqKF4FH9ymITft1JsQWUULuInAN29O', 'admin', 1, NULL, NULL, '2025-05-24 10:04:51', 'dark', '[\"view_users\",\"manage_contracts\",\"manage_shops\"]'),
(24, 'อัปเดตโฟน สาขา 2', NULL, NULL, NULL, NULL, NULL, 'diyonbox@gmail.com', 'diyonbox', '$2y$10$0030TRGwbTz0VlcmPOmznODfxcdKzKvNtAcotvJFw.xKZXMRpDrcq', 'shop', 1, 14.2049261, 100.7684815, '2025-05-24 10:58:17', 'dark', NULL),
(26, 'ใจดีโฟน', '88/50 เดอะวิลล์, นิมิตใหม่', '0808168803', '', 'https://creditgsm.com', 'utelecom', 'romihave@gmail.com', 'test2', '$2y$10$MOwggKjdaav5v4BagYU2.uzMXDfwqgrevg4nHCuc6eT9JdtSXAoxO', 'shop', 1, 13.8140899, 100.7281736, '2025-05-24 13:07:26', 'light', NULL);

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
-- Indexes for table `contract_templates`
--
ALTER TABLE `contract_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `device_models`
--
ALTER TABLE `device_models`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guarantors`
--
ALTER TABLE `guarantors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`);

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
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token_hash` (`token_hash`),
  ADD KEY `user_id` (`user_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `contract_evidence`
--
ALTER TABLE `contract_evidence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `contract_templates`
--
ALTER TABLE `contract_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_models`
--
ALTER TABLE `device_models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `guarantors`
--
ALTER TABLE `guarantors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
-- Constraints for table `guarantors`
--
ALTER TABLE `guarantors`
  ADD CONSTRAINT `guarantors_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE;

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
