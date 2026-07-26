<?php
/**
 * ISNM Complete Database Setup Script
 * Creates all databases and tables from scratch.
 * Access via: http://localhost/ISNM/database/setup_all_databases.php
 *
 * Steps:
 *   1. Test MySQL connection
 *   2. Create all 4 databases
 *   3. Create tables (from master_migration.sql + ICT extras)
 *   4. Seed essential data (roles, admin users, defaults)
 */

session_start();

// ── Security: localhost only ──
$allowedHosts = ['localhost', '127.0.0.1', '::1', '::ffff:127.0.0.1'];
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddr, $allowedHosts)) {
    die('Access denied: Only accessible from localhost');
}

require_once __DIR__ . '/../config/database.php';

// ── Load .env manually if not already loaded ──
if (!defined('STUDENTS_DB_HOST')) {
    $envPath = __DIR__ . '/../.env';
    if (is_file($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $k = trim($k);
            $v = trim($v);
            if ($k !== '' && !defined($k)) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
    }
}

function envVal($key, $default = '') {
    $v = getenv($key);
    return ($v === false) ? $default : $v;
}

// ── Config ──
$host = envVal('STUDENTS_DB_HOST', 'localhost');
$port = (int) envVal('STUDENTS_DB_PORT', 3306);
$user = envVal('STUDENTS_DB_USER', 'root');
$pass = envVal('STUDENTS_DB_PASS', '');
$charset = envVal('DB_CHARSET', 'utf8mb4');

$databases = [
    'igangaschool_staffs',
    'igangaschool_students',
    'igangaschool_website',
    'igangaschool_ict',
];

// ── Helpers ──
function addLog(&$log, $msg, $cls = 'info') {
    $log[] = ['msg' => $msg, 'cls' => $cls];
}

function createDB($conn, $dbName, &$log) {
    $safe = $conn->real_escape_string($dbName);
    $res = $conn->query("CREATE DATABASE IF NOT EXISTS `$safe` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    if ($res) {
        addLog($log, "Database '$dbName' created or already exists.", 'success');
        return true;
    }
    addLog($log, "Failed to create '$dbName': " . $conn->error, 'danger');
    return false;
}

function runSQLFileAgainstDB($conn, $dbName, $sqlContent, &$log) {
    $safe = $conn->real_escape_string($dbName);
    if (!$conn->select_db($safe)) {
        addLog($log, "Cannot select database '$dbName': " . $conn->error, 'danger');
        return 0;
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $conn->query("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");

    // Split by statement boundaries, ignoring DELIMITER blocks
    $statements = splitSQL($sqlContent);
    $ok = 0;
    $fail = 0;
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '' || $stmt[0] === '-' || stripos($stmt, 'DELIMITER') === 0) continue;
        if (stripos($stmt, 'CREATE DEFINER') !== false) continue; // skip stored procedures
        if (stripos($stmt, 'INSERT INTO') === 0) continue; // skip seed data from dumps
        if (stripos($stmt, 'SET ') === 0 && stripos($stmt, 'SET SQL_MODE') !== false) continue;
        if (stripos($stmt, 'START TRANSACTION') !== false) continue;
        if (stripos($stmt, 'COMMIT') !== false) continue;
        if (preg_match('/^\/\*!/', $stmt)) continue;

        $trimmed = rtrim($stmt, ';');
        if ($trimmed === '') continue;

        if ($conn->query($trimmed)) {
            $ok++;
        } else {
            $fail++;
        }
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    addLog($log, "Applied to '$dbName': $ok statements OK, $fail failed.", $fail > 0 ? 'warning' : 'success');
    return $ok;
}

function splitSQL($content) {
    // Remove BOM
    $content = ltrim($content, "\xEF\xBB\xBF");
    $stmts = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $len = strlen($content);
    for ($i = 0; $i < $len; $i++) {
        $ch = $content[$i];
        if ($inString) {
            $current .= $ch;
            if ($ch === $stringChar && ($i === 0 || $content[$i - 1] !== '\\')) {
                $inString = false;
            }
        } else {
            if ($ch === '\'' || $ch === '"') {
                $inString = true;
                $stringChar = $ch;
                $current .= $ch;
            } elseif ($ch === ';') {
                $stmts[] = $current . ';';
                $current = '';
            } else {
                $current .= $ch;
            }
        }
    }
    if (trim($current) !== '') {
        $stmts[] = $current;
    }
    return $stmts;
}

// ── Seed data functions ──

function seedStaffRoles($conn, &$log) {
    $count = 0;
    $roles = [
        [1, 'Director General', 'Executive leadership', 1, 1, 'dashboards/director-general.php'],
        [2, 'CEO', 'Chief Executive Officer', 1, 1, 'dashboards/ceo.php'],
        [3, 'Director Academics', 'Academic affairs oversight', 2, 2, 'dashboards/director-academics.php'],
        [4, 'Director Finance', 'Financial management oversight', 2, 2, 'dashboards/director-finance.php'],
        [5, 'Director ICT', 'ICT department head', 2, 2, 'dashboards/director-ict.php'],
        [6, 'Director Admissions', 'Admissions department head', 2, 2, 'dashboards/director-admissions.php'],
        [7, 'School Principal', 'School administration head', 2, 2, 'dashboards/school-principal.php'],
        [8, 'Deputy Principal', 'Deputy school administration', 3, 3, 'dashboards/deputy-principal.php'],
        [9, 'Academic Registrar', 'Academic records management', 3, 3, 'dashboards/academic-registrar.php'],
        [10, 'School Bursar', 'Financial operations', 3, 3, 'dashboards/school-bursar.php'],
        [11, 'School Secretary', 'Administrative support', 4, 4, 'dashboards/school-secretary.php'],
        [12, 'HR Manager', 'Human resource management', 3, 3, 'dashboards/hr-manager.php'],
        [13, 'School Librarian', 'Library management', 4, 4, 'dashboards/school-librarian.php'],
        [14, 'Head of Nursing', 'Nursing department head', 3, 3, 'dashboards/head-nursing.php'],
        [15, 'Head of Midwifery', 'Midwifery department head', 3, 3, 'dashboards/head-midwifery.php'],
        [16, 'Senior Lecturer', 'Senior academic staff', 4, 4, 'dashboards/senior-lecturers.php'],
        [17, 'Lecturer', 'Academic teaching staff', 5, 5, 'dashboards/lecturers.php'],
        [18, 'Security Officer', 'Security and access control', 5, 5, 'dashboards/security.php'],
        [19, 'Storekeeper', 'Store and inventory management', 5, 5, 'dashboards/storekeeper.php'],
        [20, 'Driver', 'Transport and logistics', 6, 6, 'dashboards/drivers.php'],
        [21, 'Matron', 'Student welfare (female)', 4, 4, 'dashboards/matrons.php'],
        [22, 'Warden', 'Hostel management', 5, 5, 'dashboards/wardens.php'],
        [23, 'Guild President', 'Student government leader', 4, 4, 'dashboards/guild-president.php'],
        [24, 'Sickbay Nurse', 'Health services', 5, 5, 'dashboards/sickbay.php'],
        [25, 'System Administrator', 'System and network admin', 1, 1, 'dashboards/system-admin.php'],
        [26, 'Computer Lab Manager', 'Computer lab operations', 4, 4, 'dashboards/computer_lab.php'],
        [27, 'Skills Lab Manager', 'Skills laboratory operations', 4, 4, 'dashboards/skills-lab.php'],
        [28, 'Skills Lab Technician', 'Skills laboratory support', 5, 5, 'dashboards/skills-lab.php'],
        [29, 'Events Coordinator', 'Events and activities', 4, 4, 'dashboards/events-manager.php'],
        [30, 'Alumni Relations Officer', 'Alumni engagement', 4, 4, 'dashboards/alumni-manager.php'],
    ];

    $check = $conn->query("SELECT COUNT(*) AS cnt FROM staff_roles");
    if ($check && $check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "Staff roles: Already seeded (skipped).", 'info');
        return;
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO staff_roles (id, role_name, role_description, role_level, hierarchy_level, dashboard_path, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    if (!$stmt) {
        addLog($log, "staff_roles: " . $conn->error, 'danger');
        return;
    }
    foreach ($roles as $r) {
        $stmt->bind_param('issiis', $r[0], $r[1], $r[2], $r[3], $r[4], $r[5]);
        $stmt->execute();
        $count += $stmt->affected_rows;
    }
    $stmt->close();
    addLog($log, "Staff roles: $count roles inserted.", 'success');
}

function seedAdminStaff($conn, &$log) {
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM staff");
    if ($check && $check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "Staff users: Already seeded (skipped).", 'info');
        return;
    }

    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $accounts = [
        ['DG-001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', $adminPass, 1, 'Director General', 'Executive'],
        ['SYSADM-001', 'System Administrator', 'admin@igangaschoolofnursingandmidwifery.ac.ug', $adminPass, 25, 'System Administrator', 'ICT'],
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO staff (staff_id, full_name, email, password, role_id, position, department, is_active, status, hire_date, is_first_login, password_changed) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'Active', CURDATE(), 0, 1)");
    if (!$stmt) {
        addLog($log, "staff: " . $conn->error, 'danger');
        return;
    }
    foreach ($accounts as $a) {
        $stmt->bind_param('ssssiss', $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6]);
        $stmt->execute();
    }
    $stmt->close();
    addLog($log, "Admin staff seeded (Director General, SysAdmin). Password: admin123", 'success');
}

function seedLeaveTypes($conn, &$log) {
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM leave_types");
    if ($check && $check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "Leave types: Already seeded (skipped).", 'info');
        return;
    }

    $types = [
        ['Annual Leave', 30, 'Annual vacation leave'],
        ['Sick Leave', 14, 'Medical/sick leave'],
        ['Maternity Leave', 90, 'Maternity leave for female staff'],
        ['Paternity Leave', 7, 'Paternity leave for male staff'],
        ['Compassionate Leave', 5, 'Compassionate/bereavement leave'],
        ['Study Leave', 60, 'Study/research leave'],
        ['Matrimonial Leave', 7, 'Marriage leave'],
        ['Pilgrimage Leave', 14, 'Religious pilgrimage leave'],
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO leave_types (type_name, days_allowed, description, status) VALUES (?, ?, ?, 'Active')");
    $added = 0;
    foreach ($types as $t) {
        $stmt->bind_param('sis', $t[0], $t[1], $t[2]);
        $stmt->execute();
        $added += $stmt->affected_rows;
    }
    $stmt->close();
    addLog($log, "Leave types: $added types inserted.", 'success');
}

function seedAcademicCalendar($conn, &$log) {
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM academic_calendar");
    if ($check && $check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "Academic calendar: Already seeded (skipped).", 'info');
        return;
    }

    $year = date('Y');
    $conn->query("INSERT IGNORE INTO academic_calendar (academic_year, semester, start_date, end_date, is_current, status) VALUES ('$year', 'First Semester', '$year-02-01', '$year-06-30', 1, 'Active')");
    $conn->query("INSERT IGNORE INTO academic_calendar (academic_year, semester, start_date, end_date, is_current, status) VALUES ('$year', 'Second Semester', '$year-07-01', '$year-12-31', 0, 'Active')");
    addLog($log, "Academic calendar: Default $year semesters created.", 'success');
}

function seedAcademicPrograms($conn, &$log) {
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM academic_programs");
    if ($check && $check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "Academic programs: Already seeded (skipped).", 'info');
        return;
    }

    $programs = [
        ['DIP-NUR', 'Diploma in Nursing', 'Diploma', 'Nursing', 3.0, 0.00],
        ['DIP-MID', 'Diploma in Midwifery', 'Diploma', 'Midwifery', 3.0, 0.00],
        ['CERT-EN', 'Certificate in Enrolled Nursing', 'Certificate', 'Nursing', 2.0, 0.00],
        ['CERT-MID', 'Certificate in Midwifery', 'Certificate', 'Midwifery', 2.0, 0.00],
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO academic_programs (program_code, program_name, program_type, department, duration_years, total_fee, status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
    $added = 0;
    foreach ($programs as $p) {
        $stmt->bind_param('ssssdd', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
        $stmt->execute();
        $added += $stmt->affected_rows;
    }
    $stmt->close();
    addLog($log, "Academic programs: $added programs inserted.", 'success');
}

function seedGradeScales($conn, &$log) {
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM grade_scales");
    if ($check && $check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "Grade scales: Already seeded (skipped).", 'info');
        return;
    }

    $grades = [
        ['A', 5.0, 80.00, 100.00, 'Excellent'],
        ['B+', 4.5, 75.00, 79.99, 'Very Good'],
        ['B', 4.0, 70.00, 74.99, 'Good'],
        ['C+', 3.5, 65.00, 69.99, 'Above Average'],
        ['C', 3.0, 60.00, 64.99, 'Average'],
        ['D', 2.0, 50.00, 59.99, 'Below Average'],
        ['E', 1.0, 40.00, 49.99, 'Poor'],
        ['F', 0.0, 0.00, 39.99, 'Fail'],
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO grade_scales (grade_letter, grade_point, min_percentage, max_percentage, remark, status) VALUES (?, ?, ?, ?, ?, 'Active')");
    $added = 0;
    foreach ($grades as $g) {
        $stmt->bind_param('sddds', $g[0], $g[1], $g[2], $g[3], $g[4]);
        $stmt->execute();
        $added += $stmt->affected_rows;
    }
    $stmt->close();
    addLog($log, "Grade scales: $added scales inserted.", 'success');
}

function seedFeeStructure($conn, &$log) {
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM fee_structures");
    if (!$check) {
        addLog($log, "fee_structures table not found, skipping fee seed.", 'info');
        return;
    }
    if ($check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "Fee structures: Already seeded (skipped).", 'info');
        return;
    }
    addLog($log, "Fee structures: No default seed data (configure manually).", 'info');
}

function seedRegistrarSettings($conn, &$log) {
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM registrar_settings");
    if (!$check) {
        addLog($log, "registrar_settings table not found, skipping.", 'info');
        return;
    }
    if ($check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "Registrar settings: Already seeded (skipped).", 'info');
        return;
    }
    $settings = [
        ['current_academic_year', date('Y'), 'Current active academic year'],
        ['current_semester', 'First Semester', 'Current active semester'],
        ['registration_open', '1', 'Whether student registration is open'],
        ['grading_system', 'GPA', 'Grading system type'],
        ['max_credits_per_semester', '24', 'Maximum credit hours per semester'],
    ];
    $stmt = $conn->prepare("INSERT IGNORE INTO registrar_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    $added = 0;
    foreach ($settings as $s) {
        $stmt->bind_param('sss', $s[0], $s[1], $s[2]);
        $stmt->execute();
        $added += $stmt->affected_rows;
    }
    $stmt->close();
    addLog($log, "Registrar settings: $added settings inserted.", 'success');
}

function seedGpaSettings($conn, &$log) {
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM gpa_settings");
    if (!$check) {
        addLog($log, "gpa_settings table not found, skipping.", 'info');
        return;
    }
    if ($check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "GPA settings: Already seeded (skipped).", 'info');
        return;
    }
    $settings = [
        ['min_pass_grade', 'D', 'Minimum grade to pass a course'],
        ['gpa_scale', '5.0', 'Maximum GPA scale'],
        ['probation_threshold', '2.0', 'GPA below which student is on probation'],
    ];
    $stmt = $conn->prepare("INSERT IGNORE INTO gpa_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    $added = 0;
    foreach ($settings as $s) {
        $stmt->bind_param('sss', $s[0], $s[1], $s[2]);
        $stmt->execute();
        $added += $stmt->affected_rows;
    }
    $stmt->close();
    addLog($log, "GPA settings: $added settings inserted.", 'success');
}

function seedStaffDepartments($conn, &$log) {
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM staff_departments");
    if (!$check) {
        addLog($log, "staff_departments table not found, skipping.", 'info');
        return;
    }
    if ($check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "Staff departments: Already seeded (skipped).", 'info');
        return;
    }
    $depts = [
        ['Executive', 'EXEC', 1],
        ['Academic Affairs', 'ACAD', 2],
        ['Academic Registrar', 'REG', 2],
        ['Administration', 'ADM', 2],
        ['Finance', 'FIN', 2],
        ['Human Resources', 'HR', 2],
        ['ICT', 'ICT', 2],
        ['Admissions', 'ADM2', 2],
        ['Nursing', 'NUR', 3],
        ['Midwifery', 'MID', 3],
        ['Library', 'LIB', 3],
        ['Student Welfare', 'SW', 3],
        ['Skills Laboratory', 'SKL', 3],
        ['Store', 'STR', 4],
        ['Transport', 'TRN', 4],
        ['Security', 'SEC', 4],
        ['Student Government', 'GOV', 4],
    ];
    $stmt = $conn->prepare("INSERT IGNORE INTO staff_departments (department_name, department_code, department_level) VALUES (?, ?, ?)");
    $added = 0;
    foreach ($depts as $d) {
        $stmt->bind_param('ssi', $d[0], $d[1], $d[2]);
        $stmt->execute();
        $added += $stmt->affected_rows;
    }
    $stmt->close();
    addLog($log, "Staff departments: $added departments inserted.", 'success');
}

function seedWebsiteCMS($conn, &$log) {
    // Seed minimal CMS settings
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM cms_settings");
    if (!$check) {
        addLog($log, "cms_settings table not found, skipping.", 'info');
        return;
    }
    if ($check->fetch_assoc()['cnt'] > 0) {
        addLog($log, "CMS settings: Already seeded (skipped).", 'info');
        return;
    }
    $settings = [
        ['site_name', 'Iganga School of Nursing and Midwifery'],
        ['site_tagline', 'Excellence in Nursing and Midwifery Education'],
        ['contact_email', 'info@igangaschoolofnursingandmidwifery.ac.ug'],
        ['contact_phone', '+256-XXX-XXXXXX'],
        ['address', 'Iganga, Uganda'],
    ];
    $stmt = $conn->prepare("INSERT IGNORE INTO cms_settings (setting_key, setting_value) VALUES (?, ?)");
    $added = 0;
    foreach ($settings as $s) {
        $stmt->bind_param('ss', $s[0], $s[1]);
        $stmt->execute();
        $added += $stmt->affected_rows;
    }
    $stmt->close();
    addLog($log, "CMS settings: $added settings inserted.", 'success');
}

// ══════════════════════════════════════════════════
//  MAIN EXECUTION
// ══════════════════════════════════════════════════

$step = isset($_GET['step']) ? (int) $_GET['step'] : 0;
$log = [];
$stats = ['databases' => 0, 'tables_ok' => 0, 'tables_fail' => 0, 'seeded' => 0];
$conn = null;

if ($step >= 1) {
    // Step 1: Test MySQL connection
    addLog($log, "Testing MySQL connection to $host:$port as $user...", 'info');
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($host, $user, $pass, null, $port);
    if ($conn->connect_error) {
        addLog($log, "Connection FAILED: " . $conn->connect_error, 'danger');
        $step = 0;
    } else {
        $conn->set_charset($charset);
        addLog($log, "Connected to MySQL " . $conn->server_info . " successfully!", 'success');
    }
}

if ($step >= 2 && $conn) {
    // Step 2: Create databases
    addLog($log, "--- Creating databases ---", 'info');
    foreach ($databases as $db) {
        if (createDB($conn, $db, $stats)) {
            $stats['databases']++;
        }
    }
}

if ($step >= 3 && $conn) {
    // Step 3: Create tables from master_migration.sql
    addLog($log, "--- Running master_migration.sql ---", 'info');
    $migrationFile = __DIR__ . '/../master_migration.sql';
    if (!is_file($migrationFile)) {
        addLog($log, "master_migration.sql not found at: $migrationFile", 'danger');
    } else {
        $sql = file_get_contents($migrationFile);
        if ($sql === false) {
            addLog($log, "Could not read master_migration.sql", 'danger');
        } else {
            // Parse sections by comments
            $staffSql = extractSection($sql, 'SECTION 1: igangaschool_staffs', 'SECTION 2: igangaschool_students');
            $studentSql = extractSection($sql, 'SECTION 2: igangaschool_students', 'SECTION 3: igangaschool_website');
            $websiteSql = extractSection($sql, 'SECTION 3: igangaschool_website', 'END OF MIGRATION');

            if ($staffSql) {
                addLog($log, "-- Applying staff tables --", 'info');
                $n = runSQLFileAgainstDB($conn, 'igangaschool_staffs', $staffSql, $log);
                $stats['tables_ok'] += $n;
            }
            if ($studentSql) {
                addLog($log, "-- Applying student tables --", 'info');
                $n = runSQLFileAgainstDB($conn, 'igangaschool_students', $studentSql, $log);
                $stats['tables_ok'] += $n;
            }
            if ($websiteSql) {
                addLog($log, "-- Applying website tables --", 'info');
                $n = runSQLFileAgainstDB($conn, 'igangaschool_website', $websiteSql, $log);
                $stats['tables_ok'] += $n;
            }
        }
    }

    // Run ICT-specific SQL from sql/ict/
    $ictSqlFile = __DIR__ . '/../sql/ict/igangaschool_ict.sql';
    if (is_file($ictSqlFile)) {
        addLog($log, "-- Applying ICT tables --", 'info');
        $ictSql = file_get_contents($ictSqlFile);
        if ($ictSql) {
            $n = runSQLFileAgainstDB($conn, 'igangaschool_ict', $ictSql, $log);
            $stats['tables_ok'] += $n;
        }
    } else {
        // Fallback: apply staff tables to ICT as base
        addLog($log, "-- Applying base tables to ICT database --", 'info');
        if ($staffSql) {
            $n = runSQLFileAgainstDB($conn, 'igangaschool_ict', $staffSql, $log);
            $stats['tables_ok'] += $n;
        }
    }

    // Also run migration_additions.sql if it exists
    $additionsFile = __DIR__ . '/../migration_additions.sql';
    if (is_file($additionsFile)) {
        addLog($log, "-- Running migration_additions.sql on all databases --", 'info');
        $addSql = file_get_contents($additionsFile);
        if ($addSql) {
            foreach ($databases as $db) {
                runSQLFileAgainstDB($conn, $db, $addSql, $log);
            }
        }
    }
}

if ($step >= 4 && $conn) {
    // Step 4: Seed essential data
    addLog($log, "--- Seeding essential data ---", 'info');

    // Staff DB seeds
    if ($conn->select_db('igangaschool_staffs')) {
        seedStaffRoles($conn, $log);
        seedAdminStaff($conn, $log);
        seedLeaveTypes($conn, $log);
        seedAcademicCalendar($conn, $log);
        seedGradeScales($conn, $log);
        seedRegistrarSettings($conn, $log);
        seedGpaSettings($conn, $log);
        seedStaffDepartments($conn, $log);
    }

    // Students DB seeds
    if ($conn->select_db('igangaschool_students')) {
        seedAcademicPrograms($conn, $log);
        seedAcademicCalendar($conn, $log);
    }

    // Website DB seeds
    if ($conn->select_db('igangaschool_website')) {
        seedWebsiteCMS($conn, $log);
    }

    // ICT DB seeds
    if ($conn->select_db('igangaschool_ict')) {
        seedStaffRoles($conn, $log);
        seedStaffDepartments($conn, $log);
    }

    addLog($log, "--- Setup complete! ---", 'success');
}

function extractSection($sql, $startMarker, $endMarker) {
    $startPos = stripos($sql, $startMarker);
    $endPos = stripos($sql, $endMarker);
    if ($startPos === false) return null;
    if ($endPos === false) $endPos = strlen($sql);
    if ($endPos <= $startPos) return substr($sql, $startPos);
    return substr($sql, $startPos, $endPos - $startPos);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISNM Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .setup-container { max-width: 900px; margin: 30px auto; }
        .header-card { background: linear-gradient(135deg, #1a237e 0%, #283593 100%); color: #fff; border-radius: 12px; padding: 30px; margin-bottom: 20px; }
        .header-card h1 { margin: 0; font-size: 1.8rem; font-weight: 700; }
        .header-card p { margin: 8px 0 0; opacity: 0.85; font-size: 0.95rem; }
        .step-indicator { display: flex; gap: 10px; margin: 20px 0; }
        .step-badge { padding: 8px 18px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; background: #e0e0e0; color: #666; }
        .step-badge.active { background: #1a237e; color: #fff; }
        .step-badge.done { background: #2e7d32; color: #fff; }
        .step-badge.pending { background: #e0e0e0; color: #666; }
        .log-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; }
        .log-header { background: #1a237e; color: #fff; padding: 15px 20px; font-weight: 600; }
        .log-body { padding: 20px; max-height: 500px; overflow-y: auto; }
        .log-entry { padding: 6px 0; border-bottom: 1px solid #f0f0f0; font-size: 0.88rem; display: flex; align-items: flex-start; gap: 8px; }
        .log-entry:last-child { border-bottom: none; }
        .log-entry i { margin-top: 2px; }
        .log-entry.success i { color: #2e7d32; }
        .log-entry.danger i { color: #c62828; }
        .log-entry.warning i { color: #f57f17; }
        .log-entry.info i { color: #1565c0; }
        .actions-bar { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-setup { padding: 12px 30px; font-size: 1rem; font-weight: 600; border-radius: 8px; }
        .creds-table { font-size: 0.85rem; }
        .creds-table td, .creds-table th { padding: 6px 10px; }
        .badge-db { font-size: 0.8rem; }
        .progress-bar-wrap { margin: 10px 0; }
        @media (max-width: 600px) { .step-indicator { flex-wrap: wrap; } }
    </style>
</head>
<body>
<div class="setup-container">
    <div class="header-card">
        <h1><i class="bi bi-database-gear"></i> ISNM Database Setup</h1>
        <p>Complete database initialization for Iganga School of Nursing and Midwifery</p>
    </div>

    <div class="step-indicator">
        <span class="step-badge <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : 'pending' ?>">
            <i class="bi bi-plug"></i> 1. Connect
        </span>
        <span class="step-badge <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : 'pending' ?>">
            <i class="bi bi-hdd-stack"></i> 2. Create DBs
        </span>
        <span class="step-badge <?= $step >= 3 ? ($step > 3 ? 'done' : 'active') : 'pending' ?>">
            <i class="bi bi-table"></i> 3. Create Tables
        </span>
        <span class="step-badge <?= $step >= 4 ? 'done' : 'pending' ?>">
            <i class="bi bi-seedling"></i> 4. Seed Data
        </span>
    </div>

    <?php if ($step === 0): ?>
    <div class="log-card">
        <div class="log-header">
            <i class="bi bi-info-circle"></i> Ready to Initialize
        </div>
        <div class="log-body">
            <p>This script will set up all 4 ISNM databases from scratch:</p>
            <ul>
                <li><span class="badge bg-primary badge-db">igangaschool_staffs</span> &mdash; Staff, HR, academics, payroll, approvals</li>
                <li><span class="badge bg-success badge-db">igangaschool_students</span> &mdash; Students, admissions, fees, hostels</li>
                <li><span class="badge bg-info badge-db">igangaschool_website</span> &mdash; Public website, CMS, donations</li>
                <li><span class="badge bg-warning badge-db">igangaschool_ict</span> &mdash; ICT assets, lab management</li>
            </ul>

            <div class="alert alert-light border mt-3 mb-3">
                <h6><i class="bi bi-key"></i> Connection Settings (from .env)</h6>
                <table class="table table-sm creds-table mb-0">
                    <tr><th>Host:</th><td><code><?= htmlspecialchars($host) ?></code></td></tr>
                    <tr><th>Port:</th><td><code><?= $port ?></code></td></tr>
                    <tr><th>User:</th><td><code><?= htmlspecialchars($user) ?></code></td></tr>
                    <tr><th>Password:</th><td><code><?= $pass !== '' ? '****' : '(empty)' ?></code></td></tr>
                    <tr><th>Charset:</th><td><code><?= htmlspecialchars($charset) ?></code></td></tr>
                </table>
            </div>

            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Warning:</strong> This will create databases and tables. Existing data will NOT be deleted
                (all CREATE statements use IF NOT EXISTS). Safe to run multiple times.
            </div>

            <p class="text-muted small mb-3">Default admin login after setup:<br>
                Email: <code>directorgeneral@igangaschoolofnursingandmidwifery.ac.ug</code><br>
                Password: <code>admin123</code>
            </p>
        </div>
    </div>
    <div class="actions-bar">
        <a href="?step=1" class="btn btn-primary btn-setup" onclick="return confirm('Begin database setup?')">
            <i class="bi bi-play-fill"></i> Run Full Setup (All Steps)
        </a>
        <a href="?step=1" class="btn btn-outline-secondary btn-setup">
            <i class="bi bi-plug"></i> Step 1 Only (Test Connection)
        </a>
    </div>

    <?php else: ?>
    <div class="log-card">
        <div class="log-header">
            <i class="bi bi-terminal"></i> Setup Log &mdash; Step <?= $step ?> of 4
        </div>
        <div class="log-body" id="logBody">
            <?php foreach ($log as $entry): ?>
            <div class="log-entry <?= $entry['cls'] ?>">
                <?php if ($entry['cls'] === 'success'): ?>
                    <i class="bi bi-check-circle-fill"></i>
                <?php elseif ($entry['cls'] === 'danger'): ?>
                    <i class="bi bi-x-circle-fill"></i>
                <?php elseif ($entry['cls'] === 'warning'): ?>
                    <i class="bi bi-exclamation-triangle-fill"></i>
                <?php else: ?>
                    <i class="bi bi-info-circle-fill"></i>
                <?php endif; ?>
                <span><?= htmlspecialchars($entry['msg']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($log)): ?>
                <div class="text-muted text-center py-3">Processing...</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($step >= 4): ?>
    <div class="log-card mt-3">
        <div class="log-header bg-success">
            <i class="bi bi-check2-circle"></i> Setup Complete
        </div>
        <div class="log-body">
            <p>All requested steps have been executed. You can now:</p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="../staff-login.php" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Staff Login</a>
                <a href="../student-login.php" class="btn btn-success"><i class="bi bi-person-fill"></i> Student Login</a>
                <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Homepage</a>
                <a href="?step=4" class="btn btn-outline-warning"><i class="bi bi-arrow-clockwise"></i> Re-run Step 4 (Seed)</a>
            </div>
        </div>
    </div>
    <?php elseif ($step < 4): ?>
    <div class="actions-bar mt-3">
        <?php if ($step === 1 && $conn): ?>
            <a href="?step=2" class="btn btn-primary btn-setup"><i class="bi bi-hdd-stack"></i> Step 2: Create Databases</a>
        <?php endif; ?>
        <?php if ($step === 2): ?>
            <a href="?step=3" class="btn btn-primary btn-setup"><i class="bi bi-table"></i> Step 3: Create Tables</a>
        <?php endif; ?>
        <?php if ($step === 3): ?>
            <a href="?step=4" class="btn btn-success btn-setup"><i class="bi bi-seedling"></i> Step 4: Seed Data</a>
        <?php endif; ?>
        <a href="?step=<?= min($step + 1, 4) ?>" class="btn btn-outline-primary btn-setup">Continue to Next Step</a>
        <a href="?step=1" class="btn btn-outline-secondary btn-setup"><i class="bi bi-arrow-counterclockwise"></i> Restart</a>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script>
    // Auto-scroll log to bottom
    const logEl = document.getElementById('logBody');
    if (logEl) logEl.scrollTop = logEl.scrollHeight;
</script>
</body>
</html>
