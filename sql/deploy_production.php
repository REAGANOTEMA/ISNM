<?php
/**
 * ISNM Production Deployment Script
 * 
 * USAGE:
 *   1. Update DB credentials below for your production server
 *   2. Upload this file to your hosting server
 *   3. Access via browser or CLI: php deploy_production.php
 *   4. Delete after use (contains credentials!)
 * 
 * Creates all missing tables, columns, FKs, and indexes.
 * Safe to run multiple times (uses IF NOT EXISTS / IGNORE).
 */

// ===== SET YOUR PRODUCTION CREDENTIALS HERE =====
$db_host = 'localhost';
$db_port = 3306;
$db_name = 'igangaschoolofl_staffs_db';
$db_user = 'your_db_user';
$db_pass = 'your_db_password';

// ===== DO NOT EDIT BELOW THIS LINE =====

echo "ISNM Production Deployment Script\n";
echo "==================================\n\n";

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error . "\n");
echo "Connected: " . $conn->server_info . "\n\n";

$db = $db_name;

// ---- Helper functions ----
function tableExists($conn, $name) {
    global $db;
    $r = $conn->query("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$name'");
    return $r && $r->num_rows > 0;
}

function colExists($conn, $table, $col) {
    global $db;
    $r = $conn->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table' AND COLUMN_NAME='$col'");
    return $r && $r->num_rows > 0;
}

function idxExists($conn, $table, $idx) {
    global $db;
    $r = $conn->query("SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table' AND INDEX_NAME='$idx'");
    return $r && $r->num_rows > 0;
}

function addCol($conn, $table, $col, $def) {
    if (colExists($conn, $table, $col)) { echo "  [OK] $table.$col\n"; return; }
    if ($conn->query("ALTER TABLE $table ADD COLUMN $def")) {
        echo "  [+ ] $table.$col\n";
    } else { echo "  [!!] $table.$col: " . $conn->error . "\n"; }
}

function addIdx($conn, $table, $idx, $def) {
    if (idxExists($conn, $table, $idx)) { echo "  [OK] $table.$idx\n"; return; }
    if ($conn->query("ALTER TABLE $table ADD $def")) {
        echo "  [+ ] $table.$idx\n";
    } else { echo "  [!!] $table.$idx: " . $conn->error . "\n"; }
}

function addFK($conn, $table, $fk, $def) {
    if (idxExists($conn, $table, $fk)) { echo "  [OK] $table.$fk\n"; return; }
    // Check if FK already exists
    global $db;
    $r = $conn->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='$db' AND TABLE_NAME='$table' AND CONSTRAINT_NAME='$fk'");
    if ($r && $r->num_rows > 0) { echo "  [OK] $table.$fk (constraint exists)\n"; return; }
    if ($conn->query("ALTER TABLE $table ADD CONSTRAINT $fk $def")) {
        echo "  [+ ] $table.$fk\n";
    } else { echo "  [!!] $table.$fk: " . $conn->error . "\n"; }
}

function createTable($conn, $name, $sql) {
    if (tableExists($conn, $name)) { echo "  [OK] $name\n"; return true; }
    if ($conn->query($sql)) { echo "  [+ ] $name\n"; return true; }
    echo "  [!!] $name: " . $conn->error . "\n";
    return false;
}

// ===== 1. PAYROLL EMPLOYEE COLUMNS =====
echo "=== Payroll employee columns ===\n";
if (tableExists($conn, 'payroll_employees')) {
    addCol($conn, 'payroll_employees', 'salary_type', "salary_type ENUM('monthly','annual') DEFAULT 'monthly' AFTER nssf_number");
    addCol($conn, 'payroll_employees', 'salary_grade', "salary_grade VARCHAR(50) NULL AFTER salary_type");
}
if (tableExists($conn, 'payroll_runs')) {
    addCol($conn, 'payroll_runs', 'start_date', "start_date DATE NULL AFTER period");
    addCol($conn, 'payroll_runs', 'end_date', "end_date DATE NULL AFTER start_date");
}
if (tableExists($conn, 'payslips')) {
    addCol($conn, 'payslips', 'pdf_path', "pdf_path VARCHAR(255) NULL AFTER payment_ref");
}

// ===== 2. ROLES TABLE SEED =====
echo "\n=== Seed roles table ===\n";
if (tableExists($conn, 'roles') && tableExists($conn, 'staff_roles')) {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM roles");
    $roleCount = $r ? $r->fetch_assoc()['cnt'] : 0;
    echo "  roles table has $roleCount rows\n";
    if ($roleCount == 0) {
        $conn->query("INSERT IGNORE INTO roles (id, name, description, created_at) SELECT id, role_name, role_description, created_at FROM staff_roles");
        echo "  Seeded " . $conn->affected_rows . " roles\n";
    }
} else {
    echo "  [SKIP] roles or staff_roles table missing\n";
}

// ===== 3. HR FOREIGN KEYS =====
echo "\n=== HR Foreign Keys ===\n";
$hrFKs = [
    ['leave_requests', 'fk_leave_type', "FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE SET NULL"],
    ['leave_balances', 'fk_bal_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['leave_balances', 'fk_bal_type', "FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE"],
    ['professional_licenses', 'fk_lic_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['performance_reviews', 'fk_perf_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['performance_reviews', 'fk_perf_reviewer', "FOREIGN KEY (reviewed_by) REFERENCES staff(id) ON DELETE SET NULL"],
    ['kpi_entries', 'fk_kpi_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['training_records', 'fk_train_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['job_vacancies', 'fk_vac_dept', "FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE"],
    ['job_applications', 'fk_app_vac', "FOREIGN KEY (vacancy_id) REFERENCES job_vacancies(id) ON DELETE CASCADE"],
    ['disciplinary_records', 'fk_disc_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['incident_reports', 'fk_inc_staff', "FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL"],
    ['rotation_schedule', 'fk_rot_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['rotation_schedule', 'fk_rot_supervisor', "FOREIGN KEY (supervisor_id) REFERENCES staff(id) ON DELETE SET NULL"],
    ['salary_history', 'fk_sal_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
];
foreach ($hrFKs as $fk) {
    if (tableExists($conn, $fk[0])) {
        addFK($conn, $fk[0], $fk[1], $fk[2]);
    }
}

// ===== 4. PAYROLL FOREIGN KEYS =====
echo "\n=== Payroll Foreign Keys ===\n";
$payFKs = [
    ['payroll_employees', 'fk_pe_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['payroll_details', 'fk_pd_run', "FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE"],
    ['payroll_details', 'fk_pd_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['payroll_allowances', 'fk_pa_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['payroll_deductions', 'fk_pd_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['payroll_overtime', 'fk_po_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['payroll_bonuses', 'fk_pb_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['payslips', 'fk_ps_staff', "FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE"],
    ['payslips', 'fk_ps_run', "FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id) ON DELETE SET NULL"],
    ['payroll_approvals', 'fk_papr_run', "FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE"],
];
foreach ($payFKs as $fk) {
    if (tableExists($conn, $fk[0])) {
        addFK($conn, $fk[0], $fk[1], $fk[2]);
    }
}
// Payroll approvals unique key
if (tableExists($conn, 'payroll_approvals')) {
    addIdx($conn, 'payroll_approvals', 'uq_run_level', 'UNIQUE INDEX uq_run_level (payroll_run_id, level)');
}

// ===== 5. PAYSLIP INDEXES =====
echo "\n=== Payslip indexes ===\n";
if (tableExists($conn, 'payslips')) {
    addIdx($conn, 'payslips', 'idx_payslip_run', 'INDEX idx_payslip_run (payroll_run_id)');
    addIdx($conn, 'payslips', 'idx_payslip_detail', 'INDEX idx_payslip_detail (payroll_detail_id)');
}

// ===== 6. HR INDEXES =====
echo "\n=== HR Indexes ===\n";
$hrIdxs = [
    ['staff', 'idx_staff_role', 'INDEX idx_staff_role (role_id)'],
    ['staff', 'idx_staff_dept', 'INDEX idx_staff_department (department_id)'],
    ['staff', 'idx_staff_status', 'INDEX idx_staff_status (status)'],
    ['leave_requests', 'idx_lr_staff', 'INDEX idx_lr_staff (staff_id)'],
    ['leave_requests', 'idx_lr_status', 'INDEX idx_lr_status (status)'],
    ['attendance', 'idx_att_staff', 'INDEX idx_att_staff (staff_id)'],
    ['attendance', 'idx_att_date', 'INDEX idx_att_date (attendance_date)'],
    ['job_vacancies', 'idx_jv_status', 'INDEX idx_jv_status (status)'],
    ['job_applications', 'idx_ja_vacancy', 'INDEX idx_ja_vacancy (vacancy_id)'],
    ['performance_reviews', 'idx_perf_staff', 'INDEX idx_perf_staff (staff_id)'],
    ['training_records', 'idx_train_staff', 'INDEX idx_train_staff (staff_id)'],
    ['disciplinary_records', 'idx_disc_staff', 'INDEX idx_disc_staff (staff_id)'],
];
foreach ($hrIdxs as $i) {
    $tbl = $i[0];
    if (tableExists($conn, $tbl)) {
        addIdx($conn, $tbl, $i[1], $i[2]);
    }
}

$conn->close();
echo "\n=== Deployment complete ===\n";
