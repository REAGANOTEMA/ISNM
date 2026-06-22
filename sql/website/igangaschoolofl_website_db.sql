-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2026 at 10:17 PM
-- Server version: 8.0.45
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `igangaschoolofl_website_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AddColIfMissing` (IN `p_schema` VARCHAR(255), IN `p_table` VARCHAR(255), IN `p_col` VARCHAR(255), IN `p_def` TEXT)   BEGIN
    DECLARE cnt INT DEFAULT 0;
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = p_schema AND TABLE_NAME = p_table AND COLUMN_NAME = p_col;
    IF cnt = 0 THEN
        SET @s = CONCAT('ALTER TABLE `', p_schema, '`.`', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `notified` tinyint(1) DEFAULT '0',
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_sick_records`
--

CREATE TABLE `daily_sick_records` (
  `id` int NOT NULL,
  `record_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `student_id` int NOT NULL,
  `student_name` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year_of_study` int DEFAULT NULL,
  `sickness_id` int DEFAULT NULL,
  `sickness_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `temperature` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `blood_pressure` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `symptoms` text COLLATE utf8mb4_general_ci,
  `diagnosis` text COLLATE utf8mb4_general_ci,
  `treatment_given` text COLLATE utf8mb4_general_ci,
  `medicines_prescribed` text COLLATE utf8mb4_general_ci,
  `severity` enum('Mild','Moderate','Severe','Critical') COLLATE utf8mb4_general_ci DEFAULT 'Mild',
  `status` enum('Treated','Referred','Admitted','Discharged','Follow-up','Critical') COLLATE utf8mb4_general_ci DEFAULT 'Treated',
  `referred_to` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attended_by` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int NOT NULL,
  `donor_name` varchar(200) NOT NULL,
  `donor_email` varchar(255) NOT NULL,
  `donor_phone` varchar(50) NOT NULL,
  `donor_address` varchar(500) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_provider` varchar(50) DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `purpose` varchar(200) DEFAULT 'General Donation',
  `notes` text,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `notified` tinyint(1) DEFAULT '0',
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock`
--

CREATE TABLE `medicine_stock` (
  `id` int NOT NULL,
  `medicine_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `medicine_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `generic_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` enum('Antibiotic','Painkiller','Anti-inflammatory','Antimalarial','Antiviral','Antifungal','Vitamins','First Aid','Allergy','Digestive','Respiratory','Dermatological','Ophthalmic','Other') COLLATE utf8mb4_general_ci DEFAULT 'Other',
  `dosage_form` enum('Tablet','Capsule','Syrup','Injection','Cream','Ointment','Drops','Inhaler','Suppository','Powder','Solution','Other') COLLATE utf8mb4_general_ci DEFAULT 'Tablet',
  `strength` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `manufacturer` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `supplier` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity_in_stock` int NOT NULL DEFAULT '0',
  `unit` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pcs',
  `reorder_level` int DEFAULT '10',
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'UGX',
  `batch_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `storage_location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requires_prescription` tinyint(1) DEFAULT '0',
  `instructions` text COLLATE utf8mb4_general_ci,
  `side_effects` text COLLATE utf8mb4_general_ci,
  `status` enum('In Stock','Low Stock','Out of Stock','Expired','Discontinued') COLLATE utf8mb4_general_ci DEFAULT 'In Stock',
  `last_restocked` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_stock`
--

INSERT INTO `medicine_stock` (`id`, `medicine_code`, `medicine_name`, `generic_name`, `category`, `dosage_form`, `strength`, `manufacturer`, `supplier`, `quantity_in_stock`, `unit`, `reorder_level`, `unit_cost`, `selling_price`, `currency`, `batch_number`, `expiry_date`, `storage_location`, `requires_prescription`, `instructions`, `side_effects`, `status`, `last_restocked`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PARA001', 'Paracetamol', 'Acetaminophen', 'Painkiller', 'Tablet', '500mg', NULL, NULL, 200, 'tablets', 50, 50.00, NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A1', 0, '1-2 tablets every 4-6 hours as needed for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(2, 'IBU001', 'Ibuprofen', 'Ibuprofen', 'Anti-inflammatory', 'Tablet', '400mg', NULL, NULL, 150, 'tablets', 30, 100.00, NULL, 'UGX', NULL, '2027-10-31', 'Cabinet A1', 0, '1 tablet 3 times daily after meals', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(3, 'AMOX001', 'Amoxicillin', 'Amoxicillin', 'Antibiotic', 'Capsule', '500mg', NULL, NULL, 100, 'capsules', 20, 200.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet B1', 1, '1 capsule 3 times daily for 7 days', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(4, 'CTM001', 'Chlorpheniramine', 'Chlorpheniramine Maleate', 'Allergy', 'Tablet', '4mg', NULL, NULL, 100, 'tablets', 20, 50.00, NULL, 'UGX', NULL, '2027-11-30', 'Cabinet A2', 0, '1 tablet every 4-6 hours for allergies', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(5, 'ORS001', 'Oral Rehydration Salts', 'ORS', 'Other', 'Powder', '20.5g/sachet', NULL, NULL, 100, 'sachets', 30, 500.00, NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', 0, 'Dissolve 1 sachet in 1L water, drink after each loose stool', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(6, 'ART001', 'Artemether/Lumefantrine', 'Coartem', 'Antimalarial', 'Tablet', '20/120mg', NULL, NULL, 60, 'tablets', 20, 1500.00, NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B2', 1, '4 tablets twice daily for 3 days', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(7, 'VITC001', 'Vitamin C', 'Ascorbic Acid', 'Vitamins', 'Tablet', '500mg', NULL, NULL, 300, 'tablets', 50, 30.00, NULL, 'UGX', NULL, '2028-12-31', 'Cabinet C1', 0, '1 tablet daily for immune support', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(8, 'MET001', 'Metered Dose Inhaler', 'Salbutamol', 'Respiratory', 'Inhaler', '100mcg/dose', NULL, NULL, 10, 'inhalers', 3, 15000.00, NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A3', 1, '1-2 puffs as needed for asthma symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(9, 'ANT001', 'Antacid', 'Aluminum/Magnesium Hydroxide', 'Digestive', 'Tablet', '500mg', NULL, NULL, 200, 'tablets', 40, 100.00, NULL, 'UGX', NULL, '2027-11-30', 'Cabinet C1', 0, '1-2 tablets after meals or when symptomatic', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(10, 'HYD001', 'Hydrocortisone Cream', 'Hydrocortisone', 'Dermatological', 'Cream', '1%', NULL, NULL, 20, 'tubes', 5, 5000.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', 0, 'Apply thin layer to affected area 2-3 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(11, 'DIA001', 'Diazepam', 'Diazepam', 'Painkiller', 'Tablet', '5mg', NULL, NULL, 30, 'tablets', 10, 200.00, NULL, 'UGX', NULL, '2026-12-31', 'Cabinet B2', 1, '1 tablet at bedtime for anxiety or muscle spasms', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(12, 'BAN001', 'Bandages', 'Cotton Bandage', 'First Aid', 'Other', '4 inches x 5 meters', NULL, NULL, 50, 'rolls', 10, 1500.00, NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', 0, 'For wound dressing and injury management', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(13, 'GAU001', 'Gauze Swabs', 'Sterile Gauze', 'First Aid', 'Other', '10x10cm', NULL, NULL, 200, 'packs', 50, 800.00, NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', 0, 'Sterile swabs for wound cleaning and dressing', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(14, 'GLU001', 'Glucose Powder', 'Dextrose', 'Vitamins', 'Powder', '500g', NULL, NULL, 10, 'packs', 3, 5000.00, NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', 0, 'Mix 2 tablespoons in water for energy', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(15, 'ALC001', 'Alcohol Swabs', 'Isopropyl Alcohol', 'First Aid', 'Solution', '70%', NULL, NULL, 300, 'swabs', 50, 100.00, NULL, 'UGX', NULL, '2028-12-31', 'Shelf E1', 0, 'Use for cleaning skin before injections', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(16, 'CLO001', 'Chloroquine', 'Chloroquine Phosphate', 'Antimalarial', 'Tablet', '250mg', NULL, NULL, 50, 'tablets', 15, 300.00, NULL, 'UGX', NULL, '2027-05-31', 'Cabinet B2', 1, 'As prescribed for malaria treatment', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(17, 'MEF001', 'Mefenamic Acid', 'Mefenamic Acid', 'Painkiller', 'Capsule', '500mg', NULL, NULL, 80, 'capsules', 20, 200.00, NULL, 'UGX', NULL, '2027-07-31', 'Cabinet A1', 0, '1 capsule 3 times daily for pain and inflammation', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(18, 'METR001', 'Metronidazole', 'Metronidazole', 'Antibiotic', 'Tablet', '400mg', NULL, NULL, 100, 'tablets', 20, 150.00, NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B1', 1, '1 tablet 3 times daily for 5-7 days', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(19, 'DIC001', 'Diclofenac Gel', 'Diclofenac Diethylamine', 'Anti-inflammatory', 'Cream', '1%', NULL, NULL, 15, 'tubes', 5, 7000.00, NULL, 'UGX', NULL, '2027-10-31', 'Cabinet D1', 0, 'Apply to affected area 3-4 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(20, 'CET001', 'Cetirizine', 'Cetirizine Hydrochloride', 'Allergy', 'Tablet', '10mg', NULL, NULL, 100, 'tablets', 20, 100.00, NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A2', 0, '1 tablet daily for allergy symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(21, 'ASP001', 'Aspirin', 'Acetylsalicylic Acid', 'Painkiller', 'Tablet', '300mg', NULL, NULL, 100, 'tablets', 25, 50.00, NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A1', 0, '1-2 tablets every 4-6 hours for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(22, 'ZIN001', 'Zinc Tablets', 'Zinc Sulfate', 'Vitamins', 'Tablet', '20mg', NULL, NULL, 150, 'tablets', 30, 100.00, NULL, 'UGX', NULL, '2028-09-30', 'Cabinet C1', 0, '1 tablet daily for immune support and wound healing', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(23, 'CLOT001', 'Clotrimazole Cream', 'Clotrimazole', 'Antifungal', 'Cream', '1%', NULL, NULL, 15, 'tubes', 5, 4000.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', 0, 'Apply to affected area twice daily for 2 weeks', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(24, 'EYE001', 'Eye Drops', 'Chloramphenicol', 'Other', 'Drops', '0.5%', NULL, NULL, 20, 'bottles', 5, 5000.00, NULL, 'UGX', NULL, '2027-04-30', 'Cabinet A3', 1, '1-2 drops in affected eye every 2-4 hours', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(25, 'BET001', 'Betadine Solution', 'Povidone-Iodine', 'First Aid', 'Solution', '10%', NULL, NULL, 10, 'bottles', 3, 8000.00, NULL, 'UGX', NULL, '2028-03-31', 'Shelf E1', 0, 'Apply to wounds for disinfection', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock_transactions`
--

CREATE TABLE `medicine_stock_transactions` (
  `id` int NOT NULL,
  `transaction_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `medicine_id` int NOT NULL,
  `transaction_type` enum('Purchase','Issue','Return','Adjustment','Damage','Expired') COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'UGX',
  `reference` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `issued_to` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_general_ci,
  `performed_by` int DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext,
  `excerpt` text,
  `featured_image` varchar(500) DEFAULT NULL,
  `author_id` int DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_role` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `recipient_type` enum('student','staff','all') NOT NULL,
  `recipient_id` int DEFAULT NULL,
  `channel` enum('sms','email','both') DEFAULT 'both',
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('Pending','Sent','Failed') DEFAULT 'Pending',
  `sent_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `id` int NOT NULL,
  `notification_id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'FK to staffs_db.staff.id or students_db.students.id',
  `user_type` enum('staff','student') NOT NULL DEFAULT 'staff',
  `read_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `content` longtext NOT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text,
  `meta_keywords` varchar(500) DEFAULT NULL,
  `status` enum('Published','Draft','Archived') DEFAULT 'Draft',
  `featured_image` varchar(500) DEFAULT NULL,
  `page_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portal_messages`
--

CREATE TABLE `portal_messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_id` int DEFAULT NULL,
  `recipient_type` enum('individual','group','all') DEFAULT 'individual',
  `subject` varchar(255) DEFAULT NULL,
  `message` text,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `user_type` enum('staff','student') COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `p256dh_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'browser',
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sickness_directory`
--

CREATE TABLE `sickness_directory` (
  `id` int NOT NULL,
  `sickness_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `sickness_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` enum('Infectious','Non-Infectious','Chronic','Injury','Mental Health','Nutritional','Other') COLLATE utf8mb4_general_ci DEFAULT 'Other',
  `common_symptoms` text COLLATE utf8mb4_general_ci,
  `description` text COLLATE utf8mb4_general_ci,
  `is_contagious` tinyint(1) DEFAULT '0',
  `typical_treatment` text COLLATE utf8mb4_general_ci,
  `status` enum('Active','Inactive') COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sickness_directory`
--

INSERT INTO `sickness_directory` (`id`, `sickness_code`, `sickness_name`, `category`, `common_symptoms`, `description`, `is_contagious`, `typical_treatment`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'MLR', 'Malaria', 'Infectious', 'Fever, chills, headache, sweating, fatigue', 'Mosquito-borne parasitic infection common in tropical regions', 0, 'Artemisinin-based combination therapy, antimalarials', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(2, 'TYP', 'Typhoid', 'Infectious', 'Prolonged fever, abdominal pain, headache, constipation or diarrhea', 'Bacterial infection spread through contaminated food/water', 1, 'Antibiotics (ciprofloxacin, azithromycin), hydration', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(3, 'FLU', 'Influenza', 'Infectious', 'Fever, cough, sore throat, body aches, fatigue', 'Viral respiratory infection spread through droplets', 1, 'Rest, fluids, antipyretics, antivirals if severe', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(4, 'COLD', 'Common Cold', 'Infectious', 'Runny nose, sneezing, sore throat, cough, mild fever', 'Viral upper respiratory tract infection', 1, 'Rest, antihistamines, decongestants, vitamin C', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(5, 'URTI', 'Upper Respiratory Tract Infection', 'Infectious', 'Cough, sore throat, nasal congestion, fever', 'Bacterial or viral infection of upper airways', 1, 'Antibiotics if bacterial, rest, fluids', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(6, 'HDCH', 'Headache/Tension Headache', 'Non-Infectious', 'Head pain, pressure around forehead, neck tension', 'Common tension-type headache from stress or fatigue', 0, 'Rest, analgesics (paracetamol, ibuprofen)', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(7, 'GSTR', 'Gastritis', 'Non-Infectious', 'Abdominal pain, nausea, bloating, indigestion', 'Inflammation of stomach lining from diet, stress, or infection', 0, 'Antacids, dietary changes, proton pump inhibitors', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(8, 'DIAR', 'Diarrhea', 'Infectious', 'Loose watery stools, abdominal cramps, dehydration', 'Common infection from contaminated food/water or viruses', 1, 'ORS, hydration, antidiarrheals, antibiotics if bacterial', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(9, 'ALLG', 'Allergic Reaction', 'Non-Infectious', 'Rash, itching, sneezing, watery eyes, swelling', 'Immune response to allergens (food, dust, pollen, drugs)', 0, 'Antihistamines, corticosteroids, avoid triggers', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(10, 'INJR', 'Injury/Accident', 'Injury', 'Pain, swelling, bruising, bleeding, limited mobility', 'Physical trauma from falls, sports, or accidents', 0, 'First aid, rest, ice, compression, elevation, analgesics', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(11, 'ANEM', 'Anemia', 'Nutritional', 'Fatigue, weakness, pale skin, shortness of breath, dizziness', 'Low red blood cell count from iron deficiency or other causes', 0, 'Iron supplements, dietary changes, B12 if needed', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(12, 'MALN', 'Malnutrition', 'Nutritional', 'Weight loss, fatigue, poor growth, weakened immunity', 'Inadequate nutrient intake affecting overall health', 0, 'Nutritional supplementation, diet counseling', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(13, 'CONS', 'Constipation', 'Non-Infectious', 'Infrequent bowel movements, straining, hard stools', 'Common digestive issue from diet or lifestyle factors', 0, 'Increased fiber intake, hydration, laxatives if needed', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(14, 'SORE', 'Sore Throat', 'Infectious', 'Pain or scratchiness in throat, difficulty swallowing', 'Viral or bacterial throat infection', 1, 'Warm salt water gargle, lozenges, antibiotics if strep', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(15, 'EYEI', 'Eye Infection', 'Infectious', 'Redness, itching, discharge, swollen eyelids', 'Bacterial or viral conjunctivitis', 1, 'Antibiotic or antiviral eye drops, hygiene', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(16, 'SKIN', 'Skin Infection/Rash', 'Infectious', 'Redness, itching, bumps, blisters, peeling', 'Fungal, bacterial, or viral skin infection', 1, 'Topical or oral antibiotics/antifungals, hygiene', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(17, 'FATG', 'Fatigue/General Malaise', 'Non-Infectious', 'Tiredness, low energy, reduced motivation', 'General feeling of being unwell without specific diagnosis', 0, 'Rest, nutrition, hydration, stress management', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(18, 'MSTR', 'Menstrual Cramps', 'Non-Infectious', 'Lower abdominal pain, back pain, nausea during menstruation', 'Painful menstrual periods common in young women', 0, 'Analgesics, heat therapy, rest, NSAIDs', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(19, 'ANXT', 'Anxiety/Stress', 'Mental Health', 'Worry, restlessness, rapid heartbeat, difficulty concentrating', 'Mental health condition common among students under academic pressure', 0, 'Counseling, stress management, relaxation techniques', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(20, 'BACK', 'Back Pain', 'Non-Infectious', 'Lower or upper back pain, stiffness, muscle tension', 'Musculoskeletal pain from poor posture, heavy lifting, or strain', 0, 'Rest, analgesics, physiotherapy, posture correction', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(21, 'THRP', 'Throat Infection/Pharyngitis', 'Infectious', 'Sore throat, red tonsils, swollen lymph nodes, fever', 'Inflammation of the pharynx from viral or bacterial infection', 1, 'Antibiotics if bacterial, rest, warm fluids', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(22, 'TOOT', 'Toothache', 'Non-Infectious', 'Tooth pain, sensitivity, swelling around tooth', 'Dental pain from cavities, infection, or impaction', 0, 'Analgesics, dental referral, antibiotics if infected', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(23, 'URIN', 'Urinary Tract Infection', 'Infectious', 'Painful urination, frequent urination, lower abdominal pain', 'Bacterial infection of the urinary tract', 0, 'Antibiotics, increased fluid intake, cranberry', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(24, 'ACNE', 'Acne/Skin Breakout', 'Non-Infectious', 'Pimples, blackheads, whiteheads, inflamed skin', 'Common skin condition from hormonal changes and stress', 0, 'Topical treatments, hygiene, dietary changes', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46'),
(25, 'FUNG', 'Fungal Infection', 'Infectious', 'Itching, redness, peeling skin, rash with defined edges', 'Fungal skin infection common in tropical climates', 1, 'Antifungal creams or oral medication, keep area dry', 'Active', NULL, '2026-06-20 08:42:46', '2026-06-20 08:42:46');

-- --------------------------------------------------------

--
-- Table structure for table `student_applications`
--

CREATE TABLE `student_applications` (
  `id` int NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date NOT NULL,
  `nationality` varchar(100) DEFAULT 'Ugandan',
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text,
  `program_applied` varchar(255) NOT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `uce_results` varchar(255) DEFAULT NULL,
  `uace_results` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Shortlisted','Admitted','Rejected','Withdrawn') DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_sick_leave`
--

CREATE TABLE `student_sick_leave` (
  `id` int NOT NULL,
  `leave_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `student_id` int NOT NULL,
  `student_name` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year_of_study` int DEFAULT NULL,
  `sickness_id` int DEFAULT NULL,
  `sickness_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `diagnosis` text COLLATE utf8mb4_general_ci,
  `leave_from` date NOT NULL,
  `leave_to` date NOT NULL,
  `total_days` int GENERATED ALWAYS AS (((to_days(`leave_to`) - to_days(`leave_from`)) + 1)) STORED,
  `leave_type` enum('Medical','Sick','Maternity','Injury','Quarantine','Other') COLLATE utf8mb4_general_ci DEFAULT 'Sick',
  `recommended_by` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `recommender_title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approved_by` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Expired','Extended') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `extended_to` date DEFAULT NULL,
  `extension_reason` text COLLATE utf8mb4_general_ci,
  `doctor_notes` text COLLATE utf8mb4_general_ci,
  `bed_rest_required` tinyint(1) DEFAULT '1',
  `parent_guardian_notified` tinyint(1) DEFAULT '0',
  `matron_notified` tinyint(1) DEFAULT '0',
  `class_teacher_notified` tinyint(1) DEFAULT '0',
  `documents_submitted` tinyint(1) DEFAULT '0',
  `documents_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `return_date_actual` date DEFAULT NULL,
  `return_notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_applications`
--

CREATE TABLE `volunteer_applications` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `profession` varchar(100) NOT NULL,
  `experience` int NOT NULL DEFAULT '0',
  `opportunity` varchar(100) NOT NULL,
  `availability` varchar(50) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `skills` text NOT NULL,
  `motivation` text NOT NULL,
  `comments` text,
  `status` enum('pending','reviewed','contacted','accepted','declined') DEFAULT 'pending',
  `notified` tinyint(1) DEFAULT '0',
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_cs_status` (`status`),
  ADD KEY `idx_cs_date` (`created_at`);

--
-- Indexes for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_number` (`record_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `sickness_id` (`sickness_id`),
  ADD KEY `visit_date` (`visit_date`),
  ADD KEY `status` (`status`),
  ADD KEY `severity` (`severity`),
  ADD KEY `student_name` (`student_name`),
  ADD KEY `program` (`program`),
  ADD KEY `dsr_student_date` (`student_id`,`visit_date`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medicine_code` (`medicine_code`),
  ADD KEY `medicine_name` (`medicine_name`),
  ADD KEY `category` (`category`),
  ADD KEY `expiry_date` (`expiry_date`),
  ADD KEY `status` (`status`),
  ADD KEY `supplier` (`supplier`),
  ADD KEY `ms_expiry_status` (`expiry_date`,`status`);

--
-- Indexes for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `transaction_type` (`transaction_type`),
  ADD KEY `transaction_date` (`transaction_date`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `mst_medicine_date` (`medicine_id`,`transaction_date`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_news_status_date` (`status`,`created_at`),
  ADD KEY `idx_news_date` (`created_at`),
  ADD KEY `idx_news_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_notif_user` (`notification_id`,`user_id`,`user_type`),
  ADD KEY `idx_user` (`user_id`,`user_type`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_page_order` (`page_order`);

--
-- Indexes for table `portal_messages`
--
ALTER TABLE `portal_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ps_user` (`user_id`,`user_type`),
  ADD KEY `idx_ps_active` (`is_active`);

--
-- Indexes for table `sickness_directory`
--
ALTER TABLE `sickness_directory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sickness_code` (`sickness_code`),
  ADD KEY `sickness_name` (`sickness_name`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `student_applications`
--
ALTER TABLE `student_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `idx_sa_status` (`status`),
  ADD KEY `idx_sa_date` (`submitted_at`);

--
-- Indexes for table `student_sick_leave`
--
ALTER TABLE `student_sick_leave`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_number` (`leave_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `sickness_id` (`sickness_id`),
  ADD KEY `leave_from` (`leave_from`),
  ADD KEY `leave_to` (`leave_to`),
  ADD KEY `status` (`status`),
  ADD KEY `student_name` (`student_name`),
  ADD KEY `program` (`program`),
  ADD KEY `ssl_student_status` (`student_id`,`status`);

--
-- Indexes for table `volunteer_applications`
--
ALTER TABLE `volunteer_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portal_messages`
--
ALTER TABLE `portal_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sickness_directory`
--
ALTER TABLE `sickness_directory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `student_applications`
--
ALTER TABLE `student_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_sick_leave`
--
ALTER TABLE `student_sick_leave`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `volunteer_applications`
--
ALTER TABLE `volunteer_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  ADD CONSTRAINT `daily_sick_records_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  ADD CONSTRAINT `medicine_stock_transactions_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicine_stock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD CONSTRAINT `notification_reads_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_sick_leave`
--
ALTER TABLE `student_sick_leave`
  ADD CONSTRAINT `student_sick_leave_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
