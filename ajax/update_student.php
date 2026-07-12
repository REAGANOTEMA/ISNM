<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$allowedStaffRoles = ['admin','bursar','accountant','finance','deputy principal','school principal','director','registrar','exam_officer','ict','hr'];
$staffRole = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($staffRole, $allowedStaffRoles)) {
    echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
    exit;
}
require_once __DIR__ . '/../config/database.php';
$conn = getStudentsConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// DELETE action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit; }
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
    } else {
        error_log("update_student delete error: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Delete failed']);
    }
    $stmt->close();
    exit;
}

// GET request: fetch student data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id < 1) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit; }
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    if ($r && $row = $r->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
    }
    $stmt->close();
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid student ID']);
    exit;
}
$first_name = trim($_POST['first_name'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$other_name = trim($_POST['other_name'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
if (empty($full_name)) $full_name = trim("$first_name $other_name $surname");
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
$course = trim($_POST['course'] ?? '');
$level = trim($_POST['level'] ?? '');
$set_name = trim($_POST['set_name'] ?? '');
$current_year = intval($_POST['current_year'] ?? 0);
$current_semester = trim($_POST['current_semester'] ?? '');
$status = trim($_POST['status'] ?? 'Active');
$dob = trim($_POST['date_of_birth'] ?? '');
$nationality = trim($_POST['nationality'] ?? '');
$address = trim($_POST['address'] ?? '');
$district = trim($_POST['district'] ?? '');
$guardian_name = trim($_POST['guardian_name'] ?? '');
$guardian_phone = trim($_POST['guardian_phone'] ?? '');
$guardian_email = trim($_POST['guardian_email'] ?? '');
$emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
$emergency_contact_phone = trim($_POST['emergency_contact_phone'] ?? '');
$emergency_contact_email = trim($_POST['emergency_contact_email'] ?? '');
$sponsor = trim($_POST['sponsor'] ?? '');
$marital_status = trim($_POST['marital_status'] ?? '');
$religion = trim($_POST['religion'] ?? '');
$intake_year = intval($_POST['intake_year'] ?? 0);
$intake_period = trim($_POST['intake_period'] ?? '');
$student_category = trim($_POST['student_category'] ?? '');

$stmt = $conn->prepare("UPDATE students SET 
    first_name=?, surname=?, other_name=?, 
    full_name=?, gender=?, 
    index_number=?, registration_number=?, 
    student_number=?, national_student_id_number=?, 
    phone=?, mobile_number=?, email=?, 
    program=?, course=?, level=?, 
    set_name=?, current_year=?, current_semester=?,
    status=?, date_of_birth=?, nationality=?,
    address=?, district=?,
    guardian_name=?, guardian_phone=?, guardian_email=?,
    emergency_contact_name=?, emergency_contact_phone=?,
    emergency_contact_email=?,
    sponsor=?, marital_status=?, religion=?,
    intake_year=?, intake_period=?, student_category=?,
    updated_at=NOW() WHERE id=?");
$stmt->bind_param("ssssssssssssssssisssssssssssssssissi",
    $first_name, $surname, $other_name,
    $full_name, $gender,
    $index_number, $registration_number,
    $student_number, $national_id,
    $phone, $mobile_number, $email,
    $program, $course, $level,
    $set_name, $current_year, $current_semester,
    $status, $dob, $nationality,
    $address, $district,
    $guardian_name, $guardian_phone, $guardian_email,
    $emergency_contact_name, $emergency_contact_phone,
    $emergency_contact_email,
    $sponsor, $marital_status, $religion,
    $intake_year, $intake_period, $student_category,
    $id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Student updated successfully.']);
} else {
    error_log("update_student update error: " . $conn->error);
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}
$stmt->close();
