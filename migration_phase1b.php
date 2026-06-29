<?php
$conn = new mysqli('localhost', 'root', '', 'igangaschoolofl_staffs_db', 3307);
if ($conn->connect_error) die('DB error: ' . $conn->connect_error);
$conn->autocommit(false);

$ok = 0; $fail = 0; $errors = [];
function run($conn, $sql, $label, &$ok, &$fail, &$errors) {
    if ($conn->query($sql)) { $ok++; echo "  OK: $label\n"; }
    else { $e = $conn->error; if (strpos($e, 'Duplicate') !== false || strpos($e, 'already exists') !== false) { $ok++; echo "  SKIP: $label (already exists)\n"; } else { $fail++; $errors[] = "$label: $e"; echo "  FAIL: $label — $e\n"; } }
}

echo "=== REMAINING MISSING COLUMNS ===\n";

// lab_practical_sessions — course_name, instructor_name
run($conn, "ALTER TABLE `lab_practical_sessions` ADD COLUMN `course_name` VARCHAR(200) DEFAULT NULL AFTER `title`", "lab_practical_sessions.course_name", $ok, $fail, $errors);
run($conn, "ALTER TABLE `lab_practical_sessions` ADD COLUMN `instructor_name` VARCHAR(200) DEFAULT NULL AFTER `course_name`", "lab_practical_sessions.instructor_name", $ok, $fail, $errors);

// payroll_overtime — all new columns
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

// leave_balance — remaining_days
run($conn, "ALTER TABLE `leave_balance` ADD COLUMN `remaining_days` INT DEFAULT NULL AFTER `used_days`", "leave_balance.remaining_days", $ok, $fail, $errors);

$conn->commit();

echo "\n=== RESULTS: $ok OK, $fail FAIL ===\n";
if ($errors) { echo "\nFAILURES:\n"; foreach ($errors as $e) echo "  - $e\n"; }

// Final table count
$r = $conn->query("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema='igangaschoolofl_staffs_db'");
echo "\nTotal tables in staffs_db: {$r->fetch_assoc()['c']}\n";
