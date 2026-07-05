<?php
/**
 * ISNM Production Deployment Script (4 DBs - SAFE/IDEMPOTENT)
 *
 * Missing original file recovery: this is a fresh replacement that:
 * - Loads .env
 * - Connects to staffs/students/website/ict DBs
 * - Adds missing columns/indexes/foreign keys when the target objects exist
 *
 * USAGE:
 *   php sql/deploy_production.php
 */

function isnm_env(string $key, $default = null) {
    $value = getenv($key);
    if ($value === false && isset($_ENV[$key])) $value = $_ENV[$key];
    if ($value === false && isset($_SERVER[$key])) $value = $_SERVER[$key];
    return $value === false ? $default : $value;
}

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

function connFromEnv(string $label, string $hostKey, string $userKey, string $passKey, string $nameKey, string $portKey, string $charsetKey): mysqli {
    $host = isnm_env($hostKey, 'localhost');
    $host = ($host === 'localhost') ? '127.0.0.1' : $host;

    $user = isnm_env($userKey, 'root');
    $pass = isnm_env($passKey, '');
    $name = isnm_env($nameKey, '');
    $port = (int) isnm_env($portKey, 3306);
    $charset = isnm_env($charsetKey, 'utf8mb4');

    if ($name === '') {
        die("  [!!] $label DB_NAME is empty (missing env: $nameKey)\n");
    }

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

function tableExists(mysqli $conn, string $name): bool {
    $dbRow = $conn->query("SELECT DATABASE() as db")->fetch_assoc();
    $db = $dbRow['db'] ?? '';
    $nameEsc = $conn->real_escape_string($name);

    $r = $conn->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$nameEsc'");
    return $r && $r->num_rows > 0;
}

function colExists(mysqli $conn, string $table, string $col): bool {
    $dbRow = $conn->query("SELECT DATABASE() as db")->fetch_assoc();
    $db = $dbRow['db'] ?? '';
    $tableEsc = $conn->real_escape_string($table);
    $colEsc = $conn->real_escape_string($col);

    $r = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$tableEsc' AND COLUMN_NAME='$colEsc'");
    return $r && $r->num_rows > 0;
}

function idxExists(mysqli $conn, string $table, string $idx): bool {
    $dbRow = $conn->query("SELECT DATABASE() as db")->fetch_assoc();
    $db = $dbRow['db'] ?? '';
    $tableEsc = $conn->real_escape_string($table);
    $idxEsc = $conn->real_escape_string($idx);

    $r = $conn->query("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$tableEsc' AND INDEX_NAME='$idxEsc'");
    return $r && $r->num_rows > 0;
}

function fkExists(mysqli $conn, string $table, string $fk): bool {
    $dbRow = $conn->query("SELECT DATABASE() as db")->fetch_assoc();
    $db = $dbRow['db'] ?? '';
    $tableEsc = $conn->real_escape_string($table);
    $fkEsc = $conn->real_escape_string($fk);

    $r = $conn->query("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='$db' AND TABLE_NAME='$tableEsc' AND CONSTRAINT_NAME='$fkEsc' AND CONSTRAINT_TYPE='FOREIGN KEY'");
    return $r && $r->num_rows > 0;
}

function addCol(mysqli $conn, string $table, string $col, string $def): void {
    if (colExists($conn, $table, $col)) {
        echo "  [OK] $table.$col\n";
        return;
    }
    $tableEsc = $conn->real_escape_string($table);
    if ($conn->query("ALTER TABLE $tableEsc ADD COLUMN $def")) {
        echo "  [+ ] $table.$col\n";
    } else {
        echo "  [!!] $table.$col: " . $conn->error . "\n";
    }
}

function addIdx(mysqli $conn, string $table, string $idx, string $def): void {
    if (idxExists($conn, $table, $idx)) {
        echo "  [OK] $table.$idx\n";
        return;
    }
    $tableEsc = $conn->real_escape_string($table);
    if ($conn->query("ALTER TABLE $tableEsc ADD $def")) {
        echo "  [+ ] $table.$idx\n";
    } else {
        echo "  [!!] $table.$idx: " . $conn->error . "\n";
    }
}

function addFK(mysqli $conn, string $table, string $fk, string $def): void {
    if (fkExists($conn, $table, $fk)) {
        echo "  [OK] $table.$fk (constraint exists)\n";
        return;
    }
    $tableEsc = $conn->real_escape_string($table);
    if ($conn->query("ALTER TABLE $tableEsc ADD CONSTRAINT $fk $def")) {
        echo "  [+ ] $table.$fk\n";
    } else {
        echo "  [!!] $table.$fk: " . $conn->error . "\n";
    }
}

function createIfMissing(mysqli $conn, string $table, string $sql): void {
    if (tableExists($conn, $table)) {
        echo "  [OK] $table exists\n";
        return;
    }
    if ($conn->query($sql)) {
        echo "  [+ ] $table created\n";
    } else {
        echo "  [!!] $table: " . $conn->error . "\n";
    }
}

// --------------------------- MAIN ---------------------------
echo "======================================================\n";
echo "  ISNM Deploy (Replacement Script) - 4 DBs\n";
echo "======================================================\n\n";

isnm_load_env(__DIR__ . '/../.env');

$staffConn = connFromEnv('Staff', 'STAFF_DB_HOST', 'STAFF_DB_USER', 'STAFF_DB_PASS', 'STAFF_DB_NAME', 'STAFF_DB_PORT', 'STAFF_DB_CHARSET');
$stuConn   = connFromEnv('Students', 'STUDENTS_DB_HOST', 'STUDENTS_DB_USER', 'STUDENTS_DB_PASS', 'STUDENTS_DB_NAME', 'STUDENTS_DB_PORT', 'STUDENTS_DB_CHARSET');
$webConn   = connFromEnv('Website', 'WEBSITE_DB_HOST', 'WEBSITE_DB_USER', 'WEBSITE_DB_PASS', 'WEBSITE_DB_NAME', 'WEBSITE_DB_PORT', 'WEBSITE_DB_CHARSET');
$ictConn   = connFromEnv('ICT', 'ICT_DB_HOST', 'ICT_DB_USER', 'ICT_DB_PASS', 'ICT_DB_NAME', 'ICT_DB_PORT', 'ICT_DB_CHARSET');

echo "\n==================== STAFF DB FIXES ====================\n";
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

echo "\n--- Minimal role seed (if roles empty) ---\n";
if (tableExists($staffConn, 'roles') && tableExists($staffConn, 'staff_roles')) {
    $r = $staffConn->query("SELECT COUNT(*) as cnt FROM roles");
    $roleCount = $r ? (int)$r->fetch_assoc()['cnt'] : 0;
    if ($roleCount === 0) {
        $staffConn->query("INSERT IGNORE INTO roles (id, name, description, created_at) SELECT id, role_name, role_description, created_at FROM staff_roles");
        echo "  Seeded roles: " . $staffConn->affected_rows . "\n";
    } else {
        echo "  roles already has $roleCount rows\n";
    }
}

echo "\n==================== STUDENTS DB FIXES ====================\n";
if (tableExists($stuConn, 'students')) {
    addCol($stuConn, 'students', 'profile_picture', "profile_picture VARCHAR(500) NULL AFTER guardian_phone");
}
if (tableExists($stuConn, 'payments')) {
    addCol($stuConn, 'payments', 'slip_number', "slip_number VARCHAR(100) NULL AFTER transaction_ref");
}

echo "\n==================== WEBSITE DB FIXES ====================\n";
// Keep minimal: add a couple indexes only if tables exist
$webIdxs = [
    ['contact_submissions', 'idx_cs_status', 'INDEX idx_cs_status (status)'],
    ['news', 'idx_news_slug', 'INDEX idx_news_slug (slug)'],
    ['notifications', 'idx_web_notif_recipient', 'INDEX idx_web_notif_recipient (recipient_id, recipient_type)'],
];
foreach ($webIdxs as $i) {
    if (tableExists($webConn, $i[0])) addIdx($webConn, $i[0], $i[1], $i[2]);
}

echo "\n==================== ICT DB FIXES ====================\n";
$ictIdxs = [
    ['it_support_tickets', 'idx_ist_status', 'INDEX idx_ist_status (status)'],
    ['it_support_tickets', 'idx_ist_priority', 'INDEX idx_ist_priority (priority)'],
    ['lab_bookings', 'idx_lb_status', 'INDEX idx_lb_status (status)'],
];
foreach ($ictIdxs as $i) {
    if (tableExists($ictConn, $i[0])) addIdx($ictConn, $i[0], $i[1], $i[2]);
}

// close
$staffConn->close();
$stuConn->close();
$webConn->close();
$ictConn->close();

echo "\n======================================================\n";
echo "  Replacement deployment script complete.\n";
echo "  (Checks were limited to safe/common schema gaps.)\n";
echo "======================================================\n";
