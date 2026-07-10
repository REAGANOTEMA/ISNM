<?php
require_once __DIR__ . '/config/database.php';
$conn = getICTConnection();
if (!$conn) { die("Connection failed: no ICT database connection"); }

$sql_statements = [
    "CREATE TABLE IF NOT EXISTS ict_asset_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );",

    "CREATE TABLE IF NOT EXISTS ict_assets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_number VARCHAR(100) UNIQUE NOT NULL,
        barcode VARCHAR(255) UNIQUE,
        qr_code VARCHAR(255) UNIQUE,
        serial_number VARCHAR(255) UNIQUE NOT NULL,
        brand VARCHAR(255),
        model VARCHAR(255),
        category_id INT,
        purchase_date DATE,
        warranty_expiry DATE,
        current_status ENUM('Active', 'In Maintenance', 'Retired', 'Transferred') DEFAULT 'Active',
        assigned_staff_id INT,
        assigned_department_id INT,
        current_location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES ict_asset_categories(id),
        FOREIGN KEY (assigned_staff_id) REFERENCES staff(id),
        FOREIGN KEY (assigned_department_id) REFERENCES departments(id)
    );",

    "CREATE TABLE IF NOT EXISTS ict_asset_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        assigned_to_staff_id INT,
        assigned_to_department_id INT,
        assignment_date DATE NOT NULL,
        return_date DATE,
        status ENUM('Assigned', 'Returned') DEFAULT 'Assigned',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES ict_assets(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to_staff_id) REFERENCES staff(id),
        FOREIGN KEY (assigned_to_department_id) REFERENCES departments(id)
    );",

    "CREATE TABLE IF NOT EXISTS ict_asset_maintenance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        maintenance_date DATE NOT NULL,
        description TEXT,
        cost DECIMAL(10, 2),
        performed_by_staff_id INT,
        status ENUM('Scheduled', 'InProgress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES ict_assets(id) ON DELETE CASCADE,
        FOREIGN KEY (performed_by_staff_id) REFERENCES staff(id)
    );",

    "CREATE TABLE IF NOT EXISTS ict_asset_warranty (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL UNIQUE,
        start_date DATE,
        end_date DATE,
        provider VARCHAR(255),
        terms TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES ict_assets(id) ON DELETE CASCADE
    );",

    "CREATE TABLE IF NOT EXISTS ict_software_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        software_name VARCHAR(255) UNIQUE NOT NULL,
        version VARCHAR(50),
        publisher VARCHAR(255),
        license_type VARCHAR(100),
        purchase_date DATE,
        expiry_date DATE,
        installation_guide TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );",

    "CREATE TABLE IF NOT EXISTS ict_software_licenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        software_id INT NOT NULL,
        license_key VARCHAR(255) UNIQUE NOT NULL,
        purchased_seats INT DEFAULT 1,
        assigned_to_staff_id INT,
        assigned_to_asset_id INT,
        activation_date DATE,
        deactivation_date DATE,
        status ENUM('Active', 'Expired', 'Revoked') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (software_id) REFERENCES ict_software_inventory(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to_staff_id) REFERENCES staff(id),
        FOREIGN KEY (assigned_to_asset_id) REFERENCES ict_assets(id)
    );",

    "CREATE TABLE IF NOT EXISTS ict_support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_number VARCHAR(100) UNIQUE NOT NULL,
        reported_by_user_id INT NOT NULL, -- Could be staff or student user ID
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
        status ENUM('Open', 'Assigned', 'InProgress', 'Resolved', 'Closed', 'Reopened') DEFAULT 'Open',
        category VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        closed_at TIMESTAMP NULL
    );",

    "CREATE TABLE IF NOT EXISTS ict_ticket_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        comment_by_user_id INT NOT NULL,
        comment_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES ict_support_tickets(id) ON DELETE CASCADE
    );",

    "CREATE TABLE IF NOT EXISTS ict_ticket_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        assigned_to_staff_id INT NOT NULL,
        assignment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        unassignment_date TIMESTAMP NULL,
        status ENUM('Assigned', 'Unassigned') DEFAULT 'Assigned',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES ict_support_tickets(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to_staff_id) REFERENCES staff(id)
    );",

    "CREATE TABLE IF NOT EXISTS ict_maintenance_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_number VARCHAR(100) UNIQUE NOT NULL,
        requested_by_user_id INT NOT NULL,
        asset_id INT,
        description TEXT NOT NULL,
        request_type VARCHAR(255),
        priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
        status ENUM('Pending', 'Approved', 'Rejected', 'InProgress', 'Completed') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        approved_by_director_id INT,
        approval_date TIMESTAMP NULL,
        FOREIGN KEY (asset_id) REFERENCES ict_assets(id) ON DELETE SET NULL,
        FOREIGN KEY (approved_by_director_id) REFERENCES staff(id) -- Assuming Director ICT is a staff member
    );",

    "CREATE TABLE IF NOT EXISTS ict_incidents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        incident_number VARCHAR(100) UNIQUE NOT NULL,
        reported_by_user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        severity ENUM('Minor', 'Major', 'Critical') DEFAULT 'Major',
        status ENUM('Open', 'Investigating', 'Resolved', 'Closed') DEFAULT 'Open',
        impacted_assets TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        closed_at TIMESTAMP NULL
    );",

    "CREATE TABLE IF NOT EXISTS ict_servers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_name VARCHAR(255) UNIQUE NOT NULL,
        ip_address VARCHAR(45) UNIQUE NOT NULL,
        os VARCHAR(255),
        cpu_cores INT,
        ram_gb INT,
        storage_gb INT,
        role VARCHAR(255),
        status ENUM('Online', 'Offline', 'Maintenance') DEFAULT 'Online',
        last_ping TIMESTAMP,
        uptime_seconds BIGINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );",

    "CREATE TABLE IF NOT EXISTS ict_network_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_name VARCHAR(255) UNIQUE NOT NULL,
        ip_address VARCHAR(45) UNIQUE,
        mac_address VARCHAR(17) UNIQUE,
        device_type ENUM('Router', 'Switch', 'Access Point', 'Firewall', 'Other') NOT NULL,
        brand VARCHAR(255),
        model VARCHAR(255),
        location VARCHAR(255),
        status ENUM('Active', 'Inactive', 'Faulty') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );",

    "CREATE TABLE IF NOT EXISTS ict_network_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        device_id INT,
        log_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        log_level VARCHAR(50),
        message TEXT,
        FOREIGN KEY (device_id) REFERENCES ict_network_devices(id) ON DELETE SET NULL
    );",

    "CREATE TABLE IF NOT EXISTS ict_wifi_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_name VARCHAR(255) UNIQUE NOT NULL,
        mac_address VARCHAR(17) UNIQUE NOT NULL,
        location VARCHAR(255),
        ssid VARCHAR(255),
        status ENUM('Active', 'Inactive', 'Maintenance') DEFAULT 'Active',
        last_connection TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );",

    "CREATE TABLE IF NOT EXISTS ict_system_backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        backup_name VARCHAR(255) NOT NULL,
        backup_type ENUM('Manual', 'Automatic', 'Cloud') NOT NULL,
        backup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        backup_size_mb DECIMAL(10, 2),
        backup_location TEXT NOT NULL,
        status ENUM('Success', 'Failed', 'InProgress') DEFAULT 'Success',
        created_by_staff_id INT,
        FOREIGN KEY (created_by_staff_id) REFERENCES staff(id)
    );",

    "CREATE TABLE IF NOT EXISTS ict_backup_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        backup_id INT,
        log_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        log_message TEXT NOT NULL,
        log_level ENUM('INFO', 'WARNING', 'ERROR') DEFAULT 'INFO',
        FOREIGN KEY (backup_id) REFERENCES ict_system_backups(id) ON DELETE CASCADE
    );",

    "CREATE TABLE IF NOT EXISTS ict_security_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        log_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        event_type VARCHAR(255) NOT NULL,
        user_id INT,
        ip_address VARCHAR(45),
        device_info TEXT,
        description TEXT NOT NULL
    );",

    "CREATE TABLE IF NOT EXISTS ict_failed_logins (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_agent TEXT
    );",

    "CREATE TABLE IF NOT EXISTS ict_login_sessions (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id VARCHAR(255) UNIQUE NOT NULL,
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        logout_time TIMESTAMP NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        status ENUM('Active', 'Expired', 'LoggedOut') DEFAULT 'Active'
    );",

    "CREATE TABLE IF NOT EXISTS ict_system_health (
        id INT AUTO_INCREMENT PRIMARY KEY,
        check_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        cpu_usage DECIMAL(5, 2),
        ram_usage DECIMAL(5, 2),
        disk_usage DECIMAL(5, 2),
        internet_status ENUM('Up', 'Down') DEFAULT 'Up',
        server_uptime_seconds BIGINT,
        active_sessions INT,
        failed_login_attempts INT,
        database_connections INT,
        overall_health_percentage DECIMAL(5, 2)
    );",

    "CREATE TABLE IF NOT EXISTS ict_system_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notification_type VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        target_user_id INT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (target_user_id) REFERENCES staff(id) -- Target could be staff or a specific role
    );",

    "CREATE TABLE IF NOT EXISTS ict_system_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alert_type VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        severity ENUM('Info', 'Warning', 'Critical') DEFAULT 'Warning',
        is_resolved BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        resolved_by_staff_id INT,
        FOREIGN KEY (resolved_by_staff_id) REFERENCES staff(id)
    );",

    "CREATE TABLE IF NOT EXISTS ict_system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );",

    "CREATE TABLE IF NOT EXISTS ict_module_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_id INT NOT NULL,
        module_name VARCHAR(255) NOT NULL,
        can_view BOOLEAN DEFAULT FALSE,
        can_create BOOLEAN DEFAULT FALSE,
        can_edit BOOLEAN DEFAULT FALSE,
        can_delete BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );",

    "CREATE TABLE IF NOT EXISTS ict_device_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );",

    "CREATE TABLE IF NOT EXISTS ict_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_name VARCHAR(255) NOT NULL,
        report_description TEXT,
        report_type ENUM('Daily', 'Weekly', 'Monthly', 'Annual', 'Custom') DEFAULT 'Custom',
        generated_by_staff_id INT,
        generated_file_path VARCHAR(255),
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (generated_by_staff_id) REFERENCES staff(id)
    );",

    "CREATE TABLE IF NOT EXISTS ict_audit_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        log_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id INT,
        action_type VARCHAR(255) NOT NULL,
        module VARCHAR(255),
        record_id INT,
        old_value TEXT,
        new_value TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        description TEXT,
        FOREIGN KEY (user_id) REFERENCES staff(id) -- Assuming all audited actions are by staff or system
    );"
];

foreach ($sql_statements as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "SQL query executed successfully: " . substr($sql, 0, 70) . "...\n";
    } else {
        echo "Error executing SQL query: " . $conn->error . " -- " . substr($sql, 0, 70) . "...\n";
    }
}

$conn->close();

?>
