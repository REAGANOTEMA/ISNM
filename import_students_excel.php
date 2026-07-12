<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/staff_dashboard_access.php';
bootstrapStaffDashboard();

require_once 'config/database.php';
require_once 'includes/SimpleXlsxReader.php';

$conn = getStudentsConnection();
if (!$conn) {
    $_SESSION['error'] = 'Database connection failed';
    header('Location: student-login.php');
    exit;
}
$conn->set_charset('utf8mb4');

// â”€â”€ Column name normalisation map â”€â”€
$KNOWN_COLUMNS = [
    'full_name'                       => 'full_name',
    'name'                            => 'full_name',
    'student_name'                    => 'full_name',
    'first_name'                      => 'first_name',
    'firstname'                       => 'first_name',
    'surname'                         => 'surname',
    'last_name'                       => 'surname',
    'lastname'                        => 'surname',
    'other_name'                      => 'other_name',
    'othername'                       => 'other_name',
    'registration_number'             => 'registration_number',
    'reg_no'                          => 'registration_number',
    'reg_number'                      => 'registration_number',
    'student_number'                  => 'student_number',
    'student_no'                      => 'student_number',
    'student_id'                      => 'student_number',
    'index_number'                    => 'index_number',
    'index_no'                        => 'index_number',
    'index'                           => 'index_number',
    'national_student_id_number'      => 'national_student_id_number',
    'national_id'                     => 'national_student_id_number',
    'nsin'                            => 'national_student_id_number',
    'nsin_reg'                        => 'national_student_id_number',
    'phone'                           => 'phone',
    'phone_no'                        => 'phone',
    'phone_number'                    => 'phone',
    'mobile_number'                   => 'phone',
    'students_contact'                => 'phone',
    'student_contact'                 => 'phone',
    'contact'                         => 'phone',
    'email'                           => 'email',
    'e_mail'                          => 'email',
    'program'                         => 'program',
    'course'                          => 'program',
    'course_name'                     => 'program',
    'level'                           => 'level',
    'year_of_study'                   => 'level',
    'set'                             => 'set_name',
    'set_name'                        => 'set_name',
    'class'                           => 'set_name',
    'intake_year'                     => 'intake_year',
    'year'                            => 'intake_year',
    'intake'                          => 'intake_year',
    'intake_period'                   => 'intake_period',
    'intake_date'                     => 'intake_date',
    'date_of_birth'                   => 'date_of_birth',
    'dob'                             => 'date_of_birth',
    'birth_date'                      => 'date_of_birth',
    'gender'                          => 'gender',
    'sex'                             => 'gender',
    'nationality'                     => 'nationality',
    'district'                        => 'district',
    'address'                         => 'address',
    'emergency_contact_name'          => 'emergency_contact_name',
    'emergency_name'                  => 'emergency_contact_name',
    'emergency_contact_phone'         => 'emergency_contact_phone',
    'emergency_phone'                 => 'emergency_contact_phone',
    'emergency_contact_email'         => 'emergency_contact_email',
    'emergency_email'                 => 'emergency_contact_email',
    'guardian_name'                   => 'guardian_name',
    'guardian_phone'                  => 'guardian_phone',
    'guardian_contact'                => 'guardian_phone',
    'course_codes'                    => 'course_codes',
    'subject_codes'                   => 'course_codes',
    'no_of_papers'                    => 'no_of_papers',
    'papers'                          => 'no_of_papers',
    'profile_picture'                 => 'profile_picture',
    'photo'                           => 'profile_picture',
    'passport_photo'                  => 'passport_photo',
    'student_category'                => 'student_category',
    'category'                        => 'student_category',
    'marital_status'                  => 'marital_status',
    'religion'                        => 'religion',
    'sponsor'                         => 'sponsor',
];

// â”€â”€ Helper: normalise a single header â”€â”€
function normaliseHeader($raw) {
    global $KNOWN_COLUMNS;
    $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $raw)));
    $key = trim($key, '_');
    return $KNOWN_COLUMNS[$key] ?? $key;
}

// â”€â”€ Helper: guess program, level, set, year from filename â”€â”€
function guessFromFilename($filename) {
    $info = ['program' => '', 'level' => '', 'set_name' => '', 'intake_year' => '', 'intake_period' => ''];
    $f = strtolower($filename);

    if (strpos($f, 'diploma') !== false) {
        $info['level'] = 'Diploma';
        $info['program'] = 'Diploma in Nursing';
        if (strpos($f, 'midwife') !== false || strpos($f, 'mid') !== false) {
            $info['program'] = 'Diploma in Midwifery';
        }
    } elseif (strpos($f, 'nurse') !== false || strpos($f, 'nursing') !== false) {
        $info['program'] = 'Certificate in Nursing';
        $info['level'] = 'Certificate';
    } elseif (strpos($f, 'midwife') !== false || strpos($f, 'mid') !== false) {
        $info['program'] = 'Certificate in Midwifery';
        $info['level'] = 'Certificate';
    }

    if (preg_match('/set[_\s]?(\d+)/i', $f, $m)) {
        $info['set_name'] = 'Set ' . $m[1];
    }

    if (preg_match('/(20\d{2})/', $f, $m)) {
        $info['intake_year'] = $m[1];
    }

    if (stripos($f, 'july') !== false || stripos($f, 'jul') !== false) {
        $info['intake_period'] = 'July';
    } elseif (stripos($f, 'january') !== false || stripos($f, 'jan') !== false) {
        $info['intake_period'] = 'January';
    }

    return $info;
}

// â”€â”€ Helper: validate program value â”€â”€
function isValidProgram($val) {
    if (empty($val)) return false;
    $known = ['Certificate in Nursing','Certificate in Midwifery','Diploma in Nursing','Diploma in Midwifery','Diploma in Nursing Education'];
    if (in_array($val, $known)) return true;
    if (preg_match('/^[0-9\/]+$/', $val)) return false;
    if (preg_match('/^[A-Za-z0-9 ]{1,2}$/', trim($val))) return false;
    $garbage = ['contact','course','id','mtc','sign','signature','students contact','parent/guardan','unmeb certificate','eng','issued back on'];
    if (in_array(strtolower(trim($val)), $garbage)) return false;
    if (strlen(trim($val)) > 50) return false;
    return true;
}

// â”€â”€ Helper: split full_name into name parts â”€â”€
function splitName($full) {
    $parts = array_values(array_filter(explode(' ', trim($full))));
    $first = $parts[0] ?? '';
    $surname = count($parts) > 1 ? end($parts) : '';
    $other = count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : '';
    return ['first_name' => $first, 'surname' => $surname, 'other_name' => $other];
}

// â”€â”€ Process a single Excel file â”€â”€
function processFile($path, $conn) {
    $filename = basename($path);
    $fileGuess = guessFromFilename($filename);

    try {
        $rows = SimpleXlsxReader::read($path);
    } catch (Exception $e) {
        return ['imported' => 0, 'skipped' => 0, 'errors' => [$filename . ': ' . $e->getMessage()]];
    }

    if (empty($rows) || count($rows) < 2) {
        return ['imported' => 0, 'skipped' => 0, 'errors' => []];
    }

    // â”€â”€ Find header row â”€â”€
    $headerRowIdx = 0;
    $headerMatchCount = 0;
    $knownKeys = array_keys($GLOBALS['KNOWN_COLUMNS']);
    foreach ($rows as $i => $row) {
        $match = 0;
        foreach ($row as $cell) {
            $norm = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $cell ?? '')));
            $norm = trim($norm, '_');
            if (in_array($norm, $knownKeys, true)) {
                $match++;
            }
        }
        if ($match > $headerMatchCount) {
            $headerMatchCount = $match;
            $headerRowIdx = $i;
        }
    }

    // â”€â”€ Parse headers â”€â”€
    $headers = [];
    foreach ($rows[$headerRowIdx] as $cell) {
        $headers[] = normaliseHeader($cell ?? '');
    }

    $imported = 0;
    $skipped = 0;
    $errors = [];

    // â”€â”€ Process data rows â”€â”€
    for ($ri = $headerRowIdx + 1; $ri < count($rows); $ri++) {
        $row = $rows[$ri];
        $data = [];
        foreach ($headers as $ci => $key) {
            $data[$key] = isset($row[$ci]) ? trim((string)$row[$ci]) : '';
        }

        $fullName = $data['full_name'] ?? '';

        // Build full_name from parts if no full_name column
        if (empty($fullName)) {
            $parts = array_filter([$data['first_name'] ?? '', $data['other_name'] ?? '', $data['surname'] ?? '']);
            $fullName = implode(' ', $parts);
        }
        if (empty($fullName)) continue;

        // Build record
        $rec = [
            'full_name'                => $fullName,
            'first_name'               => $data['first_name'] ?? '',
            'surname'                  => $data['surname'] ?? '',
            'other_name'               => $data['other_name'] ?? '',
            'student_number'           => $data['student_number'] ?? '',
            'registration_number'      => $data['registration_number'] ?? '',
            'index_number'             => $data['index_number'] ?? '',
            'national_student_id_number' => $data['national_student_id_number'] ?? '',
            'phone'                    => $data['phone'] ?? '',
            'email'                    => $data['email'] ?? '',
            'program'                  => $data['program'] ?? '',
            'level'                    => $data['level'] ?? '',
            'set_name'                 => $data['set_name'] ?? '',
            'intake_year'              => $data['intake_year'] ?? '',
            'intake_period'            => $data['intake_period'] ?? '',
            'intake_date'              => $data['intake_date'] ?? '',
            'date_of_birth'            => $data['date_of_birth'] ?? '',
            'gender'                   => $data['gender'] ?? '',
            'nationality'              => $data['nationality'] ?? '',
            'district'                 => $data['district'] ?? '',
            'address'                  => $data['address'] ?? '',
            'emergency_contact_name'   => $data['emergency_contact_name'] ?? '',
            'emergency_contact_phone'  => $data['emergency_contact_phone'] ?? '',
            'emergency_contact_email'  => $data['emergency_contact_email'] ?? '',
            'guardian_name'            => $data['guardian_name'] ?? '',
            'guardian_phone'           => $data['guardian_phone'] ?? '',
            'course_codes'             => $data['course_codes'] ?? '',
            'no_of_papers'             => $data['no_of_papers'] ?? '',
            'student_category'         => $data['student_category'] ?? '',
            'marital_status'           => $data['marital_status'] ?? '',
            'religion'                 => $data['religion'] ?? '',
            'sponsor'                  => $data['sponsor'] ?? '',
        ];

        // Fill gaps from filename guesses
        foreach ($fileGuess as $k => $v) {
            if (empty($rec[$k]) && !empty($v)) {
                $rec[$k] = $v;
            }
        }

        // Validate program â€” if it looks like garbage, use filename guess or level
        if (!empty($rec['program']) && !isValidProgram($rec['program'])) {
            $rec['program'] = $fileGuess['program'] ?? '';
            $rec['course'] = '';
        }
        if (!empty($rec['course']) && !isValidProgram($rec['course'])) {
            $rec['course'] = $fileGuess['program'] ?? '';
        }

        // Split full_name into parts if name fields missing
        if (empty($rec['first_name']) && empty($rec['surname'])) {
            $parts = splitName($fullName);
            $rec['first_name'] = $parts['first_name'];
            $rec['surname'] = $parts['surname'];
            $rec['other_name'] = $parts['other_name'];
        }

        // Normalise gender
        $gender = strtolower($rec['gender']);
        if (in_array($gender, ['m', 'male', 'boy', 'masculine'])) {
            $rec['gender'] = 'Male';
        } elseif (in_array($gender, ['f', 'female', 'girl', 'feminine'])) {
            $rec['gender'] = 'Female';
        } elseif (empty($rec['gender'])) {
            $rec['gender'] = 'Other';
        }

        // Parse dates
        $intakeDate = null;
        if (!empty($rec['intake_date'])) {
            $ts = strtotime($rec['intake_date']);
            if ($ts) $intakeDate = date('Y-m-d', $ts);
        }
        $dob = null;
        if (!empty($rec['date_of_birth'])) {
            $ts = strtotime($rec['date_of_birth']);
            if ($ts) $dob = date('Y-m-d', $ts);
        }

        $intakeYear = $rec['intake_year'] ? (int)$rec['intake_year'] : null;

        // â”€â”€ Dedup check â”€â”€
        $dedupClauses = [];
        $dedupParams = [];
        $dedupTypes = '';

        if (!empty($rec['registration_number'])) {
            $dedupClauses[] = 'registration_number = ?';
            $dedupParams[] = $rec['registration_number'];
            $dedupTypes .= 's';
        }
        if (!empty($rec['index_number'])) {
            $dedupClauses[] = 'index_number = ?';
            $dedupParams[] = $rec['index_number'];
            $dedupTypes .= 's';
        }
        if (!empty($rec['national_student_id_number'])) {
            $dedupClauses[] = 'national_student_id_number = ?';
            $dedupParams[] = $rec['national_student_id_number'];
            $dedupTypes .= 's';
        }
        if (!empty($rec['student_number'])) {
            $dedupClauses[] = 'student_number = ?';
            $dedupParams[] = $rec['student_number'];
            $dedupTypes .= 's';
        }

        $isDuplicate = false;
        if (!empty($dedupClauses)) {
            $sql = 'SELECT id FROM students WHERE ' . implode(' OR ', $dedupClauses) . ' LIMIT 1';
            $st = $conn->prepare($sql);
            if ($st) {
                $st->bind_param($dedupTypes, ...$dedupParams);
                if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); };
                if ($st->get_result()->num_rows > 0) {
                    $isDuplicate = true;
                }
                $st->close();
            }
        }

        if ($isDuplicate) {
            $skipped++;
            continue;
        }

        // â”€â”€ Insert â”€â”€
        $sql = "INSERT INTO students (
            first_name, surname, other_name, full_name,
            student_number, registration_number, index_number, national_student_id_number,
            phone, email,
            program, level, set_name,
            intake_year, intake_period, intake_date,
            date_of_birth, gender,
            nationality, district, address,
            emergency_contact_name, emergency_contact_phone, emergency_contact_email,
            guardian_name, guardian_phone,
            student_category, marital_status, religion, sponsor,
            course_codes, no_of_papers,
            status, created_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            'Active', NOW()
        )";

        $st = $conn->prepare($sql);
        if (!$st) {
            $errors[] = $fullName . ': prepare failed - ' . $conn->error;
            continue;
        }

        // Prepare values (use empty string for null)
        $v = [
            $rec['first_name'],
            $rec['surname'],
            $rec['other_name'],
            $rec['full_name'],
            $rec['student_number'] ?? '',
            $rec['registration_number'] ?? '',
            $rec['index_number'] ?? '',
            $rec['national_student_id_number'] ?? '',
            $rec['phone'] ?? '',
            $rec['email'] ?? '',
            $rec['program'] ?? '',
            $rec['level'] ?? '',
            $rec['set_name'] ?? '',
            (string)($intakeYear ?? ''),
            $rec['intake_period'] ?? '',
            $intakeDate ?? '',
            $dob ?? '',
            $rec['gender'],
            $rec['nationality'] ?? '',
            $rec['district'] ?? '',
            $rec['address'] ?? '',
            $rec['emergency_contact_name'] ?? '',
            $rec['emergency_contact_phone'] ?? '',
            $rec['emergency_contact_email'] ?? '',
            $rec['guardian_name'] ?? '',
            $rec['guardian_phone'] ?? '',
            $rec['student_category'] ?? '',
            $rec['marital_status'] ?? '',
            $rec['religion'] ?? '',
            $rec['sponsor'] ?? '',
            $rec['course_codes'] ?? '',
            $rec['no_of_papers'] ?? '',
        ];
        $types = str_repeat('s', count($v));
        $st->bind_param($types, ...$v);

        if ($st->execute()) {
            $imported++;
        } else {
            $errors[] = $fullName . ': ' . $st->error;
        }
        $st->close();
    }

    return [
        'imported' => $imported,
        'skipped'  => $skipped,
        'errors'   => $errors,
        'file'     => $filename,
    ];
}

// â”€â”€ Handle import action â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    $dir = __DIR__ . '/students_data/';
    $files = [];
    if (is_dir($dir)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $fi) {
            if ($fi->isFile() && in_array(strtolower($fi->getExtension()), ['xlsx', 'xlsm'], true)) {
                $files[] = $fi->getPathname();
            }
        }
        natcasesort($files);
        $files = array_values($files);
    }

    if (empty($files)) {
        $_SESSION['error'] = 'No Excel files found in students_data/';
    } else {
        $total = ['imported' => 0, 'skipped' => 0, 'errors' => [], 'file_results' => []];
        foreach ($files as $path) {
            $res = processFile($path, $conn);
            $total['imported'] += $res['imported'];
            $total['skipped'] += $res['skipped'];
            $total['errors'] = array_merge($total['errors'], $res['errors']);
            $total['file_results'][] = $res;
        }

        // Verify with count
        $countResult = $conn->query("SELECT COUNT(*) as cnt FROM students");
        $totalInDb = $countResult ? $countResult->fetch_assoc()['cnt'] : 0;

        $_SESSION['import_result'] = $total;
        $_SESSION['total_in_db'] = $totalInDb;

        if (!empty($total['errors'])) {
            $_SESSION['error'] = 'Import completed with ' . count($total['errors']) . ' error(s). Imported: ' . $total['imported'] . ', Skipped: ' . $total['skipped'];
        } else {
            $_SESSION['success'] = 'Import completed! ' . $total['imported'] . ' students imported, ' . $total['skipped'] . ' skipped (duplicates). Total in database: ' . $totalInDb;
        }
    }

    header('Location: import_students_excel.php');
    exit;
}

// â”€â”€ Gather file info for display â”€â”€
$dataDir = __DIR__ . '/students_data/';
$excelFiles = [];
if (is_dir($dataDir)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataDir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $fi) {
        if ($fi->isFile() && in_array(strtolower($fi->getExtension()), ['xlsx', 'xlsm'], true)) {
            $excelFiles[] = $fi->getPathname();
        }
    }
    natcasesort($excelFiles);
    $excelFiles = array_values($excelFiles);
}

// Get current DB count
$dbCount = 0;
$r = $conn->query("SELECT COUNT(*) as cnt FROM students");
if ($r) $dbCount = $r->fetch_assoc()['cnt'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Students from Excel | ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f4f8; min-height: 100vh; padding: 30px 20px; }
        .card-main { background: white; border-radius: 20px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); max-width: 960px; margin: 0 auto; overflow: hidden; }
        .card-header-custom { background: linear-gradient(135deg, #1e3a8a, #0f4c3a); color: white; padding: 28px 32px; }
        .card-header-custom h2 { font-weight: 800; margin: 0; font-size: 1.5rem; }
        .card-header-custom p { opacity: 0.85; margin: 6px 0 0; font-size: 0.9rem; }
        .card-body-custom { padding: 28px 32px; }
        .stat-badge { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 12px; font-weight: 600; }
        .stat-badge i { font-size: 1.2rem; }
        .file-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px; transition: all 0.2s; }
        .file-row:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .file-row .file-icon { width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, #059669, #10b981); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; }
        .file-row .file-info { flex: 1; margin-left: 14px; }
        .file-row .file-info strong { display: block; font-size: 0.9rem; color: #0f172a; }
        .file-row .file-info small { color: #64748b; font-size: 0.8rem; }
        .file-row .file-size { font-size: 0.82rem; color: #64748b; font-weight: 500; white-space: nowrap; }
        .btn-import { background: linear-gradient(135deg, #059669, #10b981); color: white; border: none; padding: 14px 36px; border-radius: 12px; font-weight: 700; font-size: 1rem; transition: all 0.25s; }
        .btn-import:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(5,150,105,0.3); color: white; }
        .btn-import:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .result-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .result-card.error { background: #fef2f2; border-color: #fca5a5; }
        .result-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
        .result-item { text-align: center; padding: 16px; background: white; border-radius: 10px; }
        .result-item .num { font-size: 1.8rem; font-weight: 800; color: #0f172a; }
        .result-item .lbl { font-size: 0.78rem; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px; }
        .result-item.success .num { color: #059669; }
        .result-item.warning .num { color: #d97706; }
        .result-item.danger .num { color: #dc2626; }
        .table-result thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .table-result td { font-size: 0.85rem; vertical-align: middle; }
        .summary-bar { background: linear-gradient(135deg, #1e3a8a, #0f4c3a); border-radius: 14px; padding: 20px 24px; color: white; margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; }
        .summary-bar .big { font-size: 2rem; font-weight: 800; line-height: 1; }
        .summary-bar .lbl { font-size: 0.78rem; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; }
        .progress-thin { height: 4px; background: rgba(255,255,255,0.2); border-radius: 2px; overflow: hidden; }
        .progress-thin .bar { height: 100%; background: #10b981; border-radius: 2px; transition: width 0.6s ease; }
        .error-detail { font-size: 0.82rem; color: #dc2626; padding: 4px 0; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="card-main">
        <div class="card-header-custom">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2><i class="fas fa-database me-2"></i>Student Data Import</h2>
                    <p>Import all Excel student profiles into the database</p>
                </div>
                <a href="dashboards/director-ict.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
            </div>
        </div>
        <div class="card-body-custom">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 py-3">
                    <i class="fas fa-check-circle fs-5"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 py-3">
                    <i class="fas fa-exclamation-circle fs-5"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php
            // Show detailed import results
            if (isset($_SESSION['import_result'])):
                $res = $_SESSION['import_result'];
                $totalDb = $_SESSION['total_in_db'] ?? 0;
                unset($_SESSION['import_result'], $_SESSION['total_in_db']);
            ?>
            <div class="result-card">
                <h5 class="fw-bold mb-3"><i class="fas fa-check-circle text-success me-2"></i>Import Results</h5>
                <div class="result-grid">
                    <div class="result-item success"><div class="num"><?= $res['imported'] ?></div><div class="lbl">Imported</div></div>
                    <div class="result-item warning"><div class="num"><?= $res['skipped'] ?></div><div class="lbl">Skipped</div></div>
                    <div class="result-item danger"><div class="num"><?= count($res['errors']) ?></div><div class="lbl">Errors</div></div>
                    <div class="result-item"><div class="num"><?= $totalDb ?></div><div class="lbl">Total in DB</div></div>
                </div>
                <?php if (!empty($res['file_results'])): ?>
                <div class="mt-3">
                    <h6 class="fw-bold text-muted mb-2">Per-File Breakdown</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-result mb-0">
                            <thead><tr><th>File</th><th class="text-center">Imported</th><th class="text-center">Skipped</th><th class="text-center">Errors</th></tr></thead>
                            <tbody>
                                <?php foreach ($res['file_results'] as $fr): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($fr['file'] ?? basename($fr['file'] ?? '')) ?></code></td>
                                    <td class="text-center fw-bold text-success"><?= (int)$fr['imported'] ?></td>
                                    <td class="text-center text-warning"><?= (int)$fr['skipped'] ?></td>
                                    <td class="text-center text-danger"><?= count($fr['errors'] ?? []) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($res['errors'])): ?>
                <div class="mt-3">
                    <h6 class="fw-bold text-danger mb-2"><i class="fas fa-exclamation-triangle me-1"></i>Error Details</h6>
                    <?php foreach (array_slice($res['errors'], 0, 20) as $e): ?>
                    <div class="error-detail"><i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                    <?php if (count($res['errors']) > 20): ?>
                    <div class="text-muted small mt-1">...and <?= count($res['errors']) - 20 ?> more error(s)</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Summary bar -->
            <div class="summary-bar">
                <div>
                    <div class="big"><?= $dbCount ?></div>
                    <div class="lbl">Students in Database</div>
                </div>
                <div>
                    <div class="big"><?= count($excelFiles) ?></div>
                    <div class="lbl">Excel Files Ready</div>
                </div>
                <div>
                    <div class="progress-thin" style="width:200px;max-width:100%;">
                        <div class="bar" style="width:<?= min(100, $dbCount > 0 ? 100 : 0) ?>%"></div>
                    </div>
                </div>
                <div class="text-end">
                    <small class="opacity-75">Data sourced from students_data/</small>
                </div>
            </div>

            <!-- File list -->
            <?php if (!empty($excelFiles)): ?>
            <h5 class="fw-bold mb-3"><i class="fas fa-file-excel text-success me-2"></i>Excel Files to Import</h5>
            <div class="mb-4">
                <?php foreach ($excelFiles as $path):
                    $name = basename($path);
                    $size = filesize($path);
                    $sizeStr = $size > 1024 ? round($size / 1024, 1) . ' KB' : $size . ' B';
                ?>
                <div class="file-row">
                    <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                    <div class="file-info">
                        <strong><?= htmlspecialchars($name) ?></strong>
                        <small>Ready for import</small>
                    </div>
                    <div class="file-size"><i class="fas fa-database me-1"></i><?= $sizeStr ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" onsubmit="document.getElementById('importBtn').disabled=true;document.getElementById('importBtn').innerHTML='<i class=\'fas fa-spinner fa-spin me-2\'></i>Importing...'">
                <div class="d-flex gap-3 flex-wrap align-items-center">
                    <button type="submit" name="import" class="btn-import" id="importBtn">
                        <i class="fas fa-upload me-2"></i>Import All Student Data
                    </button>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Duplicates are detected by registration_number, index_number, national ID, or student_number
                    </small>
                </div>
            </form>

            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-file-excel text-muted" style="font-size:3rem;opacity:0.4;"></i>
                <h5 class="mt-3 text-muted">No Excel files found</h5>
                <p>Place .xlsx files in the <code>students_data/</code> directory</p>
            </div>
            <?php endif; ?>

            <hr class="my-4">

            <div class="row g-3 text-muted small">
                <div class="col-md-4"><i class="fas fa-check-circle text-success me-1"></i> <strong>Columns imported:</strong> full_name, first_name, surname, other_name, student_number, registration_number, index_number, national_student_id_number, phone, email, program, level, set_name, intake_year, intake_period, intake_date, date_of_birth, gender, nationality, district, address, emergency_contact_name, emergency_contact_phone, emergency_contact_email, guardian_name, guardian_phone, course_codes, no_of_papers, student_category, marital_status, religion, sponsor</div>
                <div class="col-md-4"><i class="fas fa-brain text-info me-1"></i> <strong>Smart detection:</strong> Column headers are auto-detected with 60+ name variations. Program, level, set, and year are guessed from filenames when missing.</div>
                <div class="col-md-4"><i class="fas fa-shield-alt text-primary me-1"></i> <strong>Safe to re-run:</strong> Existing records are skipped via multi-field dedup (reg_number, index_number, national_id, student_number). No data is overwritten or lost.</div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
