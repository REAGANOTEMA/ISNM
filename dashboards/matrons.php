<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['matron']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_email = $user['email'] ?? '';
$user_name = $user['full_name'] ?? '';

$profileImageUrl = '../images/username.png';
$profileSettingsFile = __DIR__ . '/../includes/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    include_once $profileSettingsFile;
    if (function_exists('getStaffProfileImageUrl')) {
        $url = getStaffProfileImageUrl($user_id);
        if ($url) $profileImageUrl = $url;
    }
}

$flash_success = $_SESSION['success'] ?? null;
$flash_error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $post_action = $_POST['action'] ?? '';
    $redirect_url = 'matrons.php?page=welfare';

    switch ($post_action) {
        case 'add_welfare_case':
            $student_id = (int)($_POST['student_id'] ?? 0);
            $student_name = trim($_POST['student_name'] ?? '');
            $case_type = trim($_POST['case_type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $priority = trim($_POST['priority'] ?? 'Medium');

            if ($student_id && $case_type) {
                $check = $conn->prepare("SELECT id, full_name FROM students WHERE id = ?");
                if ($check) {
                    $check->bind_param("i", $student_id);
                    $check->execute();
                    $result = $check->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $student_name = $row['full_name'];
                    } else {
                        $_SESSION['error'] = "Student ID $student_id not found.";
                        header("Location: $redirect_url");
                        exit;
                    }
                    $check->close();
                }

                $stmt = $conn->prepare("INSERT INTO welfare_cases (student_id, student_name, case_type, description, reported_by, reported_by_name, assigned_to, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Open')");
                if ($stmt) {
                    $stmt->bind_param("issssiis", $student_id, $student_name, $case_type, $description, $user_id, $user_name, $user_id, $priority);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Welfare case created successfully.";
                    } else {
                        $_SESSION['error'] = "Error creating welfare case.";
                    }
                    $stmt->close();
                }
            } else {
                $_SESSION['error'] = "Please fill in all required fields.";
            }
            header("Location: $redirect_url");
            exit;

        case 'update_welfare_case':
            $case_id = (int)($_POST['case_id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            $resolution_notes = trim($_POST['resolution_notes'] ?? '');
            $priority = trim($_POST['priority'] ?? '');

            if ($case_id && $status) {
                $sets = ["status = ?"];
                $params = [$status];
                $types = "s";

                if ($priority !== '') {
                    $sets[] = "priority = ?";
                    $params[] = $priority;
                    $types .= "s";
                }
                if ($resolution_notes !== '') {
                    $sets[] = "resolution_notes = ?";
                    $params[] = $resolution_notes;
                    $types .= "s";
                }
                if ($status === 'Resolved' || $status === 'Closed') {
                    $sets[] = "resolved_at = NOW()";
                }

                $sql = "UPDATE welfare_cases SET " . implode(', ', $sets) . " WHERE id = ?";
                $params[] = $case_id;
                $types .= "i";

                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param($types, ...$params);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Welfare case #$case_id updated.";
                    } else {
                        $_SESSION['error'] = "Error updating welfare case.";
                    }
                    $stmt->close();
                }
            } else {
                $_SESSION['error'] = "Invalid case data.";
            }
            header("Location: $redirect_url");
            exit;

        case 'delete_welfare_case':
            $case_id = (int)($_POST['case_id'] ?? 0);
            if ($case_id) {
                $stmt = $conn->prepare("DELETE FROM welfare_actions WHERE case_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $case_id);
                    $stmt->execute();
                    $stmt->close();
                }
                $stmt = $conn->prepare("DELETE FROM welfare_cases WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $case_id);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Welfare case #$case_id deleted.";
                    } else {
                        $_SESSION['error'] = "Error deleting welfare case.";
                    }
                    $stmt->close();
                }
            }
            header("Location: $redirect_url");
            exit;

        case 'add_welfare_action':
            $case_id = (int)($_POST['case_id'] ?? 0);
            $action_type = trim($_POST['action_type'] ?? 'Comment');
            $notes = trim($_POST['notes'] ?? '');

            if ($case_id && $notes) {
                $stmt = $conn->prepare("INSERT INTO welfare_actions (case_id, action_by, action_by_name, action_type, notes) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("iisss", $case_id, $user_id, $user_name, $action_type, $notes);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Action added to case #$case_id.";
                    } else {
                        $_SESSION['error'] = "Error adding action.";
                    }
                    $stmt->close();
                }
            } else {
                $_SESSION['error'] = "Case ID and notes are required.";
            }
            header("Location: $redirect_url");
            exit;

        case 'add_health_incident':
            $student_id = (int)($_POST['student_id'] ?? 0);
            $incident_type = trim($_POST['incident_type'] ?? 'Other');
            $description = trim($_POST['description'] ?? '');
            $actions_taken = trim($_POST['actions_taken'] ?? '');

            if ($student_id) {
                $stmt = $conn->prepare("INSERT INTO student_health_incidents (student_id, incident_type, description, action_taken, recorded_by) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("isssi", $student_id, $incident_type, $description, $actions_taken, $user_id);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Health incident recorded.";
                    } else {
                        $_SESSION['error'] = "Error recording health incident.";
                    }
                    $stmt->close();
                }
            }
            header("Location: matrons.php?page=health");
            exit;

        case 'create_store_requisition':
            $department = trim($_POST['department'] ?? 'Dormitory');
            $urgency = trim($_POST['urgency'] ?? 'medium');
            $notes = trim($_POST['notes'] ?? '');
            $reqItems = $_POST['req_items'] ?? [];
            if (empty($reqItems)) {
                $_SESSION['error'] = "Add at least one item to the request.";
            } else {
                $reqNum = 'MAT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $stmt = $conn->prepare("INSERT INTO store_requests (request_number, requested_by, requester_name, requester_role, department, items, urgency, status, notes, created_at) VALUES (?, ?, ?, 'matron', ?, ?, ?, 'pending', ?, NOW())");
                if ($stmt) {
                    $itemsList = '';
                    foreach ($reqItems as $ri) {
                        $itemName = trim($ri['item_name'] ?? '');
                        if ($itemName) $itemsList .= ($itemsList ? ', ' : '') . $itemName;
                    }
                    $stmt->bind_param("sisssss", $reqNum, $user_id, $user_name, $department, $itemsList, $urgency, $notes);
                    if ($stmt->execute()) {
                        $reqId = $conn->insert_id;
                        $ins = $conn->prepare("INSERT INTO store_request_items (request_id, item_id, quantity_requested, notes) VALUES (?, ?, ?, ?)");
                        if ($ins) {
                            foreach ($reqItems as $ri) {
                                $itemId = (int)($ri['item_id'] ?? 0);
                                $qty = (float)($ri['quantity'] ?? 0);
                                $itemNotes = trim($ri['notes'] ?? '');
                                if ($itemId > 0 && $qty > 0) {
                                    $ins->bind_param("iids", $reqId, $itemId, $qty, $itemNotes);
                                    $ins->execute();
                                }
                            }
                            $ins->close();
                        }
                        $_SESSION['success'] = "Request <strong>$reqNum</strong> created and submitted for approval.";
                    } else {
                        $_SESSION['error'] = "Failed to create request.";
                    }
                    $stmt->close();
                }
            }
            header("Location: matrons.php?page=store_requisition");
            exit;
    }
}

$students_db = $ctx['students'];
$total_students = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM students")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_staff = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM staff")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$recent_applications = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM student_admissions")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$active_programs = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM academic_programs")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$assigned_students = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM hostel_allocations WHERE status = 'Active'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$welfare_cases_count = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM welfare_cases WHERE status NOT IN ('Resolved','Closed')")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$counseling_sessions = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM student_counseling_sessions WHERE session_date = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$health_incidents = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM student_health_incidents WHERE resolved = 0")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;

$welfare_stats = ['total' => 0, 'open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
if ($conn) {
    try {
        $result = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_c, SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as ip_c, SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved_c, SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_c FROM welfare_cases");
        if ($result && $row = $result->fetch_assoc()) {
            $welfare_stats['total'] = (int)($row['total'] ?? 0);
            $welfare_stats['open'] = (int)($row['open_c'] ?? 0);
            $welfare_stats['in_progress'] = (int)($row['ip_c'] ?? 0);
            $welfare_stats['resolved'] = (int)($row['resolved_c'] ?? 0);
            $welfare_stats['closed'] = (int)($row['closed_c'] ?? 0);
        }
    } catch (Exception $e) {}
}

$cases = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT * FROM welfare_cases ORDER BY FIELD(priority, 'Urgent', 'High', 'Medium', 'Low'), id DESC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cases[] = $row;
            }
        }
    } catch (Exception $e) {}
}

$actions_by_case = [];
if ($conn && !empty($cases)) {
    $case_ids = array_column($cases, 'id');
    if (!empty($case_ids)) {
        $placeholders = implode(',', array_fill(0, count($case_ids), '?'));
        $stmt = $conn->prepare("SELECT * FROM welfare_actions WHERE case_id IN ($placeholders) ORDER BY id DESC");
        if ($stmt) {
            $types = str_repeat('i', count($case_ids));
            $stmt->bind_param($types, ...$case_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $actions_by_case[$row['case_id']][] = $row;
            }
            $stmt->close();
        }
    }
}

$recent_activities = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_activities[] = $row;
            }
        }
    } catch (Exception $e) {}
}

// Store data for requisitions
$storeInventory = [];
$myRequests = [];
if ($conn) {
    $r = $conn->query("SELECT si.id, si.item_name, si.item_code, si.unit, si.quantity, sc.category_name FROM store_inventory si LEFT JOIN store_categories sc ON si.category_id=sc.id WHERE si.status='active' ORDER BY sc.category_name, si.item_name");
    if ($r) while ($row = $r->fetch_assoc()) $storeInventory[] = $row;
    $r2 = $conn->query("SELECT sr.*, (SELECT COUNT(*) FROM store_request_items WHERE request_id=sr.id) as item_count FROM store_requests sr WHERE sr.requested_by=$user_id ORDER BY sr.created_at DESC LIMIT 20");
    if ($r2) while ($row = $r2->fetch_assoc()) $myRequests[] = $row;
}

$pageToSection = [
    'home'           => 'overview',
    'overview'       => 'overview',
    'students'       => 'students',
    'counseling'     => 'counseling',
    'health'         => 'health',
    'health-records' => 'health',
    'accommodation'  => 'accommodation',
    'hostel'         => 'accommodation',
    'discipline'     => 'discipline',
    'activities'     => 'activities',
    'meals'          => 'overview',
    'sickbay'        => 'health',
    'welfare'        => 'students',
    'store_requisition' => 'store',
];
$requestedPage = $_GET['page'] ?? 'home';
$section = $pageToSection[$requestedPage] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>.mat-content{margin-left:270px;padding:24px;min-height:100vh}@media(max-width:768px){.mat-content{margin-left:0!important;padding:12px!important}}.welfare-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px}.welfare-stat-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.08);text-align:center}.welfare-stat-card h3{font-size:28px;margin:0 0 4px}.welfare-stat-card p{margin:0;color:#666;font-size:14px}.badge-urgent{background:#dc3545}.badge-high{background:#fd7e14}.badge-medium{background:#0dcaf0}.badge-low{background:#198754}</style>
</head>
<body class="ent-layout">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

    <div class="mat-content">
        <?php if ($flash_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($flash_success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($flash_error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="dashboard-content content-section">
            <section id="overview" class="content-section dashboard-section<?= $section === 'overview' ? ' active' : '' ?>" data-section="overview">
                <h2>Student Welfare Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-content"><h3><?php echo $assigned_students; ?></h3><p>Assigned Students</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-user-injured"></i></div><div class="stat-content"><h3><?php echo $welfare_cases_count; ?></h3><p>Open Welfare Cases</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-comments"></i></div><div class="stat-content"><h3><?php echo $counseling_sessions; ?></h3><p>Today's Sessions</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-heartbeat"></i></div><div class="stat-content"><h3><?php echo $health_incidents; ?></h3><p>Pending Health Issues</p></div></div>
                </div>
            </section>

            <section id="students" class="content-section dashboard-section<?= $section === 'students' ? ' active' : '' ?>" data-section="students">
                <h2>Student Welfare Management</h2>

                <div class="welfare-stats-grid">
                    <div class="welfare-stat-card"><h3><?php echo $welfare_stats['total']; ?></h3><p>Total Cases</p></div>
                    <div class="welfare-stat-card"><h3 style="color:#0d6efd"><?php echo $welfare_stats['open']; ?></h3><p>Open</p></div>
                    <div class="welfare-stat-card"><h3 style="color:#ffc107"><?php echo $welfare_stats['in_progress']; ?></h3><p>In Progress</p></div>
                    <div class="welfare-stat-card"><h3 style="color:#198754"><?php echo $welfare_stats['resolved']; ?></h3><p>Resolved</p></div>
                    <div class="welfare-stat-card"><h3 style="color:#6c757d"><?php echo $welfare_stats['closed']; ?></h3><p>Closed</p></div>
                </div>

                <div class="welfare-actions mb-3">
                    <button class="btn btn-success" onclick="openModal('welfareCase')">
                        <i class="fas fa-plus"></i> Add Welfare Case
                    </button>
                    <button class="btn btn-info" onclick="openModal('healthIncident')">
                        <i class="fas fa-heartbeat"></i> Record Health Incident
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Reported By</th>
                                <th>Resolution</th>
                                <th style="width:140px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($cases)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No welfare cases found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($cases as $case):
                                $cid = $case['id'];
                                $prio = $case['priority'] ?? 'Medium';
                                $stat = $case['status'] ?? 'Open';
                            ?>
                            <tr>
                                <td><?php echo $cid; ?></td>
                                <td><strong><?php echo htmlspecialchars($case['student_name'] ?? ''); ?></strong><br><small class="text-muted">ID: <?php echo $case['student_id'] ?? ''; ?></small></td>
                                <td><?php echo htmlspecialchars($case['case_type'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    $pcls = 'secondary';
                                    if ($prio === 'Urgent') $pcls = 'danger';
                                    elseif ($prio === 'High') $pcls = 'warning';
                                    elseif ($prio === 'Medium') $pcls = 'info';
                                    elseif ($prio === 'Low') $pcls = 'success';
                                    ?>
                                    <span class="badge bg-<?php echo $pcls; ?>"><?php echo htmlspecialchars($prio); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $scls = 'secondary';
                                    if ($stat === 'Open') $scls = 'primary';
                                    elseif ($stat === 'In Progress') $scls = 'warning';
                                    elseif ($stat === 'Resolved') $scls = 'success';
                                    elseif ($stat === 'Closed') $scls = 'dark';
                                    ?>
                                    <span class="badge bg-<?php echo $scls; ?>"><?php echo htmlspecialchars($stat); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($case['reported_by_name'] ?? ''); ?></td>
                                <td><small><?php echo htmlspecialchars($case['resolution_notes'] ?? '—'); ?></small></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" title="View Details" onclick="viewCase(<?php echo $cid; ?>)"><i class="fas fa-eye"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-warning me-1" title="Edit" onclick="editCase(<?php echo $cid; ?>)"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this case and all its actions?')">
                                        <input type="hidden" name="action" value="delete_welfare_case">
                                        <input type="hidden" name="case_id" value="<?php echo $cid; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="counseling" class="content-section dashboard-section<?= $section === 'counseling' ? ' active' : '' ?>" data-section="counseling">
                <h2>Counseling Services</h2>
                <div class="counseling-actions">
                    <button class="btn btn-primary" onclick="openModal('scheduleSession')"><i class="fas fa-calendar-plus"></i> Schedule Session</button>
                    <button class="btn btn-success" onclick="openModal('counselingRecord')"><i class="fas fa-file-medical"></i> Counseling Record</button>
                    <button class="btn btn-info" onclick="openModal('groupCounseling')"><i class="fas fa-users"></i> Group Counseling</button>
                    <button class="btn btn-warning" onclick="openModal('referral')"><i class="fas fa-share"></i> Referral Services</button>
                </div>
                <div class="counseling-overview">
                    <h3>Today's Counseling Schedule</h3>
                    <div class="counseling-schedule">
                        <div class="session-item">
                            <div class="session-header"><h4>Individual Counseling, Mary Student</h4><span class="session-time">10:00 AM to 11:00 AM</span></div>
                            <div class="session-details">
                                <div class="detail"><span>Topic:</span><strong>Homesickness & Adjustment</strong></div>
                                <div class="detail"><span>Type:</span><strong>Individual Session</strong></div>
                                <div class="detail"><span>Location:</span><strong>Counseling Room A</strong></div>
                            </div>
                            <div class="session-actions"><button class="btn btn-sm btn-outline-primary">Start Session</button><button class="btn btn-sm btn-outline-info">Reschedule</button></div>
                        </div>
                        <div class="session-item">
                            <div class="session-header"><h4>Group Counseling, First Year Students</h4><span class="session-time">2:00 PM to 3:30 PM</span></div>
                            <div class="session-details">
                                <div class="detail"><span>Topic:</span><strong>Academic Stress Management</strong></div>
                                <div class="detail"><span>Type:</span><strong>Group Session</strong></div>
                                <div class="detail"><span>Location:</span><strong>Main Hall</strong></div>
                            </div>
                            <div class="session-actions"><button class="btn btn-sm btn-outline-primary">Start Session</button><button class="btn btn-sm btn-outline-info">View Participants</button></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="health" class="content-section dashboard-section<?= $section === 'health' ? ' active' : '' ?>" data-section="health">
                <h2>Health Services</h2>
                <div class="health-actions">
                    <button class="btn btn-primary" onclick="openModal('healthCheck')"><i class="fas fa-stethoscope"></i> Health Check</button>
                    <button class="btn btn-success" onclick="openModal('medicalRecord')"><i class="fas fa-file-medical"></i> Medical Record</button>
                    <button class="btn btn-info" onclick="openModal('medication')"><i class="fas fa-pills"></i> Medication Management</button>
                    <button class="btn btn-warning" onclick="openModal('emergency')"><i class="fas fa-ambulance"></i> Emergency Response</button>
                </div>
                <div class="health-overview">
                    <h3>Recent Health Incidents</h3>
                    <div class="health-incidents">
                        <div class="incident-card">
                            <div class="incident-header"><h4>Student Sarah, Fever</h4><span class="incident-date">Apr 22, 2026</span></div>
                            <div class="incident-details">
                                <div class="detail"><span>Symptoms:</span><strong>Fever, headache, fatigue</strong></div>
                                <div class="detail"><span>Action:</span><strong>Referred to school clinic</strong></div>
                                <div class="detail"><span>Status:</span><strong class="text-warning">Under Observation</strong></div>
                            </div>
                            <div class="incident-actions"><button class="btn btn-sm btn-outline-primary">View Details</button><button class="btn btn-sm btn-outline-success">Update Status</button></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="accommodation" class="content-section dashboard-section<?= $section === 'accommodation' ? ' active' : '' ?>" data-section="accommodation">
                <h2>Accommodation Management</h2>
                <div class="accommodation-actions">
                    <button class="btn btn-primary" onclick="openModal('roomAssignment')"><i class="fas fa-bed"></i> Room Assignment</button>
                    <button class="btn btn-success" onclick="openModal('roomInspection')"><i class="fas fa-clipboard-check"></i> Room Inspection</button>
                    <button class="btn btn-info" onclick="openModal('maintenanceRequest')"><i class="fas fa-tools"></i> Maintenance Request</button>
                    <button class="btn btn-warning" onclick="openModal('accommodationReport')"><i class="fas fa-chart-bar"></i> Accommodation Report</button>
                </div>
                <div class="accommodation-overview">
                    <h3>Hostel Overview</h3>
                    <div class="hostel-stats">
                        <div class="hostel-stat"><h4>Girls Hostel A</h4><div class="occupancy">45/50 beds occupied</div><small>90% occupancy</small></div>
                        <div class="hostel-stat"><h4>Girls Hostel B</h4><div class="occupancy">38/40 beds occupied</div><small>95% occupancy</small></div>
                        <div class="hostel-stat"><h4>Maintenance Issues</h4><div class="issues-count">3 pending</div><small>Requires attention</small></div>
                        <div class="hostel-stat"><h4>Room Inspections</h4><div class="inspection-rate">85%</div><small>Completed this month</small></div>
                    </div>
                </div>
            </section>

            <section id="discipline" class="content-section dashboard-section<?= $section === 'discipline' ? ' active' : '' ?>" data-section="discipline">
                <h2>Student Discipline</h2>
                <div class="discipline-actions">
                    <button class="btn btn-primary" onclick="openModal('disciplineCase')"><i class="fas fa-gavel"></i> Discipline Case</button>
                    <button class="btn btn-success" onclick="openModal('disciplinaryAction')"><i class="fas fa-exclamation-triangle"></i> Disciplinary Action</button>
                    <button class="btn btn-info" onclick="openModal('behaviorReport')"><i class="fas fa-chart-line"></i> Behavior Report</button>
                    <button class="btn btn-warning" onclick="openModal('parentMeeting')"><i class="fas fa-users"></i> Parent Meeting</button>
                </div>
                <div class="discipline-overview">
                    <h3>Recent Discipline Cases</h3>
                    <div class="discipline-cases">
                        <div class="discipline-item">
                            <div class="discipline-header"><h4>Student Peter, Late Night Return</h4><span class="discipline-date">Apr 21, 2026</span></div>
                            <div class="discipline-details">
                                <div class="detail"><span>Incident:</span><strong>Returned after 10:30 PM</strong></div>
                                <div class="detail"><span>Action:</span><strong>Warning issued, parents notified</strong></div>
                                <div class="detail"><span>Status:</span><strong class="text-success">Resolved</strong></div>
                            </div>
                            <div class="discipline-actions"><button class="btn btn-sm btn-outline-primary">View Details</button><button class="btn btn-sm btn-outline-info">Follow Up</button></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="activities" class="content-section dashboard-section<?= $section === 'activities' ? ' active' : '' ?>" data-section="activities">
                <h2>Student Activities</h2>
                <div class="activity-actions">
                    <button class="btn btn-primary" onclick="openModal('organizeActivity')"><i class="fas fa-calendar-plus"></i> Organize Activity</button>
                    <button class="btn btn-success" onclick="openModal('activitySchedule')"><i class="fas fa-calendar"></i> Activity Schedule</button>
                    <button class="btn btn-info" onclick="openModal('participation')"><i class="fas fa-users"></i> Student Participation</button>
                    <button class="btn btn-warning" onclick="openModal('activityReport')"><i class="fas fa-chart-bar"></i> Activity Report</button>
                </div>
                <div class="activities-overview">
                    <h3>Upcoming Activities</h3>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-header"><h4>Girls' Empowerment Workshop</h4><span class="activity-date">Apr 25, 2026</span></div>
                            <div class="activity-details">
                                <div class="detail"><span>Type:</span><strong>Workshop</strong></div>
                                <div class="detail"><span>Participants:</span><strong>50 registered</strong></div>
                                <div class="detail"><span>Location:</span><strong>Main Hall</strong></div>
                            </div>
                            <div class="activity-actions"><button class="btn btn-sm btn-outline-primary">View Details</button><button class="btn btn-sm btn-outline-info">Manage Registration</button></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="activities-section">
                <h2>Recent Welfare Activities</h2>
                <div class="activities-list">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon"><i class="fas fa-<?php echo $activity['icon'] ?? 'check-circle'; ?>"></i></div>
                        <div class="activity-content">
                            <p><strong><?php echo $activity['action'] ?? $activity['activity'] ?? 'Activity'; ?></strong></p>
                            <small><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Store Requisition Section -->
            <section id="store" class="content-section dashboard-section<?= $section === 'store' ? ' active' : '' ?>" data-section="store">
                <h2><i class="fas fa-shopping-cart me-2"></i>Store Requisition</h2>
                <p style="color:#6b7280;margin-bottom:20px">Request cleaning and hygiene items from the store</p>

                <div style="display:flex;justify-content:flex-end;margin-bottom:20px">
                    <button class="btn btn-success" onclick="document.getElementById('newReqModal').style.display='flex'">
                        <i class="fas fa-plus"></i> New Requisition
                    </button>
                </div>

                <?php if (empty($myRequests)): ?>
                <div class="card"><div class="card-body"><div class="text-center py-4 text-muted"><i class="fas fa-clipboard-list fa-2x mb-2"></i><p>No requisitions yet</p></div></div></div>
                <?php else: ?>
                <?php foreach ($myRequests as $req):
                    $urgBadge = $req['urgency'] === 'urgent' ? 'badge-danger' : ($req['urgency'] === 'high' ? 'badge-warning' : ($req['urgency'] === 'medium' ? 'badge-info' : 'badge-secondary'));
                    $statusBadge = $req['status'] === 'pending' ? 'badge-warning' : ($req['status'] === 'approved' ? 'badge-success' : ($req['status'] === 'fulfilled' ? 'badge-success' : ($req['status'] === 'rejected' ? 'badge-danger' : 'badge-secondary')));
                ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($req['request_number']) ?></strong>
                            <span class="badge <?= $urgBadge ?>" style="margin-left:8px"><?= $req['urgency'] ?></span>
                            <span class="badge <?= $statusBadge ?>" style="margin-left:4px"><?= ucfirst($req['status']) ?></span>
                        </div>
                        <small class="text-muted"><?= date('d M Y H:i', strtotime($req['created_at'])) ?></small>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Department:</strong> <?= htmlspecialchars($req['department']) ?></p>
                        <p class="mb-1"><strong>Items:</strong> <?= htmlspecialchars($req['items'] ?? '') ?></p>
                        <?php if ($req['notes']): ?><p class="mb-0 text-muted"><small><?= htmlspecialchars($req['notes']) ?></small></p><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- New Request Modal -->
                <div id="newReqModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
                    <div style="background:#fff;border-radius:12px;width:90%;max-width:700px;max-height:90vh;overflow-y:auto">
                        <form method="POST">
                        <div style="padding:18px 24px;background:#10b981;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
                            <h5 style="margin:0"><i class="fas fa-plus me-2"></i>New Store Requisition</h5>
                            <button type="button" style="background:none;border:none;color:#fff;font-size:1.2rem" onclick="this.closest('#newReqModal').style.display='none'">&times;</button>
                        </div>
                        <div style="padding:24px">
                            <input type="hidden" name="action" value="create_store_requisition">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px">Department</label><input type="text" name="department" class="form-control" value="Dormitory" required></div>
                                <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px">Urgency</label><select name="urgency" class="form-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
                            </div>
                            <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px">Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea></div>
                            <label class="form-label fw-semibold" style="font-size:13px">Request Items</label>
                            <div id="reqItemsContainer">
                                <div class="d-flex gap-2 mb-2 req-item-row align-items-center">
                                    <select name="req_items[0][item_id]" class="form-select" style="flex:2" required>
                                        <option value="">-- Select Item --</option>
                                        <?php foreach ($storeInventory as $item): ?>
                                        <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['item_name']) ?> (<?= number_format($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" name="req_items[0][quantity]" class="form-control" style="width:80px" placeholder="Qty" min="1" required>
                                    <input type="text" name="req_items[0][item_name]" class="form-control" style="flex:1" placeholder="Item name">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.req-item-row').remove()"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addMatronReqItem()"><i class="fas fa-plus me-1"></i>Add Item</button>
                        </div>
                        <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
                            <button type="button" class="btn btn-secondary" onclick="this.closest('#newReqModal').style.display='none'">Cancel</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Submit Request</button>
                        </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modalAction">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="caseDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Case Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="caseDetailBody"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCaseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Welfare Case</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_welfare_case">
                        <input type="hidden" name="case_id" id="edit_case_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="Open">Open</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority" id="edit_priority">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resolution Notes</label>
                            <textarea class="form-control" name="resolution_notes" id="edit_resolution" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Case</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    const allCases = <?php echo json_encode($cases); ?>;
    const allActions = <?php echo json_encode($actions_by_case); ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const el = document.getElementById('currentDate');
            if (el) el.textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        document.querySelectorAll('.dashboard-sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.dashboard-sidebar .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                const targetId = this.getAttribute('href').substring(1);
                document.querySelectorAll('.content-section').forEach(s => s.style.display = 'none');
                const t = document.getElementById(targetId);
                if (t) t.style.display = 'block';
            });
        });

        function openModal(action) {
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const title = document.getElementById('modalTitle');
            const body = document.getElementById('modalBody');

            switch (action) {
                case 'welfareCase':
                    title.textContent = 'Add Welfare Case';
                    body.innerHTML = `
                        <form id="addCaseForm" method="POST">
                            <input type="hidden" name="action" value="add_welfare_case">
                            <div class="mb-3">
                                <label class="form-label">Student ID *</label>
                                <input type="number" class="form-control" name="student_id" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Case Type *</label>
                                <select class="form-select" name="case_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Academic Support">Academic Support</option>
                                    <option value="Personal Counseling">Personal Counseling</option>
                                    <option value="Financial Support">Financial Support</option>
                                    <option value="Health Issues">Health Issues</option>
                                    <option value="Disciplinary Issues">Disciplinary Issues</option>
                                    <option value="Homesickness">Homesickness</option>
                                    <option value="Family Problems">Family Problems</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority">
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Welfare Case</button>
                        </form>`;
                    document.getElementById('modalAction').style.display = 'none';
                    break;

                case 'healthIncident':
                    title.textContent = 'Record Health Incident';
                    body.innerHTML = `
                        <form id="healthIncidentForm" method="POST">
                            <input type="hidden" name="action" value="add_health_incident">
                            <div class="mb-3">
                                <label class="form-label">Student ID *</label>
                                <input type="number" class="form-control" name="student_id" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Incident Type *</label>
                                <select class="form-select" name="incident_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Injury">Injury</option>
                                    <option value="Illness">Illness</option>
                                    <option value="Allergic Reaction">Allergic Reaction</option>
                                    <option value="Mental Health">Mental Health</option>
                                    <option value="Emergency">Emergency</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Actions Taken *</label>
                                <textarea class="form-control" name="actions_taken" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Submit Health Incident</button>
                        </form>`;
                    document.getElementById('modalAction').style.display = 'none';
                    break;

                case 'scheduleSession':
                    title.textContent = 'Schedule Counseling Session';
                    body.innerHTML = `
                        <form id="sessionForm" method="POST" action="../handlers/welfare_handler.php">
                            <input type="hidden" name="action" value="schedule_session">
                            <div class="mb-3">
                                <label class="form-label">Session Type</label>
                                <select class="form-select" name="session_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Individual">Individual Counseling</option>
                                    <option value="Group">Group Counseling</option>
                                    <option value="Family">Family Counseling</option>
                                    <option value="Crisis">Crisis Intervention</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="number" class="form-control" name="student_id" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="session_date" required></div></div>
                                <div class="col-md-6"><div class="mb-3"><label class="form-label">Time</label><input type="time" class="form-control" name="session_time" required></div></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Issues Discussed</label>
                                <textarea class="form-control" name="issues_discussed" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Schedule Session</button>
                        </form>`;
                    document.getElementById('modalAction').style.display = 'none';
                    break;

                case 'healthCheck':
                    title.textContent = 'Student Health Check';
                    body.innerHTML = `
                        <form id="healthCheckForm" method="POST">
                            <input type="hidden" name="action" value="add_health_incident">
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="number" class="form-control" name="student_id" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Chief Complaint</label>
                                <textarea class="form-control" name="description" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assessment & Plan</label>
                                <textarea class="form-control" name="actions_taken" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Action Required</label>
                                <select class="form-select" name="incident_type" required>
                                    <option value="">Select Action</option>
                                    <option value="Injury">Injury</option>
                                    <option value="Illness">Illness</option>
                                    <option value="Allergic Reaction">Allergic Reaction</option>
                                    <option value="Mental Health">Mental Health</option>
                                    <option value="Emergency">Emergency</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Submit Health Record</button>
                        </form>`;
                    document.getElementById('modalAction').style.display = 'none';
                    break;
            }
            modal.show();
        }

        function viewCase(caseId) {
            const c = allCases.find(x => x.id == caseId);
            if (!c) return;
            const actions = allActions[caseId] || [];
            const pMap = {Urgent:'danger',High:'warning',Medium:'info',Low:'success'};
            const sMap = {Open:'primary','In Progress':'warning',Resolved:'success',Closed:'dark'};

            let actionsHtml = '';
            if (actions.length > 0) {
                actionsHtml = '<table class="table table-sm mt-3"><thead><tr><th>Type</th><th>By</th><th>Notes</th><th>ID</th></tr></thead><tbody>';
                actions.forEach(a => {
                    actionsHtml += `<tr><td><span class="badge bg-secondary">${esc(a.action_type)}</span></td><td>${esc(a.action_by_name)}</td><td>${esc(a.notes)}</td><td>#${a.id}</td></tr>`;
                });
                actionsHtml += '</tbody></table>';
            } else {
                actionsHtml = '<p class="text-muted mt-3">No actions recorded yet.</p>';
            }

            document.getElementById('caseDetailBody').innerHTML = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Student</h6><p>${esc(c.student_name)} (ID: ${c.student_id})</p>
                        <h6>Case Type</h6><p>${esc(c.case_type)}</p>
                        <h6>Reported By</h6><p>${esc(c.reported_by_name)}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Priority</h6><p><span class="badge bg-${pMap[c.priority]||'secondary'}">${esc(c.priority)}</span></p>
                        <h6>Status</h6><p><span class="badge bg-${sMap[c.status]||'secondary'}">${esc(c.status)}</span></p>
                        <h6>Resolution</h6><p>${esc(c.resolution_notes) || '<em>None</em>'}</p>
                        ${c.resolved_at ? '<h6>Resolved At</h6><p>'+esc(c.resolved_at)+'</p>' : ''}
                    </div>
                </div>
                <div class="col-12"><h6>Description</h6><p>${esc(c.description) || '<em>No description</em>'}</p></div>
                <hr>
                <h5>Actions / Comments</h5>
                ${actionsHtml}
                <hr>
                <h5>Add Action</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add_welfare_action">
                    <input type="hidden" name="case_id" value="${c.id}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Action Type</label>
                            <select class="form-select" name="action_type">
                                <option value="Comment">Comment</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Counseling">Counseling</option>
                                <option value="Parent Contact">Parent Contact</option>
                                <option value="Referral">Referral</option>
                                <option value="Resolution">Resolution</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Notes *</label>
                            <textarea class="form-control" name="notes" rows="3" required></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Add Action</button>
                </form>`;

            new bootstrap.Modal(document.getElementById('caseDetailModal')).show();
        }

        function editCase(caseId) {
            const c = allCases.find(x => x.id == caseId);
            if (!c) return;
            document.getElementById('edit_case_id').value = c.id;
            document.getElementById('edit_status').value = c.status || 'Open';
            document.getElementById('edit_priority').value = c.priority || 'Medium';
            document.getElementById('edit_resolution').value = c.resolution_notes || '';
            new bootstrap.Modal(document.getElementById('editCaseModal')).show();
        }

        function esc(s) {
            if (!s) return '';
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        document.getElementById('modalAction')?.addEventListener('click', function() {
            const form = document.querySelector('#modalBody form');
            if (form) form.submit();
        });

        let matronReqIdx = 1;
        function addMatronReqItem() {
            let options = '<option value="">-- Select Item --</option>';
            <?php foreach ($storeInventory as $item): ?>
            options += '<option value="<?= $item['id'] ?>"><?= addslashes(htmlspecialchars($item['item_name'])) ?> (<?= number_format($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?>)</option>';
            <?php endforeach; ?>
            let html = '<div class="d-flex gap-2 mb-2 req-item-row align-items-center">' +
                '<select name="req_items[' + matronReqIdx + '][item_id]" class="form-select" style="flex:2" required onchange="this.closest(\'.req-item-row\').querySelector(\'input[name*=item_name]\').value=this.options[this.selectedIndex].text.split(\'(\')[0].trim()">' + options + '</select>' +
                '<input type="number" name="req_items[' + matronReqIdx + '][quantity]" class="form-control" style="width:80px" placeholder="Qty" min="1" required>' +
                '<input type="text" name="req_items[' + matronReqIdx + '][item_name]" class="form-control" style="flex:1" placeholder="Item name">' +
                '<button type="button" class="btn btn-danger btn-sm" onclick="this.closest(\'.req-item-row\').remove()"><i class="fas fa-times"></i></button></div>';
            document.getElementById('reqItemsContainer').insertAdjacentHTML('beforeend', html);
            matronReqIdx++;
        }
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
