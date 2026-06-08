<?php
/**
 * ISNM Database Setup Script
 * Run this file to initialize all databases with tables and default data
 * Access via: http://yourdomain.com/setup_database.php
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>ISNM Database Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px;}";
echo ".success{color:#28a745;background:#d4edda;padding:10px;border-radius:4px;margin:5px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:4px;margin:5px 0;}";
echo ".info{color:#17a2b8;background:#d1ecf1;padding:10px;border-radius:4px;margin:5px 0;}";
echo "h1{color:#333;border-bottom:2px solid #007bff;padding-bottom:10px;}</style></head><body>";
echo "<h1>ISNM Database Setup</h1>";

// Load database configuration
require_once __DIR__ . '/config/database.php';

$allSuccess = true;

// Function to run SQL and report results
function runSQL($conn, $sql, $description) {
    global $allSuccess;
    echo "<div class='info'>Running: $description...</div>";
    try {
        if ($conn->multi_query($sql)) {
            while ($conn->more_results() && $conn->next_result()) {
                // Clear results
            }
            echo "<div class='success'>✓ $description completed successfully</div>";
            return true;
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ $description failed: " . $e->getMessage() . "</div>";
        $allSuccess = false;
        return false;
    }
}

// Get connection for setup operations (use root to create databases)
$conn = new mysqli(DB_HOST, 'root', '', null, DB_PORT);
$conn->set_charset(DB_CHARSET);

if ($conn->connect_error) {
    die("<div class='error'>✗ Failed to connect to main database: " . $conn->connect_error . "</div></body></html>");
}

echo "<div class='info'>Connected to database server successfully</div>";

// Create databases (using root connection)
$createDbs = "CREATE DATABASE IF NOT EXISTS `igangaschoolofl_staffs_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
             CREATE DATABASE IF NOT EXISTS `igangaschoolofl_students_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
             CREATE DATABASE IF NOT EXISTS `igangaschoolofl_website_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
             CREATE DATABASE IF NOT EXISTS `igangaschoolofl_ict` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
runSQL($conn, $createDbs, "Create all databases");

// Create staffs_db tables (use staffs DB credentials)
$conn_staff = new mysqli(DB_HOST, STAFF_DB_USER, STAFF_DB_PASS, 'igangaschoolofl_staffs_db', DB_PORT);
$conn_staff->set_charset(DB_CHARSET);

if ($conn_staff->connect_error) {
    die("<div class='error'>✗ Failed to connect to staffs database: " . $conn_staff->connect_error . "</div></body></html>");
}

// Core staffs tables SQL
$staffsTables = "
SET FOREIGN_KEY_CHECKS = 0;

-- Staff Roles
CREATE TABLE IF NOT EXISTS staff_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_description TEXT,
    role_level ENUM('Executive', 'Management', 'Academic', 'Support', 'Administrative') DEFAULT 'Academic',
    dashboard_path VARCHAR(255),
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name),
    INDEX idx_role_level (role_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff Table
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    role_id INT,
    status ENUM('Active', 'Inactive', 'On Leave', 'Suspended') DEFAULT 'Active',
    hire_date DATE,
    salary DECIMAL(10,2),
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_failed_attempt TIMESTAMP NULL,
    password_changed BOOLEAN DEFAULT FALSE,
    is_first_login BOOLEAN DEFAULT TRUE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES staff_roles(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_email (email),
    INDEX idx_position (position),
    INDEX idx_department (department),
    INDEX idx_status (status),
    INDEX idx_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff Profiles
CREATE TABLE IF NOT EXISTS staff_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    bio TEXT,
    profile_picture VARCHAR(255),
    qualifications TEXT,
    experience TEXT,
    skills TEXT,
    achievements TEXT,
    education_background TEXT,
    certifications TEXT,
    professional_memberships TEXT,
    research_interests TEXT,
    publications TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff Activity Log
CREATE TABLE IF NOT EXISTS staff_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    activity_type ENUM('Login', 'Logout', 'Dashboard Access', 'Data View', 'Data Edit', 'Data Delete', 'Export', 'Print', 'Settings Change', 'Account Created', 'Account Updated') NOT NULL,
    activity_description TEXT,
    module_accessed VARCHAR(100),
    record_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_module_accessed (module_accessed),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff Login Sessions
CREATE TABLE IF NOT EXISTS staff_login_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    device_info TEXT,
    browser_info TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff Login Attempts
CREATE TABLE IF NOT EXISTS staff_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(255),
    staff_id INT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_email (email),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_success (success),
    INDEX idx_staff_id (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type ENUM('text', 'number', 'boolean', 'file', 'json', 'email', 'url') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_type (setting_type),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
";

runSQL($conn_staff, $staffsTables, "Create staffs database core tables");

// Insert default staff roles
$insertRoles = "
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director General', 'Overall school administration and management with full access to all modules and departments', 'Executive', 'dashboards/director-general.php', '{\"all\": true, \"can_access_all_dashboards\": true, \"can_manage_all_staff\": true, \"super_admin\": true}'),
('School Principal', 'School academic and administrative leadership with cross-departmental viewing access', 'Executive', 'dashboards/school-principal.php', '{\"academic\": true, \"administrative\": true, \"staff\": true, \"students\": true, \"can_view_all_records\": true}'),
('CEO', 'Chief Executive Officer for strategic management', 'Executive', 'dashboards/ceo.php', '{\"strategic\": true, \"financial\": true, \"operational\": true}'),
('Director Academics', 'Academic programs oversight', 'Management', 'dashboards/director-academics.php', '{\"academic\": true, \"curriculum\": true}'),
('Director Finance', 'Financial management', 'Management', 'dashboards/director-finance.php', '{\"financial\": true, \"budgeting\": true}'),
('HR Manager', 'Human resources', 'Management', 'dashboards/hr-manager.php', '{\"hr\": true, \"staff\": true}'),
('School Bursar', 'Financial operations', 'Administrative', 'bursar_dashboard.php', '{\"financial\": true, \"fees\": true}'),
('School Librarian', 'Library management', 'Support', 'dashboards/school-librarian.php', '{\"library\": true, \"resources\": true}'),
('Head Nursing', 'Nursing department', 'Academic', 'dashboards/head-nursing.php', '{\"nursing\": true, \"department\": true}'),
('Head Midwifery', 'Midwifery department', 'Academic', 'dashboards/head-midwifery.php', '{\"midwifery\": true, \"department\": true}'),
('Lecturers', 'Teaching staff', 'Academic', 'dashboards/lecturers.php', '{\"teaching\": true, \"lecturers\": true}'),
('Security', 'Campus security', 'Support', 'dashboards/security.php', '{\"security\": true, \"safety\": true}');
";

runSQL($conn_staff, $insertRoles, "Insert default staff roles");

// Insert default system settings
$insertSettings = "
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('school_name', 'Institute of Strategic Nursing and Midwifery', 'text', 'School name for display on documents', 'general', TRUE),
('school_address', 'P.O. Box 12345, Kampala, Uganda', 'text', 'School address for documents', 'general', TRUE),
('academic_year', '2025/2026', 'text', 'Current academic year', 'academic', TRUE),
('max_login_attempts', '5', 'number', 'Maximum login attempts before account lock', 'security', FALSE),
('session_timeout', '30', 'number', 'Session timeout in minutes', 'security', FALSE),
('enable_audit_logging', 'true', 'boolean', 'Enable audit logging', 'security', FALSE);
";

runSQL($conn_staff, $insertSettings, "Insert default system settings");

// Create students_db tables
$conn_students = new mysqli(DB_HOST, STUDENTS_DB_USER, STUDENTS_DB_PASS, 'igangaschoolofl_students_db', DB_PORT);
$conn_students->set_charset(DB_CHARSET);

if ($conn_students->connect_error) {
    die("<div class='error'>✗ Failed to connect to students database</div></body></html>");
}

$studentsTables = "
SET FOREIGN_KEY_CHECKS = 0;

-- Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    national_student_id_number VARCHAR(50) UNIQUE,
    index_number VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    other_name VARCHAR(100),
    full_name VARCHAR(300),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20),
    mobile_number VARCHAR(20),
    program VARCHAR(100),
    course VARCHAR(100),
    current_year INT,
    year INT,
    level VARCHAR(50),
    set_name VARCHAR(50),
    current_semester VARCHAR(20),
    intake_date DATE,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
    nationality VARCHAR(100),
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_email VARCHAR(100),
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    profile_picture VARCHAR(500),
    passport_photo VARCHAR(500),
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn', 'deleted') DEFAULT 'Active',
    last_login TIMESTAMP NULL,
    locked_until TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    password_changed BOOLEAN DEFAULT FALSE,
    is_first_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_number (student_number),
    INDEX idx_registration_number (registration_number),
    INDEX idx_national_id (national_student_id_number),
    INDEX idx_index_number (index_number),
    INDEX idx_email (email),
    INDEX idx_program (program),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student Fees
CREATE TABLE IF NOT EXISTS student_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    paid_date DATE,
    status ENUM('Unpaid', 'Partially Paid', 'Paid', 'Overdue') DEFAULT 'Unpaid',
    payment_method VARCHAR(50),
    receipt_number VARCHAR(50),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_fee_type (fee_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
";

runSQL($conn_students, $studentsTables, "Create students database core tables");

// Insert default staff accounts
$staffPasswordHash = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K';
$principalPasswordHash = '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.';

$insertStaff = "
INSERT IGNORE INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('BUR001', 'School Bursar', 'bursar@isnm.ac.ug', '" . $staffPasswordHash . "', 'School Bursar', 'Finance Department', 
       (SELECT id FROM staff_roles WHERE role_name = 'School Bursar' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW());

INSERT IGNORE INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('SP001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '" . $principalPasswordHash . "', 'School Principal', 'Academic Affairs', 
       (SELECT id FROM staff_roles WHERE role_name = 'School Principal' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW());
";

runSQL($conn_staff, $insertStaff, "Insert default staff accounts");

// Close connections
$conn_staff->close();
$conn_students->close();
$conn->close();

// Final summary
echo "<hr>";
if ($allSuccess) {
    echo "<div class='success'><h2>✓ Database Setup Completed Successfully!</h2>";
    echo "<p>All databases have been initialized. You can now:</p>";
    echo "<ul>";
    echo "<li>Login with staff credentials (password: staff@123 for most accounts)</li>";
    echo "<li>Access the staff dashboard</li>";
    echo "<li>Delete this setup file for security</li>";
    echo "</ul></div>";
} else {
    echo "<div class='error'><h2>⚠ Setup completed with errors</h2>";
    echo "<p>Please review the error messages above and contact support.</p></div>";
}

echo "</body></html>";
?>