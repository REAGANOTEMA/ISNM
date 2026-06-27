<?php
require_once __DIR__ . '/includes/staff_dashboard_access.php';
bootstrapStaffDashboard();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/views/student_data_loader.php';

header('Content-Type: text/html; charset=utf-8');

// ─── UPLOAD DIRECTORY ───
$UPLOAD_DIR = __DIR__ . '/uploads/student_photos/';
if (!is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0755, true);
}

// ─── AJAX HANDLERS ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    $conn = getStudentsConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }

    // ── Handle file upload ──
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $fname = uniqid('stu_') . '.' . $ext;
            $dest = $UPLOAD_DIR . $fname;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $photo_path = 'uploads/student_photos/' . $fname;
            }
        }
    }

    if (in_array($action, ['add', 'edit', 'save_excel_student'])) {
        $id = $action === 'edit' ? intval($_POST['id'] ?? 0) : 0;
        $first_name = trim($_POST['first_name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $other_name = trim($_POST['other_name'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        if (empty($full_name)) {
            $full_name = trim("$first_name $other_name $surname");
        }
        $gender = $_POST['gender'] ?? 'Other';
        $index_number = trim($_POST['index_number'] ?? '');
        $registration_number = trim($_POST['registration_number'] ?? '');
        $student_number = trim($_POST['student_number'] ?? '');
        $national_id = trim($_POST['national_student_id_number'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        if (empty($mobile_number)) $mobile_number = $phone;
        if (empty($phone)) $phone = $mobile_number;
        $email = trim($_POST['email'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $set_name = trim($_POST['set_name'] ?? '');
        $year = intval($_POST['year'] ?? 0);

        if (empty($first_name) || empty($surname)) {
            echo json_encode(['success' => false, 'error' => 'First name and surname are required']);
            exit;
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO students (first_name, surname, other_name, full_name, gender, index_number, registration_number, student_number, national_student_id_number, phone, mobile_number, email, program, level, set_name, year, current_year, passport_photo, profile_picture, status, is_first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 1)");
            $stmt->bind_param('sssssssssssssssiss', $first_name, $surname, $other_name, $full_name, $gender, $index_number, $registration_number, $student_number, $national_id, $phone, $mobile_number, $email, $program, $level, $set_name, $year, $year, $photo_path, $photo_path);
        } elseif ($action === 'edit') {
            if ($photo_path !== '') {
                $stmt = $conn->prepare("UPDATE students SET first_name=?, surname=?, other_name=?, full_name=?, gender=?, index_number=?, registration_number=?, student_number=?, national_student_id_number=?, phone=?, mobile_number=?, email=?, program=?, level=?, set_name=?, year=?, current_year=?, passport_photo=?, profile_picture=? WHERE id=?");
                $stmt->bind_param('sssssssssssssssssssi', $first_name, $surname, $other_name, $full_name, $gender, $index_number, $registration_number, $student_number, $national_id, $phone, $mobile_number, $email, $program, $level, $set_name, $year, $year, $photo_path, $photo_path, $id);
            } else {
                $stmt = $conn->prepare("UPDATE students SET first_name=?, surname=?, other_name=?, full_name=?, gender=?, index_number=?, registration_number=?, student_number=?, national_student_id_number=?, phone=?, mobile_number=?, email=?, program=?, level=?, set_name=?, year=?, current_year=? WHERE id=?");
                $stmt->bind_param('sssssssssssssssiii', $first_name, $surname, $other_name, $full_name, $gender, $index_number, $registration_number, $student_number, $national_id, $phone, $mobile_number, $email, $program, $level, $set_name, $year, $year, $id);
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO students (first_name, surname, other_name, full_name, gender, index_number, registration_number, student_number, national_student_id_number, phone, mobile_number, email, program, level, set_name, year, current_year, passport_photo, profile_picture, status, is_first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 1)");
            $stmt->bind_param('sssssssssssssssiss', $first_name, $surname, $other_name, $full_name, $gender, $index_number, $registration_number, $student_number, $national_id, $phone, $mobile_number, $email, $program, $level, $set_name, $year, $year, $photo_path, $photo_path);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $action === 'edit' ? $id : $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        if ($student) {
            echo json_encode(['success' => true, 'student' => $student]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Student not found']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    $conn->close();
    exit;
}

// ─── FETCH ALL STUDENTS (DB + Excel files) ───
$loader = new StudentDataLoader();
$allLoaderStudents = $loader->loadAllStudents();
$loaderFilterOptions = $loader->getFilterOptions();

// Map StudentDataLoader output to unified format with DB ids when available
$dbIds = [];
$conn = getStudentsConnection();
$dbStudents = [];
if ($conn) {
    $result = $conn->query("SELECT id, index_number, registration_number, student_number, national_student_id_number FROM students WHERE status != 'deleted'");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $key = strtolower(trim($row['index_number'] ?? ''));
            if ($key !== '') $dbIds['index:' . $key] = (int)$row['id'];
            $key = strtolower(trim($row['national_student_id_number'] ?? ''));
            if ($key !== '') $dbIds['national:' . $key] = (int)$row['id'];
            $key = strtolower(trim($row['registration_number'] ?? ''));
            if ($key !== '') $dbIds['student_number:' . $key] = (int)$row['id'];
            $key = strtolower(trim($row['student_number'] ?? ''));
            if ($key !== '') $dbIds['student_number:' . $key] = (int)$row['id'];
        }
        $result->free();
    }
    $conn->close();
}

$students = [];
$nextTempId = -1;
foreach ($allLoaderStudents as $s) {
    $phone = $s['phone'] ?? '';
    $mobile = $s['mobile_number'] ?? '';
    if (empty($mobile)) $mobile = $phone;
    if (empty($phone)) $phone = $mobile;

    $dbId = 0;
    foreach ($dbIds as $k => $id) {
        if (strpos($k, 'index:') === 0) {
            $v = substr($k, 6);
            if ($v === strtolower(trim($s['index_number'] ?? ''))) { $dbId = $id; break; }
        }
        if (strpos($k, 'national:') === 0) {
            $v = substr($k, 9);
            if ($v === strtolower(trim($s['national_id'] ?? ''))) { $dbId = $id; break; }
        }
    }

    $photo = $s['passport_photo'] ?? $s['profile_picture'] ?? '';
    if ($photo !== '' && strpos($photo, 'uploads/') !== 0 && strpos($photo, 'images/') !== 0) {
        $photo = 'images/default-avatar.png';
    }

    $students[] = [
        'id' => $dbId > 0 ? $dbId : $nextTempId--,
        'first_name' => $s['first_name'] ?? '',
        'surname' => $s['surname'] ?? '',
        'other_name' => $s['other_name'] ?? '',
        'full_name' => $s['full_name'] ?? '',
        'gender' => $s['gender'] ?? '',
        'index_number' => $s['index_number'] ?? '',
        'registration_number' => $s['registration_number'] ?? '',
        'student_number' => $s['student_number'] ?? '',
        'national_student_id_number' => $s['national_id'] ?? '',
        'phone' => $phone,
        'mobile_number' => $mobile,
        'email' => $s['email'] ?? '',
        'program' => $s['program'] ?? '',
        'level' => $s['level'] ?? '',
        'set_name' => $s['set'] ?? '',
        'year' => $s['intake_year'] ?? '',
        'source' => $s['source_file'] ?? '',
        'has_db_id' => $dbId > 0,
        'passport_photo' => $photo,
    ];
}

$programs = $loaderFilterOptions['programs'] ?? [];
$levels = $loaderFilterOptions['levels'] ?? [];
$sets = $loaderFilterOptions['sets'] ?? [];
$genders = ['Male', 'Female', 'Other'];
$years = $loaderFilterOptions['years'] ?? [];

$maleCount = count(array_filter($students, fn($s) => strtolower($s['gender'] ?? '') === 'male'));
$femaleCount = count(array_filter($students, fn($s) => strtolower($s['gender'] ?? '') === 'female'));
$totalStudents = count($students);

$studentsJson = json_encode($students, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$programsJson = json_encode($programs);
$levelsJson = json_encode($levels);
$setsJson = json_encode($sets);
$gendersJson = json_encode($genders);
$yearsJson = json_encode(array_map('strval', $years));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Management | ISNM</title>
<link rel="icon" href="images/school-logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
:root { --primary: #0f766e; --primary-dark: #064e3b; --accent: #14b8a6; --light: #ccfbf1; }
* { box-sizing: border-box; }
body {
  font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: #fef9e7; margin: 0; min-height: 100vh; -webkit-font-smoothing: antialiased;
}
.page-header {
  background: linear-gradient(135deg, var(--primary-dark), var(--primary), #0d9488);
  color: #fff; padding: 32px 0 28px; position: relative; overflow: hidden;
}
.page-header::after {
  content: ''; position: absolute; bottom: -2px; left: 0; right: 0; height: 6px;
  background: linear-gradient(90deg, #14b8a6, #2dd4bf, #5eead4, #2dd4bf, #14b8a6);
}
.page-header h1 { font-family: 'Playfair Display', Georgia, serif; font-weight: 900; font-size: 2rem; margin: 0; }
.page-header p { opacity: .85; margin: 4px 0 0; font-size: .95rem; }
.stats-bar {
  background: #fff; border-bottom: 1px solid #d1fae5; padding: 14px 0;
  position: sticky; top: 0; z-index: 102; box-shadow: 0 2px 12px rgba(0,0,0,.04);
}
.stat-item { text-align: center; padding: 2px 10px; border-right: 1px solid #e5e7eb; }
.stat-item:last-child { border-right: none; }
.stat-item .num { font-weight: 800; font-size: 1.25rem; color: var(--primary); line-height: 1.2; }
.stat-item .lbl { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
.search-area {
  background: #fff; border-radius: 16px; padding: 24px 28px;
  margin-top: -16px; position: relative; z-index: 10;
  box-shadow: 0 8px 30px rgba(15,118,110,.12);
}
.search-input-group { position: relative; }
.search-input-group .fa-search {
  position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
  color: #9ca3af; font-size: 1rem; z-index: 5;
}
.search-input-group input {
  padding-left: 42px; height: 50px; font-size: 1.05rem;
  border: 2px solid #e5e7eb; border-radius: 12px; transition: .2s;
}
.search-input-group input:focus {
  border-color: var(--accent); box-shadow: 0 0 0 4px rgba(20,184,166,.15);
}
.search-input-group .clear-btn {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: #9ca3af; font-size: 1.2rem;
  cursor: pointer; display: none; z-index: 5;
}
.search-input-group .clear-btn:hover { color: #ef4444; }
.filter-select {
  border: 2px solid #e5e7eb; border-radius: 10px; padding: 8px 12px;
  font-size: .85rem; transition: .2s; cursor: pointer;
}
.filter-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(20,184,166,.12); }
.result-count { font-size: .85rem; color: #6b7280; padding: 8px 0 0; }
.student-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 16px; padding: 0;
}
.student-card {
  background: #fff; border-radius: 14px; padding: 18px 20px;
  transition: all .2s ease; border: 1px solid #e5e7eb;
  display: flex; align-items: flex-start; gap: 14px; position: relative;
}
.student-card:hover {
  border-color: var(--accent); box-shadow: 0 8px 24px rgba(15,118,110,.12); transform: translateY(-2px);
}
.student-card .avatar {
  width: 48px; height: 48px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.student-card .info { flex: 1; min-width: 0; }
.student-card .name { font-weight: 700; font-size: .95rem; color: #111827; }
.student-card .meta { font-size: .8rem; color: #6b7280; margin-top: 2px; display: flex; flex-wrap: wrap; gap: 4px 12px; }
.student-card .meta span { white-space: nowrap; }
.student-card .badge-program {
  display: inline-block; font-size: .68rem; padding: 2px 10px; border-radius: 20px;
  background: var(--light); color: var(--primary-dark); font-weight: 600; margin-top: 6px;
}
.student-card .actions {
  position: absolute; right: 12px; top: 12px; display: flex; gap: 4px; opacity: 0; transition: .2s;
}
.student-card:hover .actions { opacity: 1; }
.student-card .actions .btn-icon {
  width: 30px; height: 30px; border-radius: 8px; border: none;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem; cursor: pointer; transition: .15s; background: #f3f4f6; color: #6b7280;
}
.student-card .actions .btn-icon:hover { background: var(--light); color: var(--primary); }
.student-card .actions .btn-icon.danger:hover { background: #fee2e2; color: #dc2626; }
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
.empty-state .icon { font-size: 3.5rem; margin-bottom: 16px; }
.empty-state h5 { font-weight: 600; color: #6b7280; }
.pagination-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 0; flex-wrap: wrap; gap: 10px;
}
.pagination-bar .page-info { font-size: .85rem; color: #6b7280; }
.pagination-bar .btn-group .btn {
  border-radius: 8px; padding: 6px 14px; font-size: .82rem; border: 1px solid #d1d5db;
}
.pagination-bar .btn-group .btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.profile-header {
  display: flex; gap: 20px; align-items: center;
  padding: 8px 0 16px; border-bottom: 2px solid #f3f4f6; margin-bottom: 20px;
}
.profile-header .big-avatar {
  width: 72px; height: 72px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.profile-header .big-info h3 { font-weight: 800; margin: 0; font-size: 1.3rem; }
.profile-header .big-info .sub { color: #6b7280; font-size: .85rem; }
.profile-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; }
.profile-detail-grid .field { padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
.profile-detail-grid .field .label {
  font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
  color: #9ca3af; font-weight: 600;
}
.profile-detail-grid .field .value { font-size: .92rem; color: #111827; font-weight: 500; }
.profile-detail-grid .field.full-width { grid-column: 1 / -1; }
.modal-print-actions { display: flex; gap: 8px; }
@media print {
  body * { visibility: hidden; }
  #profilePrintArea, #profilePrintArea * { visibility: visible; }
  #profilePrintArea {
    position: fixed; top: 0; left: 0; right: 0; background: #fff; padding: 30px; z-index: 99999;
  }
  #profilePrintArea::before {
    content: '';
    display: block;
    width: 70px; height: 70px;
    background: url('images/school-logo.png') no-repeat center;
    background-size: contain;
    margin: 0 auto 10px;
  }
  .modal-print-actions, .btn-close, .modal-header, .page-header, .stats-bar,
  .search-area, .pagination-bar, footer, .actions { display: none !important; }
  .profile-detail-grid { break-inside: avoid; }
  .profile-header { border-bottom: 2px solid #0f766e; }
  @page { margin: 12mm; }
}


@media (max-width: 768px) {
  .page-header { padding: 20px 0 24px; }
  .page-header h1 { font-size: 1.4rem; }
  .search-area { padding: 16px; margin-top: -10px; }
  .student-grid { grid-template-columns: 1fr; }
  .profile-detail-grid { grid-template-columns: 1fr; }
  .stat-item .num { font-size: 1rem; }
  .stat-item .lbl { font-size: .6rem; }
}
@media (max-width: 576px) {
  .student-card { padding: 14px; }
  .student-card .avatar { width: 40px; height: 40px; font-size: 1rem; }
  .profile-header { flex-direction: column; text-align: center; }
  .filter-row .col-6 { margin-bottom: 6px; }
}
.loading-overlay {
  position: fixed; inset: 0; background: rgba(255,255,255,.85);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; flex-direction: column; gap: 12px;
}
.loading-overlay .spinner { width: 48px; height: 48px; border: 4px solid #e5e7eb; border-top-color: var(--primary); border-radius: 50%; animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.toast-container { position: fixed; top: 20px; right: 20px; z-index: 99999; }
.form-label { font-weight: 600; font-size: .82rem; color: #374151; margin-bottom: 2px; }
.form-control, .form-select { border-radius: 10px; border: 2px solid #e5e7eb; font-size: .9rem; }
.form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(20,184,166,.12); }
</style>
</head>
<body>

<div class="toast-container" id="toastContainer"></div>

<!-- Loading Screen -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner"></div>
  <div style="color:var(--primary);font-weight:600;font-size:.95rem">Loading student directory...</div>
</div>

<!-- Header -->
<header class="page-header">
  <div class="container">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <h1><i class="fas fa-graduation-cap me-3"></i>Student Management</h1>
        <p><i class="fas fa-database me-1"></i><span id="totalStudentsDisplay"><?= number_format($totalStudents) ?></span> records <span class="opacity-75">(database + Excel files)</span></p>
      </div>
      <div class="text-end">
        <?php if ($loggedIn): ?>
        <span class="d-inline-block bg-white bg-opacity-20 rounded-pill px-3 py-1" style="font-size:.82rem;background:rgba(255,255,255,.15)">
          <i class="fas fa-user me-1"></i><?= htmlspecialchars($userName) ?>
        </span>
        <?php endif; ?>
        <div class="mt-1">
          <a href="index.php" class="text-white text-decoration-none small me-3"><i class="fas fa-home me-1"></i>Home</a>
          <?php if ($loggedIn): ?>
          <a href="dashboards/" class="text-white text-decoration-none small me-3"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Stats Bar -->
<div class="stats-bar" id="statsBar">
  <div class="container">
    <div class="row g-0">
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statTotal">0</div><div class="lbl">Students</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statMale">0</div><div class="lbl">Male</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statFemale">0</div><div class="lbl">Female</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statPrograms">0</div><div class="lbl">Programs</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statSets">0</div><div class="lbl">Sets</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statLevels">0</div><div class="lbl">Levels</div></div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container py-3">

  <!-- Search & Filters -->
  <div class="search-area mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-12">
        <div class="search-input-group">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" class="form-control" placeholder="Search by name, index number, NSIN, phone, email, program, level…" autofocus>
          <button class="clear-btn" id="clearBtn" onclick="clearSearch()">&times;</button>
        </div>
      </div>
    </div>
    <div class="row g-2 mt-2 filter-row" id="filterRow">
      <div class="col-6 col-md">
        <select id="filterProgram" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Programs</option>
        </select>
      </div>
      <div class="col-6 col-md">
        <select id="filterLevel" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Levels</option>
        </select>
      </div>
      <div class="col-6 col-md">
        <select id="filterSet" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Sets</option>
        </select>
      </div>
      <div class="col-6 col-md">
        <select id="filterGender" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Genders</option>
        </select>
      </div>
      <div class="col-6 col-md">
        <select id="filterYear" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Years</option>
        </select>
      </div>
      <div class="col-6 col-md-auto">
        <button class="btn btn-outline-secondary w-100" style="border-radius:10px;height:42px" onclick="resetFilters()" title="Reset filters">
          <i class="fas fa-undo"></i>
        </button>
      </div>
      <div class="col-6 col-md-auto">
        <button class="btn btn-primary w-100" style="border-radius:10px;height:42px;background:var(--primary);border:none" onclick="openAddModal()" title="Add student">
          <i class="fas fa-plus me-1"></i>Add
        </button>
      </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div class="result-count" id="resultCount"></div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary" onclick="toggleView()" id="viewToggle" style="border-radius:8px">
          <i class="fas fa-list me-1"></i><span>List</span>
        </button>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" style="border-radius:8px">
          <i class="fas fa-print me-1"></i>Print
        </button>
      </div>
    </div>
  </div>

  <!-- Student Grid / List -->
  <div id="studentContainer"></div>

  <!-- Pagination -->
  <div class="pagination-bar" id="paginationBar">
    <div class="page-info" id="pageInfo"></div>
    <div class="btn-group" id="pageButtons"></div>
  </div>

</div>

<!-- Footer -->
<div style="background:#fff;border-top:1px solid #e5e7eb;padding:16px 0;margin-top:20px">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">&copy; <?= date('Y') ?> Iganga School of Nursing &amp; Midwifery</small>
    <small class="text-muted"><i class="fas fa-database me-1"></i>Student data from database + Excel files &middot; Edits saved to database</small>
  </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:18px;border:none;overflow:hidden">
      <div class="modal-header" style="background:var(--primary);color:#fff;padding:16px 24px;border:none">
        <h5 class="modal-title fw-bold"><i class="fas fa-id-card me-2"></i>Student Profile</h5>
        <div class="modal-print-actions">
          <button class="btn btn-sm btn-light me-2" onclick="editFromProfile()" style="border-radius:8px">
            <i class="fas fa-edit me-1"></i>Edit
          </button>
          <button class="btn btn-sm btn-light me-2" onclick="printProfile()" style="border-radius:8px">
            <i class="fas fa-print me-1"></i>Print
          </button>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body p-4" id="profileBody">
        <div id="profilePrintArea">
          <div class="profile-header">
            <div class="big-avatar" id="pAvatar"></div>
            <div class="big-info">
              <h3 id="pName"></h3>
              <div class="sub">
                <span id="pIndex"></span>
                <span class="mx-2">&middot;</span>
                <span id="pPhone"></span>
              </div>
            </div>
          </div>
          <div class="profile-detail-grid" id="pDetails"></div>
        </div>
      </div>
      <div class="modal-footer" style="border:none;padding:12px 24px;background:#f9fafb">
        <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Click Print for a formatted hard copy</small>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="studentFormModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:18px;border:none;overflow:hidden">
      <div class="modal-header" style="background:var(--primary);color:#fff;padding:16px 24px;border:none">
        <h5 class="modal-title fw-bold" id="formModalTitle"><i class="fas fa-user-plus me-2"></i>Add Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form id="studentForm" onsubmit="return saveStudent(event)" enctype="multipart/form-data">
          <input type="hidden" name="id" id="formId" value="0">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="first_name" id="fFirstName" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Surname <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="surname" id="fSurname" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Other Name</label>
              <input type="text" class="form-control" name="other_name" id="fOtherName">
            </div>
          </div>
          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Gender</label>
              <select class="form-select" name="gender" id="fGender">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Program / Course</label>
              <input type="text" class="form-control" name="program" id="fProgram" list="programList">
              <datalist id="programList"></datalist>
            </div>
            <div class="col-md-4">
              <label class="form-label">Level</label>
              <input type="text" class="form-control" name="level" id="fLevel" list="levelList">
              <datalist id="levelList"></datalist>
            </div>
          </div>
          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Set</label>
              <input type="text" class="form-control" name="set_name" id="fSet" list="setList">
              <datalist id="setList"></datalist>
            </div>
            <div class="col-md-4">
              <label class="form-label">Year</label>
              <input type="number" class="form-control" name="year" id="fYear" min="2000" max="2099">
            </div>
            <div class="col-md-4">
              <label class="form-label">Student Number</label>
              <input type="text" class="form-control" name="student_number" id="fStudentNumber">
            </div>
          </div>
          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Index Number</label>
              <input type="text" class="form-control" name="index_number" id="fIndexNumber">
            </div>
            <div class="col-md-4">
              <label class="form-label">Registration Number</label>
              <input type="text" class="form-control" name="registration_number" id="fRegNumber">
            </div>
            <div class="col-md-4">
              <label class="form-label">National ID (NSIN)</label>
              <input type="text" class="form-control" name="national_student_id_number" id="fNationalId">
            </div>
          </div>
          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Phone</label>
              <input type="text" class="form-control" name="phone" id="fPhone" placeholder="e.g. 0772123456">
            </div>
            <div class="col-md-4">
              <label class="form-label">Mobile Number</label>
              <input type="text" class="form-control" name="mobile_number" id="fMobileNumber" placeholder="e.g. 0772123456">
            </div>
            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" id="fEmail">
            </div>
            <div class="col-md-4">
              <label class="form-label">Photo</label>
              <input type="file" class="form-control" name="photo" id="fPhoto" accept="image/jpeg,image/png,image/gif,image/webp">
              <small class="text-muted" style="font-size:.7rem">Optional. JPG, PNG, GIF, WebP</small>
            </div>
          </div>
          <div class="row g-3 mt-1" id="photoPreviewRow" style="display:none">
            <div class="col-12">
              <img id="photoPreview" src="" alt="Preview" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb">
              <button type="button" class="btn btn-sm btn-link text-danger ms-2" onclick="clearPhoto()" style="text-decoration:none"><i class="fas fa-times"></i> Remove</button>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="border:none;padding:12px 24px;background:#f9fafb">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('studentForm').requestSubmit()" id="formSaveBtn" style="border-radius:10px;background:var(--primary);border:none;padding:8px 28px">
          <i class="fas fa-save me-1"></i>Save
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
    <div class="modal-content" style="border-radius:18px;border:none">
      <div class="modal-body text-center p-4">
        <div style="font-size:3rem;color:#ef4444;margin-bottom:12px"><i class="fas fa-exclamation-triangle"></i></div>
        <h5 class="fw-bold">Delete Student?</h5>
        <p class="text-muted mb-0" id="deleteStudentName">This action cannot be undone.</p>
      </div>
      <div class="modal-footer justify-content-center" style="border:none;padding:0 24px 20px">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="border-radius:10px">Cancel</button>
        <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn" style="border-radius:10px">
          <i class="fas fa-trash me-1"></i>Delete
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ─── DATA ───
const ALL_STUDENTS = <?= $studentsJson ?>;
const PROGRAMS = <?= $programsJson ?>;
const LEVELS = <?= $levelsJson ?>;
const SETS = <?= $setsJson ?>;
const GENDERS = <?= $gendersJson ?>;
const YEARS = <?= $yearsJson ?>;

// ─── STATE ───
const PER_PAGE = 48;
let currentPage = 1;
let filtered = [];
let isGridView = true;
let currentProfileId = null;

let formModalInstance = null;
let profileModalInstance = null;
let deleteModalInstance = null;

// ─── TOAST ───
function showToast(message, type = 'success') {
  const bg = type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#f59e0b';
  const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
  const container = document.getElementById('toastContainer');
  const el = document.createElement('div');
  el.className = 'toast align-items-center text-white border-0 show';
  el.style.background = bg; el.style.borderRadius = '12px';
  el.innerHTML = `<div class="d-flex"><div class="toast-body"><i class="fas ${icon} me-2"></i>${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  container.appendChild(el);
  const bsToast = new bootstrap.Toast(el, { delay: 4000 });
  bsToast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

// ─── INIT ───
document.addEventListener('DOMContentLoaded', function() {
  populateFilters();
  filtered = ALL_STUDENTS;
  updateStats();
  render();
  document.getElementById('loadingOverlay').style.display = 'none';

  let searchTimer;
  document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 200);
    const btn = document.querySelector('.clear-btn');
    btn.style.display = this.value.length > 0 ? 'block' : 'none';
  });
});

function populateFilters() {
  const progSel = document.getElementById('filterProgram');
  PROGRAMS.forEach(p => { const o = document.createElement('option'); o.value = p; o.textContent = p; progSel.appendChild(o); });
  const lvlSel = document.getElementById('filterLevel');
  LEVELS.forEach(l => { const o = document.createElement('option'); o.value = l; o.textContent = l; lvlSel.appendChild(o); });
  const setSel = document.getElementById('filterSet');
  SETS.forEach(s => { const o = document.createElement('option'); o.value = s; o.textContent = s; setSel.appendChild(o); });
  const genSel = document.getElementById('filterGender');
  GENDERS.forEach(g => { const o = document.createElement('option'); o.value = g; o.textContent = g; genSel.appendChild(o); });
  const yrSel = document.getElementById('filterYear');
  YEARS.forEach(y => { const o = document.createElement('option'); o.value = y; o.textContent = y; yrSel.appendChild(o); });
}

function applyFilters() {
  const q = document.getElementById('searchInput').value.toLowerCase().trim();
  const prog = document.getElementById('filterProgram').value;
  const lvl = document.getElementById('filterLevel').value;
  const set = document.getElementById('filterSet').value;
  const gen = document.getElementById('filterGender').value;
  const yr = document.getElementById('filterYear').value;

  filtered = ALL_STUDENTS.filter(s => {
    if (q) {
      const haystack = [
        s.first_name, s.surname, s.other_name, s.full_name,
        s.index_number, s.registration_number, s.student_number,
        s.national_student_id_number, s.phone, s.mobile_number,
        s.email, s.program, s.level, s.set_name, s.year
      ].filter(Boolean).join(' | ').toLowerCase();
      if (haystack.indexOf(q) === -1) return false;
    }
    if (prog && s.program !== prog) return false;
    if (lvl && s.level !== lvl) return false;
    if (set && s.set_name !== set) return false;
    if (gen && s.gender !== gen) return false;
    if (yr && String(s.year) !== yr) return false;
    return true;
  });

  currentPage = 1;
  updateStats();
  render();
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  document.querySelector('.clear-btn').style.display = 'none';
  document.getElementById('filterProgram').value = '';
  document.getElementById('filterLevel').value = '';
  document.getElementById('filterSet').value = '';
  document.getElementById('filterGender').value = '';
  document.getElementById('filterYear').value = '';
  applyFilters();
}

function clearSearch() {
  document.getElementById('searchInput').value = '';
  document.querySelector('.clear-btn').style.display = 'none';
  applyFilters();
  document.getElementById('searchInput').focus();
}

function updateStats() {
  document.getElementById('statTotal').textContent = filtered.length;
  document.getElementById('statMale').textContent = filtered.filter(s => (s.gender||'') === 'Male').length;
  document.getElementById('statFemale').textContent = filtered.filter(s => (s.gender||'') === 'Female').length;
  const progs = new Set(filtered.map(s => s.program).filter(Boolean));
  const setSet = new Set(filtered.map(s => s.set_name).filter(Boolean));
  const lvls = new Set(filtered.map(s => s.level).filter(Boolean));
  document.getElementById('statPrograms').textContent = progs.size;
  document.getElementById('statSets').textContent = setSet.size;
  document.getElementById('statLevels').textContent = lvls.size;
  document.getElementById('totalStudentsDisplay').textContent = ALL_STUDENTS.length;
}

function render() {
  const container = document.getElementById('studentContainer');
  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PER_PAGE));
  if (currentPage > pages) currentPage = pages;

  const start = (currentPage - 1) * PER_PAGE;
  const end = Math.min(start + PER_PAGE, total);
  const pageStudents = filtered.slice(start, end);

  if (total === 0) {
    container.innerHTML = `<div class="empty-state">
      <div class="icon"><i class="fas fa-search"></i></div>
      <h5>No students match your search</h5>
      <p class="text-muted">Try a different name, index number, or adjust the filters above.</p>
      <button class="btn btn-sm btn-outline-primary mt-2" onclick="resetFilters()">Clear All Filters</button>
    </div>`;
  } else if (isGridView) {
    let html = '<div class="student-grid">';
    const colors = ['#0f766e','#0891b2','#7c3aed','#dc2626','#ea580c','#ca8a04','#16a34a','#db2777','#4f46e5','#059669'];
    pageStudents.forEach(s => {
      const name = s.full_name || (s.surname + ' ' + s.first_name).trim() || 'Unknown';
      const initial = (s.first_name || s.full_name || 'S').charAt(0).toUpperCase();
      const color = colors[Math.floor(Math.random() * colors.length)];
      const idxNum = s.index_number || s.national_student_id_number || '-';
      const phone = s.mobile_number || s.phone || '-';
      const genderIcon = (s.gender||'').toLowerCase() === 'male' ? 'fa-mars' : (s.gender||'').toLowerCase() === 'female' ? 'fa-venus' : 'fa-genderless';
      const srcBadge = s.has_db_id
        ? '<span class="badge bg-success ms-1" style="font-size:.6rem;font-weight:400">DB</span>'
        : '<span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;font-weight:400">Excel</span>';
      const avatarHtml = s.passport_photo
        ? `<img src="${escHtml(s.passport_photo)}" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover;flex-shrink:0">`
        : `<div class="avatar" style="background:${color}">${initial}</div>`;
      html += `<div class="student-card">
        ${avatarHtml}
        <div class="info">
          <div class="name">${escHtml(name)} ${srcBadge}</div>
          <div class="meta">
            <span><i class="fas fa-hashtag me-1" style="font-size:.7rem"></i>${escHtml(idxNum)}</span>
            <span><i class="fas fa-phone me-1" style="font-size:.7rem"></i>${escHtml(phone)}</span>
            ${s.gender ? `<span><i class="fas ${genderIcon} me-1" style="font-size:.7rem"></i>${escHtml(s.gender)}</span>` : ''}
          </div>
          <div class="badge-program">${escHtml(s.program || 'General')}${s.set_name ? ' &middot; Set ' + escHtml(s.set_name) : ''}</div>
        </div>
        <div class="actions">
          <button class="btn-icon" onclick="event.stopPropagation(); openEditModal(${s.id})" title="Edit"><i class="fas fa-edit"></i></button>
          <button class="btn-icon danger" onclick="event.stopPropagation(); confirmDelete(${s.id}, '${escHtml(name.replace(/'/g, "\\'"))}')" title="Delete"><i class="fas fa-trash"></i></button>
          <button class="btn-icon" onclick="event.stopPropagation(); showProfile(${s.id})" title="View"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
  } else {
    let html = `<div class="table-responsive"><table class="table table-hover align-middle" style="background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb">
      <thead class="table-light"><tr>
        <th>Name</th><th>Index / NSIN</th><th>Program</th><th>Level</th><th>Set</th><th>Gender</th><th>Phone</th><th style="width:80px">Actions</th>
      </tr></thead><tbody>`;
    pageStudents.forEach(s => {
      const name = s.full_name || (s.surname + ' ' + s.first_name).trim() || 'Unknown';
      const idxNum = s.index_number || s.national_student_id_number || '-';
      const genBadge = s.gender === 'Male' ? 'bg-primary' : s.gender === 'Female' ? 'bg-danger' : 'bg-secondary';
      const phone = s.mobile_number || s.phone || '-';
      const srcBadge = s.has_db_id
        ? '<span class="badge bg-success ms-1" style="font-size:.6rem;font-weight:400">DB</span>'
        : '<span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;font-weight:400">Excel</span>';
      html += `<tr>
        <td><strong onclick="showProfile(${s.id})" style="cursor:pointer">${escHtml(name)} ${srcBadge}</strong></td>
        <td><code>${escHtml(idxNum)}</code></td>
        <td>${escHtml(s.program || '-')}</td>
        <td>${escHtml(s.level || '-')}</td>
        <td>${escHtml(s.set_name || '-')}</td>
        <td>${s.gender ? `<span class="badge ${genBadge}">${escHtml(s.gender)}</span>` : '-'}</td>
        <td>${escHtml(phone)}</td>
        <td>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" onclick="openEditModal(${s.id})" title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-outline-danger" onclick="confirmDelete(${s.id}, '${escHtml(name.replace(/'/g, "\\'"))}')" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    container.innerHTML = html;
  }

  document.getElementById('resultCount').textContent = `Showing ${start + 1}–${end} of ${total} student${total !== 1 ? 's' : ''}`;
  document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${pages}`;
  let btns = '';
  if (pages > 1) {
    if (currentPage > 1) btns += `<button class="btn btn-sm btn-outline-secondary" onclick="goPage(${currentPage - 1})"><i class="fas fa-chevron-left"></i></button>`;
    for (let p = Math.max(1, currentPage - 2); p <= Math.min(pages, currentPage + 2); p++) {
      btns += `<button class="btn btn-sm ${p === currentPage ? 'active' : 'btn-outline-secondary'}" onclick="goPage(${p})">${p}</button>`;
    }
    if (currentPage < pages) btns += `<button class="btn btn-sm btn-outline-secondary" onclick="goPage(${currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
  }
  document.getElementById('pageButtons').innerHTML = btns;
}

function goPage(p) {
  const pages = Math.ceil(filtered.length / PER_PAGE);
  if (p < 1) p = 1; if (p > pages) p = pages;
  currentPage = p;
  render();
  document.getElementById('studentContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function toggleView() {
  isGridView = !isGridView;
  const btn = document.getElementById('viewToggle');
  btn.innerHTML = isGridView ? '<i class="fas fa-list me-1"></i><span>List</span>' : '<i class="fas fa-th me-1"></i><span>Grid</span>';
  render();
}

// ─── PROFILE ───
function showProfile(id) {
  currentProfileId = id;
  const s = ALL_STUDENTS.find(x => x.id === id);
  if (!s) return;

  const name = s.full_name || (s.surname + ' ' + s.first_name).trim() || 'Unknown';
  const initial = (s.first_name || s.full_name || 'S').charAt(0).toUpperCase();
  const colors = ['#0f766e','#0891b2','#7c3aed','#dc2626','#ea580c','#ca8a04','#16a34a','#db2777','#4f46e5','#059669'];
  const color = colors[Math.floor(Math.random() * colors.length)];

  const pAvatar = document.getElementById('pAvatar');
  if (s.passport_photo) {
    pAvatar.innerHTML = `<img src="${escHtml(s.passport_photo)}" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover">`;
    pAvatar.style.background = 'none';
  } else {
    pAvatar.style.background = color;
    pAvatar.textContent = initial;
  }
  document.getElementById('pName').textContent = name;
  document.getElementById('pIndex').textContent = s.index_number || s.national_student_id_number || s.registration_number || '-';
  document.getElementById('pPhone').textContent = (s.mobile_number || s.phone || '-') + ' | ' + (s.has_db_id ? 'DB' : 'Excel: ' + (s.source || 'file'));

  const fields = [
    { label: 'Full Name', val: name },
    { label: 'First Name', val: s.first_name },
    { label: 'Surname', val: s.surname },
    { label: 'Other Name', val: s.other_name },
    { label: 'Gender', val: s.gender },
    { label: 'Program / Course', val: s.program },
    { label: 'Level', val: s.level },
    { label: 'Set', val: s.set_name },
    { label: 'Year', val: s.year },
    { label: 'Index Number', val: s.index_number },
    { label: 'Registration Number', val: s.registration_number },
    { label: 'Student Number', val: s.student_number },
    { label: 'National ID (NSIN)', val: s.national_student_id_number },
    { label: 'Phone', val: s.phone },
      { label: 'Mobile Number', val: s.mobile_number },
      { label: 'Email', val: s.email },
      { label: 'Source', val: s.has_db_id ? 'Database' : 'Excel File' },
      { label: 'Source File', val: s.source },
    ];

  let html = '';
  fields.forEach(f => {
    if (!f.val) return;
    html += `<div class="field${f.full ? ' full-width' : ''}">
      <div class="label">${escHtml(f.label)}</div>
      <div class="value">${escHtml(f.val)}</div>
    </div>`;
  });
  document.getElementById('pDetails').innerHTML = html;

  if (!profileModalInstance) {
    profileModalInstance = new bootstrap.Modal(document.getElementById('profileModal'));
  }
  profileModalInstance.show();
}

function editFromProfile() {
  if (currentProfileId) {
    profileModalInstance.hide();
    setTimeout(() => openEditModal(currentProfileId), 300);
  }
}

function printProfile() {
  const printWindow = window.open('', '_blank', 'width=800,height=600');
  const s = ALL_STUDENTS.find(x => x.id === currentProfileId);
  if (!s) return;
  const name = s.full_name || (s.surname + ' ' + s.first_name).trim() || 'Unknown';
  const avatarElem = document.getElementById('pAvatar');
  const avatarHtml = avatarElem.innerHTML || avatarElem.textContent;
  const avatarStyle = avatarElem.getAttribute('style') || '';
  const photoUrl = s.passport_photo || 'images/school-logo.png';
  printWindow.document.write(`<!DOCTYPE html><html><head>
    <title>Student Profile | ISNM</title>
    <style>
      body{font-family:'Segoe UI',sans-serif;padding:30px;color:#111;max-width:700px;margin:0 auto}
      .school-header{text-align:center;padding-bottom:16px;border-bottom:3px solid #0f766e;margin-bottom:24px}
      .school-header img{width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid #ffd600}
      .school-header h1{font-size:1.1rem;margin:6px 0 2px;color:#064e3b;font-weight:700}
      .school-header .motto{font-size:.8rem;color:#6b7280;font-style:italic}
      .header{display:flex;gap:20px;align-items:center;padding-bottom:16px;margin-bottom:20px}
      .avatar{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden}
      .avatar img{width:100%;height:100%;object-fit:cover}
      .info h2{margin:0;font-size:1.4rem;color:#064e3b}
      .info .sub{color:#6b7280;font-size:.85rem}
      .grid{display:grid;grid-template-columns:1fr 1fr;gap:4px 24px}
      .field{padding:6px 0;border-bottom:1px solid #e5e7eb;break-inside:avoid}
      .field .lbl{font-size:.72rem;text-transform:uppercase;color:#9ca3af;font-weight:600}
      .field .val{font-size:.92rem;font-weight:500}
      .field.full{grid-column:1/-1}
      .footer{text-align:center;margin-top:30px;font-size:.8rem;color:#9ca3af;border-top:1px solid #e5e7eb;padding-top:16px}
      @page{margin:10mm}
      @media print{body{padding:0}}
    </style>
  </head><body>
    <div class="school-header">
      <img src="images/school-logo.png" alt="ISNM Logo">
      <h1>Iganga School of Nursing &amp; Midwifery</h1>
      <div class="motto">"Excellence in Nursing and Midwifery Education"</div>
    </div>
    <div class="header">
      <div class="avatar"${avatarStyle ? ' style="'+avatarStyle+'"' : ''}>${avatarHtml}</div>
      <div class="info">
        <h2>${escHtml(name)}</h2>
        <div class="sub">${escHtml(s.index_number || '-')} &middot; ${escHtml(s.mobile_number || s.phone || '-')}</div>
      </div>
    </div>
    <div class="grid">`);
  document.querySelectorAll('#pDetails .field').forEach(f => {
    const label = f.querySelector('.label')?.textContent || '';
    const value = f.querySelector('.value')?.textContent || '';
    const isFull = f.classList.contains('full-width');
    printWindow.document.write(`<div class="field${isFull?' full':''}"><div class="lbl">${escHtml(label)}</div><div class="val">${escHtml(value)}</div></div>`);
  });
  printWindow.document.write(`</div>
    <div class="footer">Iganga School of Nursing &amp; Midwifery &middot; Printed ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</div>
  </body></html>`);
  printWindow.document.close();
  setTimeout(() => { printWindow.focus(); printWindow.print(); }, 300);
}

// ─── ADD / EDIT ───
function populateDatalists() {
  const progList = document.getElementById('programList'); progList.innerHTML = '';
  PROGRAMS.forEach(p => { const o = document.createElement('option'); o.value = p; progList.appendChild(o); });
  const lvlList = document.getElementById('levelList'); lvlList.innerHTML = '';
  LEVELS.forEach(l => { const o = document.createElement('option'); o.value = l; lvlList.appendChild(o); });
  const setList = document.getElementById('setList'); setList.innerHTML = '';
  SETS.forEach(s => { const o = document.createElement('option'); o.value = s; setList.appendChild(o); });
}

function openAddModal() {
  document.getElementById('formModalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Add Student';
  document.getElementById('formId').value = '0';
  document.getElementById('studentForm').reset();
  document.getElementById('fGender').value = 'Male';
  document.getElementById('photoPreviewRow').style.display = 'none';
  document.getElementById('photoPreview').src = '';
  populateDatalists();
  if (!formModalInstance) {
    formModalInstance = new bootstrap.Modal(document.getElementById('studentFormModal'));
  }
  formModalInstance.show();
}

function openEditModal(id) {
  const s = ALL_STUDENTS.find(x => x.id === id);
  if (!s) return;
  const title = s.has_db_id ? 'Edit Student' : 'Edit Student (will be saved to database)';
  document.getElementById('formModalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>' + title;
  document.getElementById('formId').value = s.id;
  document.getElementById('fFirstName').value = s.first_name || '';
  document.getElementById('fSurname').value = s.surname || '';
  document.getElementById('fOtherName').value = s.other_name || '';
  document.getElementById('fGender').value = s.gender || 'Male';
  document.getElementById('fProgram').value = s.program || '';
  document.getElementById('fLevel').value = s.level || '';
  document.getElementById('fSet').value = s.set_name || '';
  document.getElementById('fYear').value = s.year || '';
  document.getElementById('fStudentNumber').value = s.student_number || '';
  document.getElementById('fIndexNumber').value = s.index_number || '';
  document.getElementById('fRegNumber').value = s.registration_number || '';
  document.getElementById('fNationalId').value = s.national_student_id_number || '';
  document.getElementById('fPhone').value = s.phone || '';
  document.getElementById('fMobileNumber').value = s.mobile_number || '';
  document.getElementById('fEmail').value = s.email || '';
  if (s.passport_photo) {
    document.getElementById('photoPreviewRow').style.display = 'flex';
    document.getElementById('photoPreview').src = s.passport_photo;
  } else {
    document.getElementById('photoPreviewRow').style.display = 'none';
  }
  populateDatalists();
  if (!formModalInstance) {
    formModalInstance = new bootstrap.Modal(document.getElementById('studentFormModal'));
  }
  formModalInstance.show();
}

function saveStudent(e) {
  e.preventDefault();
  const form = document.getElementById('studentForm');
  const formData = new FormData(form);
  const id = parseInt(formData.get('id'));
  const s = ALL_STUDENTS.find(x => x.id === id);

  if (s && !s.has_db_id) {
    // Excel-origin student being edited: save to DB as new record
    formData.set('action', 'save_excel_student');
    formData.delete('id');
  } else if (id > 0) {
    // DB student being edited
    formData.set('action', 'edit');
  } else {
    // New student from Add form
    formData.set('action', 'add');
  }

  const btn = document.getElementById('formSaveBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

  fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
      if (data.success) {
        formModalInstance.hide();
        showToast(data.message || (s && !s.has_db_id ? 'Student saved to database successfully' : id > 0 ? 'Student updated successfully' : 'Student added successfully'));
        location.reload();
      } else {
        showToast(data.error || 'Failed to save student', 'error');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
      showToast('Network error: ' + err.message, 'error');
    });

  return false;
}

// ─── DELETE ───
let deleteTargetId = null;

function confirmDelete(id, name) {
  const s = ALL_STUDENTS.find(x => x.id === id);
  deleteTargetId = id;
  if (s && !s.has_db_id) {
    document.getElementById('deleteStudentName').textContent = `"${name}" is from an Excel file. To delete it permanently, first save it to the database by editing it, then delete.`;
    document.getElementById('confirmDeleteBtn').style.display = 'none';
  } else {
    document.getElementById('deleteStudentName').textContent = `Are you sure you want to delete "${name}"? This cannot be undone.`;
    document.getElementById('confirmDeleteBtn').style.display = 'inline-block';
  }
  if (!deleteModalInstance) {
    deleteModalInstance = new bootstrap.Modal(document.getElementById('deleteModal'));
  }
  deleteModalInstance.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
  if (!deleteTargetId) return;
  const btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';

  const formData = new FormData();
  formData.set('action', 'delete');
  formData.set('id', deleteTargetId);

  fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-trash me-1"></i>Delete';
      if (data.success) {
        deleteModalInstance.hide();
        showToast('Student deleted successfully');
        location.reload();
      } else {
        showToast(data.error || 'Failed to delete student', 'error');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-trash me-1"></i>Delete';
      showToast('Network error: ' + err.message, 'error');
    });
});

// ─── PHOTO PREVIEW ───
document.addEventListener('change', function(e) {
  if (e.target && e.target.id === 'fPhoto') {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(ev) {
        document.getElementById('photoPreviewRow').style.display = 'flex';
        document.getElementById('photoPreview').src = ev.target.result;
      };
      reader.readAsDataURL(file);
    }
  }
});

function clearPhoto() {
  document.getElementById('fPhoto').value = '';
  document.getElementById('photoPreviewRow').style.display = 'none';
  document.getElementById('photoPreview').src = '';
}

// ─── HELPERS ───
function escHtml(str) {
  if (!str && str !== 0) return '';
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}
</script>
</body>
</html>
