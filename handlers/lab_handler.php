<?php
/**
 * Lab Manager unified AJAX/Form POST handler.
 * Returns JSON: {success: bool, message: string, data: mixed}
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$data = bootstrapStaffDashboard(['lab', 'ict', 'it', 'director']);
$auth = $data['auth'];
$staff = $data['staff'];
$ict = getICTConnection();
$students = getStudentsConnection();
$user = $data['user'];
$userId = (int)($user['id'] ?? 0);

header('Content-Type: application/json');

// CSRF protection
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token']);
    exit;
}

function respond($success, $message, $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        // â”€â”€ LAB ROOMS â”€â”€
        case 'add_lab_room':
            $name = $_POST['room_name'] ?? '';
            $code = $_POST['room_code'] ?? '';
            $capacity = (int)($_POST['capacity'] ?? 0);
            $computers = (int)($_POST['computer_count'] ?? 0);
            $location = $_POST['location'] ?? '';
            if (!$name || !$code) throw new Exception('Room name and code required');
            $stmt = $ict->prepare("INSERT INTO lab_rooms (room_name, room_code, capacity, computer_count, location) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('ssiis', $name, $code, $capacity, $computers, $location);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Lab room added', ['id' => $ict->insert_id]);
            break;

        case 'edit_lab_room':
            $id = (int)($_POST['id'] ?? 0);
            $name = $_POST['room_name'] ?? '';
            $code = $_POST['room_code'] ?? '';
            $capacity = (int)($_POST['capacity'] ?? 0);
            $computers = (int)($_POST['computer_count'] ?? 0);
            $location = $_POST['location'] ?? '';
            $status = $_POST['status'] ?? 'active';
            if (!$id) throw new Exception('Room ID required');
            $stmt = $ict->prepare("UPDATE lab_rooms SET room_name=?, room_code=?, capacity=?, computer_count=?, location=?, status=? WHERE id=?");
            $stmt->bind_param('ssiissi', $name, $code, $capacity, $computers, $location, $status, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Lab room updated');
            break;

        case 'delete_lab_room':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Room ID required');
            $stmt = $ict->prepare("UPDATE lab_rooms SET status='inactive' WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Lab room deactivated');
            break;

        // â”€â”€ COMPUTERS â”€â”€
        case 'add_computer':
            $cid = $_POST['computer_id'] ?? '';
            $name = $_POST['computer_name'] ?? '';
            $location = $_POST['location'] ?? '';
            $ip = $_POST['ip_address'] ?? '';
            $mac = $_POST['mac_address'] ?? '';
            $specs = $_POST['specifications'] ?? '';
            $os = $_POST['os_installed'] ?? '';
            $purchase = $_POST['purchase_date'] ?? null;
            $warranty = $_POST['warranty_expiry'] ?? null;
            if (!$cid || !$name) throw new Exception('Computer ID and name required');
            $stmt = $ict->prepare("INSERT INTO lab_computers (computer_id, computer_name, location, ip_address, mac_address, specifications, os_installed, purchase_date, warranty_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssssss', $cid, $name, $location, $ip, $mac, $specs, $os, $purchase, $warranty);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Computer added', ['id' => $ict->insert_id]);
            break;

        case 'edit_computer':
            $id = (int)($_POST['id'] ?? 0);
            $cid = $_POST['computer_id'] ?? '';
            $name = $_POST['computer_name'] ?? '';
            $location = $_POST['location'] ?? '';
            $status = $_POST['status'] ?? 'online';
            $ip = $_POST['ip_address'] ?? '';
            $mac = $_POST['mac_address'] ?? '';
            $specs = $_POST['specifications'] ?? '';
            $os = $_POST['os_installed'] ?? '';
            if (!$id) throw new Exception('Computer ID required');
            $stmt = $ict->prepare("UPDATE lab_computers SET computer_id=?, computer_name=?, location=?, status=?, ip_address=?, mac_address=?, specifications=?, os_installed=? WHERE id=?");
            $stmt->bind_param('ssssssssi', $cid, $name, $location, $status, $ip, $mac, $specs, $os, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Computer updated');
            break;

        case 'delete_computer':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Computer ID required');
            $stmt = $ict->prepare("UPDATE lab_computers SET status='deleted' WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Computer removed');
            break;

        // â”€â”€ STUDENT ID CARDS â”€â”€
        case 'generate_id_card':
            $studentId = (int)($_POST['student_id'] ?? 0);
            $expiry = $_POST['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'));
            if (!$studentId) throw new Exception('Student ID required');
            $stmt = $students->prepare("SELECT id, student_number, registration_number, full_name, program, intake_date, profile_picture, passport_photo FROM students WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $studentId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stu = $stmt->get_result()->fetch_assoc();
            if (!$stu) throw new Exception('Student not found');
            $cardNum = 'ID-' . strtoupper(substr(md5($studentId . time()), 0, 10));
            $photo = $stu['passport_photo'] ?: $stu['profile_picture'] ?: '';
            $intake = $stu['intake_date'] ? date('Y-m', strtotime($stu['intake_date'])) : '';
            $stmt = $ict->prepare("INSERT INTO student_id_cards (student_id, card_number, registration_number, program, intake, academic_year, expiry_date, photo_path, issued_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $year = date('Y');
            $stmt->bind_param('isssssssi', $studentId, $cardNum, $stu['registration_number'], $stu['program'], $intake, $year, $expiry, $photo, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $cardId = $ict->insert_id;
            $stmt = $ict->prepare("INSERT INTO id_card_print_history (card_id, student_id, print_type, printed_by) VALUES (?, ?, 'new', ?)");
            $stmt->bind_param('iii', $cardId, $studentId, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("UPDATE student_id_cards SET last_print_date = NOW(), print_count = print_count + 1 WHERE id = ?");
            $stmt->bind_param('i', $cardId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'ID card generated', ['id' => $cardId, 'card_number' => $cardNum, 'student' => $stu]);
            break;

        case 'reprint_id_card':
            $cardId = (int)($_POST['card_id'] ?? 0);
            $reason = $_POST['reason'] ?? 'reprint';
            if (!$cardId) throw new Exception('Card ID required');
            $stmt = $ict->prepare("SELECT * FROM student_id_cards WHERE id = ?");
            $stmt->bind_param('i', $cardId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $card = $stmt->get_result()->fetch_assoc();
            if (!$card) throw new Exception('Card not found');
            $stmt = $ict->prepare("INSERT INTO id_card_print_history (card_id, student_id, print_type, reason, printed_by) VALUES (?, ?, 'reprint', ?, ?)");
            $stmt->bind_param('iiss', $cardId, $card['student_id'], $reason, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("UPDATE student_id_cards SET last_print_date = NOW(), print_count = print_count + 1 WHERE id = ?");
            $stmt->bind_param('i', $cardId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Reprint logged');
            break;

        case 'replace_id_card':
            $cardId = (int)($_POST['card_id'] ?? 0);
            $reason = $_POST['reason'] ?? 'lost';
            $charge = (float)($_POST['charge_amount'] ?? 0);
            if (!$cardId) throw new Exception('Card ID required');
            $stmt = $ict->prepare("SELECT * FROM student_id_cards WHERE id = ?");
            $stmt->bind_param('i', $cardId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $card = $stmt->get_result()->fetch_assoc();
            if (!$card) throw new Exception('Original card not found');
            $cardNum = 'ID-' . strtoupper(substr(md5($card['student_id'] . time()), 0, 10));
            $expiry = date('Y-m-d', strtotime('+1 year'));
            $stmt = $ict->prepare("INSERT INTO student_id_cards (student_id, card_number, registration_number, program, intake, academic_year, expiry_date, photo_path, status, issued_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
            $year = date('Y');
            $stmt->bind_param('isssssssi', $card['student_id'], $cardNum, $card['registration_number'], $card['program'], $card['intake'], $year, $expiry, $card['photo_path'], $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $newId = $ict->insert_id;
            $stmt = $ict->prepare("INSERT INTO id_card_replacements (student_id, original_card_id, reason, charge_amount, approved_by, new_card_id, status) VALUES (?, ?, ?, ?, ?, ?, 'approved')");
            $stmt->bind_param('iisiii', $card['student_id'], $cardId, $reason, $charge, $userId, $newId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("UPDATE student_id_cards SET status='replaced' WHERE id = ?");
            $stmt->bind_param('i', $cardId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Card replacement created', ['new_card_id' => $newId, 'card_number' => $cardNum]);
            break;

        case 'verify_id_card':
            $cardNum = $_POST['card_number'] ?? '';
            if (!$cardNum) throw new Exception('Card number required');
            $stmt = $ict->prepare("SELECT c.*, s.full_name, s.student_number FROM student_id_cards c JOIN igangaschool_students.students s ON c.student_id = s.id WHERE c.card_number = ? LIMIT 1");
            $stmt->bind_param('s', $cardNum);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $card = $stmt->get_result()->fetch_assoc();
            if (!$card) throw new Exception('Card not found');
            respond(true, 'Card verified', $card);
            break;

        // â”€â”€ PRACTICAL SESSIONS â”€â”€
        case 'add_practical_session':
            $code = $_POST['session_code'] ?? '';
            $course = $_POST['course_name'] ?? '';
            $instructor = $_POST['instructor_name'] ?? '';
            $roomId = (int)($_POST['lab_room_id'] ?? 0);
            $date = $_POST['session_date'] ?? '';
            $start = $_POST['start_time'] ?? '';
            $end = $_POST['end_time'] ?? '';
            $program = $_POST['program'] ?? '';
            $year = (int)($_POST['year'] ?? 0);
            $sem = $_POST['semester'] ?? '';
            $max = (int)($_POST['max_students'] ?? 0);
            if (!$code || !$course || !$date) throw new Exception('Required fields missing');
            $stmt = $ict->prepare("INSERT INTO lab_practical_sessions (session_code, course_name, instructor_name, lab_room_id, session_date, start_time, end_time, program, year, semester, max_students, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssissssiiii', $code, $course, $instructor, $roomId, $date, $start, $end, $program, $year, $sem, $max, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Practical session created', ['id' => $ict->insert_id]);
            break;

        case 'edit_practical_session':
            $id = (int)($_POST['id'] ?? 0);
            $course = $_POST['course_name'] ?? '';
            $instructor = $_POST['instructor_name'] ?? '';
            $roomId = (int)($_POST['lab_room_id'] ?? 0);
            $date = $_POST['session_date'] ?? '';
            $start = $_POST['start_time'] ?? '';
            $end = $_POST['end_time'] ?? '';
            $status = $_POST['status'] ?? 'scheduled';
            if (!$id) throw new Exception('Session ID required');
            $stmt = $ict->prepare("UPDATE lab_practical_sessions SET course_name=?, instructor_name=?, lab_room_id=?, session_date=?, start_time=?, end_time=?, status=? WHERE id=?");
            $stmt->bind_param('ssissssi', $course, $instructor, $roomId, $date, $start, $end, $status, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Session updated');
            break;

        case 'delete_practical_session':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Session ID required');
            $stmt = $ict->prepare("UPDATE lab_practical_sessions SET status='cancelled' WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Session cancelled');
            break;

        // â”€â”€ LAB ATTENDANCE â”€â”€
        case 'mark_attendance':
            $studentId = (int)($_POST['student_id'] ?? 0);
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $roomId = (int)($_POST['lab_room_id'] ?? 0);
            $computerId = (int)($_POST['computer_id'] ?? 0);
            $statusVal = $_POST['status'] ?? 'present';
            $date = $_POST['attendance_date'] ?? date('Y-m-d');
            if (!$studentId) throw new Exception('Student ID required');
            $stmt = $ict->prepare("INSERT INTO lab_attendance (student_id, lab_room_id, session_id, attendance_date, time_in, computer_id, status, marked_by) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
            $stmt->bind_param('iiisisi', $studentId, $roomId, $sessionId, $date, $computerId, $statusVal, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Attendance marked');
            break;

        case 'bulk_attendance':
            $students = $_POST['students'] ?? '';
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $roomId = (int)($_POST['lab_room_id'] ?? 0);
            $date = $_POST['attendance_date'] ?? date('Y-m-d');
            $ids = array_map('intval', explode(',', $students));
            $count = 0;
            $checkStmt = $ict->prepare("SELECT id FROM lab_attendance WHERE student_id=? AND session_id=? AND attendance_date=?");
            $insertStmt = $ict->prepare("INSERT INTO lab_attendance (student_id, lab_room_id, session_id, attendance_date, time_in, status, marked_by) VALUES (?, ?, ?, ?, NOW(), 'present', ?)");
            foreach ($ids as $sid) {
                if ($sid <= 0) continue;
                $checkStmt->bind_param('iis', $sid, $sessionId, $date);
                if (!$checkStmt->execute()) { error_log('$checkStmt execute failed: ' . ($checkStmt->error ?? 'unknown')); };
                $check = $checkStmt->get_result();
                if ($check && $check->num_rows === 0) {
                    $insertStmt->bind_param('iiisi', $sid, $roomId, $sessionId, $date, $userId);
                    if (!$insertStmt->execute()) { error_log('$insertStmt execute failed: ' . ($insertStmt->error ?? 'unknown')); };
                    $count++;
                }
            }
            respond(true, "$count attendance records marked");
            break;

        // â”€â”€ EQUIPMENT â”€â”€
        case 'add_equipment':
            $code = $_POST['equipment_code'] ?? '';
            $name = $_POST['equipment_name'] ?? '';
            $type = $_POST['equipment_type'] ?? 'other';
            $brand = $_POST['brand'] ?? '';
            $model = $_POST['model'] ?? '';
            $serial = $_POST['serial_number'] ?? '';
            $roomId = (int)($_POST['lab_room_id'] ?? 0);
            $purchase = $_POST['purchase_date'] ?? null;
            $warranty = $_POST['warranty_expiry'] ?? null;
            if (!$code || !$name) throw new Exception('Code and name required');
            $stmt = $ict->prepare("INSERT INTO lab_equipment (equipment_code, equipment_name, equipment_type, brand, model, serial_number, lab_room_id, purchase_date, warranty_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssiss', $code, $name, $type, $brand, $model, $serial, $roomId, $purchase, $warranty);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Equipment added', ['id' => $ict->insert_id]);
            break;

        case 'edit_equipment':
            $id = (int)($_POST['id'] ?? 0);
            $name = $_POST['equipment_name'] ?? '';
            $type = $_POST['equipment_type'] ?? '';
            $brand = $_POST['brand'] ?? '';
            $model = $_POST['model'] ?? '';
            $serial = $_POST['serial_number'] ?? '';
            $roomId = (int)($_POST['lab_room_id'] ?? 0);
            $condition = $_POST['condition_status'] ?? 'good';
            $statusEq = $_POST['status'] ?? 'available';
            if (!$id) throw new Exception('Equipment ID required');
            $stmt = $ict->prepare("UPDATE lab_equipment SET equipment_name=?, equipment_type=?, brand=?, model=?, serial_number=?, lab_room_id=?, condition_status=?, status=? WHERE id=?");
            $stmt->bind_param('sssssisii', $name, $type, $brand, $model, $serial, $roomId, $condition, $statusEq, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Equipment updated');
            break;

        case 'delete_equipment':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Equipment ID required');
            $stmt = $ict->prepare("UPDATE lab_equipment SET status='retired' WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Equipment retired');
            break;

        case 'checkout_equipment':
            $eqId = (int)($_POST['equipment_id'] ?? 0);
            $to = $_POST['checked_out_to'] ?? '';
            $type = $_POST['borrower_type'] ?? 'student';
            $borrowerId = (int)($_POST['borrower_id'] ?? 0);
            $returnDate = $_POST['expected_return'] ?? null;
            if (!$eqId || !$to) throw new Exception('Equipment ID and borrower required');
            $stmt = $ict->prepare("INSERT INTO lab_equipment_checkout (equipment_id, checked_out_to, borrower_type, borrower_id, checkout_date, expected_return, checked_out_by) VALUES (?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->bind_param('issiii', $eqId, $to, $type, $borrowerId, $returnDate, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("UPDATE lab_equipment SET status='in_use' WHERE id=?");
            $stmt->bind_param('i', $eqId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Equipment checked out');
            break;

        case 'return_equipment':
            $checkoutId = (int)($_POST['checkout_id'] ?? 0);
            $condition = $_POST['condition_at_return'] ?? '';
            if (!$checkoutId) throw new Exception('Checkout ID required');
            $stmt = $ict->prepare("UPDATE lab_equipment_checkout SET actual_return=NOW(), condition_at_return=?, status='returned' WHERE id=?");
            $stmt->bind_param('si', $condition, $checkoutId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("SELECT equipment_id FROM lab_equipment_checkout WHERE id=?");
            $stmt->bind_param('i', $checkoutId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $co = $stmt->get_result()->fetch_assoc();
            if ($co) {
                $stmt = $ict->prepare("UPDATE lab_equipment SET status='available' WHERE id=?");
                $stmt->bind_param('i', $co['equipment_id']);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            }
            respond(true, 'Equipment returned');
            break;

        // â”€â”€ PRINTING â”€â”€
        case 'create_print_job':
            $reqName = $_POST['requester_name'] ?? '';
            $reqType = $_POST['requester_type'] ?? 'student';
            $reqId = (int)($_POST['requester_id'] ?? 0);
            $docName = $_POST['document_name'] ?? '';
            $pages = (int)($_POST['pages'] ?? 1);
            $copies = (int)($_POST['copies'] ?? 1);
            $printType = $_POST['print_type'] ?? 'bw';
            $paperSize = $_POST['paper_size'] ?? 'A4';
            if (!$reqName || $pages < 1) throw new Exception('Requester name and valid page count required');
            $stmt = $ict->prepare("SELECT charge_per_page FROM printing_charges WHERE print_type=? AND paper_size=? AND is_active=1 LIMIT 1");
            $stmt->bind_param('ss', $printType, $paperSize);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $chargeRow = $stmt->get_result()->fetch_assoc();
            $chargePerPage = $chargeRow ? (float)$chargeRow['charge_per_page'] : 0;
            $total = $chargePerPage * $pages * $copies;
            $num = 'PRT-' . strtoupper(substr(md5(time()), 0, 8));
            $stmt = $ict->prepare("INSERT INTO printing_jobs (job_number, requester_name, requester_type, requester_id, document_name, pages, copies, print_type, paper_size, charge_per_page, total_charge) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssiiissd', $num, $reqName, $reqType, $reqId, $docName, $pages, $copies, $printType, $paperSize, $chargePerPage, $total);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Print job created', ['job_number' => $num, 'total_charge' => $total, 'id' => $ict->insert_id]);
            break;

        case 'complete_print_job':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Job ID required');
            $stmt = $ict->prepare("UPDATE printing_jobs SET status='completed', printed_by=?, printed_at=NOW() WHERE id=?");
            $stmt->bind_param('ii', $userId, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Print job completed');
            break;

        case 'cancel_print_job':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Job ID required');
            $stmt = $ict->prepare("UPDATE printing_jobs SET status='cancelled' WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Print job cancelled');
            break;

        case 'update_printing_charge':
            $id = (int)($_POST['id'] ?? 0);
            $charge = (float)($_POST['charge_per_page'] ?? 0);
            if (!$id) throw new Exception('Charge ID required');
            $stmt = $ict->prepare("UPDATE printing_charges SET charge_per_page=?, updated_by=? WHERE id=?");
            $stmt->bind_param('dii', $charge, $userId, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Printing charge updated');
            break;

        // â”€â”€ REPAIRS / TECHNICAL SUPPORT â”€â”€
        case 'report_repair':
            $computerId = (int)($_POST['computer_id'] ?? 0);
            $reportedBy = $_POST['reported_by'] ?? '';
            $desc = $_POST['issue_description'] ?? '';
            $category = $_POST['issue_category'] ?? 'other';
            $priority = $_POST['priority'] ?? 'medium';
            if (!$reportedBy || !$desc) throw new Exception('Reporter and description required');
            $num = 'RPR-' . strtoupper(substr(md5(time()), 0, 8));
            $stmt = $ict->prepare("INSERT INTO computer_repairs (repair_number, computer_id, reported_by, issue_description, issue_category, priority) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sissss', $num, $computerId, $reportedBy, $desc, $category, $priority);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Repair reported', ['repair_number' => $num, 'id' => $ict->insert_id]);
            break;

        case 'update_repair_status':
            $id = (int)($_POST['id'] ?? 0);
            $statusR = $_POST['status'] ?? '';
            $diagnosis = $_POST['diagnosis'] ?? '';
            $resolution = $_POST['resolution'] ?? '';
            $parts = $_POST['parts_replaced'] ?? '';
            $cost = (float)($_POST['cost'] ?? 0);
            $technician = $_POST['assigned_technician'] ?? '';
            if (!$id) throw new Exception('Repair ID required');
            $sets = ['status=?', 'diagnosis=?', 'resolution=?', 'parts_replaced=?', 'cost=?', 'assigned_technician=?'];
            $params = [$statusR, $diagnosis, $resolution, $parts, $cost, $technician];
            $types = 'ssssds';
            if ($statusR === 'diagnosed') { $sets[] = "diagnosed_date=NOW()"; }
            if ($statusR === 'completed' || $statusR === 'closed') { $sets[] = "completed_date=NOW()"; }
            $params[] = $id;
            $types .= 'i';
            $stmt = $ict->prepare("UPDATE computer_repairs SET " . implode(',', $sets) . " WHERE id=?");
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Repair updated');
            break;

        // â”€â”€ SOFTWARE â”€â”€
        case 'add_software':
            $name = $_POST['software_name'] ?? '';
            $ver = $_POST['version'] ?? '';
            $license = $_POST['license_key'] ?? '';
            $type = $_POST['license_type'] ?? 'educational';
            $expiry = $_POST['license_expiry'] ?? null;
            $category = $_POST['category'] ?? 'utility';
            if (!$name) throw new Exception('Software name required');
            $stmt = $ict->prepare("INSERT INTO software_inventory (software_name, version, license_key, license_type, license_expiry, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $name, $ver, $license, $type, $expiry, $category);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Software added', ['id' => $ict->insert_id]);
            break;

        case 'edit_software':
            $id = (int)($_POST['id'] ?? 0);
            $name = $_POST['software_name'] ?? '';
            $ver = $_POST['version'] ?? '';
            $license = $_POST['license_key'] ?? '';
            $type = $_POST['license_type'] ?? '';
            $expiry = $_POST['license_expiry'] ?? null;
            if (!$id) throw new Exception('Software ID required');
            $stmt = $ict->prepare("UPDATE software_inventory SET software_name=?, version=?, license_key=?, license_type=?, license_expiry=? WHERE id=?");
            $stmt->bind_param('sssssi', $name, $ver, $license, $type, $expiry, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Software updated');
            break;

        case 'delete_software':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Software ID required');
            $stmt = $ict->prepare("DELETE FROM software_inventory WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Software deleted');
            break;

        case 'install_software':
            $swId = (int)($_POST['software_id'] ?? 0);
            $compId = (int)($_POST['computer_id'] ?? 0);
            $version = $_POST['version_installed'] ?? '';
            if (!$swId || !$compId) throw new Exception('Software and computer required');
            $stmt = $ict->prepare("INSERT INTO software_installations (software_id, computer_id, version_installed, installed_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiis', $swId, $compId, $version, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Installation recorded');
            break;

        // â”€â”€ CONSUMABLES â”€â”€
        case 'add_consumable':
            $name = $_POST['item_name'] ?? '';
            $cat = $_POST['item_category'] ?? 'other';
            $qty = (int)($_POST['quantity'] ?? 0);
            $reorder = (int)($_POST['reorder_level'] ?? 5);
            $cost = (float)($_POST['unit_cost'] ?? 0);
            $supplier = $_POST['supplier'] ?? '';
            if (!$name) throw new Exception('Item name required');
            $stmt = $ict->prepare("INSERT INTO lab_consumables (item_name, item_category, quantity, reorder_level, unit_cost, supplier) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssiids', $name, $cat, $qty, $reorder, $cost, $supplier);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Consumable added', ['id' => $ict->insert_id]);
            break;

        case 'update_consumable_stock':
            $id = (int)($_POST['id'] ?? 0);
            $qty = (int)($_POST['quantity'] ?? 0);
            if (!$id) throw new Exception('Item ID required');
            $stmt = $ict->prepare("UPDATE lab_consumables SET quantity=? WHERE id=?");
            $stmt->bind_param('ii', $qty, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Stock updated');
            break;

        case 'edit_consumable':
            $id = (int)($_POST['id'] ?? 0);
            $name = $_POST['item_name'] ?? '';
            $cat = $_POST['item_category'] ?? '';
            $qty = (int)($_POST['quantity'] ?? 0);
            $reorder = (int)($_POST['reorder_level'] ?? 0);
            $cost = (float)($_POST['unit_cost'] ?? 0);
            $supplier = $_POST['supplier'] ?? '';
            if (!$id || !$name) throw new Exception('Item ID and name required');
            $stmt = $ict->prepare("UPDATE lab_consumables SET item_name=?, item_category=?, quantity=?, reorder_level=?, unit_cost=?, supplier=? WHERE id=?");
            $stmt->bind_param('ssiidsi', $name, $cat, $qty, $reorder, $cost, $supplier, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Consumable updated');
            break;

        case 'delete_consumable':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Item ID required');
            $stmt = $ict->prepare("DELETE FROM lab_consumables WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Consumable deleted');
            break;

        // â”€â”€ SETTINGS â”€â”€
        case 'save_setting':
            $key = $_POST['setting_key'] ?? '';
            $value = $_POST['setting_value'] ?? '';
            if (!$key) throw new Exception('Setting key required');
            $stmt = $ict->prepare("INSERT INTO ict_system_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=?, updated_by=?");
            $stmt->bind_param('ssisi', $key, $value, $userId, $value, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            respond(true, 'Setting saved');
            break;

        default:
            throw new Exception('Unknown action: ' . $action);
    }
} catch (Exception $e) {
    respond(false, $e->getMessage());
}
