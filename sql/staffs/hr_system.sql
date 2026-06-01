-- ============================================================
-- ISNM COMPLETE HR MANAGEMENT SYSTEM
-- Comprehensive SQL Schema with all required features
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. HR USER ACCOUNTS & AUTHENTICATION
-- ============================================================

CREATE TABLE IF NOT EXISTS hr_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('hr_manager', 'hr_assistant', 'director', 'head_of_department', 'payroll_officer') DEFAULT 'hr_manager',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- ============================================================
-- 2. STAFF RECORDS MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS staff_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    category_code VARCHAR(20),
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) UNIQUE NOT NULL,
    staff_code VARCHAR(20),
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    email VARCHAR(255),
    phone_primary VARCHAR(20),
    phone_secondary VARCHAR(20),
    national_id VARCHAR(50),
    passport_number VARCHAR(50),
    marital_status ENUM('single', 'married', 'divorced', 'widowed'),
    home_address TEXT,
    residential_address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    country VARCHAR(100),
    next_of_kin_name VARCHAR(255),
    next_of_kin_phone VARCHAR(20),
    next_of_kin_relationship VARCHAR(50),
    profile_photo VARCHAR(255),
    status ENUM('active', 'on_leave', 'suspended', 'retired', 'resigned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS employment_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    job_category VARCHAR(100),
    department VARCHAR(100) NOT NULL,
    sub_department VARCHAR(100),
    staff_category_id INT,
    employment_type ENUM('permanent', 'contract', 'temporary', 'part_time') DEFAULT 'permanent',
    grade VARCHAR(20),
    salary_grade VARCHAR(20),
    reports_to INT,
    employment_start_date DATE NOT NULL,
    employment_end_date DATE,
    contract_start_date DATE,
    contract_end_date DATE,
    contract_renewal_date DATE,
    office_location VARCHAR(255),
    office_contact VARCHAR(20),
    professional_license VARCHAR(100),
    license_expiry_date DATE,
    license_issuing_body VARCHAR(255),
    nursing_council_number VARCHAR(50),
    council_number_expiry DATE,
    qualification_level VARCHAR(100),
    specialization VARCHAR(255),
    years_of_experience INT,
    previous_employer VARCHAR(255),
    previous_position VARCHAR(255),
    reason_for_leaving TEXT,
    status ENUM('active', 'inactive', 'on_leave', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_department (department),
    INDEX idx_status (status),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_category_id) REFERENCES staff_categories(id)
);

-- ... rest of HR schema ...
