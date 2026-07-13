<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['matron', 'warden']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);

// CSRF validation
if (!empty($_SESSION['user_id'])) {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit();
    }
}

$action = $_POST['action'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? '../dashboards/wardens.php';
$allowedHost = $_SERVER['SERVER_NAME'] ?? '';
if (!empty($allowedHost) && isset(parse_url($referrer)['host']) && parse_url($referrer)['host'] !== $allowedHost) {
    $referrer = '../dashboards/wardens.php';
}

if (!$conn) { header("Location: $referrer"); exit; }

switch ($action) {
    case 'create_welfare_case':
        $student_id = (int)($_POST['student_id'] ?? 0);
        $case_type = trim($_POST['case_type'] ?? '');
        $description = trim($_POST['description'] ?? $_POST['case_description'] ?? '');
        if ($student_id && $case_type) {
            $stmt = $conn->prepare("INSERT INTO student_welfare_cases (student_id, case_type, description, status, assigned_to) VALUES (?, ?, ?, 'Open', ?)");
            $stmt->bind_param("issi", $student_id, $case_type, $description, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Welfare case created successfully.";
            } else {
                error_log("welfare_handler create_welfare_case error: " . $conn->error);
                $_SESSION['error'] = "Error creating welfare case. Please try again.";
            }
            $stmt->close();
        }
        break;

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
                $_SESSION['success'] = "Counseling session scheduled successfully.";
            } else {
                error_log("welfare_handler schedule_session error: " . $conn->error);
                $_SESSION['error'] = "Error scheduling session. Please try again.";
            }
            $stmt->close();
        }
        break;

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
                $_SESSION['success'] = "Discipline case created successfully.";
            } else {
                error_log("welfare_handler create_discipline_case error: " . $conn->error);
                $_SESSION['error'] = "Error creating discipline case. Please try again.";
            }
            $stmt->close();
        }
        break;

    case 'create_health_incident':
        $student_id = (int)($_POST['student_id'] ?? 0);
        $incident_type = trim($_POST['incident_type'] ?? 'Other');
        $description = trim($_POST['description'] ?? '');
        $actions_taken = trim($_POST['actions_taken'] ?? '');
        if ($student_id) {
            $stmt = $conn->prepare("INSERT INTO student_health_incidents (student_id, incident_type, description, action_taken, recorded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $student_id, $incident_type, $description, $actions_taken, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Health incident recorded successfully.";
            } else {
                error_log("welfare_handler create_health_incident error: " . $conn->error);
                $_SESSION['error'] = "Error recording health incident. Please try again.";
            }
            $stmt->close();
        }
        break;
}

header("Location: $referrer");
exit;
