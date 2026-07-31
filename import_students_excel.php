<?php
require_once __DIR__ . '/includes/staff_dashboard_access.php';
require_once __DIR__ . '/includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['director_general', 'ceo', 'director', 'principal', 'system_administrator', 'registrar', 'admissions']);
$staff = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staff && $studentsConn) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Invalid security token.';
    } else {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
                $error = 'Only CSV and Excel files are supported.';
            } else {
                $tmpFile = $_FILES['excel_file']['tmp_name'];
                $imported = 0; $skipped = 0; $errors = [];

                if ($ext === 'csv') {
                    $handle = fopen($tmpFile, 'r');
                    if ($handle) {
                        $header = fgetcsv($handle);
                        $colMap = [];
                        if ($header) {
                            foreach ($header as $i => $col) {
                                $col = strtolower(trim($col));
                                if (in_array($col, ['student_number', 'student_no', 'reg_no', 'registration_number'])) $colMap['student_number'] = $i;
                                elseif (in_array($col, ['full_name', 'name', 'student_name'])) $colMap['full_name'] = $i;
                                elseif (in_array($col, ['first_name', 'fname'])) $colMap['first_name'] = $i;
                                elseif (in_array($col, ['last_name', 'lname', 'surname'])) $colMap['last_name'] = $i;
                                elseif (in_array($col, ['email', 'email_address'])) $colMap['email'] = $i;
                                elseif (in_array($col, ['phone', 'phone_number', 'telephone'])) $colMap['phone'] = $i;
                                elseif (in_array($col, ['gender', 'sex'])) $colMap['gender'] = $i;
                                elseif (in_array($col, ['course', 'program', 'course_name', 'program_name'])) $colMap['course_name'] = $i;
                                elseif (in_array($col, ['set', 'set_name', 'class_set'])) $colMap['set_name'] = $i;
                                elseif (in_array($col, ['date_of_birth', 'dob'])) $colMap['date_of_birth'] = $i;
                            }
                        }

                        while (($row = fgetcsv($handle)) !== false) {
                            $student_number = $colMap['student_number'] !== false ? trim($row[$colMap['student_number']] ?? '') : '';
                            $full_name = $colMap['full_name'] !== false ? trim($row[$colMap['full_name']] ?? '') : '';
                            $first_name = $colMap['first_name'] !== false ? trim($row[$colMap['first_name']] ?? '') : '';
                            $last_name = $colMap['last_name'] !== false ? trim($row[$colMap['last_name']] ?? '') : '';
                            $email = $colMap['email'] !== false ? trim($row[$colMap['email']] ?? '') : '';
                            $phone = $colMap['phone'] !== false ? trim($row[$colMap['phone']] ?? '') : '';
                            $gender = $colMap['gender'] !== false ? trim($row[$colMap['gender']] ?? '') : '';
                            $course_name = $colMap['course_name'] !== false ? trim($row[$colMap['course_name']] ?? '') : '';
                            $set_name = $colMap['set_name'] !== false ? trim($row[$colMap['set_name']] ?? '') : '';
                            $dob = $colMap['date_of_birth'] !== false ? trim($row[$colMap['date_of_birth']] ?? '') : '';

                            if (!$student_number && !$full_name && !$first_name) { $skipped++; continue; }

                            if (!$full_name && $first_name) {
                                $full_name = trim($first_name . ' ' . $last_name);
                            }

                            $check = $studentsConn->prepare("SELECT id FROM students WHERE student_number = ? LIMIT 1");
                            if ($check) {
                                $check->bind_param("s", $student_number);
                                $check->execute();
                                if ($check->get_result()->num_rows > 0) {
                                    $check->close();
                                    $skipped++;
                                    continue;
                                }
                                $check->close();
                            }

                            $password_hash = password_hash('Student@123', PASSWORD_DEFAULT);
                            $intake_year = date('Y');
                            $intake_period = date('n') <= 6 ? 'January' : 'July';
                            $stmt = $studentsConn->prepare("INSERT INTO students (student_number, full_name, first_name, surname, email, phone, gender, program, set_name, date_of_birth, intake_year, intake_period, password, is_first_login, password_changed, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, 'Active', NOW())");
                            if ($stmt) {
                                $stmt->bind_param("sssssssssssss", $student_number, $full_name, $first_name, $last_name, $email, $phone, $gender, $course_name, $set_name, $dob, $intake_year, $intake_period, $password_hash);
                                if ($stmt->execute()) {
                                    $imported++;
                                } else {
                                    $errors[] = "Failed to import $student_number: " . $stmt->error;
                                }
                                $stmt->close();
                            }
                        }
                        fclose($handle);
                    }
                } else {
                    $error = 'Excel (.xlsx) import requires PHPExcel library. Please export to CSV first.';
                }

                $success = "Import complete: $imported students imported, $skipped skipped.";
                if (!empty($errors)) {
                    $error .= " " . implode(' ', array_slice($errors, 0, 5));
                }
            }
        } else {
            $error = 'Please select a file to upload.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import Students - ISNM</title>
<?php include_once __DIR__ . '/includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/includes/dashboard_topbar.php'; ?>

<div class="content-wrapper" style="padding:2rem;">
<div class="container-fluid">
<h3><i class="fas fa-file-import me-2"></i>Import Students from Excel/CSV</h3>
<p class="text-muted">Upload a CSV file with student data. Columns: student_number, full_name (or first_name + last_name), email, phone, gender, course_name, set_name, date_of_birth.</p>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card mt-4">
<div class="card-body">
<form method="POST" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="mb-3">
<label class="form-label fw-bold">Select CSV File</label>
<input type="file" name="excel_file" class="form-control" accept=".csv,.xlsx,.xls" required>
</div>
<div class="mb-3">
<button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Import Students</button>
<a href="../dashboards/director-general.php" class="btn btn-secondary ms-2">Cancel</a>
</div>
</form>
</div>
</div>

<div class="card mt-4">
<div class="card-body">
<h5>CSV Format Requirements</h5>
<table class="table table-bordered table-sm">
<thead><tr><th>Column</th><th>Required</th><th>Description</th></tr></thead>
<tbody>
<tr><td>student_number</td><td>Yes</td><td>Unique student registration number</td></tr>
<tr><td>full_name</td><td>Yes*</td><td>Full name (or provide first_name + last_name)</td></tr>
<tr><td>first_name</td><td>*If no full_name</td><td>Student first name</td></tr>
<tr><td>last_name</td><td>No</td><td>Student last name/surname</td></tr>
<tr><td>email</td><td>No</td><td>Student email address</td></tr>
<tr><td>phone</td><td>No</td><td>Phone number</td></tr>
<tr><td>gender</td><td>No</td><td>Male/Female</td></tr>
<tr><td>course_name</td><td>No</td><td>Program/course enrolled in</td></tr>
<tr><td>set_name</td><td>No</td><td>Set/Class (e.g., Set 1, Set 2)</td></tr>
<tr><td>date_of_birth</td><td>No</td><td>Date of birth (YYYY-MM-DD)</td></tr>
</tbody>
</table>
<p class="text-muted small">Default password for imported students: <code>Student@123</code></p>
</div>
</div>
</div>
</div>

<?php include_once __DIR__ . '/includes/dashboard_footer.php'; ?>
</body>
</html>
