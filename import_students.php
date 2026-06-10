<?php
set_time_limit(120);
require_once __DIR__ . '/includes/SimpleXlsxReader.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/views/student_data_loader.php';

$loader = new StudentDataLoader();
$all    = $loader->loadAllStudents();
$stats  = $loader->getStatistics();

echo "=== Excel Read Results ===\n";
echo "Total students loaded: " . $stats['total_students'] . "\n";
echo "Programs: " . implode(', ', $stats['programs'] ?? []) . "\n";
echo "Sets: "     . implode(', ', $stats['sets'] ?? []) . "\n";
echo "Files: "    . $stats['data_files'] . "\n\n";

if (empty($all)) {
    echo "No students found. Checking Excel files directly...\n";
    $dir = __DIR__ . '/students_data/';
    foreach (glob($dir . '*.xlsx') as $f) {
        $rows = SimpleXlsxReader::read($f);
        echo basename($f) . ': ' . count($rows) . " rows (inc header)\n";
        if (!empty($rows[0])) echo "  Header: " . implode(' | ', array_slice($rows[0], 0, 8)) . "\n";
        if (!empty($rows[1])) echo "  Row 1:  " . implode(' | ', array_slice($rows[1], 0, 8)) . "\n";
    }
    die("\nFix the Excel read mapping then re-run.\n");
}

// Show first 3 records
echo "Sample records:\n";
foreach (array_slice($all, 0, 3) as $i => $s) {
    echo ($i+1).". {$s['full_name']} | {$s['index_number']} | {$s['phone']} | {$s['program']} | {$s['set']}\n";
}
echo "\n";

// Import into students DB
$conn = getStudentsConnection();
if (!$conn) { die("Cannot connect to students DB\n"); }

// Ensure students table has needed columns
$conn->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS `set_name` varchar(50) DEFAULT NULL");
$conn->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS `intake_year` varchar(10) DEFAULT NULL");
$conn->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS `intake_period` varchar(20) DEFAULT NULL");
$conn->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS `other_name` varchar(100) DEFAULT NULL");

$ins = $conn->prepare("INSERT INTO students
    (student_number, index_number, first_name, surname, other_name,
     email, phone, program, level, set_name, intake_year, intake_period,
     gender, status, is_first_login, password_changed, created_at, updated_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'Active',1,0,NOW(),NOW())
    ON DUPLICATE KEY UPDATE
        first_name=VALUES(first_name), surname=VALUES(surname),
        phone=VALUES(phone), program=VALUES(program),
        set_name=VALUES(set_name), intake_year=VALUES(intake_year),
        updated_at=NOW()");

if (!$ins) { die("Prepare failed: " . $conn->error . "\n"); }

$imported = 0; $skipped = 0;
foreach ($all as $s) {
    $idx = trim($s['index_number'] ?? '');
    $fn  = trim($s['first_name']   ?? '');
    $sn  = trim($s['surname']      ?? '');
    $full= trim($s['full_name']    ?? '');

    // Skip empty rows
    if ($idx === '' && $full === '') { $skipped++; continue; }

    // Parse name if first_name empty
    if ($fn === '' && $full !== '') {
        $parts = preg_split('/\s+/', $full, 3);
        $sn = $parts[0] ?? '';
        $fn = $parts[1] ?? $sn;
    }
    if ($fn === '') { $skipped++; continue; }

    $num   = $idx ?: ('STU' . str_pad($imported + 1, 5, '0', STR_PAD_LEFT));
    $other = trim($s['other_name'] ?? '');
    $email = trim($s['email'] ?? '');
    $phone = trim($s['phone'] ?? '');
    $prog  = trim($s['program'] ?? '');
    $level = trim($s['level']   ?? '');
    $set   = trim($s['set']     ?? '');
    $yr    = trim($s['intake_year']   ?? '');
    $per   = trim($s['intake_period'] ?? '');
    $gender= trim($s['gender'] ?? '');

    $ins->bind_param('sssssssssssss',
        $num, $idx, $fn, $sn, $other,
        $email, $phone, $prog, $level, $set, $yr, $per, $gender);
    if ($ins->execute()) { $imported++; }
    else { error_log("Import err [{$fn} {$sn}]: " . $ins->error); $skipped++; }
}
$ins->close();

echo "Import complete: $imported imported, $skipped skipped.\n";

// Final count
$r = $conn->query("SELECT COUNT(*) AS c FROM students");
echo "Total students in DB: " . $r->fetch_assoc()['c'] . "\n";

unlink(__FILE__);
echo "Done!\n";
