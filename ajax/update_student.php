<?php
/**
 * AJAX Student Update Handler - ISNM
 * Handles student CRUD operations via AJAX with cross-database sync.
 */
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$allowedStaffRoles = ['admin','bursar','accountant','finance','deputy principal','school principal','director','registrar','exam_officer','ict','hr','director general','ceo','academic registrar','school bursar','director finance','hr manager','system administrator','school secretary','director admissions'];
$staffRole = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($staffRole, $allowedStaffRoles)) {
    echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
    exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student_sync.php';
$conn = getStudentsConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ============================================================
// DELETE action
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }
    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit; }

    // Get student_number for cross-db sync
    $stmt = $conn->prepare("SELECT student_number FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    $studentNumber = '';
    if ($row = $r->fetch_assoc()) { $studentNumber = $row['student_number']; }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE students SET status = 'deleted', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Sync across databases
        if (!empty($studentNumber)) {
            deleteStudentAcrossDatabases($studentNumber);
        }
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
    } else {
        error_log("update_student delete error: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Delete failed']);
    }
    $stmt->close();
    exit;
}

// ============================================================
// STATUS CHANGE action
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }
    $id = intval($_POST['id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    if ($id < 1 || empty($newStatus)) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }

    $validStatuses = ['Active','Inactive','Suspended','Graduated','Withdrawn'];
    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit;
    }

    // Get current status and student number
    $stmt = $conn->prepare("SELECT status, student_number FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    $row = $r->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
        exit;
    }

    $oldStatus = $row['status'];
    $studentNumber = $row['student_number'];

    // Extra fields based on status
    $extraFields = '';
    $extraTypes = '';
    $extraParams = [];
    switch ($newStatus) {
        case 'Suspended':
            $extraFields = ', suspended_at = NOW(), suspension_reason = ?';
            $extraParams[] = $reason;
            $extraTypes = 's';
            break;
        case 'Active':
            $extraFields = ', activated_at = NOW()';
            break;
        case 'Graduated':
            $extraFields = ', graduation_date = CURDATE()';
            break;
        case 'Withdrawn':
            $extraFields = ', transfer_date = CURDATE(), transfer_reason = ?';
            $extraParams[] = $reason;
            $extraTypes = 's';
            break;
        case 'Inactive':
            $extraFields = ', archived_at = NOW()';
            break;
    }

    $sql = "UPDATE students SET status = ?$extraFields, updated_at = NOW() WHERE id = ?";
    $params = array_merge([$newStatus], $extraParams, [$id]);
    $types = $extraTypes . 'i';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        // Record status history
        $hist = $conn->prepare("INSERT INTO student_status_history (student_id, old_status, new_status, reason, changed_by, changed_by_name) VALUES (?, ?, ?, ?, ?, ?)");
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        $hist->bind_param('isssi', $id, $oldStatus, $newStatus, $reason, $userId);
        if (!$hist->execute()) error_log('Status history insert failed: ' . $hist->error);
        $hist->close();

        // Sync status across databases
        if (!empty($studentNumber)) {
            syncStatusAcrossDatabases($studentNumber, $newStatus);
        }

        echo json_encode(['success' => true, 'message' => "Student status changed to $newStatus."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Status update failed']);
    }
    $stmt->close();
    exit;
}

// ============================================================
// GET request: fetch student data
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id < 1) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit; }
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND status != 'deleted' LIMIT 1");
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

// ============================================================
// GET: Fetch student requirements status
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_requirements']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id < 1) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit; }
    $stmt = $conn->prepare("
        SELECT srs.*, ar.requirement_name, ar.type as requirement_type, ar.is_mandatory, ar.display_order
        FROM student_requirements_status srs
        JOIN admission_requirements ar ON srs.requirement_id = ar.id
        WHERE srs.student_id = ?
        ORDER BY ar.display_order ASC
    ");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    $reqs = [];
    while ($row = $r->fetch_assoc()) { $reqs[] = $row; }
    $stmt->close();
    echo json_encode(['success' => true, 'requirements' => $reqs]);
    exit;
}

// ============================================================
// POST: Update student requirement status
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_requirement') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }
    $studentId = intval($_POST['student_id'] ?? 0);
    $requirementId = intval($_POST['requirement_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($studentId < 1 || $requirementId < 1 || empty($status)) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }

    $validStatuses = ['Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received','Not Yet Given'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid requirement status']);
        exit;
    }

    $userName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    $userId = (int)($_SESSION['user_id'] ?? 0);

    $sql = "INSERT INTO student_requirements_status (student_id, requirement_id, status, remarks, verified_by, verified_by_name, submission_date, verified_at)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)
            ON DUPLICATE KEY UPDATE
              status = VALUES(status),
              remarks = VALUES(remarks),
              verified_by = VALUES(verified_by),
              verified_by_name = VALUES(verified_by_name),
              submission_date = CASE WHEN VALUES(status) IN ('Submitted','Received','Verified') THEN CURDATE() ELSE submission_date END,
              verified_at = CASE WHEN VALUES(status) = 'Verified' THEN NOW() ELSE verified_at END,
              updated_at = NOW()";

    $verifiedAt = ($status === 'Verified') ? date('Y-m-d H:i:s') : null;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iisssss', $studentId, $requirementId, $status, $remarks, $userId, $userName, $verifiedAt);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Requirement status updated.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================================
// POST: Update requirement document upload
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_requirement_doc') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }
    $studentId = intval($_POST['student_id'] ?? 0);
    $requirementId = intval($_POST['requirement_id'] ?? 0);

    if ($studentId < 1 || $requirementId < 1) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'File upload failed']);
        exit;
    }

    $file = $_FILES['document'];
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'File type not allowed. Use PDF, JPG, PNG, or DOC/DOCX.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../studentUploads/requirements/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "req_{$studentId}_{$requirementId}_" . time() . ".{$ext}";
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $docPath = 'studentUploads/requirements/' . $filename;
        $docName = $file['name'];

        $stmt = $conn->prepare("UPDATE student_requirements_status SET document_path = ?, document_name = ?, status = 'Submitted', submission_date = CURDATE() WHERE student_id = ? AND requirement_id = ?");
        $stmt->bind_param('ssii', $docPath, $docName, $studentId, $requirementId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Document uploaded successfully.', 'file_path' => $docPath, 'file_name' => $docName]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database update failed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
    }
    exit;
}

// ============================================================
// POST: Full student update
// ============================================================
$id = intval($_POST['id'] ?? 0);
if ($id < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid student ID']);
    exit;
}
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

// Collect ALL form fields
$first_name = trim($_POST['first_name'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$other_name = trim($_POST['other_name'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
if (empty($full_name)) $full_name = trim("$first_name $other_name $surname");

$gender = $_POST['gender'] ?? 'Other';
$index_number = trim($_POST['index_number'] ?? '');
$registration_number = trim($_POST['registration_number'] ?? '');
$student_number = trim($_POST['student_number'] ?? '');
$admission_number = trim($_POST['admission_number'] ?? '');
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
$county = trim($_POST['county'] ?? '');
$sub_county = trim($_POST['sub_county'] ?? '');
$village = trim($_POST['village'] ?? '');
$guardian_name = trim($_POST['guardian_name'] ?? '');
$guardian_phone = trim($_POST['guardian_phone'] ?? '');
$guardian_email = trim($_POST['guardian_email'] ?? '');
$guardian_relationship = trim($_POST['guardian_relationship'] ?? '');
$emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
$emergency_contact_phone = trim($_POST['emergency_contact_phone'] ?? '');
$emergency_contact_email = trim($_POST['emergency_contact_email'] ?? '');
$sponsor = trim($_POST['sponsor'] ?? '');
$marital_status = trim($_POST['marital_status'] ?? '');
$religion = trim($_POST['religion'] ?? '');
$student_category = trim($_POST['student_category'] ?? '');
$stream = trim($_POST['stream'] ?? '');
$class_name = trim($_POST['class_name'] ?? '');
$previous_school = trim($_POST['previous_school'] ?? '');
$previous_qualification = trim($_POST['previous_qualification'] ?? '');
$hostel_required = intval($_POST['hostel_required'] ?? 0);
$transport_required = intval($_POST['transport_required'] ?? 0);
$notes = trim($_POST['notes'] ?? '');
$blood_group = trim($_POST['blood_group'] ?? '');
$medical_conditions = trim($_POST['medical_conditions'] ?? '');
$allergies = trim($_POST['allergies'] ?? '');

$stmt = $conn->prepare("UPDATE students SET 
    first_name=?, surname=?, other_name=?, 
    full_name=?, gender=?, 
    index_number=?, registration_number=?, 
    student_number=?, admission_number=?, national_student_id_number=?, 
    phone=?, mobile_number=?, email=?, 
    program=?, course=?, level=?, 
    set_name=?, current_year=?, current_semester=?,
    status=?, date_of_birth=?, nationality=?,
    address=?, district=?, county=?, sub_county=?, village=?,
    guardian_name=?, guardian_phone=?, guardian_email=?, guardian_relationship=?,
    emergency_contact_name=?, emergency_contact_phone=?,
    emergency_contact_email=?,
    sponsor=?, marital_status=?, religion=?,
    student_category=?, stream=?, class_name=?,
    previous_school=?, previous_qualification=?,
    hostel_required=?, transport_required=?, notes=?,
    blood_group=?, medical_conditions=?, allergies=?,
    updated_at=NOW() WHERE id=?");
$stmt->bind_param("ssssssssssssssssisssssssssssssssssssssssssssssi",
    $first_name, $surname, $other_name,
    $full_name, $gender,
    $index_number, $registration_number,
    $student_number, $admission_number, $national_id,
    $phone, $mobile_number, $email,
    $program, $course, $level,
    $set_name, $current_year, $current_semester,
    $status, $dob, $nationality,
    $address, $district, $county, $sub_county, $village,
    $guardian_name, $guardian_phone, $guardian_email, $guardian_relationship,
    $emergency_contact_name, $emergency_contact_phone,
    $emergency_contact_email,
    $sponsor, $marital_status, $religion,
    $student_category, $stream, $class_name,
    $previous_school, $previous_qualification,
    $hostel_required, $transport_required, $notes,
    $blood_group, $medical_conditions, $allergies,
    $id
);

if ($stmt->execute()) {
    // Sync across all databases
    if (!empty($student_number) && !empty($first_name) && !empty($surname)) {
        $syncData = [
            'student_id' => $id,
            'student_number' => $student_number,
            'admission_number' => $admission_number,
            'first_name' => $first_name,
            'surname' => $surname,
            'other_name' => $other_name,
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'mobile_number' => $mobile_number,
            'gender' => $gender,
            'program' => $program,
            'course' => $course,
            'level' => $level,
            'set_name' => $set_name,
            'status' => $status,
            'district' => $district,
            'guardian_name' => $guardian_name,
            'guardian_phone' => $guardian_phone,
            'religion' => $religion,
        ];
        syncStudentRecord($syncData, 'update');
    }

    echo json_encode(['success' => true, 'message' => 'Student updated successfully.']);
} else {
    error_log("update_student update error: " . $conn->error);
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}
$stmt->close();
