<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['matron', 'warden']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);

if (!$conn) { header('Content-Type: application/json'); http_response_code(500); echo json_encode(['success' => false, 'message' => 'Database connection failed']); exit; }

// CSRF validation — always enforced for POST
header('Content-Type: application/json');
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit();
}

$action = $_POST['action'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? '../dashboards/wardens.php';
$allowedHost = $_SERVER['SERVER_NAME'] ?? '';
if (!empty($allowedHost) && isset(parse_url($referrer)['host']) && parse_url($referrer)['host'] !== $allowedHost) {
    $referrer = '../dashboards/wardens.php';
}

switch ($action) {
    case 'create_welfare_case':
        $student_id = (int)($_POST['student_id'] ?? 0);
        $case_type = trim($_POST['case_type'] ?? '');
        $description = trim($_POST['description'] ?? $_POST['case_description'] ?? '');
        if ($student_id && $case_type) {
            $stmt = $conn->prepare("INSERT INTO student_welfare_cases (student_id, case_type, description, status, assigned_to) VALUES (?, ?, ?, 'Open', ?)");
            $stmt->bind_param("issi", $student_id, $case_type, $description, $user_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Welfare case created successfully.']);
            } else {
                error_log("welfare_handler create_welfare_case error: " . $conn->error);
                echo json_encode(['success' => false, 'message' => 'Error creating welfare case.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Student ID and case type required.']);
        }
        exit;

    case 'schedule_session':
        $student_id = (int)($_POST['student_id'] ?? 0);
        $session_type = trim($_POST['session_type'] ?? 'Individual');
        $session_date = trim($_POST['session_date'] ?? '');
        $session_time = trim($_POST['session_time'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $issues = trim($_POST['issues_discussed'] ?? '');
        if ($student_id && $session_date) {
            $session_id = 'CS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $stmt = $conn->prepare("INSERT INTO student_counseling_sessions (student_id, counselor_id, session_date, session_time, session_type, issues_discussed, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssss", $student_id, $user_id, $session_date, $session_time, $session_type, $issues, $location);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Counseling session scheduled successfully.']);
            } else {
                error_log("welfare_handler schedule_session error: " . $conn->error);
                echo json_encode(['success' => false, 'message' => 'Error scheduling session.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Student ID and session date required.']);
        }
        exit;

    case 'create_discipline_case':
        $student_id = (int)($_POST['student_id'] ?? 0);
        $incident_type = trim($_POST['incident_type'] ?? '');
        $incident_date = trim($_POST['incident_date'] ?? date('Y-m-d'));
        $action_taken = trim($_POST['action_taken'] ?? 'Warning');
        $description = trim($_POST['description'] ?? '');
        if ($student_id && $incident_type) {
            $stmt = $conn->prepare("INSERT INTO student_discipline_records (student_id, incident_type, description, action_taken, reported_by, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("isssi", $student_id, $incident_type, $description, $action_taken, $user_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Discipline case created successfully.']);
            } else {
                error_log("welfare_handler create_discipline_case error: " . $conn->error);
                echo json_encode(['success' => false, 'message' => 'Error creating discipline case.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Student ID and incident type required.']);
        }
        exit;

    case 'create_health_incident':
        $student_id = (int)($_POST['student_id'] ?? 0);
        $incident_type = trim($_POST['incident_type'] ?? 'Other');
        $description = trim($_POST['description'] ?? '');
        $actions_taken = trim($_POST['actions_taken'] ?? '');
        if ($student_id) {
            $stmt = $conn->prepare("INSERT INTO student_health_incidents (student_id, incident_type, description, action_taken, recorded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $student_id, $incident_type, $description, $actions_taken, $user_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Health incident recorded successfully.']);
            } else {
                error_log("welfare_handler create_health_incident error: " . $conn->error);
                echo json_encode(['success' => false, 'message' => 'Error recording health incident.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Student ID required.']);
        }
        exit;

    // ── UPDATE OPERATIONS ──

    case 'update_welfare_case':
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Case ID required.']); exit; }
        $validStatuses = ['Open', 'In Progress', 'Resolved', 'Closed'];
        $sets = [];
        $types = '';
        $params = [];
        if ($status && in_array($status, $validStatuses, true)) {
            $sets[] = 'status = ?';
            $types .= 's';
            $params[] = $status;
        }
        if ($description !== '') {
            $sets[] = 'description = ?';
            $types .= 's';
            $params[] = $description;
        }
        if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'No fields to update.']); exit; }
        $types .= 'i';
        $params[] = $id;
        $stmt = $conn->prepare("UPDATE student_welfare_cases SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Welfare case updated.']);
        } else {
            error_log("welfare_handler update_welfare_case error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error updating welfare case.']);
        }
        $stmt->close();
        exit;

    case 'delete_welfare_case':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Case ID required.']); exit; }
        $stmt = $conn->prepare("DELETE FROM student_welfare_cases WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Welfare case deleted.']);
        } else {
            error_log("welfare_handler delete_welfare_case error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error deleting welfare case.']);
        }
        $stmt->close();
        exit;

    case 'update_counseling_session':
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $session_date = trim($_POST['session_date'] ?? '');
        $session_time = trim($_POST['session_time'] ?? '');
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Session ID required.']); exit; }
        $validStatuses = ['Scheduled', 'Completed', 'Cancelled', 'No Show'];
        $sets = [];
        $types = '';
        $params = [];
        if ($status && in_array($status, $validStatuses, true)) {
            $sets[] = 'status = ?';
            $types .= 's';
            $params[] = $status;
        }
        if ($notes !== '') {
            $sets[] = 'notes = ?';
            $types .= 's';
            $params[] = $notes;
        }
        if ($session_date !== '') {
            $sets[] = 'session_date = ?';
            $types .= 's';
            $params[] = $session_date;
        }
        if ($session_time !== '') {
            $sets[] = 'session_time = ?';
            $types .= 's';
            $params[] = $session_time;
        }
        if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'No fields to update.']); exit; }
        $types .= 'i';
        $params[] = $id;
        $stmt = $conn->prepare("UPDATE student_counseling_sessions SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Counseling session updated.']);
        } else {
            error_log("welfare_handler update_counseling_session error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error updating counseling session.']);
        }
        $stmt->close();
        exit;

    case 'delete_counseling_session':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Session ID required.']); exit; }
        $stmt = $conn->prepare("DELETE FROM student_counseling_sessions WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Counseling session deleted.']);
        } else {
            error_log("welfare_handler delete_counseling_session error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error deleting counseling session.']);
        }
        $stmt->close();
        exit;

    case 'update_discipline_record':
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $action_taken = trim($_POST['action_taken'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Record ID required.']); exit; }
        $validStatuses = ['Pending', 'Reviewed', 'Resolved', 'Escalated'];
        $sets = [];
        $types = '';
        $params = [];
        if ($status && in_array($status, $validStatuses, true)) {
            $sets[] = 'status = ?';
            $types .= 's';
            $params[] = $status;
        }
        if ($action_taken !== '') {
            $sets[] = 'action_taken = ?';
            $types .= 's';
            $params[] = $action_taken;
        }
        if ($description !== '') {
            $sets[] = 'description = ?';
            $types .= 's';
            $params[] = $description;
        }
        if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'No fields to update.']); exit; }
        $types .= 'i';
        $params[] = $id;
        $stmt = $conn->prepare("UPDATE student_discipline_records SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Discipline record updated.']);
        } else {
            error_log("welfare_handler update_discipline_record error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error updating discipline record.']);
        }
        $stmt->close();
        exit;

    case 'delete_discipline_record':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Record ID required.']); exit; }
        $stmt = $conn->prepare("DELETE FROM student_discipline_records WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Discipline record deleted.']);
        } else {
            error_log("welfare_handler delete_discipline_record error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error deleting discipline record.']);
        }
        $stmt->close();
        exit;

    case 'update_health_incident':
        $id = (int)($_POST['id'] ?? 0);
        $incident_type = trim($_POST['incident_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $actions_taken = trim($_POST['actions_taken'] ?? '');
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Incident ID required.']); exit; }
        $sets = [];
        $types = '';
        $params = [];
        if ($incident_type !== '') {
            $sets[] = 'incident_type = ?';
            $types .= 's';
            $params[] = $incident_type;
        }
        if ($description !== '') {
            $sets[] = 'description = ?';
            $types .= 's';
            $params[] = $description;
        }
        if ($actions_taken !== '') {
            $sets[] = 'action_taken = ?';
            $types .= 's';
            $params[] = $actions_taken;
        }
        if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'No fields to update.']); exit; }
        $types .= 'i';
        $params[] = $id;
        $stmt = $conn->prepare("UPDATE student_health_incidents SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Health incident updated.']);
        } else {
            error_log("welfare_handler update_health_incident error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error updating health incident.']);
        }
        $stmt->close();
        exit;

    case 'delete_health_incident':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Incident ID required.']); exit; }
        $stmt = $conn->prepare("DELETE FROM student_health_incidents WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Health incident deleted.']);
        } else {
            error_log("welfare_handler delete_health_incident error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error deleting health incident.']);
        }
        $stmt->close();
        exit;
}
