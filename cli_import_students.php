<?php
/**
 * CLI student import script â€” bypasses web session/redirect complexity.
 * Usage: php cli_import_students.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SimpleXlsxReader.php';

$conn = getStudentsConnection();
if (!$conn) { die("Database connection failed\n"); }
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

function normaliseHeader($raw) {
    global $KNOWN_COLUMNS;
    $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $raw)));
    $key = trim($key, '_');
    return $KNOWN_COLUMNS[$key] ?? $key;
}

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

// Check if a program value looks valid (not a phone number, single char, or garbage)
function isValidProgram($val) {
    if (empty($val)) return false;
    $known = ['Certificate in Nursing','Certificate in Midwifery','Diploma in Nursing','Diploma in Midwifery','Diploma in Nursing Education'];
    if (in_array($val, $known)) return true;
    if (preg_match('/^[0-9\/]+$/', $val)) return false; // phone numbers / numbers
    if (preg_match('/^[A-Za-z0-9 ]{1,2}$/', trim($val))) return false; // single/double char like B, C, D, 1, CN, CM
    $garbage = ['contact','course','id','mtc','sign','signature','students contact','parent/guardan','unmeb certificate','eng','issued back on'];
    if (in_array(strtolower(trim($val)), $garbage)) return false;
    if (strlen(trim($val)) > 50) return false;
    return true;
}

function splitName($full) {
    $parts = array_values(array_filter(explode(' ', trim($full))));
    $first = $parts[0] ?? '';
    $surname = count($parts) > 1 ? end($parts) : '';
    $other = count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : '';
    return ['first_name' => $first, 'surname' => $surname, 'other_name' => $other];
}

function processFile($path, $conn) {
    global $KNOWN_COLUMNS;
    $knownKeys = array_keys($KNOWN_COLUMNS);
    $filename = basename($path);
    $fileGuess = guessFromFilename($filename);

    try {
        $rows = SimpleXlsxReader::read($path);
    } catch (Exception $e) {
        return ['imported' => 0, 'skipped' => 0, 'errors' => [$filename . ': ' . $e->getMessage()], 'file' => $filename];
    }

    if (empty($rows) || count($rows) < 2) {
        return ['imported' => 0, 'skipped' => 0, 'errors' => [], 'file' => $filename];
    }

    // Find header row
    $headerRowIdx = 0;
    $headerMatchCount = 0;
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

    $headers = [];
    foreach ($rows[$headerRowIdx] as $cell) {
        $headers[] = normaliseHeader($cell ?? '');
    }

    echo "  Headers: " . implode(', ', $headers) . "\n";

    $imported = 0;
    $skipped = 0;
    $errors = [];

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

        $rec = [
            'full_name'                  => $fullName,
            'first_name'                 => $data['first_name'] ?? '',
            'surname'                    => $data['surname'] ?? '',
            'other_name'                 => $data['other_name'] ?? '',
            'student_number'             => $data['student_number'] ?? '',
            'registration_number'        => $data['registration_number'] ?? '',
            'index_number'               => $data['index_number'] ?? '',
            'national_student_id_number' => $data['national_student_id_number'] ?? '',
            'phone'                      => $data['phone'] ?? '',
            'email'                      => $data['email'] ?? '',
            'program'                    => $data['program'] ?? '',
            'level'                      => $data['level'] ?? '',
            'set_name'                   => $data['set_name'] ?? '',
            'intake_year'                => $data['intake_year'] ?? '',
            'intake_period'              => $data['intake_period'] ?? '',
            'intake_date'                => $data['intake_date'] ?? '',
            'date_of_birth'              => $data['date_of_birth'] ?? '',
            'gender'                     => $data['gender'] ?? '',
            'nationality'                => $data['nationality'] ?? '',
            'district'                   => $data['district'] ?? '',
            'address'                    => $data['address'] ?? '',
            'emergency_contact_name'     => $data['emergency_contact_name'] ?? '',
            'emergency_contact_phone'    => $data['emergency_contact_phone'] ?? '',
            'emergency_contact_email'    => $data['emergency_contact_email'] ?? '',
            'guardian_name'              => $data['guardian_name'] ?? '',
            'guardian_phone'             => $data['guardian_phone'] ?? '',
            'guardian_email'             => $data['guardian_email'] ?? '',
            'course_codes'               => $data['course_codes'] ?? '',
            'no_of_papers'               => $data['no_of_papers'] ?? '',
            'student_category'           => $data['student_category'] ?? '',
            'marital_status'             => $data['marital_status'] ?? '',
            'religion'                   => $data['religion'] ?? '',
            'sponsor'                    => $data['sponsor'] ?? '',
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

        // Build full_name from parts if we have parts but no full_name (unlikely but safe)
        if (empty($rec['full_name']) && !empty($rec['first_name'])) {
            $parts = array_filter([$rec['first_name'], $rec['other_name'], $rec['surname']]);
            $rec['full_name'] = implode(' ', $parts);
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

        // Dedup check
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

        // Insert
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

        // Prepare values (use empty string for null to avoid bind_param reference issues)
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

    return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'file' => $filename];
}

// â”€â”€ Main â”€â”€
echo "=== ISNM Student Data CLI Import ===\n\n";

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
    echo "No Excel files found in students_data/\n";
    exit(1);
}

echo "Found " . count($files) . " Excel file(s):\n";
foreach ($files as $f) {
    echo "  - " . basename($f) . "\n";
}
echo "\n";

$totalImported = 0;
$totalSkipped = 0;
$totalErrors = [];

foreach ($files as $path) {
    $name = basename($path);
    echo "Processing: $name\n";
    $res = processFile($path, $conn);
    $totalImported += $res['imported'];
    $totalSkipped += $res['skipped'];
    $totalErrors = array_merge($totalErrors, $res['errors']);
    echo "  -> Imported: {$res['imported']}, Skipped: {$res['skipped']}, Errors: " . count($res['errors']) . "\n";
}

$countResult = $conn->query("SELECT COUNT(*) as cnt FROM students");
$totalInDb = $countResult ? $countResult->fetch_assoc()['cnt'] : 0;

echo "\n=== IMPORT COMPLETE ===\n";
echo "  Imported:  $totalImported\n";
echo "  Skipped:   $totalSkipped\n";
echo "  Errors:    " . count($totalErrors) . "\n";
echo "  Total in DB: $totalInDb\n";

if (!empty($totalErrors)) {
    echo "\nError details (first 20):\n";
    foreach (array_slice($totalErrors, 0, 20) as $e) {
        echo "  - $e\n";
    }
}

$conn->close();
echo "\nDone.\n";
