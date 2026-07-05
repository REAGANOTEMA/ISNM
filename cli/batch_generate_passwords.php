<?php
/**
 * Batch Password Generator for Excel Students
 * 
 * Usage: php cli/batch_generate_passwords.php [--dry-run] [--csv]
 * 
 * Scans all Excel files in students_data/, creates DB records with
 * generated passwords for any student who doesn't already have one.
 */

$dryRun = in_array('--dry-run', $argv ?? []);
$csvOutput = in_array('--csv', $argv ?? []);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SimpleXlsxReader.php';

function generatePassword($length = 10): string {
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghjkmnpqrstuvwxyz';
    $digits = '23456789';
    $all = $upper . $lower . $digits;
    $password = '';
    $password .= $upper[random_int(0, strlen($upper) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $digits[random_int(0, strlen($digits) - 1)];
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    return str_shuffle($password);
}

function splitFullName($fullName): array {
    $parts = preg_split('/\s+/', trim($fullName));
    if (count($parts) >= 2) {
        $first = array_shift($parts);
        $surname = array_pop($parts);
        return [$first, $surname];
    }
    return [$parts[0] ?? '', $parts[0] ?? ''];
}

function isNonStudentRow(string $fullName): bool {
    $fullName = trim($fullName);
    if ($fullName === '' || strlen($fullName) < 3) return true;
    $lower = strtolower($fullName);
    $patterns = ['verified by', 'iganga school', 'students capture form', 'ministry of education',
        'health education', 'verification tool', 'name of the institution', 'program: certificate',
        'program: diploma', 'first name sur', 'diploma midwives', 'diploma nurses',
        'certificate nurses', 'set 25 nurses', 'set 25 midwives', 'set 26',
        'set 27', 'set 28'];
    foreach ($patterns as $pat) {
        if (strpos($lower, $pat) !== false) return true;
    }
    return false;
}

function normalizeHeader($h): string {
    $h = strtolower(trim((string) $h));
    $h = preg_replace('/[^a-z0-9]+/', '_', $h);
    $h = trim($h, '_');
    $replace = [
        'student_no' => 'student_number', 'student_id' => 'student_number',
        'index_no' => 'index_number', 'reg_no' => 'registration_number',
        'nsin' => 'index_number', 'phone_no' => 'phone',
        'phone_number' => 'phone', 'mobile_number' => 'phone',
        'sur_name' => 'surname', 'last_name' => 'surname',
        'firstname' => 'first_name', 'dob' => 'date_of_birth',
        'intake_year' => 'year', 'class_set' => 'set', 'set_name' => 'set',
    ];
    return $replace[$h] ?? $h;
}

$studentsConn = getStudentsConnection();
if (!$studentsConn) {
    fwrite(STDERR, "ERROR: Could not connect to students database.\n");
    exit(1);
}

// ── Preload all existing student index numbers & passwords from DB ──
echo "Loading existing student records from database... ";
$existingMap = []; // index_number => ['id' => ..., 'has_password' => bool]
$r = $studentsConn->query("SELECT id, index_number, password FROM students WHERE index_number IS NOT NULL AND index_number != ''");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $existingMap[$row['index_number']] = [
            'id' => $row['id'],
            'has_password' => !empty($row['password']),
        ];
    }
    $r->free();
}
echo count($existingMap) . " records loaded.\n\n";

// ── Get Excel files ──
$dataDir = __DIR__ . '/../students_data/';
$files = glob($dataDir . '*.xlsx') ?: [];
$files = array_merge($files, glob($dataDir . '*.xlsm') ?: []);
$files = array_merge($files, glob($dataDir . '*.xls') ?: []);

if (empty($files)) {
    fwrite(STDERR, "No Excel files found in students_data/\n");
    exit(1);
}

$totalCreated = 0;
$totalUpdated = 0;
$totalSkipped = 0;
$totalErrors = 0;
$results = [];

echo "ISNM Batch Password Generator\n";
echo str_repeat('=', 60) . "\n";
if ($dryRun) echo " [DRY RUN]\n\n";

foreach ($files as $file) {
    $filename = basename($file);
    $rows = SimpleXlsxReader::read($file);
    if (empty($rows)) { echo "  {$filename}: empty\n"; continue; }

    // Find header row
    $headerIdx = null;
    foreach ($rows as $idx => $row) {
        $score = 0;
        foreach ($row as $cell) {
            $n = normalizeHeader((string) $cell);
            if (in_array($n, ['surname', 'first_name', 'full_name', 'name', 'index_number', 'phone', 'email', 'program'])) $score++;
        }
        if ($score >= 2) { $headerIdx = $idx; break; }
    }
    if ($headerIdx === null) { echo "  {$filename}: no header\n"; continue; }

    // Map headers
    $headerMap = [];
    foreach ($rows[$headerIdx] as $i => $h) {
        $headerMap[$i] = normalizeHeader((string) $h);
    }

    $pendingInserts = [];
    $pendingUpdates = [];

    for ($i = $headerIdx + 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (empty(array_filter($row, fn($v) => trim((string) $v) !== ''))) continue;

        $get = function($key) use ($row, $headerMap) {
            foreach ($headerMap as $ci => $hk) {
                if ($hk === $key) return trim((string) ($row[$ci] ?? ''));
            }
            return '';
        };

        $indexNumber = $get('index_number') ?: $get('student_number');
        $fullName = $get('full_name') ?: trim($get('first_name') . ' ' . $get('surname'));
        $phone = $get('phone');
        $program = $get('program');
        $level = $get('level');
        $setName = $get('set');
        $email = $get('email');

        if (!$fullName && !$indexNumber && !$phone) continue;
        if (isNonStudentRow($fullName)) { $totalSkipped++; continue; }

        // Use index if available, else generate placeholder
        if (!$indexNumber) {
            $indexNumber = 'XLSX' . date('Ymd') . sprintf('%04d', $i);
        }

        // Check existing
        $existing = $existingMap[$indexNumber] ?? null;
        if ($existing && $existing['has_password']) {
            $totalSkipped++;
            continue;
        }

        $plainPassword = generatePassword(10);
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);

        if ($existing) {
            $pendingUpdates[] = [$hash, $existing['id'], $indexNumber, $fullName, $plainPassword, $filename];
        } else {
            [$firstName, $surname] = splitFullName($fullName);
            $pendingInserts[] = [$indexNumber, $firstName, $surname, $email, $phone, $program, $level, $setName, $hash, $fullName, $plainPassword, $filename];
        }
    }

    // ── Batch execute ──
    $created = 0;
    $updated = 0;

    if (!empty($pendingInserts) && !$dryRun) {
        $stmt = $studentsConn->prepare("INSERT INTO students (student_number, index_number, first_name, surname, other_name, email, phone, program, level, set_name, status, password, is_first_login, password_changed, created_at, updated_at) VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, ?, 'Active', ?, FALSE, TRUE, NOW(), NOW())");
        if ($stmt) {
            foreach ($pendingInserts as $row) {
                $stmt->bind_param('ssssssssss', $row[0], $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]);
                if ($stmt->execute()) $created++;
                else $totalErrors++;
            }
            $stmt->close();
        }
    } else {
        $created = count($pendingInserts);
    }

    if (!empty($pendingUpdates) && !$dryRun) {
        $stmt = $studentsConn->prepare("UPDATE students SET password=?, password_changed=TRUE, is_first_login=FALSE, login_attempts=0, locked_until=NULL, updated_at=NOW() WHERE id=?");
        if ($stmt) {
            foreach ($pendingUpdates as $row) {
                $stmt->bind_param('si', $row[0], $row[1]);
                if ($stmt->execute()) $updated++;
                else $totalErrors++;
            }
            $stmt->close();
        }
    } else {
        $updated = count($pendingUpdates);
    }

    $totalCreated += $created;
    $totalUpdated += $updated;

    foreach ($pendingInserts as $p) {
        $results[] = ['action' => 'Created', 'index' => $p[0], 'name' => $p[9], 'password' => $p[10], 'file' => $p[11]];
    }
    foreach ($pendingUpdates as $p) {
        $results[] = ['action' => 'Updated', 'index' => $p[2], 'name' => $p[3], 'password' => $p[4], 'file' => $p[5]];
    }

    echo "  {$filename}: {$created} created, {$updated} updated\n";
}

// ── Summary ──
echo str_repeat('=', 60) . "\n";
echo "SUMMARY: {$totalCreated} created, {$totalUpdated} updated, {$totalSkipped} skipped, {$totalErrors} errors\n";
if ($dryRun) echo "  (dry run — no changes made)\n";

if ($csvOutput && !empty($results)) {
    echo "\nindex_number,full_name,password,action,source_file\n";
    foreach ($results as $r) {
        echo "{$r['index']},{$r['name']},{$r['password']},{$r['action']},{$r['file']}\n";
    }
}
echo "\n";
