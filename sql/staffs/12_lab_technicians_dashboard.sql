-- ============================================================
-- ISNM LAB TECHNICIANS DASHBOARD SQL
-- Complete Laboratory Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. LAB TECHNICIANS USER ACCOUNTS
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
('LAB001', 'Lab Technician', 'lab@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$lab@isnmHashedPassword', '+256701000020', 'Lab Technicians', 'Support',
 (SELECT id FROM staff_roles WHERE role_name = 'Lab Technicians' LIMIT 1), 'Active', CURDATE(), NOW()),
('LAB002', 'Senior Lab Technician', 'senior_lab@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$senior_lab@isnmHashedPassword', '+256701000031', 'Senior Lab Technician', 'Support',
 (SELECT id FROM staff_roles WHERE role_name = 'Lab Technicians' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. LABORATORY MANAGEMENT TABLES
-- ============================================================

-- Laboratory Equipment
CREATE TABLE IF NOT EXISTS lab_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id VARCHAR(50) UNIQUE NOT NULL,
    equipment_name VARCHAR(255) NOT NULL,
    equipment_type ENUM('Microscope', 'Centrifuge', 'Autoclave', 'Spectrophotometer', 'PCR', 'Incubator', 'Refrigerator', 'Freezer', 'Other') NOT NULL,
    manufacturer VARCHAR(255),
    serial_number VARCHAR(100),
    model VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    calibration_date DATE,
    next_calibration_date DATE,
    location VARCHAR(255),
    status ENUM('Operational', 'Maintenance', 'Repair', 'Retired') DEFAULT 'Operational',
    last_serviced_by VARCHAR(255),
    service_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_status (status)
);

-- Laboratory Inventory
CREATE TABLE IF NOT EXISTS lab_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id VARCHAR(50) UNIQUE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_category ENUM('Reagent', 'Chemical', 'Consumable', 'Glassware', 'Plasticware', 'Media', 'Antibody', 'Enzyme') NOT NULL,
    manufacturer VARCHAR(255),
    catalog_number VARCHAR(100),
    batch_number VARCHAR(100),
    unit_of_measure VARCHAR(50),
    quantity_on_hand DECIMAL(15,2) DEFAULT 0,
    reorder_level DECIMAL(15,2) DEFAULT 0,
    storage_location VARCHAR(255),
    expiry_date DATE,
    date_received DATE,
    received_by INT,
    status ENUM('In Stock', 'Low Stock', 'Out of Stock', 'Expired', 'Quarantine') DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (received_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_item_id (item_id),
    INDEX idx_item_name (item_name),
    INDEX idx_category (item_category),
    INDEX idx_expiry_date (expiry_date)
);

-- Lab Skills Sessions
CREATE TABLE IF NOT EXISTS lab_skills_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(50) UNIQUE NOT NULL,
    session_title VARCHAR(255) NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    duration_minutes INT,
    target_department ENUM('Nursing', 'Midwifery', 'Both') DEFAULT 'Both',
    year_of_study INT,
    students_expected INT,
    students_attended INT,
    instructor_id INT,
    instructor_name VARCHAR(255),
    equipment_used TEXT,
    materials_used TEXT,
    pre_test_score DECIMAL(5,2),
    post_test_score DECIMAL(5,2),
    session_status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    evaluation_notes TEXT,
    completed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_session_date (session_date)
);

-- Lab Safety Records
CREATE TABLE IF NOT EXISTS lab_safety_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(50) UNIQUE NOT NULL,
    incident_date DATE NOT NULL,
    incident_type ENUM('Accident', 'Near Miss', 'Spill', 'Exposure', 'Equipment Failure') NOT NULL,
    severity ENUM('Minor', 'Moderate', 'Severe', 'Critical') DEFAULT 'Minor',
    person_involved VARCHAR(255),
    person_type ENUM('Student', 'Staff', 'Visitor') NOT NULL,
    description TEXT NOT NULL,
    immediate_action TEXT,
    follow_up_action TEXT,
    reported_by INT,
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT FALSE,
    resolution_date TIMESTAMP NULL,
    resolved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_record_id (record_id),
    INDEX idx_incident_date (incident_date)
);

-- Chemical Inventory
CREATE TABLE IF NOT EXISTS lab_chemical_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chemical_id VARCHAR(50) UNIQUE NOT NULL,
    chemical_name VARCHAR(255) NOT NULL,
    cas_number VARCHAR(50),
    chemical_formula VARCHAR(100),
    hazard_classification VARCHAR(100),
    storage_requirements TEXT,
    quantity_on_hand DECIMAL(15,2),
    unit_of_measure VARCHAR(50),
    expiry_date DATE,
    date_received DATE,
    storage_location VARCHAR(255),
    supplier VARCHAR(255),
    msds_file VARCHAR(500),
    status ENUM('In Stock', 'Low Stock', 'Out of Stock', 'Expired') DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chemical_id (chemical_id),
    INDEX idx_chemical_name (chemical_name),
    INDEX idx_expiry_date (expiry_date)
);

-- Lab Experiments Tracking
CREATE TABLE IF NOT EXISTS lab_experiments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id VARCHAR(50) UNIQUE NOT NULL,
    experiment_name VARCHAR(255) NOT NULL,
    course_code VARCHAR(50),
    batch_number VARCHAR(50),
    session_id INT,
    experiment_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    students_enrolled INT,
    students_completed INT,
    instructor_id INT,
    lab_technician_id INT,
    equipment_used TEXT,
    reagents_used TEXT,
    observations TEXT,
    results TEXT,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES lab_skills_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (instructor_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (lab_technician_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_experiment_date (experiment_date)
);

-- ============================================================
-- 3. PROCEDURES FOR LAB TECHNICIANS
-- ============================================================

DELIMITER //

-- Record lab equipment maintenance
CREATE PROCEDURE lab_record_maintenance(
    IN p_equipment_id INT,
    IN p_status VARCHAR(20),
    IN p_notes TEXT
)
BEGIN
    UPDATE lab_equipment 
    SET status = p_status,
        service_notes = CONCAT(COALESCE(service_notes, ''), '\n', p_notes),
        updated_at = NOW()
    WHERE id = p_equipment_id;
END //

-- Update inventory quantity
CREATE PROCEDURE lab_update_inventory(
    IN p_item_id INT,
    IN p_new_quantity DECIMAL(15,2)
)
BEGIN
    UPDATE lab_inventory 
    SET quantity_on_hand = p_new_quantity,
        status = CASE 
            WHEN p_new_quantity <= 0 THEN 'Out of Stock'
            WHEN p_new_quantity <= reorder_level THEN 'Low Stock'
            ELSE 'In Stock'
        END,
        updated_at = NOW()
    WHERE id = p_item_id;
END //

-- Schedule lab session
CREATE PROCEDURE lab_schedule_session(
    IN p_title VARCHAR(255),
    IN p_skill_name VARCHAR(255),
    IN p_session_date DATE,
    IN p_instructor_id INT
)
BEGIN
    DECLARE v_session_id VARCHAR(50);
    SET v_session_id = CONCAT('LSS', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO lab_skills_sessions (
        session_id, session_title, skill_name, session_date, instructor_id
    ) VALUES (
        v_session_id, p_title, p_skill_name, p_session_date, p_instructor_id
    );
END //

DELIMITER ;

COMMIT;