<?php
/**
 * ISNM ERP — Phase 1 Critical Migration
 * Creates missing tables + adds missing columns
 * MariaDB 10.4.32 compatible
 */
$conn = new mysqli('localhost', 'root', '', 'igangaschoolofl_staffs_db', 3307);
if ($conn->connect_error) die('DB error: ' . $conn->connect_error);
$conn->autocommit(false);

$ok = 0; $fail = 0; $errors = [];

function run($conn, $sql, $label, &$ok, &$fail, &$errors) {
    if ($conn->query($sql)) { $ok++; echo "  OK: $label\n"; }
    else { $fail++; $errors[] = "$label: " . $conn->error; echo "  FAIL: $label — {$conn->error}\n"; }
}

echo "=== CREATING MISSING TABLES ===\n";

run($conn, "CREATE TABLE IF NOT EXISTS `payroll_bonus` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `payroll_employee_id` INT NOT NULL,
  `bonus_type` VARCHAR(50) NOT NULL DEFAULT 'one_time',
  `bonus_name` VARCHAR(150) NOT NULL DEFAULT '',
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `is_taxable` TINYINT(1) NOT NULL DEFAULT 0,
  `bonus_date` DATE DEFAULT NULL,
  `payroll_period_id` INT DEFAULT NULL,
  `status` ENUM('pending','approved','paid','rejected') NOT NULL DEFAULT 'pending',
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pb_employee` (`payroll_employee_id`),
  INDEX `idx_pb_period` (`payroll_period_id`),
  INDEX `idx_pb_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "payroll_bonus", $ok, $fail, $errors);

run($conn, "CREATE TABLE IF NOT EXISTS `payroll_periods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `period_code` VARCHAR(20) NOT NULL,
  `period_name` VARCHAR(100) NOT NULL,
  `frequency` VARCHAR(20) NOT NULL DEFAULT 'monthly',
  `month` INT NOT NULL,
  `year` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `payment_date` DATE DEFAULT NULL,
  `status` ENUM('draft','open','processing','processed','approved','closed') NOT NULL DEFAULT 'draft',
  `is_closed` TINYINT(1) NOT NULL DEFAULT 0,
  `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
  `closed_by` INT DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_period_code` (`period_code`),
  INDEX `idx_pp_status` (`status`),
  INDEX `idx_pp_year_month` (`year`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "payroll_periods", $ok, $fail, $errors);

run($conn, "CREATE TABLE IF NOT EXISTS `payroll_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `payroll_run_id` INT NOT NULL,
  `payroll_employee_id` INT NOT NULL,
  `staff_id` INT NOT NULL,
  `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_allowances` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_bonus` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_overtime` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_statutory_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paye_tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `nssf_employee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `nssf_employer` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `net_pay` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `bank_account` VARCHAR(50) DEFAULT NULL,
  `mobile_money` VARCHAR(50) DEFAULT NULL,
  `payment_method` VARCHAR(30) DEFAULT 'bank',
  `status` ENUM('active','inactive','cancelled') NOT NULL DEFAULT 'active',
  `payment_status` ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `payment_date` DATE DEFAULT NULL,
  `payment_reference` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pi_run` (`payroll_run_id`),
  INDEX `idx_pi_employee` (`payroll_employee_id`),
  INDEX `idx_pi_staff` (`staff_id`),
  INDEX `idx_pi_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "payroll_items", $ok, $fail, $errors);

run($conn, "CREATE TABLE IF NOT EXISTS `lab_equipment_checkout` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `equipment_id` INT NOT NULL,
  `checked_out_to` VARCHAR(150) NOT NULL,
  `borrower_type` ENUM('student','staff','other') NOT NULL DEFAULT 'student',
  `borrower_id` INT DEFAULT NULL,
  `checkout_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expected_return` DATE DEFAULT NULL,
  `actual_return` DATETIME DEFAULT NULL,
  `condition_at_return` VARCHAR(100) DEFAULT NULL,
  `checked_out_by` INT DEFAULT NULL,
  `status` ENUM('checked_out','returned','overdue') NOT NULL DEFAULT 'checked_out',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_lec_equipment` (`equipment_id`),
  INDEX `idx_lec_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "lab_equipment_checkout", $ok, $fail, $errors);

echo "\n=== ADDING MISSING COLUMNS ===\n";

// staff table
run($conn, "ALTER TABLE `staff` ADD COLUMN `reset_token` VARCHAR(255) DEFAULT NULL AFTER `locked_until`", "staff.reset_token", $ok, $fail, $errors);
run($conn, "ALTER TABLE `staff` ADD COLUMN `reset_expiry` DATETIME DEFAULT NULL AFTER `reset_token`", "staff.reset_expiry", $ok, $fail, $errors);

// store_requests
run($conn, "ALTER TABLE `store_requests` ADD COLUMN `fulfilled_by` INT UNSIGNED DEFAULT NULL AFTER `status`", "store_requests.fulfilled_by", $ok, $fail, $errors);
run($conn, "ALTER TABLE `store_requests` ADD COLUMN `fulfilled_at` DATETIME DEFAULT NULL AFTER `fulfilled_by`", "store_requests.fulfilled_at", $ok, $fail, $errors);
run($conn, "ALTER TABLE `store_requests` ADD COLUMN `forwarded_to_role` VARCHAR(100) DEFAULT NULL AFTER `forwarded_to`", "store_requests.forwarded_to_role", $ok, $fail, $errors);
run($conn, "ALTER TABLE `store_requests` ADD COLUMN `approved_at` DATETIME DEFAULT NULL AFTER `approved_by`", "store_requests.approved_at", $ok, $fail, $errors);
run($conn, "ALTER TABLE `store_requests` ADD COLUMN `rejection_reason` TEXT DEFAULT NULL AFTER `approved_at`", "store_requests.rejection_reason", $ok, $fail, $errors);

// security_incidents
run($conn, "ALTER TABLE `security_incidents` ADD COLUMN `incident_date` DATETIME DEFAULT NULL AFTER `description`", "security_incidents.incident_date", $ok, $fail, $errors);
run($conn, "ALTER TABLE `security_incidents` ADD COLUMN `severity` ENUM('Low','Medium','High','Critical') DEFAULT 'Medium' AFTER `incident_date`", "security_incidents.severity", $ok, $fail, $errors);
run($conn, "ALTER TABLE `security_incidents` ADD COLUMN `reported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `severity`", "security_incidents.reported_at", $ok, $fail, $errors);

// student_welfare_cases
run($conn, "ALTER TABLE `student_welfare_cases` ADD COLUMN `case_description` TEXT DEFAULT NULL AFTER `description`", "student_welfare_cases.case_description", $ok, $fail, $errors);
run($conn, "ALTER TABLE `student_welfare_cases` ADD COLUMN `immediate_actions` TEXT DEFAULT NULL AFTER `case_description`", "student_welfare_cases.immediate_actions", $ok, $fail, $errors);
run($conn, "ALTER TABLE `student_welfare_cases` ADD COLUMN `severity` VARCHAR(20) DEFAULT 'medium' AFTER `immediate_actions`", "student_welfare_cases.severity", $ok, $fail, $errors);
run($conn, "ALTER TABLE `student_welfare_cases` ADD COLUMN `reported_by` INT UNSIGNED DEFAULT NULL AFTER `severity`", "student_welfare_cases.reported_by", $ok, $fail, $errors);

// lab_attendance
run($conn, "ALTER TABLE `lab_attendance` ADD COLUMN `student_name` VARCHAR(200) DEFAULT NULL AFTER `student_id`", "lab_attendance.student_name", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_attendance` ADD COLUMN `session` VARCHAR(100) DEFAULT NULL AFTER `student_name`", "lab_attendance.session", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_attendance` ADD COLUMN `attendance_status` VARCHAR(20) DEFAULT 'present' AFTER `status`", "lab_attendance.attendance_status", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_attendance` ADD COLUMN `check_in_time` TIME DEFAULT NULL AFTER `attendance_status`", "lab_attendance.check_in_time", $ok, $fail, $errors);

// lab_practical_sessions
run($conn, "ALTER TABLE `lab_practical_sessions` ADD COLUMN `title` VARCHAR(200) DEFAULT NULL AFTER `session_code`", "lab_practical_sessions.title", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_practical_sessions` ADD COLUMN `course_name` VARCHAR(200) DEFAULT NULL AFTER `title`", "lab_practical_sessions.course_name", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_practical_sessions` ADD COLUMN `instructor_name` VARCHAR(200) DEFAULT NULL AFTER `course_name`", "lab_practical_sessions.instructor_name", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_practical_sessions` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `instructor_name`", "lab_practical_sessions.description", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_practical_sessions` ADD COLUMN `instructor` VARCHAR(200) DEFAULT NULL AFTER `description`", "lab_practical_sessions.instructor", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_practical_sessions` ADD COLUMN `year_level` VARCHAR(20) DEFAULT NULL AFTER `year`", "lab_practical_sessions.year_level", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_practical_sessions` ADD COLUMN `location` VARCHAR(200) DEFAULT NULL AFTER `end_time`", "lab_practical_sessions.location", $ok, $fail, $errors);

// payroll_overtime — add new columns, keep old for bursar-payroll.php compatibility
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `payroll_employee_id` INT DEFAULT NULL AFTER `id`", "payroll_overtime.payroll_employee_id", $ok, $fail, $errors);
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `overtime_type` VARCHAR(20) DEFAULT 'normal' AFTER `payroll_employee_id`", "payroll_overtime.overtime_type", $ok, $fail, $errors);
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `hours_worked` DECIMAL(8,2) DEFAULT 0.00 AFTER `overtime_type`", "payroll_overtime.hours_worked", $ok, $fail, $errors);
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `rate_multiplier` DECIMAL(4,2) DEFAULT 1.50 AFTER `hours_worked`", "payroll_overtime.rate_multiplier", $ok, $fail, $errors);
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `hourly_rate` DECIMAL(10,2) DEFAULT 0.00 AFTER `rate_multiplier`", "payroll_overtime.hourly_rate", $ok, $fail, $errors);
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `overtime_date` DATE DEFAULT NULL AFTER `hourly_rate`", "payroll_overtime.overtime_date", $ok, $fail, $errors);
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `overtime_date`", "payroll_overtime.description", $ok, $fail, $errors);
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `status` VARCHAR(20) DEFAULT 'pending' AFTER `description`", "payroll_overtime.status", $ok, $fail, $errors);
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `total_amount` DECIMAL(12,2) DEFAULT 0.00 AFTER `status`", "payroll_overtime.total_amount", $ok, $fail, $errors);
run($conn, "ALTER TABLE `payroll_overtime` ADD COLUMN `payroll_period_id` INT DEFAULT NULL AFTER `total_amount`", "payroll_overtime.payroll_period_id", $ok, $fail, $errors);

// Fix student_welfare_cases — add missing column that some dashboards query
run($conn, "ALTER TABLE `student_welfare_cases` ADD COLUMN `assigned_to` INT DEFAULT NULL AFTER `reported_by`", "student_welfare_cases.assigned_to", $ok, $fail, $errors);

// Leave balance — add remaining_days if missing (some code uses it)
run($conn, "ALTER TABLE `leave_balance` ADD COLUMN `remaining_days` INT DEFAULT NULL AFTER `used_days`", "leave_balance.remaining_days", $ok, $fail, $errors);

$conn->commit();

echo "\n=== RESULTS: $ok OK, $fail FAIL ===\n";
if ($errors) {
    echo "\nFAILURES:\n";
    foreach ($errors as $e) echo "  - $e\n";
}

echo "\n=== VERIFICATION ===\n";
$newTables = ['payroll_bonus', 'payroll_periods', 'payroll_items', 'lab_equipment_checkout'];
foreach ($newTables as $t) {
    $r = $conn->query("SELECT COUNT(*) c FROM `$t`");
    echo "$t: " . ($r ? $r->fetch_assoc()['c'] . " rows" : "ERROR") . "\n";
}
