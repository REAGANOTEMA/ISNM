<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['matron', 'warden']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);

$action = $_POST['action'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? '../dashboards/wardens.php';

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
                $_SESSION['error'] = "Error creating welfare case: " . $conn->error;
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
            $stmt = $conn->prepare("INSERT INTO student_counseling_sessions (session_id, student_id, counselor_id, session_date, session_time, session_type, issues_discussed) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siissss", $session_id, $student_id, $user_id, $session_date, $session_time, $session_type, $issues);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Counseling session scheduled successfully.";
            } else {
                $_SESSION['error'] = "Error scheduling session: " . $conn->error;
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
            $stmt = $conn->prepare("INSERT INTO student_discipline_records (student_id, incident_date, incident_type, description, action_taken, reported_by, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("issssi", $student_id, $incident_date, $incident_type, $description, $action_taken, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Discipline case created successfully.";
            } else {
                $_SESSION['error'] = "Error creating discipline case: " . $conn->error;
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
            $incident_id = 'HI-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $stmt = $conn->prepare("INSERT INTO student_health_incidents (incident_id, student_id, incident_date, incident_type, description, actions_taken, reported_by) VALUES (?, ?, CURDATE(), ?, ?, ?, ?)");
            $stmt->bind_param("sisssi", $incident_id, $student_id, $incident_type, $description, $actions_taken, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Health incident recorded successfully.";
            } else {
                $_SESSION['error'] = "Error recording health incident: " . $conn->error;
            }
            $stmt->close();
        }
        break;
}

header("Location: $referrer");
exit;
