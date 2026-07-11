<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['warden']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$students_db_name = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';
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

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_welfare_case') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $case_type = trim($_POST['case_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = trim($_POST['priority'] ?? 'Medium');
        $assigned_to = (int)($_POST['assigned_to'] ?? 0);
        $assigned_to_name = trim($_POST['assigned_to_name'] ?? '');

        $stmt = $conn->prepare("SELECT id, CONCAT(first_name,' ',surname) as full_name FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$student) {
            $flash = 'Student not found.';
            $flashType = 'danger';
        } else {
            $stmt = $conn->prepare("INSERT INTO welfare_cases (student_id, student_name, case_type, description, reported_by, reported_by_name, assigned_to, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Open')");
            $sname = $student['full_name'];
            $stmt->bind_param("isssissi", $student_id, $sname, $case_type, $description, $user_id, $user_name, $assigned_to, $priority);
            if ($stmt->execute()) {
                $flash = 'Welfare case created.';
                $flashType = 'success';
            } else {
                $flash = 'Failed to create case.';
                $flashType = 'danger';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_welfare_case') {
        $case_id = (int)($_POST['case_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Open');
        $resolution_notes = trim($_POST['resolution_notes'] ?? '');

        $sql = "UPDATE welfare_cases SET status = ?";
        $types = "s";
        $params = [$status];

        if ($status === 'Resolved' || $status === 'Closed') {
            $sql .= ", resolution_notes = ?, resolved_at = NOW()";
            $types .= "s";
            $params[] = $resolution_notes;
        }

        $sql .= " WHERE id = ?";
        $types .= "i";
        $params[] = $case_id;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $flash = 'Case updated.';
            $flashType = 'success';
        } else {
            $flash = 'Failed to update case.';
            $flashType = 'danger';
        }
        $stmt->close();
    }

    if ($action === 'delete_welfare_case') {
        $case_id = (int)($_POST['case_id'] ?? 0);
        $conn->query("DELETE FROM welfare_actions WHERE case_id = $case_id");
        $stmt = $conn->prepare("DELETE FROM welfare_cases WHERE id = ?");
        $stmt->bind_param("i", $case_id);
        if ($stmt->execute()) {
            $flash = 'Case deleted.';
            $flashType = 'success';
        } else {
            $flash = 'Failed to delete case.';
            $flashType = 'danger';
        }
        $stmt->close();
    }

    if ($action === 'add_welfare_action') {
        $case_id = (int)($_POST['case_id'] ?? 0);
        $action_type = trim($_POST['action_type'] ?? 'Comment');
        $notes = trim($_POST['notes'] ?? '');

        $stmt = $conn->prepare("INSERT INTO welfare_actions (case_id, action_by, action_by_name, action_type, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $case_id, $user_id, $user_name, $action_type, $notes);
        if ($stmt->execute()) {
            $flash = 'Action added.';
            $flashType = 'success';
        } else {
            $flash = 'Failed to add action.';
            $flashType = 'danger';
        }
        $stmt->close();
    }

    if ($action === 'add_discipline_case') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $incident_type = trim($_POST['incident_type'] ?? '');
        $incident_date = trim($_POST['incident_date'] ?? '');
        $action_taken = trim($_POST['action_taken'] ?? 'Warning');
        $description = trim($_POST['description'] ?? '');

        $stmt = $conn->prepare("SELECT id, CONCAT(first_name,' ',surname) as full_name FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$student) {
            $flash = 'Student not found.';
            $flashType = 'danger';
        } else {
            $stmt = $conn->prepare("INSERT INTO student_discipline (student_id, student_name, incident_type, incident_date, action_taken, description, reported_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $sname = $student['full_name'];
            $stmt->bind_param("isssssi", $student_id, $sname, $incident_type, $incident_date, $action_taken, $description, $user_id);
            if ($stmt->execute()) {
                $flash = 'Discipline case created.';
                $flashType = 'success';
            } else {
                $flash = 'Failed to create discipline case.';
                $flashType = 'danger';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_discipline_case') {
        $case_id = (int)($_POST['case_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM student_discipline WHERE id = ?");
        $stmt->bind_param("i", $case_id);
        if ($stmt->execute()) {
            $flash = 'Discipline case deleted.';
            $flashType = 'success';
        } else {
            $flash = 'Failed to delete discipline case.';
            $flashType = 'danger';
        }
        $stmt->close();
    }

    if ($action === 'create_warden_requisition') {
        $department = trim($_POST['department'] ?? 'Hostel');
        $urgency = trim($_POST['urgency'] ?? 'medium');
        $notes = trim($_POST['notes'] ?? '');
        $reqItems = $_POST['req_items'] ?? [];
        if (empty($reqItems)) {
            $flash = "Add at least one item to the request.";
            $flashType = "danger";
        } else {
            $reqNum = 'WRD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $itemsList = '';
            foreach ($reqItems as $ri) {
                $itemName = trim($ri['item_name'] ?? '');
                if ($itemName) $itemsList .= ($itemsList ? ', ' : '') . $itemName;
            }
            $stmt = $conn->prepare("INSERT INTO store_requests (request_number, requested_by, requester_name, requester_role, department, items, urgency, status, notes, created_at) VALUES (?, ?, ?, 'warden', ?, ?, ?, 'pending', ?, NOW())");
            if ($stmt) {
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
                    $flash = "Request <strong>$reqNum</strong> created and submitted for approval.";
                    $flashType = "success";
                } else {
                    $flash = "Failed to create request.";
                    $flashType = "danger";
                }
                $stmt->close();
            }
        }
    }
}

$students_db = $ctx['students'];
$total_students = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM students")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$active_programs = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM academic_programs")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_staff = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM staff")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$recent_applications = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM student_admissions")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$assigned_students = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM hostel_allocations WHERE status = 'Active'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$welfare_cases_count = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM welfare_cases WHERE status NOT IN ('Resolved','Closed')")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$counseling_sessions = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM student_counseling_sessions WHERE session_date = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$discipline_cases_count = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM student_discipline WHERE status = 'Pending'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;

$all_welfare_cases = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT wc.*, CONCAT(s.first_name,' ',s.surname) as student_name FROM welfare_cases wc LEFT JOIN igangaschool_students.students s ON wc.student_id=s.id ORDER BY wc.created_at DESC");
        if ($r) $all_welfare_cases = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('wardens context: ' . $e->getMessage()); }
}

$welfare_actions_map = [];
if ($conn && !empty($all_welfare_cases)) {
    $case_ids = array_column($all_welfare_cases, 'id');
    $placeholders = implode(',', array_fill(0, count($case_ids), '?'));
    $types = str_repeat('i', count($case_ids));
    $stmt = $conn->prepare("SELECT * FROM welfare_actions WHERE case_id IN ($placeholders) ORDER BY created_at ASC");
    $stmt->bind_param($types, ...$case_ids);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $welfare_actions_map[$row['case_id']][] = $row;
        }
    }
    $stmt->close();
}

$today_counseling = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT cs.*, CONCAT(s.first_name,' ',s.surname) as student_name FROM student_counseling_sessions cs LEFT JOIN igangaschool_students.students s ON cs.student_id=s.id WHERE DATE(cs.session_date)=CURDATE() ORDER BY cs.session_time LIMIT 5");
        if ($r) $today_counseling = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('wardens context: ' . $e->getMessage()); }
}

$all_discipline_cases = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT sd.*, CONCAT(s.first_name,' ',s.surname) as student_name FROM student_discipline sd LEFT JOIN igangaschool_students.students s ON sd.student_id=s.id ORDER BY sd.created_at DESC");
        if ($r) $all_discipline_cases = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('wardens context: ' . $e->getMessage()); }
}

$hostel_stats = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT hr.hostel_name, hr.total_beds, (SELECT COUNT(*) FROM hostel_allocations ha WHERE ha.hostel_room_id=hr.id AND ha.status='Active') as occupied FROM hostel_rooms hr GROUP BY hr.hostel_name");
        if ($r) $hostel_stats = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('wardens context: ' . $e->getMessage()); }
}

$upcoming_activities = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM student_activities WHERE activity_date >= CURDATE() ORDER BY activity_date LIMIT 3");
        if ($r) $upcoming_activities = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('wardens context: ' . $e->getMessage()); }
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
    } catch (Exception $e) { error_log('wardens context: ' . $e->getMessage()); }
}

$staff_list = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) as full_name FROM staff ORDER BY first_name");
        if ($r) $staff_list = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('wardens context: ' . $e->getMessage()); }
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
    'home'          => 'overview',
    'overview'      => 'overview',
    'students'      => 'students',
    'counseling'    => 'counseling',
    'discipline'    => 'discipline',
    'accommodation' => 'accommodation',
    'hostel'        => 'accommodation',
    'activities'    => 'activities',
    'security'      => 'security',
    'welfare'       => 'students',
    'reports'       => 'overview',
    'warden_requisition' => 'store',
];
$requestedPage = $_GET['page'] ?? 'home';
$section = $pageToSection[$requestedPage] ?? 'overview';
$edit_case_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$view_case_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$edit_case = null;
if ($edit_case_id && $conn) {
    $stmt = $conn->prepare("SELECT wc.*, CONCAT(s.first_name,' ',s.surname) as student_name FROM welfare_cases wc LEFT JOIN igangaschool_students.students s ON wc.student_id=s.id WHERE wc.id = ?");
    $stmt->bind_param("i", $edit_case_id);
    $stmt->execute();
    $edit_case = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$view_case = null;
if ($view_case_id && $conn) {
    $stmt = $conn->prepare("SELECT wc.*, CONCAT(s.first_name,' ',s.surname) as student_name FROM welfare_cases wc LEFT JOIN igangaschool_students.students s ON wc.student_id=s.id WHERE wc.id = ?");
    $stmt->bind_param("i", $view_case_id);
    $stmt->execute();
    $view_case = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($view_case) {
        $stmt2 = $conn->prepare("SELECT * FROM welfare_actions WHERE case_id = ? ORDER BY created_at ASC");
        $stmt2->bind_param("i", $view_case_id);
        $stmt2->execute();
        $view_case_actions = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.war-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.war-content{margin-left:0!important;padding:12px!important}}
.table-actions .btn{margin-right:4px}
.case-detail-card{background:#fff;border-radius:8px;padding:20px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.case-detail-card h4{margin-bottom:12px;color:#333}
.action-log{max-height:300px;overflow-y:auto}
.action-log .log-entry{padding:10px;border-left:3px solid #007bff;margin-bottom:8px;background:#f8f9fa;border-radius:4px}
.action-log .log-entry .log-meta{font-size:.8em;color:#666;margin-bottom:4px}
.alert-flash{margin-bottom:20px}
</style>
</head>
<body class="ent-layout">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="war-content">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flashType ?> alert-flash alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($view_case): ?>
    <div class="case-detail-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Case #<?= $view_case['id'] ?> - <?= htmlspecialchars($view_case['student_name'] ?? 'Student') ?></h4>
            <a href="wardens.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="row mb-3">
            <div class="col-md-3"><strong>Case Type:</strong> <?= htmlspecialchars($view_case['case_type'] ?? '-') ?></div>
            <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-<?= ($view_case['status']==='Resolved'?'success':($view_case['status']==='Closed'?'secondary':'warning')) ?>"><?= htmlspecialchars($view_case['status']) ?></span></div>
            <div class="col-md-3"><strong>Priority:</strong> <?= htmlspecialchars($view_case['priority'] ?? '-') ?></div>
            <div class="col-md-3"><strong>Reported:</strong> <?= !empty($view_case['created_at']) ? date('M j, Y', strtotime($view_case['created_at'])) : '-' ?></div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6"><strong>Description:</strong><br><?= nl2br(htmlspecialchars($view_case['description'] ?? '-')) ?></div>
            <div class="col-md-6"><strong>Resolution Notes:</strong><br><?= nl2br(htmlspecialchars($view_case['resolution_notes'] ?? '-')) ?></div>
        </div>

        <h5 class="mt-4">Action Log</h5>
        <div class="action-log">
            <?php if (!empty($view_case_actions)): ?>
            <?php foreach ($view_case_actions as $la): ?>
            <div class="log-entry">
                <div class="log-meta">
                    <strong><?= htmlspecialchars($la['action_by_name'] ?? 'Staff') ?></strong> &mdash;
                    <em><?= htmlspecialchars($la['action_type'] ?? 'Comment') ?></em> &mdash;
                    <?= date('M j, Y H:i', strtotime($la['created_at'])) ?>
                </div>
                <div><?= nl2br(htmlspecialchars($la['notes'] ?? '')) ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="text-muted">No actions recorded yet.</div>
            <?php endif; ?>
        </div>

        <hr>
        <h5>Add Action / Comment</h5>
        <form action="wardens.php" method="POST" class="row g-2">
            <input type="hidden" name="action" value="add_welfare_action">
            <input type="hidden" name="case_id" value="<?= $view_case['id'] ?>">
            <div class="col-md-3">
                <select name="action_type" class="form-select" required>
                    <option value="Comment">Comment</option>
                    <option value="Follow-up">Follow-up</option>
                    <option value="Note">Note</option>
                    <option value="Escalation">Escalation</option>
                    <option value="Referral">Referral</option>
                </select>
            </div>
            <div class="col-md-7">
                <input type="text" name="notes" class="form-control" placeholder="Enter notes..." required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Add</button>
            </div>
        </form>
    </div>

    <?php elseif ($edit_case): ?>
    <div class="case-detail-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Edit Case #<?= $edit_case['id'] ?></h4>
            <a href="wardens.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <form action="wardens.php" method="POST">
            <input type="hidden" name="action" value="update_welfare_case">
            <input type="hidden" name="case_id" value="<?= $edit_case['id'] ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Student</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($edit_case['student_name'] ?? '') ?>" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Case Type</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($edit_case['case_type'] ?? '') ?>" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($edit_case['priority'] ?? '') ?>" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Open" <?= $edit_case['status']==='Open'?'selected':'' ?>>Open</option>
                        <option value="In Progress" <?= $edit_case['status']==='In Progress'?'selected':'' ?>>In Progress</option>
                        <option value="Resolved" <?= $edit_case['status']==='Resolved'?'selected':'' ?>>Resolved</option>
                        <option value="Closed" <?= $edit_case['status']==='Closed'?'selected':'' ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Resolution Notes</label>
                    <textarea name="resolution_notes" class="form-control" rows="2" placeholder="Notes for resolution..."><?= htmlspecialchars($edit_case['resolution_notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" rows="2" disabled><?= htmlspecialchars($edit_case['description'] ?? '') ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Case</button>
        </form>
    </div>

    <?php else: ?>
    <div class="dashboard-content content-section">
        <section id="overview" class="content-section dashboard-section<?= $section==='overview'?' active':'' ?>" data-section="overview">
            <h2>Student Welfare Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-content"><h3><?= $assigned_students ?></h3><p>Assigned Students</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-injured"></i></div>
                    <div class="stat-content"><h3><?= $welfare_cases_count ?></h3><p>Open Welfare Cases</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-comments"></i></div>
                    <div class="stat-content"><h3><?= $counseling_sessions ?></h3><p>Today's Sessions</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-gavel"></i></div>
                    <div class="stat-content"><h3><?= $discipline_cases_count ?></h3><p>Pending Discipline Cases</p></div>
                </div>
            </div>
        </section>

        <section id="students" class="content-section dashboard-section<?= $section==='students'?' active':'' ?>" data-section="students">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Student Welfare Cases</h2>
                <button class="btn btn-success" onclick="document.getElementById('addWelfareForm').style.display='block'"><i class="fas fa-plus"></i> New Welfare Case</button>
            </div>

            <div id="addWelfareForm" style="display:none" class="case-detail-card mb-4">
                <h5>Create Welfare Case</h5>
                <form action="wardens.php" method="POST">
                    <input type="hidden" name="action" value="add_welfare_case">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">Student ID</label>
                            <input type="number" name="student_id" class="form-control" placeholder="Student ID" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Case Type</label>
                            <select name="case_type" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Academic Support">Academic Support</option>
                                <option value="Personal Counseling">Personal Counseling</option>
                                <option value="Financial Support">Financial Support</option>
                                <option value="Health Issues">Health Issues</option>
                                <option value="Homesickness">Homesickness</option>
                                <option value="Family Problems">Family Problems</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select" required>
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Assigned To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="0">Unassigned</option>
                                <?php foreach ($staff_list as $sl): ?>
                                <option value="<?= $sl['id'] ?>"><?= htmlspecialchars($sl['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="1" required placeholder="Brief description..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Case</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addWelfareForm').style.display='none'">Cancel</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Reported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_welfare_cases)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No welfare cases found</td></tr>
                        <?php else: ?>
                        <?php foreach ($all_welfare_cases as $wc): ?>
                        <tr>
                            <td><?= $wc['id'] ?></td>
                            <td><?= htmlspecialchars($wc['student_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($wc['case_type'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($wc['priority']==='Urgent'?'danger':($wc['priority']==='High'?'warning':'info')) ?>"><?= htmlspecialchars($wc['priority'] ?? '-') ?></span></td>
                            <td><span class="badge bg-<?= ($wc['status']==='Resolved'?'success':($wc['status']==='Closed'?'secondary':($wc['status']==='In Progress'?'primary':'warning'))) ?>"><?= htmlspecialchars($wc['status']) ?></span></td>
                            <td><?= !empty($wc['created_at']) ? date('M j, Y', strtotime($wc['created_at'])) : '-' ?></td>
                            <td class="table-actions">
                                <a href="wardens.php?view=<?= $wc['id'] ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                <a href="wardens.php?edit=<?= $wc['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                <form action="wardens.php" method="POST" style="display:inline" onsubmit="return confirm('Delete this case?')">
                                    <input type="hidden" name="action" value="delete_welfare_case">
                                    <input type="hidden" name="case_id" value="<?= $wc['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($all_welfare_cases)): ?>
            <h5 class="mt-4">Case Action Logs</h5>
            <?php foreach ($all_welfare_cases as $wc): ?>
            <?php if (!empty($welfare_actions_map[$wc['id']])): ?>
            <div class="case-detail-card mb-3">
                <h6>Case #<?= $wc['id'] ?> - <?= htmlspecialchars($wc['student_name'] ?? '') ?></h6>
                <div class="action-log">
                    <?php foreach ($welfare_actions_map[$wc['id']] as $la): ?>
                    <div class="log-entry">
                        <div class="log-meta">
                            <strong><?= htmlspecialchars($la['action_by_name'] ?? 'Staff') ?></strong> &mdash;
                            <em><?= htmlspecialchars($la['action_type'] ?? 'Comment') ?></em> &mdash;
                            <?= date('M j, Y H:i', strtotime($la['created_at'])) ?>
                        </div>
                        <div><?= nl2br(htmlspecialchars($la['notes'] ?? '')) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section id="counseling" class="content-section dashboard-section<?= $section==='counseling'?' active':'' ?>" data-section="counseling">
            <h2>Counseling Services</h2>
            <div class="counseling-actions">
                <button class="btn btn-primary" onclick="openModal('scheduleSession')"><i class="fas fa-calendar-plus"></i> Schedule Session</button>
                <button class="btn btn-success" onclick="openModal('counselingRecord')"><i class="fas fa-file-medical"></i> Counseling Record</button>
            </div>
            <div class="counseling-overview">
                <h3>Today's Counseling Schedule</h3>
                <div class="counseling-schedule">
                    <?php if (empty($today_counseling)): ?>
                    <div class="text-center text-muted py-4">No counseling sessions scheduled for today</div>
                    <?php else: ?>
                    <?php foreach ($today_counseling as $cs): ?>
                    <div class="session-item">
                        <div class="session-header">
                            <h4><?= htmlspecialchars($cs['session_type'] ?? 'Counseling') ?> - <?= htmlspecialchars($cs['student_name'] ?? 'Student') ?></h4>
                            <span class="session-time"><?= htmlspecialchars($cs['session_time'] ?? '-') ?></span>
                        </div>
                        <div class="session-details">
                            <div class="detail"><span>Topic:</span><strong><?= htmlspecialchars($cs['issues_discussed'] ?? $cs['session_type'] ?? 'General') ?></strong></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="discipline" class="content-section dashboard-section<?= $section==='discipline'?' active':'' ?>" data-section="discipline">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Student Discipline</h2>
                <button class="btn btn-success" onclick="document.getElementById('addDisciplineForm').style.display='block'"><i class="fas fa-plus"></i> New Discipline Case</button>
            </div>

            <div id="addDisciplineForm" style="display:none" class="case-detail-card mb-4">
                <h5>Create Discipline Case</h5>
                <form action="wardens.php" method="POST">
                    <input type="hidden" name="action" value="add_discipline_case">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">Student ID</label>
                            <input type="number" name="student_id" class="form-control" placeholder="Student ID" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Incident Type</label>
                            <select name="incident_type" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Unauthorized Absence">Unauthorized Absence</option>
                                <option value="Misconduct">Misconduct</option>
                                <option value="Academic Dishonesty">Academic Dishonesty</option>
                                <option value="Property Damage">Property Damage</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Incident Date</label>
                            <input type="date" name="incident_date" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Action Taken</label>
                            <select name="action_taken" class="form-select" required>
                                <option value="Warning">Verbal Warning</option>
                                <option value="Written Warning">Written Warning</option>
                                <option value="Probation">Probation</option>
                                <option value="Suspension">Suspension</option>
                                <option value="Expulsion">Expulsion</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="1" required placeholder="Incident description..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Case</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addDisciplineForm').style.display='none'">Cancel</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Incident</th>
                            <th>Date</th>
                            <th>Action Taken</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_discipline_cases)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No discipline cases found</td></tr>
                        <?php else: ?>
                        <?php foreach ($all_discipline_cases as $dc): ?>
                        <tr>
                            <td><?= $dc['id'] ?></td>
                            <td><?= htmlspecialchars($dc['student_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($dc['incident_type'] ?? '-') ?></td>
                            <td><?= !empty($dc['incident_date']) ? date('M j, Y', strtotime($dc['incident_date'])) : '-' ?></td>
                            <td><?= htmlspecialchars($dc['action_taken'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($dc['status']==='Resolved'?'success':($dc['status']==='Closed'?'secondary':'danger')) ?>"><?= htmlspecialchars($dc['status'] ?? 'Pending') ?></span></td>
                            <td class="table-actions">
                                <form action="wardens.php" method="POST" style="display:inline" onsubmit="return confirm('Delete this case?')">
                                    <input type="hidden" name="action" value="delete_discipline_case">
                                    <input type="hidden" name="case_id" value="<?= $dc['id'] ?>">
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

        <section id="accommodation" class="content-section dashboard-section<?= $section==='accommodation'?' active':'' ?>" data-section="accommodation">
            <h2>Accommodation Management</h2>
            <div class="accommodation-overview">
                <h3>Hostel Overview</h3>
                <div class="hostel-stats">
                    <?php if (empty($hostel_stats)): ?>
                    <div class="text-center text-muted py-4 col-12">No hostel data available</div>
                    <?php else: ?>
                    <?php foreach ($hostel_stats as $hs): $occ = (int)($hs['occupied']??0); $total = (int)($hs['total_beds']??1); $pct = $total > 0 ? round($occ/$total*100,1) : 0; ?>
                    <div class="hostel-stat">
                        <h4><?= htmlspecialchars($hs['hostel_name'] ?? 'Hostel') ?></h4>
                        <div class="occupancy"><?= $occ ?>/<?= $total ?> beds occupied</div>
                        <small><?= $pct ?>% occupancy</small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="activities" class="content-section dashboard-section<?= $section==='activities'?' active':'' ?>" data-section="activities">
            <h2>Student Activities</h2>
            <div class="activities-overview">
                <h3>Upcoming Activities</h3>
                <div class="activity-list">
                    <?php if (empty($upcoming_activities)): ?>
                    <div class="text-center text-muted py-4">No upcoming activities</div>
                    <?php else: ?>
                    <?php foreach ($upcoming_activities as $act): ?>
                    <div class="activity-item">
                        <div class="activity-header">
                            <h4><?= htmlspecialchars($act['title'] ?? $act['activity_name'] ?? 'Activity') ?></h4>
                            <span class="activity-date"><?= !empty($act['activity_date']) ? date('M j, Y', strtotime($act['activity_date'])) : '-' ?></span>
                        </div>
                        <div class="activity-details">
                            <div class="detail"><span>Type:</span><strong><?= htmlspecialchars($act['activity_type'] ?? 'General') ?></strong></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="security" class="content-section dashboard-section<?= $section==='security'?' active':'' ?>" data-section="security">
            <h2>Security & Safety</h2>
            <div class="security-overview">
                <div class="security-stats">
                    <div class="security-stat"><h4>Security Personnel</h4><div>5 on duty</div><small>All positions covered</small></div>
                    <div class="security-stat"><h4>Incidents Today</h4><div>0</div><small>No incidents reported</small></div>
                </div>
            </div>
        </section>

        <section class="activities-section">
            <h2>Recent Welfare Activities</h2>
            <div class="activities-list">
                <?php if (empty($recent_activities)): ?>
                <div class="text-muted">No recent activities</div>
                <?php else: ?>
                <?php foreach ($recent_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="activity-content">
                        <p><strong><?= htmlspecialchars($activity['activity'] ?? 'Activity') ?></strong></p>
                        <small><?= date('M j, Y H:i', strtotime($activity['created_at'])) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php endif; ?>

    <?php if ($section === 'store'): ?>
    <div id="store" class="content-section dashboard-section active" data-section="store">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="fas fa-store me-2"></i>Store Requisitions</h4>
            <button class="btn btn-primary" onclick="openModal('Requisition Form', document.getElementById('reqFormHTML').innerHTML, 'Submit')"><i class="fas fa-plus me-1"></i>New Request</button>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card h-100"><div class="card-body text-center"><h3 class="text-primary"><?= count($myRequests) ?></h3><small>My Requests</small></div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body text-center"><h3 class="text-warning"><?= count(array_filter($myRequests, fn($r) => $r['status'] === 'pending')) ?></h3><small>Pending</small></div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body text-center"><h3 class="text-success"><?= count(array_filter($myRequests, fn($r) => $r['status'] === 'fulfilled' || $r['status'] === 'approved')) ?></h3><small>Fulfilled</small></div></div></div>
        </div>
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-list me-2"></i>Requisition History</h6></div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Request #</th><th>Items</th><th>Urgency</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($myRequests)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No requisitions yet</td></tr>
                    <?php else: foreach ($myRequests as $req): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($req['request_number']) ?></strong></td>
                        <td><?= htmlspecialchars($req['items']) ?></td>
                        <td><span class="badge bg-<?= ($req['urgency'] ?? 'medium') === 'urgent' ? 'danger' : (($req['urgency'] ?? 'medium') === 'high' ? 'warning' : 'info') ?>"><?= ucfirst(htmlspecialchars($req['urgency'] ?? 'medium')) ?></span></td>
                        <td><span class="badge bg-<?= $req['status'] === 'approved' || $req['status'] === 'fulfilled' ? 'success' : ($req['status'] === 'rejected' ? 'danger' : ($req['status'] === 'pending_approval' ? 'warning' : 'secondary')) ?>"><?= ucfirst(htmlspecialchars($req['status'])) ?></span></td>
                        <td><small><?= date('M j, Y', strtotime($req['created_at'])) ?></small></td>
                        <td><a href="?page=warden_requisition" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
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

<script>
document.getElementById('modalAction')?.addEventListener('click', function() {
    const form = document.querySelector('#modalBody form');
    if (form) form.submit();
});

let wardenReqIdx = 1;
function addWardenReqItem() {
    let options = '<option value="">-- Select Item --</option>';
    <?php foreach ($storeInventory as $item): ?>
    options += '<option value="<?= $item['id'] ?>"><?= addslashes(htmlspecialchars($item['item_name'])) ?> (<?= number_format($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?>)</option>';
    <?php endforeach; ?>
    let html = '<div class="d-flex gap-2 mb-2 req-item-row align-items-center">' +
        '<select name="req_items[' + wardenReqIdx + '][item_id]" class="form-select" style="flex:2" required onchange="this.closest(\'.req-item-row\').querySelector(\'input[name*=item_name]\').value=this.options[this.selectedIndex].text.split(\'(\')[0].trim()">' + options + '</select>' +
        '<input type="number" name="req_items[' + wardenReqIdx + '][quantity]" class="form-control" style="width:80px" placeholder="Qty" min="1" required>' +
        '<input type="text" name="req_items[' + wardenReqIdx + '][item_name]" class="form-control" style="flex:1" placeholder="Item name">' +
        '<button type="button" class="btn btn-danger btn-sm" onclick="this.closest(\'.req-item-row\').remove()"><i class="fas fa-times"></i></button></div>';
    document.getElementById('reqItemsContainer').insertAdjacentHTML('beforeend', html);
    wardenReqIdx++;
}
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
        document.querySelectorAll('.content-section').forEach(section => {
            section.style.display = 'none';
        });
        const targetSection = document.getElementById(targetId);
        if (targetSection) targetSection.style.display = 'block';
    });
});

function openModal(title, content, actionText) {
    const modal = new bootstrap.Modal(document.getElementById('actionModal'));
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalAction = document.getElementById('modalAction');
    modalTitle.textContent = title;
    modalBody.innerHTML = content;
    if (actionText) modalAction.textContent = actionText;
    modal.show();
}
</script>

<div id="reqFormHTML" style="display:none">
    <form action="?page=warden_requisition" method="POST">
        <input type="hidden" name="action" value="create_warden_requisition">
        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label">Department</label><input type="text" class="form-control" name="department" value="Hostel" required></div>
            <div class="col-md-6"><label class="form-label">Urgency</label>
                <select class="form-select" name="urgency" required>
                    <option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option>
                </select></div>
        </div>
        <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2" placeholder="Optional notes"></textarea></div>
        <div class="mb-3"><label class="form-label">Requested Items</label>
            <div id="reqItemsContainer"></div>
            <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="addWardenReqItem()"><i class="fas fa-plus me-1"></i>Add Item</button>
        </div>
        <button type="submit" class="btn btn-primary w-100">Submit Requisition</button>
    </form>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
