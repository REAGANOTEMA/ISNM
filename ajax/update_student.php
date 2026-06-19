<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/database.php';
$conn = getStudentsConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// GET request: fetch student data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id < 1) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit; }
    $r = $conn->query("SELECT * FROM students WHERE id=$id LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
    }
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid student ID']);
    exit;
}
$first_name = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
$surname = $conn->real_escape_string(trim($_POST['surname'] ?? ''));
$other_name = $conn->real_escape_string(trim($_POST['other_name'] ?? ''));
$full_name = trim($_POST['full_name'] ?? '');
if (empty($full_name)) $full_name = trim("$first_name $other_name $surname");
$full_name = $conn->real_escape_string($full_name);
$gender = $conn->real_escape_string($_POST['gender'] ?? 'Other');
$index_number = $conn->real_escape_string(trim($_POST['index_number'] ?? ''));
$registration_number = $conn->real_escape_string(trim($_POST['registration_number'] ?? ''));
$student_number = $conn->real_escape_string(trim($_POST['student_number'] ?? ''));
$national_id = $conn->real_escape_string(trim($_POST['national_student_id_number'] ?? ''));
$phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
$mobile_number = $conn->real_escape_string(trim($_POST['mobile_number'] ?? ''));
if (empty($mobile_number)) $mobile_number = $phone;
if (empty($phone)) $phone = $mobile_number;
$email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
$program = $conn->real_escape_string(trim($_POST['program'] ?? ''));
$course = $conn->real_escape_string(trim($_POST['course'] ?? ''));
$level = $conn->real_escape_string(trim($_POST['level'] ?? ''));
$set_name = $conn->real_escape_string(trim($_POST['set_name'] ?? ''));
$current_year = intval($_POST['current_year'] ?? 0);
$current_semester = $conn->real_escape_string(trim($_POST['current_semester'] ?? ''));
$status = $conn->real_escape_string(trim($_POST['status'] ?? 'Active'));
$dob = $conn->real_escape_string(trim($_POST['date_of_birth'] ?? ''));
$nationality = $conn->real_escape_string(trim($_POST['nationality'] ?? ''));
$address = $conn->real_escape_string(trim($_POST['address'] ?? ''));
$district = $conn->real_escape_string(trim($_POST['district'] ?? ''));
$guardian_name = $conn->real_escape_string(trim($_POST['guardian_name'] ?? ''));
$guardian_phone = $conn->real_escape_string(trim($_POST['guardian_phone'] ?? ''));
$guardian_email = $conn->real_escape_string(trim($_POST['guardian_email'] ?? ''));
$emergency_contact_name = $conn->real_escape_string(trim($_POST['emergency_contact_name'] ?? ''));
$emergency_contact_phone = $conn->real_escape_string(trim($_POST['emergency_contact_phone'] ?? ''));
$emergency_contact_email = $conn->real_escape_string(trim($_POST['emergency_contact_email'] ?? ''));
$sponsor = $conn->real_escape_string(trim($_POST['sponsor'] ?? ''));
$marital_status = $conn->real_escape_string(trim($_POST['marital_status'] ?? ''));
$religion = $conn->real_escape_string(trim($_POST['religion'] ?? ''));
$intake_year = intval($_POST['intake_year'] ?? 0);
$intake_period = $conn->real_escape_string(trim($_POST['intake_period'] ?? ''));
$student_category = $conn->real_escape_string(trim($_POST['student_category'] ?? ''));

$sql = "UPDATE students SET 
    first_name='$first_name', surname='$surname', other_name='$other_name', 
    full_name='$full_name', gender='$gender', 
    index_number='$index_number', registration_number='$registration_number', 
    student_number='$student_number', national_student_id_number='$national_id', 
    phone='$phone', mobile_number='$mobile_number', email='$email', 
    program='$program', course='$course', level='$level', 
    set_name='$set_name', current_year=$current_year, current_semester='$current_semester',
    status='$status', date_of_birth='$dob', nationality='$nationality',
    address='$address', district='$district',
    guardian_name='$guardian_name', guardian_phone='$guardian_phone', guardian_email='$guardian_email',
    emergency_contact_name='$emergency_contact_name', emergency_contact_phone='$emergency_contact_phone',
    emergency_contact_email='$emergency_contact_email',
    sponsor='$sponsor', marital_status='$marital_status', religion='$religion',
    intake_year=$intake_year, intake_period='$intake_period', student_category='$student_category',
    updated_at=NOW() WHERE id=$id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Student updated successfully.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $conn->error]);
}
