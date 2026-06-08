-- ============================================
-- ISNM Computer Lab Management System
-- ICT Department Database Tables
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS igangaschoolofl_ict;
USE igangaschoolofl_ict;

-- ============================================
-- 1. Lab Computers Table
-- ============================================
CREATE TABLE IF NOT EXISTS lab_computers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    computer_id VARCHAR(50) UNIQUE NOT NULL,
    computer_name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    status ENUM('online', 'offline', 'maintenance', 'deleted') DEFAULT 'online',
    ip_address VARCHAR(45),
    mac_address VARCHAR(17),
    specifications TEXT,
    os_installed VARCHAR(100),
    last_maintenance DATE,
    next_maintenance DATE,
    issues_reported TEXT,
    assigned_to VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_location (location)
);

-- ============================================
-- 2. Lab Bookings Table
-- ============================================
CREATE TABLE IF NOT EXISTS lab_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_reference VARCHAR(50) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    instructor_name VARCHAR(100) NOT NULL,
    instructor_email VARCHAR(100),
    booking_date DATE NOT NULL,
    time_slot VARCHAR(50) NOT NULL,
    number_of_students INT NOT NULL,
    purpose TEXT,
    special_requirements TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    approved_by INT,
    lab_assigned VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (booking_date),
    INDEX idx_status (status),
    INDEX idx_instructor (instructor_name)
);

-- ============================================
-- 3. IT Support Tickets Table
-- ============================================
CREATE TABLE IF NOT EXISTS it_support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    requester_name VARCHAR(100) NOT NULL,
    requester_email VARCHAR(100),
    requester_type ENUM('student', 'staff', 'faculty') NOT NULL,
    issue_type ENUM('hardware', 'software', 'network', 'account', 'other') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT,
    resolution_notes TEXT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_requester (requester_name),
    INDEX idx_type (issue_type)
);

-- ============================================
-- 4. Software Inventory Table
-- ============================================
CREATE TABLE IF NOT EXISTS software_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    software_name VARCHAR(200) NOT NULL,
    version VARCHAR(50),
    license_key VARCHAR(200),
    license_type ENUM('free', 'commercial', 'educational', 'trial') DEFAULT 'educational',
    license_expiry DATE,
    installation_count INT DEFAULT 0,
    update_available BOOLEAN DEFAULT FALSE,
    latest_version VARCHAR(50),
    download_url VARCHAR(500),
    category ENUM('os', 'office', 'development', 'design', 'antivirus', 'utility', 'other') DEFAULT 'utility',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_update (update_available)
);

-- ============================================
-- 5. Network Devices Table
-- ============================================
CREATE TABLE IF NOT EXISTS network_devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_name VARCHAR(100) NOT NULL,
    device_type ENUM('router', 'switch', 'access_point', 'firewall', 'server', 'other') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    mac_address VARCHAR(17),
    location VARCHAR(100),
    status ENUM('online', 'offline', 'maintenance') DEFAULT 'online',
    firmware_version VARCHAR(50),
    last_check TIMESTAMP,
    uptime_hours INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (device_type),
    INDEX idx_ip (ip_address)
);

-- ============================================
-- 6. Maintenance Logs Table
-- ============================================
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    computer_id VARCHAR(50) NOT NULL,
    maintenance_type ENUM('routine', 'repair', 'upgrade', 'cleaning') NOT NULL,
    description TEXT NOT NULL,
    performed_by VARCHAR(100) NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0.00,
    parts_replaced TEXT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    scheduled_date DATE,
    completed_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_computer (computer_id),
    INDEX idx_status (status),
    INDEX idx_date (scheduled_date)
);

-- ============================================
-- 7. Lab Usage Statistics Table
-- ============================================
CREATE TABLE IF NOT EXISTS lab_usage_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lab_name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    total_sessions INT DEFAULT 0,
    total_users INT DEFAULT 0,
    peak_concurrent_users INT DEFAULT 0,
    average_session_duration INT DEFAULT 0, -- in minutes
    computers_used INT DEFAULT 0,
    computers_available INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_lab_date (lab_name, date),
    INDEX idx_date (date),
    INDEX idx_lab (lab_name)
);

-- ============================================
-- Insert Sample Data for Testing
-- ============================================

-- Sample Lab Computers
INSERT INTO lab_computers (computer_id, computer_name, location, status, ip_address, mac_address, specifications, os_installed, last_maintenance, next_maintenance, issues_reported) VALUES
('LAB-A-001', 'Computer Lab A - Station 1', 'Lab A - Floor 1', 'online', '192.168.1.101', 'AA:BB:CC:DD:EE:01', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', '2024-05-01', '2024-08-01', NULL),
('LAB-A-002', 'Computer Lab A - Station 2', 'Lab A - Floor 1', 'online', '192.168.1.102', 'AA:BB:CC:DD:EE:02', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', '2024-05-01', '2024-08-01', NULL),
('LAB-A-003', 'Computer Lab A - Station 3', 'Lab A - Floor 1', 'offline', '192.168.1.103', 'AA:BB:CC:DD:EE:03', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', '2024-05-01', '2024-08-01', 'Hardware issue - PSU replacement needed'),
('LAB-B-001', 'Computer Lab B - Station 1', 'Lab B - Floor 2', 'online', '192.168.2.101', 'BB:CC:DD:EE:FF:01', 'Intel i7, 16GB RAM, 512GB SSD', 'Windows 11 Pro', '2024-05-15', '2024-08-15', NULL),
('LAB-B-002', 'Computer Lab B - Station 2', 'Lab B - Floor 2', 'maintenance', '192.168.2.102', 'BB:CC:DD:EE:FF:02', 'Intel i7, 16GB RAM, 512GB SSD', 'Windows 11 Pro', '2024-05-15', '2024-08-15', 'OS reinstallation in progress');

-- Sample Lab Bookings
INSERT INTO lab_bookings (booking_reference, course_name, instructor_name, instructor_email, booking_date, time_slot, number_of_students, purpose, special_requirements, status, approved_by, lab_assigned) VALUES
('BK-2024-001', 'Introduction to Nursing Informatics', 'Dr. Sarah Johnson', 'sjohnson@isnm.ac.ug', '2024-06-10', '09:00 AM - 11:00 AM', 25, 'Practical session on electronic health records', NULL, 'confirmed', NULL, 'Lab A'),
('BK-2024-002', 'Research Methods', 'Prof. Michael Okonkwo', 'mokonkwo@isnm.ac.ug', '2024-06-10', '02:00 PM - 04:00 PM', 30, 'Data analysis using SPSS', NULL, 'pending', NULL, 'Lab B'),
('BK-2024-003', 'Computer Literacy', 'Ms. Grace Namukasa', 'gnamukasa@isnm.ac.ug', '2024-06-11', '09:00 AM - 11:00 AM', 20, 'Basic computer skills training', NULL, 'confirmed', NULL, 'Lab A');

-- Sample IT Support Tickets
INSERT INTO it_support_tickets (ticket_number, requester_name, requester_email, requester_type, issue_type, priority, description, status, assigned_to, resolution_notes, resolved_at) VALUES
('TKT-2024-001', 'John Mugisha', 'jmugisha@student.isnm.ac.ug', 'student', 'software', 'medium', 'Unable to access SPSS software on Lab A computers', 'open', NULL, NULL, NULL),
('TKT-2024-002', 'Dr. Emily Achieng', 'eachieng@isnm.ac.ug', 'staff', 'hardware', 'high', 'Projector in Lab B not displaying properly', 'in_progress', NULL, NULL, NULL),
('TKT-2024-003', 'Peter Kato', 'pkato@student.isnm.ac.ug', 'student', 'account', 'low', 'Forgot password for student portal', 'open', NULL, NULL, NULL),
('TKT-2024-004', 'Ms. Ruth Akello', 'rakello@isnm.ac.ug', 'staff', 'network', 'critical', 'WiFi connection dropping frequently in Lab A', 'open', NULL, NULL, NULL);

-- Sample Software Inventory
INSERT INTO software_inventory (software_name, version, license_key, license_type, license_expiry, installation_count, update_available, latest_version, download_url, category, notes) VALUES
('Microsoft Office 365', '2024', NULL, 'educational', '2025-12-31', 50, FALSE, '2024', NULL, 'office', NULL),
('SPSS Statistics', '29.0', NULL, 'commercial', '2024-12-31', 25, TRUE, '30.0', NULL, 'development', NULL),
('Windows 11 Pro', '23H2', NULL, 'educational', '2026-06-30', 50, FALSE, '23H2', NULL, 'os', NULL),
('Adobe Creative Cloud', '2024', NULL, 'educational', '2024-08-31', 15, TRUE, '2024.1', NULL, 'design', NULL),
('Malwarebytes Antivirus', '5.0', NULL, 'commercial', '2025-01-15', 50, FALSE, '5.0', NULL, 'antivirus', NULL);

-- Sample Network Devices
INSERT INTO network_devices (device_name, device_type, ip_address, mac_address, location, status, firmware_version, last_check, uptime_hours, notes) VALUES
('Main Router', 'router', '192.168.0.1', '00:11:22:33:44:55', 'Server Room', 'online', 'v2.1.0', NULL, 720, NULL),
('Lab A Switch', 'switch', '192.168.1.1', '00:11:22:33:44:56', 'Lab A - Floor 1', 'online', 'v1.5.2', NULL, 480, NULL),
('Lab B Switch', 'switch', '192.168.2.1', '00:11:22:33:44:57', 'Lab B - Floor 2', 'online', 'v1.5.2', NULL, 480, NULL),
('WiFi Access Point A', 'access_point', '192.168.0.10', '00:11:22:33:44:58', 'Lab A - Floor 1', 'online', 'v3.2.1', NULL, 240, NULL),
('WiFi Access Point B', 'access_point', '192.168.0.11', '00:11:22:33:44:59', 'Lab B - Floor 2', 'offline', 'v3.2.1', NULL, 0, 'Needs repair'),
('Firewall', 'firewall', '192.168.0.2', '00:11:22:33:44:60', 'Server Room', 'online', 'v4.0.0', NULL, 720, NULL);

-- Sample Maintenance Logs
INSERT INTO maintenance_logs (computer_id, maintenance_type, description, performed_by, cost, parts_replaced, status, scheduled_date, completed_date) VALUES
('LAB-A-003', 'repair', 'Power supply unit replacement required', 'IT Technician - James', 150.00, NULL, 'scheduled', '2024-06-12', NULL),
('LAB-B-002', 'routine', 'Operating system reinstallation and updates', 'IT Technician - Sarah', 0.00, NULL, 'in_progress', '2024-06-10', NULL);

-- Sample Lab Usage Statistics
INSERT INTO lab_usage_stats (lab_name, date, total_sessions, total_users, peak_concurrent_users, average_session_duration, computers_used, computers_available, notes) VALUES
('Lab A', '2024-06-05', 8, 45, 25, 90, 22, 25, NULL),
('Lab B', '2024-06-05', 6, 35, 20, 85, 18, 20, NULL),
('Lab A', '2024-06-06', 10, 55, 28, 95, 24, 25, NULL),
('Lab B', '2024-06-06', 7, 40, 22, 80, 19, 20, NULL);

-- ============================================
-- Create Views for Common Queries
-- ============================================

-- View: Computer Availability Summary
CREATE OR REPLACE VIEW v_computer_availability AS
SELECT 
    location,
    COUNT(*) as total_computers,
    SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_count,
    SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_count,
    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count,
    ROUND(SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as availability_percentage
FROM lab_computers
WHERE status != 'deleted'
GROUP BY location;

-- View: Active Support Tickets Summary
CREATE OR REPLACE VIEW v_active_tickets AS
SELECT 
    priority,
    COUNT(*) as ticket_count,
    GROUP_CONCAT(ticket_number) as ticket_numbers
FROM it_support_tickets
WHERE status IN ('open', 'in_progress')
GROUP BY priority
ORDER BY 
    CASE priority 
        WHEN 'critical' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        ELSE 4 
    END;

-- ============================================
-- Grant Permissions (adjust as needed)
-- ============================================
-- GRANT ALL PRIVILEGES ON igangaschoolofl_ict.* TO 'ict_user'@'localhost' IDENTIFIED BY 'secure_password';
-- FLUSH PRIVILEGES;

-- ============================================
-- End of Script
-- ============================================