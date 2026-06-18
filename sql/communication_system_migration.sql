-- ==============================================================================
-- ISNM (Iganga School of Nursing & Midwifery) ERP System
-- SQL Migration: Staff Communication System
-- Database: igangaschoolofl_staffs_db
-- ==============================================================================
-- This migration adds staff-to-department communication tables and seeds
-- department routing emails from the existing staff_departments table.
-- Run this ONCE after deploying the communication feature.
-- ==============================================================================

-- 1. Communication Channels (department routing emails)
CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`communication_channels` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `department_code` VARCHAR(20) NOT NULL,
    `department_name` VARCHAR(255) NOT NULL,
    `routing_email` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_department_code` (`department_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Staff Communications (sent messages log)
CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`staff_communications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `sender_id` INT(11) NOT NULL,
    `sender_email` VARCHAR(255) NOT NULL,
    `sender_name` VARCHAR(255) NOT NULL,
    `recipient_type` ENUM('department','all_staff') NOT NULL DEFAULT 'department',
    `recipient_id` VARCHAR(50) DEFAULT NULL,
    `recipient_name` VARCHAR(255) DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message_body` TEXT NOT NULL,
    `priority` ENUM('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
    `email_status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sender_id` (`sender_id`),
    KEY `idx_recipient_type` (`recipient_type`),
    KEY `idx_recipient_id` (`recipient_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Seed department routing emails from existing staff_departments table
--    Uses contact_email if available, otherwise auto-generates from department_code.
INSERT IGNORE INTO `igangaschoolofl_staffs_db`.`communication_channels`
    (`department_code`, `department_name`, `routing_email`)
SELECT
    `department_code`,
    `department_name`,
    COALESCE(
        NULLIF(TRIM(`contact_email`), ''),
        CONCAT(LOWER(`department_code`), '@igangaschoolofnursingandmidwifery.ac.ug')
    )
FROM `igangaschoolofl_staffs_db`.`staff_departments`;

-- 4. Verify seeding
SELECT COUNT(*) AS channels_seeded FROM `igangaschoolofl_staffs_db`.`communication_channels`;
