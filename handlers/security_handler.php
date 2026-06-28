<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['security']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);

$action = $_POST['action'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? '../dashboards/security.php';

if ($action === 'report_incident' && $conn) {
    $incident_type = trim($_POST['incident_type'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $severity = trim($_POST['severity'] ?? 'Medium');
    $description = trim($_POST['description'] ?? '');

    if ($incident_type && $description) {
        $stmt = $conn->prepare("INSERT INTO security_incidents (incident_type, location, description, status, reported_by) VALUES (?, ?, ?, 'Reported', ?)");
        $stmt->bind_param("sssi", $incident_type, $location, $description, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Incident reported successfully.";
        } else {
            error_log("security_handler report_incident error: " . $conn->error);
            $_SESSION['error'] = "Error reporting incident. Please try again.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Incident type and description are required.";
    }
}

header("Location: $referrer");
exit;
