-- ============================================================
-- ISNM SECURITY DEPARTMENT DASHBOARD SQL
-- Complete Security Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. SECURITY USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('SEC001', 'Security Officer', 'security@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$security@isnmHashedPassword', '+256701000022', 'Security', 'Security Services',
 (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active', CURDATE(), NOW()),
('SEC002', 'Chief Security Officer', 'cso@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$cso@isnmHashedPassword', '+256701000030', 'Chief Security Officer', 'Security Services',
 (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. SECURITY MANAGEMENT TABLES
-- ============================================================

-- Security Patrols
CREATE TABLE IF NOT EXISTS security_patrols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patrol_id VARCHAR(50) UNIQUE NOT NULL,
    patrol_date DATE NOT NULL,
    patrol_shift ENUM('Morning', 'Afternoon', 'Night') NOT NULL,
    patrol_area VARCHAR(255) NOT NULL,
    patrol_route TEXT,
    start_time TIME,
    end_time TIME,
    duration_minutes INT,
    team_leader INT,
    officers_involved TEXT,
    incidents_reported INT DEFAULT 0,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_leader) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_patrol_id (patrol_id),
    INDEX idx_patrol_date (patrol_date)
);

-- Security Incidents
CREATE TABLE IF NOT EXISTS security_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id VARCHAR(50) UNIQUE NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_type ENUM('Theft', 'Assault', 'Fire', 'Medical', 'Accident', 'Vandalism', 'Suspicious Activity', 'Other') NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    parties_involved TEXT,
    injuries_reported BOOLEAN DEFAULT FALSE,
    injuries_description TEXT,
    reported_by INT,
    reporter_name VARCHAR(255),
    reported_via ENUM('Phone', 'Email', 'In Person', 'Radio', 'Online') DEFAULT 'In Person',
    response_team TEXT,
    action_taken TEXT,
    resolution_status ENUM('Reported', 'In Investigation', 'Resolved', 'Closed') DEFAULT 'Reported',
    resolved_by INT,
    resolution_date TIMESTAMP NULL,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_incident_id (incident_id),
    INDEX idx_incident_date (incident_date),
    INDEX idx_severity (severity)
);

-- Access Control Logs
CREATE TABLE IF NOT EXISTS security_access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_id VARCHAR(50) UNIQUE NOT NULL,
    access_point VARCHAR(255) NOT NULL,
    access_date DATE NOT NULL,
    access_time TIME,
    person_type ENUM('Staff', 'Student', 'Visitor', 'Vendor', 'Unknown') NOT NULL,
    person_id INT,
    person_name VARCHAR(255),
    access_direction ENUM('Entry', 'Exit') NOT NULL,
    access_method ENUM('ID Card', 'Biometric', 'PIN', 'Manual') DEFAULT 'ID Card',
    authorized BOOLEAN DEFAULT TRUE,
    denial_reason VARCHAR(255),
    captured_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_id (log_id),
    INDEX idx_access_date (access_date),
    INDEX idx_person_type (person_type)
);

-- Visitor Management
CREATE TABLE IF NOT EXISTS security_visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id VARCHAR(50) UNIQUE NOT NULL,
    visitor_name VARCHAR(255) NOT NULL,
    visitor_phone VARCHAR(20),
    visitor_email VARCHAR(100),
    visitor_company VARCHAR(255),
    visitor_nature ENUM('Official', 'Parent', 'Guardian', 'Service Provider', 'Delivery', 'Other') NOT NULL,
    purpose_of_visit TEXT,
    person_to_visit INT,
    person_to_visit_name VARCHAR(255),
    visit_date DATE NOT NULL,
    expected_arrival TIME,
    expected_departure TIME,
    actual_arrival TIMESTAMP NULL,
    actual_departure TIMESTAMP NULL,
    vehicle_number VARCHAR(50),
    items_carried TEXT,
    security_check_passed BOOLEAN DEFAULT FALSE,
    check_in_by INT,
    check_out_by INT,
    status ENUM('Scheduled', 'Checked In', 'On Campus', 'Checked Out', 'No Show') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_to_visit) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (check_in_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (check_out_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_visit_date (visit_date)
);

-- Security Equipment
CREATE TABLE IF NOT EXISTS security_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id VARCHAR(50) UNIQUE NOT NULL,
    equipment_name VARCHAR(255) NOT NULL,
    equipment_type ENUM('Camera', 'CCTV', 'Metal Detector', 'Scanner', 'Radio', 'Flashlight', 'Baton', 'Other') NOT NULL,
    serial_number VARCHAR(100),
    location_installed VARCHAR(255),
    installation_date DATE,
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    condition_status ENUM('Excellent', 'Good', 'Fair', 'Poor', 'Broken') DEFAULT 'Good',
    status ENUM('Active', 'Inactive', 'Maintenance', 'Retired') DEFAULT 'Active',
    assigned_to INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_status (status)
);

-- Emergency Contacts and Procedures
CREATE TABLE IF NOT EXISTS security_emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_name VARCHAR(255) NOT NULL,
    contact_type ENUM('Police', 'Hospital', 'Fire', 'Ambulance', 'Internal') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    secondary_phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    response_time_minutes INT,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_type (contact_type),
    INDEX idx_is_active (is_active)
);

-- ============================================================
-- 3. PROCEDURES FOR SECURITY DEPARTMENT
-- ============================================================

DELIMITER //

-- Report security incident
CREATE PROCEDURE security_report_incident(
    IN p_incident_type VARCHAR(50),
    IN p_location VARCHAR(255),
    IN p_description TEXT,
    IN p_reported_by INT,
    IN p_severity VARCHAR(20)
)
BEGIN
    DECLARE v_incident_id VARCHAR(50);
    SET v_incident_id = CONCAT('INC', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO security_incidents (
        incident_id, incident_type, location, description, reported_by, severity
    ) VALUES (
        v_incident_id, p_incident_type, p_location, p_description, p_reported_by, p_severity
    );
END //

-- Record visitor check-in
CREATE PROCEDURE security_visitor_checkin(
    IN p_visitor_id INT,
    IN p_checked_by INT
)
BEGIN
    UPDATE security_visitors 
    SET actual_arrival = NOW(),
        status = 'Checked In',
        check_in_by = p_checked_by
    WHERE id = p_visitor_id;
END //

-- Record visitor check-out
CREATE PROCEDURE security_visitor_checkout(
    IN p_visitor_id INT,
    IN p_checked_by INT
)
BEGIN
    UPDATE security_visitors 
    SET actual_departure = NOW(),
        status = 'Checked Out',
        check_out_by = p_checked_by
    WHERE id = p_visitor_id;
END //

DELIMITER ;

COMMIT;