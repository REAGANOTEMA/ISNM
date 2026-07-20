<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['security officer','security','admin']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? '';

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
$isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest');
$referrer = $_SERVER['HTTP_REFERER'] ?? '../dashboards/security.php';
$allowedHost = $_SERVER['SERVER_NAME'] ?? '';
if (!empty($allowedHost) && isset(parse_url($referrer)['host']) && parse_url($referrer)['host'] !== $allowedHost) {
    $referrer = '../dashboards/security.php';
}

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
        $description   = trim($_POST['description'] ?? '');
        if ($incident_type && $description) {
            $stmt = $conn->prepare("INSERT INTO security_incidents (incident_type, location, description, status, reported_by) VALUES (?, ?, ?, 'Reported', ?)");
            $stmt->bind_param("sssi", $incident_type, $location, $description, $user_id);
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
        if ($id && $status) {
            $resolved_at = ($status === 'Resolved' || $status === 'Closed') ? date('Y-m-d H:i:s') : null;
            if ($resolved_at) {
                $stmt = $conn->prepare("UPDATE security_incidents SET status=?, resolved_at=? WHERE id=?");
                $stmt->bind_param("ssi", $status, $resolved_at, $id);
            } else {
                $stmt = $conn->prepare("UPDATE security_incidents SET status=? WHERE id=?");
                $stmt->bind_param("si", $status, $id);
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
        $patrol_area  = trim($_POST['patrol_area'] ?? '');
        $start_time   = trim($_POST['start_time'] ?? date('Y-m-d H:i:s'));
        $findings     = trim($_POST['findings'] ?? '');
        $status       = trim($_POST['status'] ?? 'Scheduled');
        if ($patrol_area) {
            $parts       = explode('T', $start_time);
            $patrol_date = $parts[0] ?? date('Y-m-d');
            $s_time      = $parts[1] ?? (str_contains($start_time, ' ') ? explode(' ', $start_time)[1] ?? date('H:i:s') : date('H:i:s'));
            if (!str_contains($s_time, ':')) { $s_time = date('H:i:s'); }
            $stmt = $conn->prepare("INSERT INTO security_patrols (guard_id, patrol_area, patrol_date, start_time, notes, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $officer_id, $patrol_area, $patrol_date, $s_time, $findings, $status);
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
            $parts2 = explode('T', $end_time);
            $e_time = $parts2[1] ?? (str_contains($end_time, ' ') ? explode(' ', $end_time)[1] ?? null : null);
            if ($e_time) {
                $stmt = $conn->prepare("UPDATE security_patrols SET end_time=?, notes=?, status=? WHERE id=?");
                $stmt->bind_param("sssi", $e_time, $findings, $status, $id);
            } else {
                $stmt = $conn->prepare("UPDATE security_patrols SET notes=?, status=? WHERE id=?");
                $stmt->bind_param("ssi", $findings, $status, $id);
            }
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
        $parts            = explode('T', $check_in_time);
        $visit_date       = $parts[0] ?? date('Y-m-d');
        $arrival_time     = $parts[1] ?? date('H:i:s');
        if (!str_contains($arrival_time, ':')) {
            $arrival_time = date('H:i:s');
        }
        if ($visitor_name && $purpose) {
            $stmt = $conn->prepare("INSERT INTO security_visitors (visitor_name, visitor_phone, visitor_nature, person_to_visit_name, visit_date, actual_arrival, badge_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Checked In')");
            $stmt->bind_param("sssssss", $visitor_name, $phone, $purpose, $person_to_visit, $visit_date, $arrival_time, $id_number);
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
        $parts        = explode('T', $check_out);
        $depart_time  = $parts[1] ?? (str_contains($check_out, ' ') ? explode(' ', $check_out)[1] ?? date('H:i:s') : date('H:i:s'));
        $status       = trim($_POST['status'] ?? 'Checked Out');
        if ($id) {
            $stmt = $conn->prepare("UPDATE security_visitors SET actual_departure=?, status=? WHERE id=?");
            $stmt->bind_param("ssi", $depart_time, $status, $id);
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
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
            exit;
        }
        $_SESSION['error'] = "Unknown action.";
        break;
}

if ($isAjax) {
    header('Content-Type: application/json');
    $successMsg = $_SESSION['success'] ?? '';
    $errorMsg = $_SESSION['error'] ?? '';
    unset($_SESSION['success'], $_SESSION['error']);
    echo json_encode(['success' => !empty($successMsg), 'message' => $successMsg ?: $errorMsg]);
    exit;
}

header("Location: $referrer");
exit;
