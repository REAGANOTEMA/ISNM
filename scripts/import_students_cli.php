<?php
// CLI script to import Excel files from students_data/ into students_db
// Run: php scripts/import_students_cli.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "Please run composer install to get PhpSpreadsheet (vendor/autoload.php not found).\n";
    exit(1);
}
require_once __DIR__ . '/../vendor/autoload.php';

$conn = getStudentsConnection();
$conn->set_charset('utf8mb4');

$dir = __DIR__ . '/../students_data/';
$files = glob($dir . '*.xlsx');
if (empty($files)) {
    echo "No .xlsx files found in students_data/.\n";
    exit(0);
}

$imported = 0;
$skipped = 0;
$errors = [];

foreach ($files as $file) {
    echo "Processing: " . basename($file) . "\n";
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);
        $header = [];
        foreach ($rows as $r => $cols) {
            if ($r === 1) {
                // build header map
                $c = 0;
                foreach ($cols as $cell) {
                    $header[$c] = strtolower(trim(preg_replace('/[^a-z0-9_]+/i', '_', $cell)));
                    $c++;
                }
                continue;
            }

            $c = 0;
            $data = [];
            foreach ($cols as $cell) {
                $key = $header[$c] ?? ('col_' . $c);
                $data[$key] = trim((string)$cell);
                $c++;
            }

            $fullName = $data['full_name'] ?? $data['name'] ?? '';
            $regNumber = $data['registration_number'] ?? $data['reg_no'] ?? '';
            if (empty($fullName) || empty($regNumber)) {
                $skipped++;
                continue;
            }

            // check duplicate
            $check = $conn->prepare("SELECT id FROM students WHERE registration_number = ? LIMIT 1");
            $check->bind_param('s', $regNumber);
            $check->execute();
            $res = $check->get_result();
            if ($res && $res->num_rows > 0) {
                $skipped++;
                continue;
            }

            $nationalId = $data['national_student_id_number'] ?? $data['national_id'] ?? null;
            $indexNumber = $data['index_number'] ?? null;
            $mobile = $data['mobile_number'] ?? $data['phone'] ?? null;
            $email = $data['email'] ?? null;
            $course = $data['course'] ?? null;
            $year = is_numeric($data['year'] ?? null) ? (int)$data['year'] : null;
            $setName = $data['set_name'] ?? $data['class'] ?? null;
            $intakeDate = !empty($data['intake_date']) ? date('Y-m-d', strtotime($data['intake_date'])) : null;
            $dob = !empty($data['date_of_birth']) ? date('Y-m-d', strtotime($data['date_of_birth'])) : null;
            $gender = !empty($data['gender']) ? ucfirst(strtolower($data['gender'])) : null;
            $address = $data['address'] ?? null;
            $emgName = $data['emergency_contact_name'] ?? null;
            $emgPhone = $data['emergency_contact_phone'] ?? null;

            $sql = "INSERT INTO students (full_name, registration_number, national_student_id_number, index_number, mobile_number, email, course, year, set_name, intake_date, date_of_birth, gender, address, emergency_contact_name, emergency_contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $errors[] = "Prepare failed: " . $conn->error;
                continue;
            }
            $year_val = $year !== null ? (string)$year : '';
            $stmt->bind_param('sssssssssssssss',
                $fullName,
                $regNumber,
                $nationalId,
                $indexNumber,
                $mobile,
                $email,
                $course,
                $year_val,
                $setName,
                $intakeDate,
                $dob,
                $gender,
                $address,
                $emgName,
                $emgPhone
            );
            // Note: binding types above intentionally simplified; fallback to execute and check
            if ($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Failed to insert: $fullName - " . $stmt->error;
            }
        }
    } catch (Exception $e) {
        $errors[] = "Error processing file " . basename($file) . ": " . $e->getMessage();
    }
}

echo "Imported: $imported\nSkipped: $skipped\nErrors: " . count($errors) . "\n";
if (!empty($errors)) {
    echo "First errors:\n" . implode("\n", array_slice($errors, 0, 10)) . "\n";
}

exit(0);
