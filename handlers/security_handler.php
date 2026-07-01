<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['security officer','security','admin']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? '';

$action = $_POST['action'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? '../dashboards/security.php';

if (!$conn) {
    $_SESSION['error'] = 'Database connection failed.';
    header("Location: $referrer");
    exit;
}

switch ($action) {

    // ─── INCIDENTS ───
    case 'report_incident':
    case 'add_incident':
        $incident_type = trim($_POST['incident_type'] ?? '');
        $location      = trim($_POST['location'] ?? '');
        $severity      = trim($_POST['severity'] ?? 'Medium');
        $description   = trim($_POST['description'] ?? '');
        if ($incident_type && $description) {
            $stmt = $conn->prepare("INSERT INTO security_incidents (incident_type, location, description, severity, status, reported_by, reported_by_name, incident_date) VALUES (?, ?, ?, ?, 'Reported', ?, ?, NOW())");
            $stmt->bind_param("ssssis", $incident_type, $location, $description, $severity, $user_id, $user_name);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Incident reported successfully.";
            } else {
                error_log("security_handler add_incident error: " . $conn->error);
                $_SESSION['error'] = "Error reporting incident.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Incident type and description are required.";
        }
        break;

    case 'update_incident':
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $resolution_notes = trim($_POST['resolution_notes'] ?? '');
        if ($id && $status) {
            $resolved_at = ($status === 'Resolved' || $status === 'Closed') ? date('Y-m-d H:i:s') : null;
            if ($resolved_at) {
                $stmt = $conn->prepare("UPDATE security_incidents SET status=?, resolution_notes=?, resolved_at=? WHERE id=?");
                $stmt->bind_param("sssi", $status, $resolution_notes, $resolved_at, $id);
            } else {
                $stmt = $conn->prepare("UPDATE security_incidents SET status=?, resolution_notes=? WHERE id=?");
                $stmt->bind_param("ssi", $status, $resolution_notes, $id);
            }
            if ($stmt->execute()) {
                $_SESSION['success'] = "Incident #$id updated.";
            } else {
                error_log("security_handler update_incident error: " . $conn->error);
                $_SESSION['error'] = "Error updating incident.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Invalid incident data.";
        }
        break;

    case 'delete_incident':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM security_incidents WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Incident #$id deleted.";
            } else {
                error_log("security_handler delete_incident error: " . $conn->error);
                $_SESSION['error'] = "Error deleting incident.";
            }
            $stmt->close();
        }
        break;

    // ─── PATROLS ───
    case 'add_patrol':
        $officer_id   = (int)($_POST['officer_id'] ?? $user_id);
        $officer_name = trim($_POST['officer_name'] ?? $user_name);
        $patrol_area  = trim($_POST['patrol_area'] ?? '');
        $start_time   = trim($_POST['start_time'] ?? date('Y-m-d H:i:s'));
        $findings     = trim($_POST['findings'] ?? '');
        $status       = trim($_POST['status'] ?? 'Scheduled');
        if ($patrol_area) {
            $stmt = $conn->prepare("INSERT INTO security_patrols (officer_id, officer_name, patrol_area, start_time, findings, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $officer_id, $officer_name, $patrol_area, $start_time, $findings, $status);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Patrol record added.";
            } else {
                error_log("security_handler add_patrol error: " . $conn->error);
                $_SESSION['error'] = "Error adding patrol.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Patrol area is required.";
        }
        break;

    case 'update_patrol':
        $id         = (int)($_POST['id'] ?? 0);
        $end_time   = trim($_POST['end_time'] ?? '');
        $findings   = trim($_POST['findings'] ?? '');
        $status     = trim($_POST['status'] ?? '');
        if ($id) {
            $stmt = $conn->prepare("UPDATE security_patrols SET end_time=?, findings=?, status=? WHERE id=?");
            $stmt->bind_param("sssi", $end_time, $findings, $status, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Patrol #$id updated.";
            } else {
                error_log("security_handler update_patrol error: " . $conn->error);
                $_SESSION['error'] = "Error updating patrol.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Invalid patrol ID.";
        }
        break;

    // ─── VISITORS ───
    case 'add_visitor':
        $visitor_name     = trim($_POST['visitor_name'] ?? '');
        $id_number        = trim($_POST['id_number'] ?? '');
        $phone            = trim($_POST['phone'] ?? '');
        $purpose          = trim($_POST['purpose'] ?? '');
        $person_to_visit  = trim($_POST['person_to_visit'] ?? '');
        $check_in_time    = trim($_POST['check_in_time'] ?? date('Y-m-d H:i:s'));
        if ($visitor_name && $purpose) {
            $stmt = $conn->prepare("INSERT INTO security_visitors (visitor_name, id_number, phone, purpose, person_to_visit, check_in_time, status) VALUES (?, ?, ?, ?, ?, ?, 'Checked In')");
            $stmt->bind_param("ssssss", $visitor_name, $id_number, $phone, $purpose, $person_to_visit, $check_in_time);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Visitor '$visitor_name' checked in.";
            } else {
                error_log("security_handler add_visitor error: " . $conn->error);
                $_SESSION['error'] = "Error registering visitor.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Visitor name and purpose are required.";
        }
        break;

    case 'update_visitor':
        $id           = (int)($_POST['id'] ?? 0);
        $check_out    = trim($_POST['check_out_time'] ?? date('Y-m-d H:i:s'));
        $status       = trim($_POST['status'] ?? 'Checked Out');
        if ($id) {
            $stmt = $conn->prepare("UPDATE security_visitors SET check_out_time=?, status=? WHERE id=?");
            $stmt->bind_param("ssi", $check_out, $status, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Visitor #$id updated.";
            } else {
                error_log("security_handler update_visitor error: " . $conn->error);
                $_SESSION['error'] = "Error updating visitor.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Invalid visitor ID.";
        }
        break;

    default:
        $_SESSION['error'] = "Unknown action.";
        break;
}

header("Location: $referrer");
exit;
