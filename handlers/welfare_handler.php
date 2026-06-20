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
        $case_type = $conn->real_escape_string($_POST['case_type'] ?? '');
        $priority = $conn->real_escape_string($_POST['priority'] ?? 'Medium');
        $description = $conn->real_escape_string($_POST['case_description'] ?? '');
        $actions_taken = $conn->real_escape_string($_POST['immediate_actions'] ?? '');
        $follow_up = isset($_POST['follow_up_required']) ? 1 : 0;
        if ($student_id && $case_type) {
            $case_number = 'WEL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $sql = "INSERT INTO student_welfare_cases (case_number, student_id, case_type, priority, case_description, immediate_actions, status, assigned_warden, follow_up_required)
                    VALUES ('$case_number', $student_id, '$case_type', '$priority', '$description', '$actions_taken', 'Open', $user_id, $follow_up)";
            $conn->query($sql);
        }
        break;

    case 'schedule_session':
        $student_id = (int)($_POST['student_id'] ?? 0);
        $session_type = $conn->real_escape_string($_POST['session_type'] ?? 'Individual');
        $session_date = $conn->real_escape_string($_POST['session_date'] ?? '');
        $session_time = $conn->real_escape_string($_POST['session_time'] ?? '');
        $location = $conn->real_escape_string($_POST['location'] ?? '');
        $issues = $conn->real_escape_string($_POST['issues_discussed'] ?? '');
        if ($student_id && $session_date) {
            $session_id = 'CS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $sql = "INSERT INTO student_counseling_sessions (session_id, student_id, counselor_id, session_date, session_time, session_type, issues_discussed)
                    VALUES ('$session_id', $student_id, $user_id, '$session_date', '$session_time', '$session_type', '$issues')";
            $conn->query($sql);
        }
        break;

    case 'create_discipline_case':
        $student_id = (int)($_POST['student_id'] ?? 0);
        $incident_type = $conn->real_escape_string($_POST['incident_type'] ?? '');
        $incident_date = $conn->real_escape_string($_POST['incident_date'] ?? '');
        $action_taken = $conn->real_escape_string($_POST['action_taken'] ?? 'Warning');
        if ($student_id && $incident_type) {
            $case_number = 'DISC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $sql = "INSERT INTO student_discipline (case_number, student_id, incident_date, incident_type, action_taken, status)
                    VALUES ('$case_number', $student_id, '$incident_date', '$incident_type', '$action_taken', 'Pending')";
            $conn->query($sql);
        }
        break;

    case 'create_health_incident':
        $student_id = (int)($_POST['student_id'] ?? 0);
        $incident_type = $conn->real_escape_string($_POST['incident_type'] ?? 'Other');
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $actions_taken = $conn->real_escape_string($_POST['actions_taken'] ?? '');
        if ($student_id) {
            $incident_id = 'HI-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $sql = "INSERT INTO student_health_incidents (incident_id, student_id, incident_date, incident_type, location, resolved)
                    VALUES ('$incident_id', $student_id, CURDATE(), '$incident_type', '$description', 0)";
            $conn->query($sql);
        }
        break;
}

header("Location: $referrer");
exit;
