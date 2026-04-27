-- Quick database setup for ISNM School Management System
-- Creates essential tables for login system

USE isnm_db;

-- Create users table for staff authentication
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default-avatar.png',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `login_attempts` int(11) DEFAULT 0,
  `account_locked` tinyint(1) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample staff users with hashed passwords
INSERT IGNORE INTO `users` (`user_id`, `username`, `first_name`, `last_name`, `email`, `password`, `role`) VALUES
('DG001', 'john.mugisha', 'John', 'Mugisha', 'john.mugisha@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director General'),
('CEO001', 'sarah.nakato', 'Sarah', 'Nakato', 'sarah.nakato@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chief Executive Officer'),
('SP001', 'peter.lutaaya', 'Peter', 'Lutaaya', 'peter.lutaaya@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Principal'),
('SEC001', 'joy.nabwire', 'Joy', 'Nabwire', 'joy.nabwire@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Secretary'),
('AR001', 'henry.mugisha', 'Henry', 'Mugisha', 'henry.mugisha@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Academic Registrar'),
('BUR001', 'patience.nabasumba', 'Patience', 'Nabasumba', 'patience.nabasumba@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Bursar'),
('HR001', 'robert.ssewanyana', 'Robert', 'Ssewanyana', 'robert.ssewanyana@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR Manager'),
('DA001', 'michael.mukasa', 'Michael', 'Mukasa', 'michael.mukasa@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director Academics'),
('DI001', 'david.ssekandi', 'David', 'Ssekandi', 'david.ssekandi@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director ICT'),
('DF001', 'grace.namulinda', 'Grace', 'Namulinda', 'grace.namulinda@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director Finance');

-- Note: The password hash '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' corresponds to 'password'
-- All staff accounts have the default password: password
