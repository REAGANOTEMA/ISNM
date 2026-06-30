<?php
/**
 * ISNM Production Deployment Script
 *
 * Handles ALL 4 databases: staffs, students, website, ICT.
 * Adds missing columns, indexes, and foreign keys via INFORMATION_SCHEMA.
 * Safe to run multiple times.
 *
 * USAGE:
 *   1. Ensure .env has all DB_* / STUDENTS_DB_* / STAFF_DB_* / WEBSITE_DB_* / ICT_DB_* vars
 *   2. Upload to hosting and run: php deploy_production.php
 *   3. Delete after use
 */

// ---- Bootstrap from .env (same approach as config/database.php) ----
if (!function_exists('isnm_env')) {
    function isnm_env(string $key, $default = null) {
        $value = getenv($key);
        if ($value === false && isset($_ENV[$key])) $value = $_ENV[$key];
        if ($value === false && isset($_SERVER[$key])) $value = $_SERVER[$key];
        return $value === false ? $default : $value;
    }
}
if (!function_exists('isnm_load_env')) {
    function isnm_load_env(string $path): void {
        if (!is_file($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                $value = substr($value, 1, -1);
            }
            if ($key !== '') {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv($key . '=' . $value);
            }
        }
    }
}
isnm_load_env(__DIR__ . '/../.env');

// ---- Helper functions ----
function connFromEnv(string $label, string $hostKey, string $userKey, string $passKey, string $nameKey, string $portKey, string $charsetKey) {
    $host = isnm_env($hostKey, 'localhost');
    $host = ($host === 'localhost') ? '127.0.0.1' : $host;
    $user = isnm_env($userKey, 'root');
    $pass = isnm_env($passKey, '');
    $name = isnm_env($nameKey, '');
    $port = (int) isnm_env($portKey, 3306);
    $charset = isnm_env($charsetKey, 'utf8mb4');
    $ports = array_values(array_unique(array_filter([$port, 3306, 3307])));
    $errors = [];
    foreach ($ports as $tryPort) {
        $conn = @new mysqli($host, $user, $pass, $name, $tryPort);
        if (!$conn->connect_error) {
            $conn->set_charset($charset);
            echo "  Connected to $label ($name) on port $tryPort\n";
            return $conn;
        }
        $errors[] = "$tryPort: " . $conn->connect_error;
    }
    die("  [!!] $label DB connection failed: " . implode(' | ', $errors) . "\n");
}

function tableExists($conn, $name) {
    $db = $conn->real_escape_string($conn->query("SELECT DATABASE() as db")->fetch_assoc()['db']);
    $name = $conn->real_escape_string($name);
    $r = $conn->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$name'");
    return $r && $r->num_rows > 0;
}

function colExists($conn, $table, $col) {
    $db = $conn->real_escape_string($conn->query("SELECT DATABASE() as db")->fetch_assoc()['db']);
    $table = $conn->real_escape_string($table);
    $col = $conn->real_escape_string($col);
    $r = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table' AND COLUMN_NAME='$col'");
    return $r && $r->num_rows > 0;
}

function idxExists($conn, $table, $idx) {
    $db = $conn->real_escape_string($conn->query("SELECT DATABASE() as db")->fetch_assoc()['db']);
    $table = $conn->real_escape_string($table);
    $idx = $conn->real_escape_string($idx);
    $r = $conn->query("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table' AND INDEX_NAME='$idx'");
    return $r && $r->num_rows > 0;
}

function fkExists($conn, $table, $fk) {
    $db = $conn->real_escape_string($conn->query("SELECT DATABASE() as db")->fetch_assoc()['db']);
    $table = $conn->real_escape_string($table);
    $fk = $conn->real_escape_string($fk);
    $r = $conn->query("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='$db' AND TABLE_NAME='$table' AND CONSTRAINT_NAME='$fk' AND CONSTRAINT_TYPE='FOREIGN KEY'");
    return $r && $r->num_rows > 0;
}

function addCol($conn, $table, $col, $def) {
    if (colExists($conn, $table, $col)) { echo "  [OK] $table.$col\n"; return; }
    $table = $conn->real_escape_string($table);
    if ($conn->query("ALTER TABLE $table ADD COLUMN $def")) {
        echo "  [+ ] $table.$col\n";
    } else { echo "  [!!] $table.$col: " . $conn->error . "\n"; }
}

function addIdx($conn, $table, $idx, $def) {
    if (idxExists($conn, $table, $idx)) { echo "  [OK] $table.$idx\n"; return; }
    $table = $conn->real_escape_string($table);
    if ($conn->query("ALTER TABLE $table ADD $def")) {
        echo "  [+ ] $table.$idx\n";
    } else { echo "  [!!] $table.$idx: " . $conn->error . "\n"; }
}

function addFK($conn, $table, $fk, $def) {
    if (fkExists($conn, $table, $fk)) { echo "  [OK] $table.$fk (constraint exists)\n"; return; }
    $table = $conn->real_escape_string($table);
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

echo "======================================================\n";
echo "  ISNM Production Deployment Script (ALL 4 DATABASES)\n";
echo "======================================================\n\n";

// ====================================================================
// 1. STAFF DB  (igangaschoolofl_staffs_db)
// ====================================================================
echo "============================================================\n";
echo "  DATABASE 1: igangaschoolofl_staffs_db\n";
echo "============================================================\n";
$staffConn = connFromEnv('Staff', 'STAFF_DB_HOST', 'STAFF_DB_USER', 'STAFF_DB_PASS', 'STAFF_DB_NAME', 'STAFF_DB_PORT', 'STAFF_DB_CHARSET');

// --- 1A. Payroll employee columns ---
echo "\n--- Payroll columns ---\n";
if (tableExists($staffConn, 'payroll_employees')) {
    addCol($staffConn, 'payroll_employees', 'salary_type', "salary_type ENUM('monthly','annual') DEFAULT 'monthly' AFTER nssf_number");
    addCol($staffConn, 'payroll_employees', 'salary_grade', "salary_grade VARCHAR(50) NULL AFTER salary_type");
}
if (tableExists($staffConn, 'payroll_runs')) {
    addCol($staffConn, 'payroll_runs', 'start_date', "start_date DATE NULL AFTER period");
    addCol($staffConn, 'payroll_runs', 'end_date', "end_date DATE NULL AFTER start_date");
}
if (tableExists($staffConn, 'payslips')) {
    addCol($staffConn, 'payslips', 'pdf_path', "pdf_path VARCHAR(255) NULL AFTER payment_ref");
}

// --- 1B. Roles table seed ---
echo "\n--- Seed roles table ---\n";
if (tableExists($staffConn, 'roles') && tableExists($staffConn, 'staff_roles')) {
    $r = $staffConn->query("SELECT COUNT(*) as cnt FROM roles");
    $roleCount = $r ? $r->fetch_assoc()['cnt'] : 0;
    echo "  roles table has $roleCount rows\n";
    if ($roleCount == 0) {
        $staffConn->query("INSERT IGNORE INTO roles (id, name, description, created_at) SELECT id, role_name, role_description, created_at FROM staff_roles");
        echo "  Seeded " . $staffConn->affected_rows . " roles\n";
    }
} else {
    echo "  [SKIP] roles or staff_roles table missing\n";
}

// --- 1C. HR Foreign Keys ---
echo "\n--- HR Foreign Keys ---\n";
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
    if (tableExists($staffConn, $fk[0])) {
        addFK($staffConn, $fk[0], $fk[1], $fk[2]);
    }
}

// --- 1D. Payroll Foreign Keys ---
echo "\n--- Payroll Foreign Keys ---\n";
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
    if (tableExists($staffConn, $fk[0])) {
        addFK($staffConn, $fk[0], $fk[1], $fk[2]);
    }
}
if (tableExists($staffConn, 'payroll_approvals')) {
    addIdx($staffConn, 'payroll_approvals', 'uq_run_level', 'UNIQUE INDEX uq_run_level (payroll_run_id, level)');
}

// --- 1E. Payslip indexes ---
echo "\n--- Payslip indexes ---\n";
if (tableExists($staffConn, 'payslips')) {
    addIdx($staffConn, 'payslips', 'idx_payslip_run', 'INDEX idx_payslip_run (payroll_run_id)');
    addIdx($staffConn, 'payslips', 'idx_payslip_detail', 'INDEX idx_payslip_detail (payroll_detail_id)');
}

// --- 1F. HR Indexes ---
echo "\n--- HR Indexes ---\n";
$hrIdxs = [
    ['staff', 'idx_staff_role', 'INDEX idx_staff_role (role_id)'],
    ['staff', 'idx_staff_dept', 'INDEX idx_staff_department (department_id)'],
    ['staff', 'idx_staff_status', 'INDEX idx_staff_status (status)'],
    ['staff', 'idx_staff_email', 'INDEX idx_staff_email (email)'],
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
    if (tableExists($staffConn, $i[0])) {
        addIdx($staffConn, $i[0], $i[1], $i[2]);
    }
}

$staffConn->close();

// ====================================================================
// 2. STUDENTS DB  (igangaschoolofl_students_db)
// ====================================================================
echo "\n============================================================\n";
echo "  DATABASE 2: igangaschoolofl_students_db\n";
echo "============================================================\n";
$stuConn = connFromEnv('Students', 'STUDENTS_DB_HOST', 'STUDENTS_DB_USER', 'STUDENTS_DB_PASS', 'STUDENTS_DB_NAME', 'STUDENTS_DB_PORT', 'STUDENTS_DB_CHARSET');

// --- 2A. Additional columns (if any) ---
echo "\n--- Additional columns ---\n";
if (tableExists($stuConn, 'students')) {
    addCol($stuConn, 'students', 'profile_picture', "profile_picture VARCHAR(500) NULL AFTER guardian_phone");
}
if (tableExists($stuConn, 'payments')) {
    addCol($stuConn, 'payments', 'slip_number', "slip_number VARCHAR(100) NULL AFTER transaction_ref");
}

// --- 2B. Student DB Foreign Keys ---
echo "\n--- Student Foreign Keys ---\n";

// int-based student_id → students(id)
$stuFKs_int = [
    ['student_fees', 'fk_sf_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['student_invoices', 'fk_si_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['student_attendance', 'fk_sa_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['payments', 'fk_pay_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['student_academic_records', 'fk_ar_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['student_academic_records', 'fk_ar_course', "FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE SET NULL"],
    ['student_fee_assignments', 'fk_sfa_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['payment_receipts', 'fk_prec_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['payment_receipts', 'fk_prec_payment', "FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE"],
    ['proof_of_payments', 'fk_pop_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['proof_of_payments', 'fk_pop_payment', "FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL"],
    ['student_discipline', 'fk_sd_student', "FOREIGN KEY (student_id) REFERENCES students(student_number) ON DELETE CASCADE"],
    ['student_hostel_allocations', 'fk_sha_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['cash_book', 'fk_cb_student', "FOREIGN KEY (related_student_id) REFERENCES students(id) ON DELETE SET NULL"],
    ['fee_adjustments', 'fk_fa_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['sponsorships', 'fk_spon_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['library_fines', 'fk_lf_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['student_penalties', 'fk_spen_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['student_requests', 'fk_sreq_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['student_sick_leave', 'fk_ssl_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['graduation_candidates', 'fk_gc_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['registrar_certificates', 'fk_rc_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['registrar_transcript_requests', 'fk_rtr_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['student_notifications', 'fk_sn_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
    ['student_messages', 'fk_sm_student', "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE"],
];
foreach ($stuFKs_int as $fk) {
    if (tableExists($stuConn, $fk[0])) {
        addFK($stuConn, $fk[0], $fk[1], $fk[2]);
    }
}

// varchar-based student_id → students(student_number)
$stuFKs_varchar = [
    ['hostel_allocations', 'fk_ha_student', "FOREIGN KEY (student_id) REFERENCES students(student_number) ON DELETE CASCADE"],
    ['student_course_registrations', 'fk_scr_student', "FOREIGN KEY (student_id) REFERENCES students(student_number) ON DELETE CASCADE"],
    ['payment_subscriptions', 'fk_psub_student', "FOREIGN KEY (student_id) REFERENCES students(student_number) ON DELETE CASCADE"],
    ['clinical_placements', 'fk_cp_student', "FOREIGN KEY (student_id) REFERENCES students(student_number) ON DELETE SET NULL"],
];
foreach ($stuFKs_varchar as $fk) {
    if (tableExists($stuConn, $fk[0])) {
        addFK($stuConn, $fk[0], $fk[1], $fk[2]);
    }
}

// Hostel FK: room_id → hostel_rooms(id)
if (tableExists($stuConn, 'hostel_allocations')) {
    addFK($stuConn, 'hostel_allocations', 'fk_ha_room', "FOREIGN KEY (room_id) REFERENCES hostel_rooms(id) ON DELETE CASCADE");
}

// Course registrations FK: course_id → courses(id)
if (tableExists($stuConn, 'student_course_registrations')) {
    addFK($stuConn, 'student_course_registrations', 'fk_scr_course', "FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE");
}

// Library FKs
if (tableExists($stuConn, 'library_borrowing')) {
    addFK($stuConn, 'library_borrowing', 'fk_lib_borrow_student', "FOREIGN KEY (student_id) REFERENCES students(student_number) ON DELETE CASCADE");
    addFK($stuConn, 'library_borrowing', 'fk_lib_borrow_book', "FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE");
}

// --- 2C. Student DB Indexes ---
echo "\n--- Student Indexes ---\n";
$stuIdxs = [
    ['students', 'idx_stu_status', 'INDEX idx_stu_status (status)'],
    ['students', 'idx_stu_student_number', 'INDEX idx_stu_student_number (student_number)'],
    ['students', 'idx_stu_email', 'INDEX idx_stu_email (email)'],
    ['students', 'idx_stu_program', 'INDEX idx_stu_program (program)'],
    ['student_fees', 'idx_sf_student', 'INDEX idx_sf_student (student_id)'],
    ['student_fees', 'idx_sf_status', 'INDEX idx_sf_status (status)'],
    ['student_fees', 'idx_sf_due_date', 'INDEX idx_sf_due_date (due_date)'],
    ['student_invoices', 'idx_si_student', 'INDEX idx_si_student (student_id)'],
    ['student_invoices', 'idx_si_status', 'INDEX idx_si_status (status)'],
    ['student_invoices', 'idx_si_invoice_number', 'INDEX idx_si_invoice_number (invoice_number)'],
    ['student_attendance', 'idx_sa_student', 'INDEX idx_sa_student (student_id)'],
    ['student_attendance', 'idx_sa_date', 'INDEX idx_sa_date (date)'],
    ['hostel_allocations', 'idx_ha_student', 'INDEX idx_ha_student (student_id)'],
    ['hostel_allocations', 'idx_ha_room', 'INDEX idx_ha_room (room_id)'],
    ['hostel_allocations', 'idx_ha_status', 'INDEX idx_ha_status (status)'],
    ['hostel_rooms', 'idx_hr_status', 'INDEX idx_hr_status (status)'],
    ['hostel_rooms', 'idx_hr_room_number', 'INDEX idx_hr_room_number (room_number)'],
    ['payments', 'idx_pay_student', 'INDEX idx_pay_student (student_id)'],
    ['payments', 'idx_pay_status', 'INDEX idx_pay_status (status)'],
    ['payments', 'idx_pay_date', 'INDEX idx_pay_date (payment_date)'],
    ['payments', 'idx_pay_ref', 'INDEX idx_pay_reference (payment_reference)'],
    ['student_course_registrations', 'idx_scr_student', 'INDEX idx_scr_student (student_id)'],
    ['student_course_registrations', 'idx_scr_course', 'INDEX idx_scr_course (course_id)'],
    ['student_course_registrations', 'idx_scr_academic_year', 'INDEX idx_scr_academic_year (academic_year)'],
    ['student_academic_records', 'idx_ar_student', 'INDEX idx_ar_student (student_id)'],
    ['student_academic_records', 'idx_ar_course', 'INDEX idx_ar_course (course_code)'],
    ['courses', 'idx_crs_code', 'INDEX idx_crs_code (course_code)'],
    ['courses', 'idx_crs_status', 'INDEX idx_crs_status (status)'],
    ['library_borrowing', 'idx_lib_borrow_student', 'INDEX idx_lib_borrow_student (student_id)'],
    ['library_borrowing', 'idx_lib_borrow_book', 'INDEX idx_lib_borrow_book (book_id)'],
    ['library_borrowing', 'idx_lib_borrow_status', 'INDEX idx_lib_borrow_status (status)'],
    ['library_borrowing', 'idx_lib_borrow_due', 'INDEX idx_lib_borrow_due (due_date)'],
    ['library_books', 'idx_lib_books_isbn', 'INDEX idx_lib_books_isbn (isbn)'],
    ['library_books', 'idx_lib_books_category', 'INDEX idx_lib_books_category (category)'],
    ['student_discipline', 'idx_sd_student', 'INDEX idx_sd_student (student_id)'],
    ['student_discipline', 'idx_sd_status', 'INDEX idx_sd_status (status)'],
    ['applications', 'idx_app_student', 'INDEX idx_app_student (student_id)'],
    ['applications', 'idx_app_status', 'INDEX idx_app_status (status)'],
    ['student_fee_assignments', 'idx_sfa_student', 'INDEX idx_sfa_student (student_id)'],
    ['student_fee_assignments', 'idx_sfa_status', 'INDEX idx_sfa_status (status)'],
    ['payment_receipts', 'idx_prec_student', 'INDEX idx_prec_student (student_id)'],
    ['payment_receipts', 'idx_prec_payment', 'INDEX idx_prec_payment (payment_id)'],
];
foreach ($stuIdxs as $i) {
    if (tableExists($stuConn, $i[0])) {
        addIdx($stuConn, $i[0], $i[1], $i[2]);
    }
}

$stuConn->close();

// ====================================================================
// 3. WEBSITE DB  (igangaschoolofl_website_db)
// ====================================================================
echo "\n============================================================\n";
echo "  DATABASE 3: igangaschoolofl_website_db\n";
echo "============================================================\n";
$webConn = connFromEnv('Website', 'WEBSITE_DB_HOST', 'WEBSITE_DB_USER', 'WEBSITE_DB_PASS', 'WEBSITE_DB_NAME', 'WEBSITE_DB_PORT', 'WEBSITE_DB_CHARSET');

// --- 3A. Website DB Indexes ---
echo "\n--- Website Indexes ---\n";
$webIdxs = [
    ['contact_submissions', 'idx_cs_status', 'INDEX idx_cs_status (status)'],
    ['contact_submissions', 'idx_cs_created_at', 'INDEX idx_cs_created_at (created_at)'],
    ['contact_submissions', 'idx_cs_email', 'INDEX idx_cs_email (email)'],
    ['news', 'idx_news_status', 'INDEX idx_news_status (status)'],
    ['news', 'idx_news_slug', 'INDEX idx_news_slug (slug)'],
    ['news', 'idx_news_published_at', 'INDEX idx_news_published_at (published_at)'],
    ['news', 'idx_news_author', 'INDEX idx_news_author (author_id)'],
    ['notifications', 'idx_web_notif_recipient', 'INDEX idx_web_notif_recipient (recipient_id, recipient_type)'],
    ['notifications', 'idx_web_notif_status', 'INDEX idx_web_notif_status (status)'],
    ['notification_reads', 'idx_nr_notification', 'INDEX idx_nr_notification (notification_id)'],
    ['notification_reads', 'idx_nr_user', 'INDEX idx_nr_user (user_id, user_type)'],
    ['pages', 'idx_pages_slug', 'INDEX idx_pages_slug (slug)'],
    ['pages', 'idx_pages_status', 'INDEX idx_pages_status (status)'],
    ['portal_messages', 'idx_pm_sender', 'INDEX idx_pm_sender (sender_id)'],
    ['portal_messages', 'idx_pm_recipient', 'INDEX idx_pm_recipient (recipient_id)'],
    ['portal_messages', 'idx_pm_read', 'INDEX idx_pm_read (is_read)'],
    ['push_subscriptions', 'idx_ps_user', 'INDEX idx_ps_user (user_id, user_type)'],
    ['donations', 'idx_don_status', 'INDEX idx_don_status (status)'],
    ['donations', 'idx_don_created_at', 'INDEX idx_don_created_at (created_at)'],
    ['student_applications', 'idx_web_sa_status', 'INDEX idx_web_sa_status (status)'],
    ['student_applications', 'idx_web_sa_email', 'INDEX idx_web_sa_email (email)'],
    ['volunteer_applications', 'idx_va_status', 'INDEX idx_va_status (status)'],
    ['volunteer_applications', 'idx_va_email', 'INDEX idx_va_email (email)'],
    ['daily_sick_records', 'idx_web_dsr_student', 'INDEX idx_web_dsr_student (student_id)'],
    ['daily_sick_records', 'idx_web_dsr_date', 'INDEX idx_web_dsr_date (visit_date)'],
    ['student_sick_leave', 'idx_web_ssl_student', 'INDEX idx_web_ssl_student (student_id)'],
    ['student_sick_leave', 'idx_web_ssl_status', 'INDEX idx_web_ssl_status (status)'],
    ['medicine_stock', 'idx_web_ms_status', 'INDEX idx_web_ms_status (status)'],
    ['medicine_stock_transactions', 'idx_web_mst_medicine', 'INDEX idx_web_mst_medicine (medicine_id)'],
    ['sickness_directory', 'idx_web_sd_status', 'INDEX idx_web_sd_status (status)'],
];
foreach ($webIdxs as $i) {
    if (tableExists($webConn, $i[0])) {
        addIdx($webConn, $i[0], $i[1], $i[2]);
    }
}

$webConn->close();

// ====================================================================
// 4. ICT DB  (igangaschoolofl_ict)
// ====================================================================
echo "\n============================================================\n";
echo "  DATABASE 4: igangaschoolofl_ict\n";
echo "============================================================\n";
$ictConn = connFromEnv('ICT', 'ICT_DB_HOST', 'ICT_DB_USER', 'ICT_DB_PASS', 'ICT_DB_NAME', 'ICT_DB_PORT', 'ICT_DB_CHARSET');

// --- 4A. ICT DB Indexes ---
echo "\n--- ICT Indexes ---\n";
$ictIdxs = [
    ['it_support_tickets', 'idx_ist_status', 'INDEX idx_ist_status (status)'],
    ['it_support_tickets', 'idx_ist_priority', 'INDEX idx_ist_priority (priority)'],
    ['it_support_tickets', 'idx_ist_assigned_to', 'INDEX idx_ist_assigned_to (assigned_to)'],
    ['it_support_tickets', 'idx_ist_requester_type', 'INDEX idx_ist_requester_type (requester_type)'],
    ['it_support_tickets', 'idx_ist_created_at', 'INDEX idx_ist_created_at (created_at)'],
    ['lab_bookings', 'idx_lb_date', 'INDEX idx_lb_date (booking_date)'],
    ['lab_bookings', 'idx_lb_status', 'INDEX idx_lb_status (status)'],
    ['lab_bookings', 'idx_lb_instructor', 'INDEX idx_lb_instructor (instructor_email)'],
    ['lab_computers', 'idx_lc_status', 'INDEX idx_lc_status (status)'],
    ['lab_computers', 'idx_lc_location', 'INDEX idx_lc_location (location)'],
    ['lab_usage_stats', 'idx_lus_date', 'INDEX idx_lus_date (date)'],
    ['lab_usage_stats', 'idx_lus_lab', 'INDEX idx_lus_lab (lab_name)'],
    ['maintenance_logs', 'idx_ml_computer', 'INDEX idx_ml_computer (computer_id)'],
    ['maintenance_logs', 'idx_ml_status', 'INDEX idx_ml_status (status)'],
    ['maintenance_logs', 'idx_ml_scheduled_date', 'INDEX idx_ml_scheduled_date (scheduled_date)'],
    ['network_devices', 'idx_nd_status', 'INDEX idx_nd_status (status)'],
    ['network_devices', 'idx_nd_type', 'INDEX idx_nd_type (device_type)'],
    ['network_devices', 'idx_nd_location', 'INDEX idx_nd_location (location)'],
    ['software_inventory', 'idx_si_category', 'INDEX idx_si_category (category)'],
    ['software_inventory', 'idx_si_license_type', 'INDEX idx_si_license_type (license_type)'],
    ['daily_sick_records', 'idx_ict_dsr_student', 'INDEX idx_ict_dsr_student (student_id)'],
    ['daily_sick_records', 'idx_ict_dsr_date', 'INDEX idx_ict_dsr_date (visit_date)'],
    ['student_sick_leave', 'idx_ict_ssl_student', 'INDEX idx_ict_ssl_student (student_id)'],
    ['student_sick_leave', 'idx_ict_ssl_status', 'INDEX idx_ict_ssl_status (status)'],
    ['medicine_stock', 'idx_ict_ms_status', 'INDEX idx_ict_ms_status (status)'],
    ['medicine_stock_transactions', 'idx_ict_mst_medicine', 'INDEX idx_ict_mst_medicine (medicine_id)'],
    ['sickness_directory', 'idx_ict_sd_status', 'INDEX idx_ict_sd_status (status)'],
];
foreach ($ictIdxs as $i) {
    if (tableExists($ictConn, $i[0])) {
        addIdx($ictConn, $i[0], $i[1], $i[2]);
    }
}

$ictConn->close();

echo "\n======================================================\n";
echo "  Deployment complete — all 4 databases processed.\n";
echo "======================================================\n";
