<?php
/**
 * Migration Script: School Secretary Module Tables
 * Creates all required tables for the enhanced School Secretary dashboard.
 * Run this script once after deployment or when adding new secretary modules.
 *
 * Usage from CLI:    php db_migrate_secretary_tables.php
 * Usage from browser: https://yourdomain.com/db_migrate_secretary_tables.php
 */

// Load database config from .env
$configPath = __DIR__ . '/config/database.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

// Read credentials from config constants or fall back to defaults
$db_host   = defined('STAFF_DB_HOST') ? STAFF_DB_HOST : 'localhost';
$db_user   = defined('STAFF_DB_USER') ? STAFF_DB_USER : 'root';
$db_pass   = defined('STAFF_DB_PASS') ? STAFF_DB_PASS : '';
$db_name   = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschoolofl_staffs_db';
$db_port   = defined('STAFF_DB_PORT') ? STAFF_DB_PORT : 3306;

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    // Try connecting without selecting a database, then create it
    $conn = @new mysqli($db_host, $db_user, $db_pass, '', $db_port);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($db_name);
    if ($conn->error) {
        die("Could not create/select database '$db_name': " . $conn->error);
    }
}

echo "Connected to database: " . $conn->host_info . "\n";
echo "Database: " . $dbname . "\n\n";

$sql_statements = [

    // ── Appointments ──
    "CREATE TABLE IF NOT EXISTS secretary_appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        location VARCHAR(255),
        status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS secretary_meetings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        meeting_date DATE NOT NULL,
        meeting_time TIME NOT NULL,
        duration_minutes INT DEFAULT 60,
        location VARCHAR(255),
        venue VARCHAR(255),
        meeting_type VARCHAR(100) DEFAULT 'general',
        attendees TEXT,
        organizer VARCHAR(255),
        minutes TEXT,
        outcome TEXT,
        status ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
        is_recurring TINYINT(1) DEFAULT 0,
        recurrence_pattern VARCHAR(50),
        reminder_sent TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS secretary_meeting_agenda (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meeting_id INT NOT NULL,
        topic VARCHAR(500) NOT NULL,
        presenter VARCHAR(255),
        duration_minutes INT DEFAULT 0,
        display_order INT DEFAULT 0,
        notes TEXT,
        status ENUM('pending','discussed','deferred','cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (meeting_id) REFERENCES secretary_meetings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS secretary_meeting_action_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meeting_id INT NOT NULL,
        agenda_id INT DEFAULT NULL,
        action TEXT NOT NULL,
        assigned_to VARCHAR(255),
        assigned_to_id INT DEFAULT NULL,
        due_date DATE,
        priority ENUM('low','medium','high','critical') DEFAULT 'medium',
        status ENUM('open','in_progress','completed','overdue') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (meeting_id) REFERENCES secretary_meetings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ── Official Documents ──
    "CREATE TABLE IF NOT EXISTS secretary_official_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        doc_type ENUM('letter','memo','circular','notice','report','minutes','certificate','form','proposal','other') DEFAULT 'letter',
        title VARCHAR(500) NOT NULL,
        reference_number VARCHAR(100),
        subject VARCHAR(500),
        content LONGTEXT,
        department VARCHAR(255),
        category VARCHAR(100),
        status ENUM('draft','review','approved','published','archived','rejected') DEFAULT 'draft',
        priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
        recipient_name VARCHAR(255),
        recipient_organization VARCHAR(255),
        file_path VARCHAR(500),
        file_name VARCHAR(255),
        file_size INT DEFAULT 0,
        mime_type VARCHAR(100),
        is_confidential TINYINT(1) DEFAULT 0,
        reviewed_by INT DEFAULT NULL,
        reviewed_at TIMESTAMP NULL,
        approved_by INT DEFAULT NULL,
        approved_at TIMESTAMP NULL,
        published_by INT DEFAULT NULL,
        published_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ── Messages (Internal Comms) ──
    "CREATE TABLE IF NOT EXISTS secretary_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        recipient_id INT NOT NULL,
        subject VARCHAR(255),
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ── Internal Requests ──
    "CREATE TABLE IF NOT EXISTS secretary_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        request_type VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        priority ENUM('low','medium','high') DEFAULT 'medium',
        status ENUM('pending','in_progress','resolved','rejected') DEFAULT 'pending',
        assigned_to INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ── Announcements ──
    "CREATE TABLE IF NOT EXISTS secretary_announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        target_audience VARCHAR(100) DEFAULT 'all',
        is_active TINYINT(1) DEFAULT 1,
        publish_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ── Contacts ──
    "CREATE TABLE IF NOT EXISTS secretary_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        contact_name VARCHAR(255) NOT NULL,
        contact_email VARCHAR(255),
        contact_phone VARCHAR(50),
        organization VARCHAR(255),
        category VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ── Correspondence ──
    "CREATE TABLE IF NOT EXISTS secretary_correspondence (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('incoming','outgoing') NOT NULL DEFAULT 'outgoing',
        subject VARCHAR(255) NOT NULL,
        content TEXT,
        recipient_name VARCHAR(255),
        recipient_email VARCHAR(255),
        recipient_phone VARCHAR(50),
        category VARCHAR(100),
        status ENUM('draft','sent','received','archived') DEFAULT 'draft',
        reference_number VARCHAR(100),
        attachment_path VARCHAR(500),
        sent_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

];

$success = 0;
$errors = 0;

foreach ($sql_statements as $sql) {
    if ($conn->query($sql) === TRUE) {
        $tableName = '';
        preg_match('/CREATE TABLE IF NOT EXISTS (\S+)/', $sql, $matches);
        if (isset($matches[1])) {
            $tableName = $matches[1];
        }
        echo "  [OK] Created table: $tableName\n";
        $success++;
    } else {
        $tableName = '';
        preg_match('/CREATE TABLE IF NOT EXISTS (\S+)/', $sql, $matches);
        if (isset($matches[1])) {
            $tableName = $matches[1];
        }
        echo "  [FAIL] $tableName: " . $conn->error . "\n";
        $errors++;
    }
}

// ── Handle ALTER TABLE for existing secretary_meetings ──
echo "\n--- Checking secretary_meetings columns ---\n";
$colCheck = $conn->query("SHOW COLUMNS FROM secretary_meetings LIKE 'venue'");
if ($colCheck && $colCheck->num_rows === 0) {
    $alterSQL = [
        "ALTER TABLE secretary_meetings ADD COLUMN venue VARCHAR(255) AFTER location",
        "ALTER TABLE secretary_meetings ADD COLUMN meeting_type VARCHAR(100) DEFAULT 'general' AFTER location",
        "ALTER TABLE secretary_meetings ADD COLUMN organizer VARCHAR(255) AFTER attendees",
        "ALTER TABLE secretary_meetings ADD COLUMN minutes TEXT AFTER attendees",
        "ALTER TABLE secretary_meetings ADD COLUMN outcome TEXT AFTER minutes",
        "ALTER TABLE secretary_meetings ADD COLUMN is_recurring TINYINT(1) DEFAULT 0 AFTER status",
        "ALTER TABLE secretary_meetings ADD COLUMN recurrence_pattern VARCHAR(50) AFTER is_recurring",
        "ALTER TABLE secretary_meetings ADD COLUMN reminder_sent TINYINT(1) DEFAULT 0 AFTER recurrence_pattern",
    ];
    foreach ($alterSQL as $sql) {
        if ($conn->query($sql) === TRUE) {
            $col = substr($sql, strpos($sql, 'ADD COLUMN') + 11, strpos($sql, 'AFTER') === false ? 999 : strpos($sql, 'AFTER') - strpos($sql, 'ADD COLUMN') - 11);
            echo "  [OK] Added column: " . trim($col) . "\n";
            $success++;
        } else {
            echo "  [INFO] " . $conn->error . "\n";
        }
    }
} else {
    echo "  [OK] Columns already exist.\n";
}

echo "\n========================================\n";
echo "Migration complete.\n";
echo "  Successful: $success\n";
echo "  Errors: $errors\n";
echo "========================================\n";

$conn->close();
