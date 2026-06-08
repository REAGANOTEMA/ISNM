<?php
/**
 * ISNM Complete Setup Script
 * Creates all missing databases, roles, and test accounts
 */

$host = 'localhost';
$user = 'root';
$pass = 'ReagaN23#';
$port = 3306;

// Create all databases
$databases = [
    'igangaschoolofl_staffs_db',
    'igangaschoolofl_students_db',
    'igangaschoolofl_website_db',
    'igangaschoolofl_ict',
];

$conn = new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

foreach ($databases as $db) {
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$db' ready.\n";
}

// Setup staff database - create tables and roles
$conn->select_db('igangaschoolofl_staffs_db');

// Create staff_roles table
$conn->query("CREATE TABLE IF NOT EXISTS staff_roles (
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
)");

// Create staff table
$conn->query("CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    role_id INT,
    status ENUM('Active', 'Inactive', 'On Leave', 'Suspended') DEFAULT 'Active',
    hire_date DATE,
    password_changed BOOLEAN DEFAULT FALSE,
    is_first_login BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES staff_roles(id) ON DELETE SET NULL ON UPDATE CASCADE
)");

// Create system_settings table
$conn->query("CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type ENUM('text', 'number', 'boolean', 'file', 'json', 'email', 'url') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert all roles with dashboard paths
$roles_sql = "INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director General', 'Overall school administration and management with full access to all modules and departments', 'Executive', 'dashboards/director-general.php', '{\"all\": true, \"can_access_all_dashboards\": true, \"can_manage_all_staff\": true, \"can_view_all_departments\": true, \"can_edit_all_data\": true, \"can_delete_all_data\": true, \"can_view_financial\": true, \"can_view_academic\": true, \"can_view_hr\": true, \"can_view_students\": true, \"can_view_all_records\": true, \"super_admin\": true}'),
('CEO', 'Chief Executive Officer - Executive Leadership', 'Executive', 'dashboards/ceo.php', '{\"executive\": true, \"can_access_all_dashboards\": true}'),
('Director Academics', 'Academic Affairs Director', 'Management', 'dashboards/director-academics.php', '{\"academic\": true, \"can_manage_courses\": true, \"can_view_grades\": true}'),
('Director Finance', 'Financial Affairs Director', 'Management', 'dashboards/director-finance.php', '{\"finance\": true, \"can_view_financial\": true}'),
('Director ICT', 'Head of ICT Department - Oversight & Management', 'Management', 'dashboards/director-ict.php', '{\"ict\":true,\"systems\":true,\"can_manage_it\":true,\"can_access_computer_lab\":true}'),
('Computer Lab Manager', 'ICT Operations - Lab Management & Support', 'Management', 'computer_lab.php', '{\"computer_lab\":true,\"it_inventory\":true,\"it_support\":true}'),
('School Principal', 'Chief Academic Officer', 'Management', 'dashboards/school-principal.php', '{\"academic\": true, \"can_manage_staff\": true}'),
('Deputy Principal', 'Assistant Academic Officer', 'Management', 'dashboards/deputy-principal.php', '{\"academic\": true}'),
('Academic Registrar', 'Student Records Management', 'Administrative', 'dashboards/academic-registrar.php', '{\"registrar\": true, \"can_manage_students\": true}'),
('HR Manager', 'Human Resources Management', 'Administrative', 'dashboards/hr-manager.php', '{\"hr\": true, \"can_manage_staff\": true}'),
('School Secretary', 'Administrative Support', 'Administrative', 'dashboards/school-secretary.php', '{\"secretary\": true}'),
('School Librarian', 'Library Management', 'Support', 'dashboards/school-librarian.php', '{\"library\": true}'),
('Head Nursing', 'Nursing Department Head', 'Academic', 'dashboards/head-nursing.php', '{\"nursing\": true}'),
('Head Midwifery', 'Midwifery Department Head', 'Academic', 'dashboards/head-midwifery.php', '{\"midwifery\": true}'),
('Senior Lecturers', 'Advanced Teaching Staff', 'Academic', 'dashboards/senior-lecturers.php', '{\"teaching\": true}'),
('Lecturers', 'Classroom Teaching Staff', 'Academic', 'dashboards/lecturers.php', '{\"teaching\": true}'),
('Matrons', 'Student Welfare', 'Support', 'dashboards/matrons.php', '{\"welfare\": true}'),
('Wardens', 'Student Care & Support', 'Support', 'dashboards/wardens.php', '{\"welfare\": true}'),
('Sickbay', 'Student Health Support', 'Support', 'dashboards/sickbay.php', '{\"health\": true}'),
('Drivers', 'Transport Services', 'Support', 'dashboards/drivers.php', '{\"transport\": true}'),
('Security', 'Campus Security', 'Support', 'dashboards/security.php', '{\"security\": true}'),
('Store Keeper', 'Inventory Management', 'Support', 'dashboards/storekeeper.php', '{\"inventory\": true}'),
('Guild President', 'Student Leadership', 'Student', 'dashboards/guild-president.php', '{\"student_leadership\": true}'),
('Non-Teaching Staff', 'Non-Teaching Staff', 'Support', 'dashboards/non-teaching-staff.php', '{\"support\": true}'),
('Director Admissions & Requirements', 'Admissions & Requirements Clearance', 'Administrative', 'dashboards/director-admissions.php', '{\"admissions\": true}'),
('School Bursar', 'Chief Financial Officer', 'Management', 'dashboards/school-bursar.php', '{\"finance\": true}'),
('Bursar', 'Finance Staff', 'Management', 'dashboards/school-bursar.php', '{\"finance\": true}')";

if ($conn->query($roles_sql) === TRUE) {
    echo "Roles inserted/updated successfully.\n";
} else {
    echo "Error inserting roles: " . $conn->error . "\n";
}

// Insert staff with correct role references
$staff_sql = "INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT001', 'ICT Director', 'ict.director@igangaschoolofnursingandmidwifery.ac.ug', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director ICT', 'Information Communication Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('LAB001', 'Computer Lab Manager', 'computerlab@igangaschoolofnursingandmidwifery.ac.ug', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Computer Lab Manager', 'Information Communication Technology', (SELECT id FROM staff_roles WHERE role_name = 'Computer Lab Manager'), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '\$2y\$10\$DorisJoy2026', 'Director General', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'Director General'), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '\$2y\$10\$Lovely2God', 'Chief Executive Officer', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'CEO'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE
    position = VALUES(position),
    department = VALUES(department),
    role_id = VALUES(role_id),
    status = 'Active',
    password = VALUES(password),
    updated_at = NOW()";

if ($conn->query($staff_sql) === TRUE) {
    echo "Staff accounts created/updated successfully.\n";
} else {
    echo "Error inserting staff: " . $conn->error . "\n";
}

// Insert system settings
$conn->query("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('school_name', 'Institute of Strategic Nursing and Midwifery', 'text', 'School name for display on documents', 'general', TRUE),
('school_phone', '+256 123 456 789', 'text', 'School phone number', 'general', TRUE),
('academic_year', '2025/2026', 'text', 'Current academic year', 'academic', TRUE),
('max_login_attempts', '5', 'number', 'Maximum login attempts before account lock', 'security', FALSE)");

echo "System settings inserted successfully.\n";

// Setup ICT database tables
$conn->select_db('igangaschoolofl_ict');

// Drop existing tables to ensure clean schema
$conn->query("DROP TABLE IF EXISTS staff_login_sessions");
$conn->query("DROP TABLE IF EXISTS staff_login_attempts");
$conn->query("DROP TABLE IF EXISTS staff_activity_log");
$conn->query("DROP TABLE IF EXISTS staff_permissions");
$conn->query("DROP TABLE IF EXISTS network_devices");
$conn->query("DROP TABLE IF EXISTS software_inventory");
$conn->query("DROP TABLE IF EXISTS it_support_tickets");
$conn->query("DROP TABLE IF EXISTS lab_bookings");
$conn->query("DROP TABLE IF EXISTS lab_computers");

$ict_tables = [
    "CREATE TABLE IF NOT EXISTS lab_computers (
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS lab_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_reference VARCHAR(50) UNIQUE NOT NULL,
        course_name VARCHAR(200) NOT NULL,
        instructor_name VARCHAR(100) NOT NULL,
        booking_date DATE NOT NULL,
        time_slot VARCHAR(50) NOT NULL,
        number_of_students INT DEFAULT 0,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS it_support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_number VARCHAR(50) UNIQUE NOT NULL,
        requester_name VARCHAR(100) NOT NULL,
        requester_type ENUM('staff', 'student') DEFAULT 'staff',
        requester_email VARCHAR(100),
        issue_type ENUM('hardware', 'software', 'network', 'login', 'other') DEFAULT 'other',
        priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        description TEXT NOT NULL,
        status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        assigned_to VARCHAR(100),
        resolved_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS software_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        software_name VARCHAR(200) NOT NULL,
        version VARCHAR(50),
        license_type ENUM('free', 'open_source', 'commercial', 'educational') DEFAULT 'educational',
        installed_on DATE,
        update_available BOOLEAN DEFAULT FALSE,
        last_updated DATE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS network_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_name VARCHAR(100) NOT NULL,
        device_type ENUM('router', 'switch', 'access_point', 'firewall', 'server', 'other') DEFAULT 'other',
        ip_address VARCHAR(45),
        mac_address VARCHAR(17),
        location VARCHAR(100),
        status ENUM('online', 'offline', 'maintenance') DEFAULT 'online',
        firmware_version VARCHAR(50),
        last_checked TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS staff_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        module VARCHAR(100) NOT NULL,
        permission_level ENUM('View', 'Edit', 'Admin', 'Super Admin') DEFAULT 'View',
        granted_by INT,
        granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_staff_module (staff_id, module)
    )",
    "CREATE TABLE IF NOT EXISTS staff_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        activity_description TEXT,
        module_accessed VARCHAR(100),
        ip_address VARCHAR(50),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_staff_id (staff_id),
        INDEX idx_created_at (created_at)
    )",
    "CREATE TABLE IF NOT EXISTS staff_login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        ip_address VARCHAR(50),
        user_agent TEXT,
        success BOOLEAN DEFAULT FALSE,
        failure_reason VARCHAR(100),
        staff_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_created_at (created_at)
    )",
    "CREATE TABLE IF NOT EXISTS staff_login_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        session_token VARCHAR(255) NOT NULL,
        ip_address VARCHAR(50),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        INDEX idx_staff_id (staff_id),
        INDEX idx_session_token (session_token),
        INDEX idx_expires_at (expires_at)
    )"
];

$ict_success = true;
foreach ($ict_tables as $table_sql) {
    if ($conn->query($table_sql) !== TRUE) {
        echo "Error creating ICT table: " . $conn->error . "\n";
        echo "SQL: $table_sql\n";
        $ict_success = false;
    }
}

// Add sample lab computers
$conn->query("INSERT IGNORE INTO lab_computers (computer_id, computer_name, location, status, ip_address, os_installed) VALUES ('LAB-PC-001', 'Workstation 1', 'Main Lab', 'online', '192.168.1.101', 'Windows 11')");
$conn->query("INSERT IGNORE INTO lab_computers (computer_id, computer_name, location, status, ip_address, os_installed) VALUES ('LAB-PC-002', 'Workstation 2', 'Main Lab', 'online', '192.168.1.102', 'Windows 11')");
$conn->query("INSERT IGNORE INTO lab_computers (computer_id, computer_name, location, status, ip_address, os_installed) VALUES ('LAB-PC-003', 'Workstation 3', 'Main Lab', 'offline', '192.168.1.103', 'Windows 11')");
$conn->query("INSERT IGNORE INTO lab_computers (computer_id, computer_name, location, status, ip_address, os_installed) VALUES ('LAB-PC-004', 'Workstation 4', 'Main Lab', 'online', '192.168.1.104', 'Windows 11')");
$conn->query("INSERT IGNORE INTO lab_computers (computer_id, computer_name, location, status, ip_address, os_installed) VALUES ('LAB-PC-005', 'Workstation 5', 'Main Lab', 'maintenance', '192.168.1.105', 'Windows 11')");

// Add sample software
$conn->query("INSERT IGNORE INTO software_inventory (software_name, version, license_type, installed_on) VALUES ('Microsoft Office 365', '2024', 'educational', '2024-01-15')");
$conn->query("INSERT IGNORE INTO software_inventory (software_name, version, license_type, installed_on) VALUES ('Visual Studio Code', '1.85', 'free', '2024-02-01')");
$conn->query("INSERT IGNORE INTO software_inventory (software_name, version, license_type, installed_on) VALUES ('Adobe Creative Suite', '2024', 'educational', '2024-01-20')");
$conn->query("INSERT IGNORE INTO software_inventory (software_name, version, license_type, installed_on) VALUES ('Antivirus Pro', '12.5', 'commercial', '2024-03-01')");

// Add sample network devices
$conn->query("INSERT IGNORE INTO network_devices (device_name, device_type, ip_address, mac_address, location, status) VALUES ('Main Router', 'router', '192.168.1.1', 'AA:BB:CC:DD:EE:01', 'Server Room', 'online')");
$conn->query("INSERT IGNORE INTO network_devices (device_name, device_type, ip_address, mac_address, location, status) VALUES ('Lab Switch', 'switch', '192.168.1.2', 'AA:BB:CC:DD:EE:02', 'Main Lab', 'online')");
$conn->query("INSERT IGNORE INTO network_devices (device_name, device_type, ip_address, mac_address, location, status) VALUES ('WiFi Access Point 1', 'access_point', '192.168.1.10', 'AA:BB:CC:DD:EE:10', 'Main Lab', 'online')");

$conn->close();

echo "\n=== SETUP COMPLETE ===\n";
echo "Accounts ready for login:\n";
echo "  Director ICT: ict.director@igangaschoolofnursingandmidwifery.ac.ug / Techno123\n";
echo "  Computer Lab Manager: computerlab@igangaschoolofnursingandmidwifery.ac.ug / LabManager123\n";
echo "\nNote: igangaschoolofl_ict database with lab tables has been created.\n";
echo "Both accounts are Active and linked to their correct roles.\n";
