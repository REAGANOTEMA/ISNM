<?php
require_once __DIR__ . '/includes/staff_dashboard_access.php';
bootstrapStaffDashboard();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$conn = DatabaseConnection::getStudentsConnection();
$student = null;
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
    if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $r = $stmt->get_result(); $stmt->close(); } else { $r = false; }
    if ($r) $student = $r->fetch_assoc();
}
if (!$student) { echo '<html><body><h2>Student not found</h2><a href="javascript:window.close()">Close</a></body></html>'; exit; }

$name = htmlspecialchars($student['full_name'] ?: trim(($student['first_name']??'') . ' ' . ($student['surname']??'')));
$reg = htmlspecialchars($student['registration_number'] ?: $student['student_number'] ?: '');
$index = htmlspecialchars($student['index_number'] ?: $student['national_student_id_number'] ?: '');
$course = htmlspecialchars($student['course'] ?: $student['program'] ?: '');
$phone = htmlspecialchars($student['phone'] ?: $student['mobile_number'] ?: '');
$email = htmlspecialchars($student['email'] ?: '');
$year = htmlspecialchars($student['current_year'] ?: $student['year'] ?: '');
$gender = htmlspecialchars($student['gender'] ?: '');
$status = htmlspecialchars($student['status'] ?: '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Profile - <?= $name ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-family: 'Segoe UI',system-ui,sans-serif; padding: 30px; background: #fff; }
  .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #1a237e; padding-bottom: 20px; }
  .header img { height: 70px; margin-bottom: 10px; }
  .header h2 { color: #1a237e; margin: 0; }
  .header p { color: #666; margin: 5px 0 0; }
  .section { margin-bottom: 25px; }
  .section h5 { color: #3e2723; border-bottom: 1px solid #e0e0e0; padding-bottom: 8px; margin-bottom: 15px; }
  .info-row { display: flex; padding: 6px 0; border-bottom: 1px solid #f5f5f5; }
  .info-label { width: 200px; font-weight: 600; color: #555; flex-shrink: 0; }
  .info-value { flex: 1; color: #222; }
  @media print { .no-print { display: none; } body { padding: 15px; } }
</style>
</head>
<body>
<div class="no-print mb-3"><button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button> <button class="btn btn-secondary" onclick="window.close()">Close</button></div>
<div class="header">
  <img src="images/school-logo.png" alt="ISNM">
  <h2>Iganga School of Nursing and Midwifery</h2>
  <p>Student Profile Record</p>
</div>
<div class="section">
  <h5>Personal Information</h5>
  <div class="info-row"><div class="info-label">Full Name</div><div class="info-value"><?= $name ?></div></div>
  <div class="info-row"><div class="info-label">Registration Number</div><div class="info-value"><?= $reg ?: '-' ?></div></div>
  <div class="info-row"><div class="info-label">Index Number</div><div class="info-value"><?= $index ?: '-' ?></div></div>
  <div class="info-row"><div class="info-label">Course / Program</div><div class="info-value"><?= $course ?: '-' ?></div></div>
  <div class="info-row"><div class="info-label">Year of Study</div><div class="info-value"><?= $year ?: '-' ?></div></div>
  <div class="info-row"><div class="info-label">Gender</div><div class="info-value"><?= $gender ?: '-' ?></div></div>
  <div class="info-row"><div class="info-label">Phone</div><div class="info-value"><?= $phone ?: '-' ?></div></div>
  <div class="info-row"><div class="info-label">Email</div><div class="info-value"><?= $email ?: '-' ?></div></div>
  <div class="info-row"><div class="info-label">Status</div><div class="info-value"><?= $status ?: '-' ?></div></div>
</div>
<div style="text-align:center;color:#999;font-size:12px;margin-top:30px;border-top:1px solid #eee;padding-top:15px">
  Generated on <?= date('d M Y, h:i A') ?> | ISNM Student Management System
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
