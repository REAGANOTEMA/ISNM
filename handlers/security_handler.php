<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['security']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);

$action = $_POST['action'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? '../dashboards/security.php';

if ($action === 'report_incident' && $conn) {
    $incident_type = $conn->real_escape_string($_POST['incident_type'] ?? '');
    $location = $conn->real_escape_string($_POST['location'] ?? '');
    $severity = $conn->real_escape_string($_POST['severity'] ?? 'Medium');
    $description = $conn->real_escape_string($_POST['description'] ?? '');

    $incident_number = 'SEC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    $sql = "INSERT INTO security_incidents (incident_number, incident_type, location, description, severity, status, reported_by, incident_date)
            VALUES ('$incident_number', '$incident_type', '$location', '$description', '$severity', 'Reported', $user_id, NOW())";
    $conn->query($sql);
}

header("Location: $referrer");
exit;
