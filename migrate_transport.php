<?php
/**
 * Run this once on production to create/fix transport tables for the driver dashboard.
 * Access it via browser: https://igangaschoolofnursingandmidwifery.ac.ug/migrate_transport.php
 * DELETE THIS FILE AFTER RUNNING.
 */
require_once __DIR__ . '/config/database.php';
$conn = isnm_mysqli_connect('Staff', STAFF_DB_HOST, STAFF_DB_USER, STAFF_DB_PASS, STAFF_DB_NAME, STAFF_DB_PORT, STAFF_DB_CHARSET);
if (!$conn) { die('DB connection failed'); }

function ensureColumn($conn, $table, $column, $definition) {
    $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($r && $r->num_rows === 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        return "added $column to $table";
    }
    return "$column already exists in $table";
}

$updates = [];

// 1. transport_vehicles
$conn->query("CREATE TABLE IF NOT EXISTS `transport_vehicles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT, `vehicle_number` VARCHAR(50) NOT NULL,
    `vehicle_type` VARCHAR(50) NOT NULL, `capacity` INT(11) DEFAULT 0,
    `fuel_type` VARCHAR(50) DEFAULT 'Diesel', `insurance_expiry` DATE DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'Available', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$updates[] = 'transport_vehicles: ensured';

// 2. transport_routes
$conn->query("CREATE TABLE IF NOT EXISTS `transport_routes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT, `route_name` VARCHAR(255) NOT NULL,
    `start_location` VARCHAR(255) DEFAULT NULL, `end_location` VARCHAR(255) DEFAULT NULL,
    `distance_km` DECIMAL(8,2) DEFAULT NULL, `estimated_duration_minutes` INT(11) DEFAULT 30,
    `route_type` VARCHAR(20) DEFAULT 'both', `fare_amount` DECIMAL(10,2) DEFAULT 0,
    `notes` TEXT, `status` VARCHAR(20) DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$updates[] = 'transport_routes: ensured';

// 3. transport_trips
$conn->query("CREATE TABLE IF NOT EXISTS `transport_trips` (
    `id` INT(11) NOT NULL AUTO_INCREMENT, `vehicle_id` INT(11) DEFAULT 0,
    `driver_id` INT(11) DEFAULT 0, `route_id` INT(11) DEFAULT 0,
    `route_name` VARCHAR(255) DEFAULT '', `departure_time` DATETIME DEFAULT NULL,
    `arrival_time` DATETIME DEFAULT NULL, `passengers_count` INT(11) DEFAULT 0,
    `fuel_cost` DECIMAL(10,2) DEFAULT 0, `trip_distance` DECIMAL(8,2) DEFAULT 0,
    `trip_fare` DECIMAL(10,2) DEFAULT 0, `notes` TEXT, `status` VARCHAR(20) DEFAULT 'Scheduled',
    `dg_approval_status` VARCHAR(20) DEFAULT 'none', `requested_by` INT(11) DEFAULT NULL,
    `dg_approved_by` INT(11) DEFAULT NULL, `dg_approved_at` DATETIME DEFAULT NULL,
    `rejection_reason` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$updates[] = 'transport_trips: ensured';

// 4. transport_student_assignments
$conn->query("CREATE TABLE IF NOT EXISTS `transport_student_assignments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) DEFAULT 0,
    `student_name` VARCHAR(255) DEFAULT '', `registration_number` VARCHAR(100) DEFAULT '',
    `route_id` INT(11) DEFAULT 0, `vehicle_id` INT(11) DEFAULT 0,
    `pickup_point` VARCHAR(255) DEFAULT '', `dropoff_point` VARCHAR(255) DEFAULT '',
    `academic_year` VARCHAR(20) DEFAULT '', `status` VARCHAR(20) DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$updates[] = 'transport_student_assignments: ensured';

// 5. transport_fuel_log
$conn->query("CREATE TABLE IF NOT EXISTS `transport_fuel_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT, `vehicle_id` INT(11) DEFAULT 0,
    `driver_id` INT(11) DEFAULT 0, `fuel_date` DATE DEFAULT NULL,
    `liters` DECIMAL(8,2) DEFAULT 0, `cost` DECIMAL(10,2) DEFAULT 0,
    `odometer_reading` DECIMAL(10,1) DEFAULT 0, `station` VARCHAR(255) DEFAULT '',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$updates[] = 'transport_fuel_log: ensured';

// Add missing columns to existing tables (idempotent)
try {
    $updates[] = ensureColumn($conn, 'transport_vehicles', 'vehicle_number', "VARCHAR(50) NOT NULL DEFAULT ''");
    $updates[] = ensureColumn($conn, 'transport_vehicles', 'insurance_expiry', "DATE DEFAULT NULL");
    $updates[] = ensureColumn($conn, 'transport_routes', 'estimated_duration_minutes', "INT(11) DEFAULT 30");
    $updates[] = ensureColumn($conn, 'transport_routes', 'route_type', "VARCHAR(20) DEFAULT 'both'");
    $updates[] = ensureColumn($conn, 'transport_routes', 'fare_amount', "DECIMAL(10,2) DEFAULT 0");
    $updates[] = ensureColumn($conn, 'transport_routes', 'notes', "TEXT");
    $updates[] = ensureColumn($conn, 'transport_trips', 'route_name', "VARCHAR(255) DEFAULT ''");
    $updates[] = ensureColumn($conn, 'transport_trips', 'passengers_count', "INT(11) DEFAULT 0");
    $updates[] = ensureColumn($conn, 'transport_trips', 'fuel_cost', "DECIMAL(10,2) DEFAULT 0");
    $updates[] = ensureColumn($conn, 'transport_trips', 'trip_distance', "DECIMAL(8,2) DEFAULT 0");
    $updates[] = ensureColumn($conn, 'transport_trips', 'trip_fare', "DECIMAL(10,2) DEFAULT 0");
    $updates[] = ensureColumn($conn, 'transport_trips', 'requested_by', "INT(11) DEFAULT NULL");
    $updates[] = ensureColumn($conn, 'transport_trips', 'rejection_reason', "TEXT");
    $updates[] = ensureColumn($conn, 'transport_trips', 'notes', "TEXT");
    $updates[] = ensureColumn($conn, 'transport_student_assignments', 'student_name', "VARCHAR(255) DEFAULT ''");
    $updates[] = ensureColumn($conn, 'transport_student_assignments', 'registration_number', "VARCHAR(100) DEFAULT ''");
    $updates[] = ensureColumn($conn, 'transport_student_assignments', 'route_id', "INT(11) DEFAULT 0");
    $updates[] = ensureColumn($conn, 'transport_student_assignments', 'vehicle_id', "INT(11) DEFAULT 0");
    $updates[] = ensureColumn($conn, 'transport_student_assignments', 'pickup_point', "VARCHAR(255) DEFAULT ''");
    $updates[] = ensureColumn($conn, 'transport_student_assignments', 'dropoff_point', "VARCHAR(255) DEFAULT ''");
    $updates[] = ensureColumn($conn, 'transport_student_assignments', 'academic_year', "VARCHAR(20) DEFAULT ''");
    $updates[] = ensureColumn($conn, 'transport_fuel_log', 'odometer_reading', "DECIMAL(10,1) DEFAULT 0");
    $updates[] = ensureColumn($conn, 'transport_fuel_log', 'station', "VARCHAR(255) DEFAULT ''");
} catch (Exception $e) {
    $updates[] = 'Warning: ' . $e->getMessage();
}

echo '<h2>Transport Tables Migration</h2>';
echo '<ul><li>' . implode('</li><li>', $updates) . '</li></ul>';
echo '<p style="color:green;font-weight:bold">All transport tables are ready for the Driver dashboard.</p>';
echo '<p><strong>DELETE THIS FILE AFTER RUNNING.</strong></p>';
