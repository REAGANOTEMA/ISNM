-- ============================================================
-- ISNM MATRONS & WARDENS DASHBOARD SQL
-- Complete Student Welfare Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. MATRONS & WARDENS USER ACCOUNTS
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
('MAT001', 'Matron', 'matrons@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$matrons@isnmHashedPassword', '+256701000018', 'Matrons', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Matrons' LIMIT 1), 'Active', CURDATE(), NOW()),
('WAR001', 'Warden', 'wardens@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$wardens@isnmHashedPassword', '+256701000019', 'Wardens', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Wardens' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. STUDENT WELFARE MANAGEMENT TABLES
-- ============================================================

-- Student Welfare Cases
CREATE TABLE IF NOT EXISTS student_welfare_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    case_type ENUM('Financial', 'Health', 'Family', 'Academic', 'Discipline', 'Personal', 'Emergency') NOT NULL,
    case_priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    case_description TEXT NOT NULL,
    reported_by INT,
    reporter_name VARCHAR(255),
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_to INT,
    assigned_date TIMESTAMP NULL,
    action_taken TEXT,
    outcome TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    follow_up_completed BOOLEAN DEFAULT FALSE,
    closed BOOLEAN DEFAULT FALSE,
    closed_by INT,
    closure_date TIMESTAMP NULL,
    status ENUM('Reported', 'Assigned', 'In Progress', 'Follow Up', 'Closed', 'Escalated') DEFAULT 'Reported',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (closed_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_case_id (case_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Student Health Records
CREATE TABLE IF NOT EXISTS student_health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    health_facility VARCHAR(255),
    visit_date DATE NOT NULL,
    visit_time TIME,
    health_worker VARCHAR(255),
    complaint TEXT,
    diagnosis TEXT,
    treatment_given TEXT,
    medication_prescribed TEXT,
    medication_dispensed BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    follow_up_completed BOOLEAN DEFAULT FALSE,
    referred BOOLEAN DEFAULT FALSE,
    referral_facility VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_record_id (record_id),
    INDEX idx_student_id (student_id),
    INDEX idx_visit_date (visit_date)
);

-- Health Incidents
CREATE TABLE IF NOT EXISTS student_health_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_type ENUM('Injury', 'Illness', 'Allergic Reaction', 'Mental Health', 'Emergency', 'Other') NOT NULL,
    location VARCHAR(255),
    description TEXT NOT NULL,
    severity ENUM('Minor', 'Moderate', 'Severe', 'Critical') NOT NULL,
    first_aid_provided BOOLEAN DEFAULT FALSE,
    first_aid_description TEXT,
    hospitalized BOOLEAN DEFAULT FALSE,
    hospital_name VARCHAR(255),
    attended_by VARCHAR(255),
    parent_notified BOOLEAN DEFAULT FALSE,
    parent_phone VARCHAR(20),
    reported_by INT,
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT FALSE,
    resolution_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_incident_id (incident_id),
    INDEX idx_student_id (student_id),
    INDEX idx_incident_date (incident_date)
);

-- Counseling Sessions
CREATE TABLE IF NOT EXISTS student_counseling_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    counselor_id INT,
    counselor_name VARCHAR(255),
    session_date DATE NOT NULL,
    session_time TIME,
    session_duration_minutes INT,
    session_type ENUM('Individual', 'Group', 'Family', 'Crisis') DEFAULT 'Individual',
    issues_discussed TEXT,
    advice_given TEXT,
    referrals_made TEXT,
    follow_up_required BOOLEAN DEFAULT TRUE,
    follow_up_date DATE,
    session_outcome TEXT,
    student_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (counselor_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_student_id (student_id),
    INDEX idx_session_date (session_date)
);

-- Room Inspections
CREATE TABLE IF NOT EXISTS student_room_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id VARCHAR(50) UNIQUE NOT NULL,
    room_id INT,
    room_number VARCHAR(50) NOT NULL,
    inspection_date DATE NOT NULL,
    inspected_by INT,
    inspector_name VARCHAR(255),
    cleanliness_score INT,
    maintenance_issues TEXT,
    disciplinary_issues TEXT,
    items_confiscated TEXT,
    action_taken TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    next_inspection_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspected_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_inspection_id (inspection_id),
    INDEX idx_room_number (room_number),
    INDEX idx_inspection_date (inspection_date)
);

-- Emergency Contacts
CREATE TABLE IF NOT EXISTS student_emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    contact_relationship VARCHAR(100),
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(100),
    contact_address TEXT,
    is_primary BOOLEAN DEFAULT FALSE,
    notified BOOLEAN DEFAULT FALSE,
    last_notified TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    INDEX idx_contact_id (contact_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 3. PROCEDURES FOR MATRONS & WARDENS
-- ============================================================

DELIMITER //

-- Record student welfare case
CREATE PROCEDURE welfare_record_case(
    IN p_student_id INT,
    IN p_case_type VARCHAR(50),
    IN p_description TEXT,
    IN p_priority VARCHAR(20),
    IN p_reported_by INT
)
BEGIN
    DECLARE v_case_id VARCHAR(50);
    SET v_case_id = CONCAT('WEL', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_welfare_cases (
        case_id, student_id, case_type, case_description, case_priority, reported_by
    ) VALUES (
        v_case_id, p_student_id, p_case_type, p_description, p_priority, p_reported_by
    );
END //

-- Record health incident
CREATE PROCEDURE welfare_record_health_incident(
    IN p_student_id INT,
    IN p_incident_type VARCHAR(50),
    IN p_description TEXT,
    IN p_severity VARCHAR(20),
    IN p_reported_by INT
)
BEGIN
    DECLARE v_incident_id VARCHAR(50);
    SET v_incident_id = CONCAT('HLTH', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_health_incidents (
        incident_id, student_id, incident_type, description, severity, reported_by
    ) VALUES (
        v_incident_id, p_student_id, p_incident_type, p_description, p_severity, p_reported_by
    );
END //

-- Schedule counseling session
CREATE PROCEDURE welfare_schedule_counseling(
    IN p_student_id INT,
    IN p_counselor_id INT,
    IN p_session_date DATE,
    IN p_session_type VARCHAR(50)
)
BEGIN
    DECLARE v_session_id VARCHAR(50);
    SET v_session_id = CONCAT('COUN', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_counseling_sessions (
        session_id, student_id, counselor_id, session_date, session_type
    ) VALUES (
        v_session_id, p_student_id, p_counselor_id, p_session_date, p_session_type
    );
END //

DELIMITER ;

COMMIT;