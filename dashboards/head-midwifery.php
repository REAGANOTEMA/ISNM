<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

$ctx = bootstrapStaffDashboard(['head midwifery', 'head of midwifery']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Head of Midwifery';
$user_id = (int)($user['id'] ?? 0);
$students_db_name = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

// â”€â”€ CRUD POST Handlers â”€â”€
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) { die('Invalid CSRF token'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'add_student') {
        $sid   = trim($_POST['student_id'] ?? '');
        $sname = trim($_POST['student_name'] ?? '');
        $prog  = trim($_POST['program'] ?? '');
        $year  = (int)($_POST['year_of_study'] ?? 1);
        if ($sid && $sname && $prog) {
            try {
                $stmt = $conn->prepare("INSERT INTO midwifery_students (student_id, student_name, program, year_of_study, clinical_hours, status) VALUES (?, ?, ?, ?, 0, 'Active')");
                $stmt->bind_param('sssi', $sid, $sname, $prog, $year);
                if ($stmt->execute()) {
                    $flash = '<div class="alert alert-success">Student added successfully.</div>';
                } else {
                    $flash = '<div class="alert alert-danger">Failed to add student.</div>';
                }
                $stmt->close();
            } catch (Exception $e) {
                $flash = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            $flash = '<div class="alert alert-warning">Please fill all required fields.</div>';
        }
    }

    if ($action === 'update_student') {
        $id   = (int)($_POST['id'] ?? 0);
        $sid  = trim($_POST['student_id'] ?? '');
        $sname = trim($_POST['student_name'] ?? '');
        $prog = trim($_POST['program'] ?? '');
        $year = (int)($_POST['year_of_study'] ?? 1);
        $status = trim($_POST['status'] ?? 'Active');
        if ($id && $sid && $sname) {
            try {
                $stmt = $conn->prepare("UPDATE midwifery_students SET student_id=?, student_name=?, program=?, year_of_study=?, status=? WHERE id=?");
                $stmt->bind_param('sssisi', $sid, $sname, $prog, $year, $status, $id);
                if ($stmt->execute()) {
                    $flash = '<div class="alert alert-success">Student updated successfully.</div>';
                } else {
                    $flash = '<div class="alert alert-danger">Failed to update student.</div>';
                }
                $stmt->close();
            } catch (Exception $e) {
                $flash = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    if ($action === 'delete_student') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                $stmt = $conn->prepare("DELETE FROM midwifery_students WHERE id=?");
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $flash = '<div class="alert alert-success">Student deleted.</div>';
                } else {
                    $flash = '<div class="alert alert-danger">Failed to delete student.</div>';
                }
                $stmt->close();
            } catch (Exception $e) {
                $flash = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    if ($action === 'add_placement') {
        $sid      = trim($_POST['student_id'] ?? '');
        $facility = trim($_POST['facility_name'] ?? '');
        $dept     = trim($_POST['department'] ?? '');
        $start    = trim($_POST['start_date'] ?? '');
        $end      = trim($_POST['end_date'] ?? '');
        $sup      = trim($_POST['supervisor'] ?? '');
        $dObs     = (int)($_POST['deliveries_observed'] ?? 0);
        $dAss     = (int)($_POST['deliveries_assisted'] ?? 0);
        if ($sid && $facility && $start) {
            try {
                $stmt = $conn->prepare("INSERT INTO midwifery_clinical_placements (student_id, facility_name, department, start_date, end_date, supervisor, deliveries_observed, deliveries_assisted, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', '')");
                $stmt->bind_param('ssssssii', $sid, $facility, $dept, $start, $end, $sup, $dObs, $dAss);
                if ($stmt->execute()) {
                    $flash = '<div class="alert alert-success">Placement added successfully.</div>';
                } else {
                    $flash = '<div class="alert alert-danger">Failed to add placement.</div>';
                }
                $stmt->close();
            } catch (Exception $e) {
                $flash = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            $flash = '<div class="alert alert-warning">Please fill all required fields.</div>';
        }
    }

    if ($action === 'update_placement') {
        $id      = (int)($_POST['id'] ?? 0);
        $sid     = trim($_POST['student_id'] ?? '');
        $facility = trim($_POST['facility_name'] ?? '');
        $dept    = trim($_POST['department'] ?? '');
        $start   = trim($_POST['start_date'] ?? '');
        $end     = trim($_POST['end_date'] ?? '');
        $sup     = trim($_POST['supervisor'] ?? '');
        $dObs    = (int)($_POST['deliveries_observed'] ?? 0);
        $dAss    = (int)($_POST['deliveries_assisted'] ?? 0);
        $status  = trim($_POST['status'] ?? 'Active');
        $notes   = trim($_POST['notes'] ?? '');
        if ($id && $sid && $facility) {
            try {
                $stmt = $conn->prepare("UPDATE midwifery_clinical_placements SET student_id=?, facility_name=?, department=?, start_date=?, end_date=?, supervisor=?, deliveries_observed=?, deliveries_assisted=?, status=?, notes=? WHERE id=?");
                $stmt->bind_param('ssssssiiissi', $sid, $facility, $dept, $start, $end, $sup, $dObs, $dAss, $status, $notes, $id);
                if ($stmt->execute()) {
                    $flash = '<div class="alert alert-success">Placement updated.</div>';
                } else {
                    $flash = '<div class="alert alert-danger">Failed to update placement.</div>';
                }
                $stmt->close();
            } catch (Exception $e) {
                $flash = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    if ($action === 'delete_placement') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                $stmt = $conn->prepare("DELETE FROM midwifery_clinical_placements WHERE id=?");
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $flash = '<div class="alert alert-success">Placement deleted.</div>';
                } else {
                    $flash = '<div class="alert alert-danger">Failed to delete placement.</div>';
                }
                $stmt->close();
            } catch (Exception $e) {
                $flash = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    if ($action === 'add_skill') {
        $skname = trim($_POST['skill_name'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $cat    = trim($_POST['category'] ?? '');
        $mand   = isset($_POST['is_mandatory']) ? 1 : 0;
        if ($skname) {
            try {
                $stmt = $conn->prepare("INSERT INTO midwifery_skills_training (skill_name, description, category, is_mandatory) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('sssi', $skname, $desc, $cat, $mand);
                if ($stmt->execute()) {
                    $flash = '<div class="alert alert-success">Skill added successfully.</div>';
                } else {
                    $flash = '<div class="alert alert-danger">Failed to add skill.</div>';
                }
                $stmt->close();
            } catch (Exception $e) {
                $flash = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    if ($action === 'delete_skill') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                $stmt = $conn->prepare("DELETE FROM midwifery_skills_training WHERE id=?");
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $flash = '<div class="alert alert-success">Skill deleted.</div>';
                }
                $stmt->close();
            } catch (Exception $e) {
                $flash = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    // AJAX handlers for staff management (return JSON, not redirect)
    if (in_array($action, ['add_staff', 'edit_staff', 'delete_staff'])) {
        header('Content-Type: application/json');
        $response = ['success' => false, 'error' => 'Unknown action'];
        if ($action === 'add_staff' && $conn) {
            $name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $position = trim($_POST['position'] ?? '');
            if ($name && $email) {
                $stmt = $conn->prepare("INSERT INTO staff (full_name, email, phone, department, position, status) VALUES (?, ?, ?, 'Midwifery', ?, 'Active')");
                $stmt->bind_param('ssss', $name, $email, $phone, $position);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            } else {
                $response['error'] = 'Name and email are required';
            }
        } elseif ($action === 'edit_staff' && $conn) {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $status = trim($_POST['status'] ?? 'Active');
            if ($id && $name && $email) {
                $stmt = $conn->prepare("UPDATE staff SET full_name=?, email=?, phone=?, position=?, status=? WHERE id=?");
                $stmt->bind_param('sssssi', $name, $email, $phone, $position, $status, $id);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            } else {
                $response['error'] = 'ID, name, and email are required';
            }
        } elseif ($action === 'delete_staff' && $conn) {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM staff WHERE id=?");
                $stmt->bind_param('i', $id);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            } else {
                $response['error'] = 'Staff ID is required';
            }
        }
        echo json_encode($response);
        exit;
    }

    // Redirect after POST to prevent resubmission
    header('Location: head-midwifery.php?page=' . ($_GET['page'] ?? 'home'));
    exit;
}

// â”€â”€ Page routing â”€â”€
$pageToSection = [
    'home'       => 'overview',
    'overview'   => 'overview',
    'students'   => 'students',
    'clinical'   => 'clinical',
    'skills'     => 'skills',
    'timetable'  => 'timetable',
    'courses'    => 'courses',
    'staff'      => 'staff',
];
$page  = $_GET['page'] ?? 'home';
$section = $pageToSection[$page] ?? 'overview';

$profileImageUrl = '../images/username.png';
$profileSettingsFile = __DIR__ . '/../includes/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    include_once $profileSettingsFile;
    if (function_exists('getStaffProfileImageUrl')) {
        $url = getStaffProfileImageUrl($user_id);
        if ($url) $profileImageUrl = $url;
    }
}

// Set dashboard statistics from database
$total_students = 0;
$total_staff = 0;
$active_programs = 0;
$midwifery_courses = 0;

try {
    if ($ctx['students']) {
        $result = $ctx['students']->query("SELECT COUNT(*) as cnt FROM students");
        if ($result) $total_students = (int)$result->fetch_assoc()['cnt'];
    }
    $staff_result = $conn->query("SELECT COUNT(*) as cnt FROM staff");
    if ($staff_result) $total_staff = (int)$staff_result->fetch_assoc()['cnt'];
    $prog_result = $conn->query("SELECT COUNT(*) as cnt FROM academic_programs WHERE department LIKE '%Midwifery%' AND status='Active'");
    if ($prog_result) $active_programs = (int)$prog_result->fetch_assoc()['cnt'];
    $course_result = $conn->query("SELECT COUNT(DISTINCT course_code) as cnt FROM course_assignments WHERE course_name LIKE '%Midwifery%' AND status='Active'");
    if ($course_result) $midwifery_courses = (int)$course_result->fetch_assoc()['cnt'];
} catch (Exception $e) {
    error_log('head-midwifery stats: ' . $e->getMessage());
}

// Get midwifery students from dedicated table
$mw_students = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT id, student_id, student_name, program, year_of_study, clinical_hours, status FROM midwifery_students ORDER BY student_name");
        if ($r) $mw_students = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('mw_students: ' . $e->getMessage()); }
}

// Get clinical placements
$mw_placements = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM midwifery_clinical_placements ORDER BY start_date DESC");
        if ($r) $mw_placements = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('mw_placements: ' . $e->getMessage()); }
}

// Get skills training
$mw_skills = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM midwifery_skills_training ORDER BY category, skill_name");
        if ($r) $mw_skills = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('mw_skills: ' . $e->getMessage()); }
}

// Legacy student query (for fallback stats)
$midwifery_students = [];
if ($ctx['students']) {
    try {
        $r = $ctx['students']->query("SELECT id, first_name, surname, program, level, status FROM students WHERE program LIKE '%Midwifery%' ORDER BY first_name LIMIT 50");
        if ($r) $midwifery_students = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('head-midwifery context: ' . $e->getMessage()); }
}

// Get programs
$programs_data = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT program_name, duration, (SELECT COUNT(*) FROM {$students_db_name}.students WHERE program LIKE CONCAT('%', program_name, '%')) AS enrolled FROM academic_programs WHERE department LIKE '%Midwifery%' AND status='Active'");
        if ($r) $programs_data = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('head-midwifery context: ' . $e->getMessage()); }
}

// Get recent activities
$recent_activities = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_activities[] = $row;
            }
        }
    } catch (Exception $e) { error_log('head-midwifery context: ' . $e->getMessage()); }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>

.mid-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.mid-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="mid-content">

<?php switch ($section):
    case 'overview': ?>
        <section id="overview" class="content-section dashboard-section active" data-section="overview">
            <h2>Department Overview</h2>
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_students); ?></h3>
                        <p>Total Midwifery Students</p>
                    </div>
                </div>
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_staff); ?></h3>
                        <p>Faculty Members</p>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($midwifery_courses); ?></h3>
                        <p>Active Courses</p>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($active_programs); ?></h3>
                        <p>Active Programs</p>
                    </div>
                </div>
            </div>
        </section>
        <?php break;
    case 'students': ?>
        <section id="students" class="content-section dashboard-section active" data-section="students">
            <h2><i class="fas fa-user-graduate me-2"></i>Midwifery Students</h2>
            <?= $flash ?>
            <div class="mb-3">
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addStudentForm">
                    <i class="fas fa-plus me-1"></i> Add Student
                </button>
            </div>
            <div class="collapse mb-4" id="addStudentForm">
                <div class="card card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add_student">
                        <?php csrfField(); ?>
                        <div class="col-md-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" required placeholder="e.g. MW-2026-001">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="student_name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Program</label>
                            <input type="text" name="program" class="form-control" required placeholder="e.g. BSc Midwifery">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <select name="year_of_study" class="form-select">
                                <option value="1">Year 1</option>
                                <option value="2">Year 2</option>
                                <option value="3">Year 3</option>
                                <option value="4">Year 4</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100"><i class="fas fa-save"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <input class="form-control form-control-sm" style="max-width:300px" id="mwSearch" type="text" placeholder="Search..." onkeyup="filterTable('mwSearch','mwTable')">
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="mwTable">
                    <thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>Year</th><th>Clinical Hours</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($mw_students)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No midwifery students found</td></tr>
                        <?php else: ?>
                        <?php foreach ($mw_students as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['student_id']) ?></td>
                            <td><?= htmlspecialchars($s['student_name']) ?></td>
                            <td><?= htmlspecialchars($s['program'] ?? '-') ?></td>
                            <td>Year <?= htmlspecialchars($s['year_of_study'] ?? '?') ?></td>
                            <td><?= (int)($s['clinical_hours'] ?? 0) ?></td>
                            <td><span class="badge bg-<?= ($s['status'] ?? '')==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($s['status'] ?? 'Active') ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editStudent(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this student?')">
                                    <input type="hidden" name="action" value="delete_student">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit Student Modal -->
            <div class="modal fade" id="editStudentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_student">
                            <?php csrfField(); ?>
                            <input type="hidden" name="id" id="editStudentId">
                            <div class="modal-header"><h5 class="modal-title">Edit Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="mb-3"><label class="form-label">Student ID</label><input type="text" name="student_id" id="editStudentSid" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="student_name" id="editStudentName" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Program</label><input type="text" name="program" id="editStudentProg" class="form-control"></div>
                                <div class="mb-3"><label class="form-label">Year</label><select name="year_of_study" id="editStudentYear" class="form-select"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select></div>
                                <div class="mb-3"><label class="form-label">Status</label><select name="status" id="editStudentStatus" class="form-select"><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="Graduated">Graduated</option></select></div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <?php break;
    case 'courses': ?>
        <section id="courses" class="content-section dashboard-section active" data-section="courses">
            <h2><i class="fas fa-book-open me-2"></i>Course Management</h2>
            <p class="text-muted">Manage midwifery courses, curriculum, and syllabi.</p>
            <?php
            $mw_course_list = [];
            if ($conn) {
                try {
                    $cr = $conn->query("SELECT * FROM course_assignments WHERE course_name LIKE '%Midwifery%' ORDER BY course_name");
                    if ($cr) $mw_course_list = $cr->fetch_all(MYSQLI_ASSOC);
                } catch (Exception $e) { error_log('head-midwifery courses: ' . $e->getMessage()); }
            }
            ?>
            <?php if (!empty($mw_course_list)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Course Code</th><th>Course Name</th><th>Instructor</th><th>Credits</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($mw_course_list as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['course_code'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['course_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['instructor'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['credits'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($c['status'] ?? 'Active') === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($c['status'] ?? 'Active') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-book fa-3x mb-3 opacity-25"></i>
                <p>No midwifery courses found. Courses will appear here once assigned.</p>
            </div>
            <?php endif; ?>
        </section>
        <?php break;
    case 'clinical': ?>
        <section id="clinical" class="content-section dashboard-section active" data-section="clinical">
            <h2><i class="fas fa-clinic-medical me-2"></i>Clinical Placements</h2>
            <?= $flash ?>
            <div class="mb-3">
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addPlacementForm">
                    <i class="fas fa-plus me-1"></i> Add Placement
                </button>
            </div>
            <div class="collapse mb-4" id="addPlacementForm">
                <div class="card card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add_placement">
                        <?php csrfField(); ?>
                        <div class="col-md-3">
                            <label class="form-label">Student ID</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">Select student...</option>
                                <?php foreach ($mw_students as $st): ?>
                                <option value="<?= htmlspecialchars($st['student_id']) ?>"><?= htmlspecialchars($st['student_id'] . ' - ' . $st['student_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Facility</label>
                            <input type="text" name="facility_name" class="form-control" required placeholder="Hospital name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g. Maternity Ward">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Supervisor</label>
                            <input type="text" name="supervisor" class="form-control" placeholder="Supervisor name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Deliveries Observed</label>
                            <input type="number" name="deliveries_observed" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Deliveries Assisted</label>
                            <input type="number" name="deliveries_assisted" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Placement</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <input class="form-control form-control-sm" style="max-width:300px" id="plSearch" type="text" placeholder="Search..." onkeyup="filterTable('plSearch','plTable')">
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="plTable">
                    <thead><tr><th>Student ID</th><th>Facility</th><th>Dept</th><th>Supervisor</th><th>Start</th><th>End</th><th>Obs</th><th>Assist</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($mw_placements)): ?>
                        <tr><td colspan="10" class="text-center text-muted">No placements found</td></tr>
                        <?php else: ?>
                        <?php foreach ($mw_placements as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['student_id']) ?></td>
                            <td><?= htmlspecialchars($p['facility_name']) ?></td>
                            <td><?= htmlspecialchars($p['department'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['supervisor'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['start_date']) ?></td>
                            <td><?= htmlspecialchars($p['end_date'] ?? '-') ?></td>
                            <td><?= (int)$p['deliveries_observed'] ?></td>
                            <td><?= (int)$p['deliveries_assisted'] ?></td>
                            <td><span class="badge bg-<?= ($p['status'] ?? '')==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($p['status'] ?? 'Active') ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editPlacement(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this placement?')">
                                    <input type="hidden" name="action" value="delete_placement">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit Placement Modal -->
            <div class="modal fade" id="editPlacementModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_placement">
                            <?php csrfField(); ?>
                            <input type="hidden" name="id" id="editPlId">
                            <div class="modal-header"><h5 class="modal-title">Edit Placement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label">Student ID</label><input type="text" name="student_id" id="editPlSid" class="form-control" required></div>
                                    <div class="col-md-4"><label class="form-label">Facility</label><input type="text" name="facility_name" id="editPlFacility" class="form-control" required></div>
                                    <div class="col-md-4"><label class="form-label">Department</label><input type="text" name="department" id="editPlDept" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label">Supervisor</label><input type="text" name="supervisor" id="editPlSup" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label">Start Date</label><input type="date" name="start_date" id="editPlStart" class="form-control" required></div>
                                    <div class="col-md-4"><label class="form-label">End Date</label><input type="date" name="end_date" id="editPlEnd" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label">Deliveries Observed</label><input type="number" name="deliveries_observed" id="editPlObs" class="form-control" min="0"></div>
                                    <div class="col-md-4"><label class="form-label">Deliveries Assisted</label><input type="number" name="deliveries_assisted" id="editPlAss" class="form-control" min="0"></div>
                                    <div class="col-md-4"><label class="form-label">Status</label><select name="status" id="editPlStatus" class="form-select"><option value="Active">Active</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option></select></div>
                                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" id="editPlNotes" class="form-control" rows="2"></textarea></div>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <?php break;
    case 'timetable': ?>
        <section id="timetable" class="content-section dashboard-section active" data-section="timetable">
            <h2><i class="fas fa-calendar-week me-2"></i>Timetable</h2>
            <?php if (file_exists(__DIR__ . '/timetable.php')): ?>
                <?php include __DIR__ . '/timetable.php'; ?>
            <?php else: ?>
            <p class="text-muted">Midwifery department timetable will appear here.</p>
            <?php endif; ?>
        </section>
        <?php break;
    case 'skills': ?>
        <section id="skills" class="content-section dashboard-section active" data-section="skills">
            <h2><i class="fas fa-star me-2"></i>Skills Training</h2>
            <?= $flash ?>
            <div class="mb-3">
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addSkillForm">
                    <i class="fas fa-plus me-1"></i> Add Skill
                </button>
            </div>
            <div class="collapse mb-4" id="addSkillForm">
                <div class="card card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add_skill">
                        <?php csrfField(); ?>
                        <div class="col-md-3">
                            <label class="form-label">Skill Name</label>
                            <input type="text" name="skill_name" class="form-control" required placeholder="e.g. Normal Delivery">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Brief description">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="e.g. Core Skills">
                        </div>
                        <div class="col-md-1 form-check d-flex align-items-center pt-4">
                            <input type="checkbox" name="is_mandatory" class="form-check-input" id="addSkillMand" value="1">
                            <label class="form-check-label ms-2" for="addSkillMand">Mandatory</label>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100"><i class="fas fa-save"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="mb-2">
                <input class="form-control form-control-sm" style="max-width:300px" id="skSearch" type="text" placeholder="Search..." onkeyup="filterTable('skSearch','skTable')">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="skTable">
                    <thead><tr><th>Skill Name</th><th>Description</th><th>Category</th><th>Mandatory</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($mw_skills)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No skills found</td></tr>
                        <?php else: ?>
                        <?php foreach ($mw_skills as $sk): ?>
                        <tr>
                            <td><?= htmlspecialchars($sk['skill_name']) ?></td>
                            <td><?= htmlspecialchars($sk['description'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($sk['category'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $sk['is_mandatory'] ? 'warning' : 'info' ?>"><?= $sk['is_mandatory'] ? 'Yes' : 'No' ?></span></td>
                            <td>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this skill?')">
                                    <input type="hidden" name="action" value="delete_skill">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= $sk['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php break;
    case 'staff':
        // Staff AJAX handlers are processed in the top POST handler above
    ?>
        <section id="staff" class="content-section dashboard-section active" data-section="staff">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="fas fa-users me-2"></i>Department Staff</h2>
                <button class="btn btn-primary btn-sm" onclick="showAddStaff()"><i class="fas fa-plus me-1"></i>Add Staff</button>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <input class="form-control form-control-sm" style="max-width:300px" id="stSearch" type="text" placeholder="Search..." onkeyup="filterTable('stSearch','stTable')">
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="stTable">
                    <thead><tr><th>Name</th><th>Position</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                        $staff_list = [];
                        if ($conn) {
                            $sr = $conn->query("SELECT id, full_name, position, email, phone, status FROM staff WHERE department LIKE '%Midwifery%' ORDER BY full_name");
                            if ($sr) $staff_list = $sr->fetch_all(MYSQLI_ASSOC);
                        }
                        if (empty($staff_list)): ?>
                        <tr><td colspan="6" class="text-center text-muted">No midwifery staff found</td></tr>
                        <?php else: ?>
                        <?php foreach ($staff_list as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['full_name']) ?></td>
                            <td><?= htmlspecialchars($s['position'] ?? '-') ?></td>
                            <td><a href="mailto:<?= htmlspecialchars($s['email']) ?>"><i class="fas fa-envelope text-primary me-1"></i><?= htmlspecialchars($s['email']) ?></a></td>
                            <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($s['status'] ?? 'Active') === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($s['status'] ?? 'Active') ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editStaff(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['phone'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($s['position'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($s['status'] ?? 'Active', ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteStaff(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Add Staff Modal -->
        <div class="modal fade" id="addStaffModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add Midwifery Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form id="addStaffForm" onsubmit="event.preventDefault(); submitAddStaff()">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Position</label><input type="text" name="position" class="form-control"></div>
                        <input type="hidden" name="action" value="add_staff">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                    </div>
                </form>
            </div></div>
        </div>

        <!-- Edit Staff Modal -->
        <div class="modal fade" id="editStaffModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form id="editStaffForm" onsubmit="event.preventDefault(); submitEditStaff()">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" id="edit_full_name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" id="edit_phone" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Position</label><input type="text" name="position" id="edit_position" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Status</label><select name="status" id="edit_status" class="form-select"><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="On Leave">On Leave</option><option value="Terminated">Terminated</option></select></div>
                        <input type="hidden" name="action" value="edit_staff">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                    </div>
                </form>
            </div></div>
        </div>

        <script>
        function showAddStaff() { new bootstrap.Modal(document.getElementById('addStaffModal')).show(); }
        function editStaff(id, name, email, phone, position, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_full_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_position').value = position;
            document.getElementById('edit_status').value = status;
            new bootstrap.Modal(document.getElementById('editStaffModal')).show();
        }
        function deleteStaff(id, name) {
            if (!confirm('Delete staff member "' + name + '"? This cannot be undone.')) return;
            var fd = new FormData();
            fd.append('action', 'delete_staff');
            fd.append('id', id);
            fd.append('csrf_token', window.CSRF_TOKEN);
            fetch(window.location.href, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) { window.location.reload(); }
                    else { alert('Error: ' + (d.error || 'Failed')); }
                })
                .catch(function(e) { alert('Error deleting staff'); });
        }
        function submitAddStaff() {
            var fd = new FormData(document.getElementById('addStaffForm'));
            fd.append('csrf_token', window.CSRF_TOKEN);
            fetch(window.location.href, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) { window.location.reload(); }
                    else { alert('Error: ' + (d.error || 'Failed')); }
                })
                .catch(function(e) { alert('Error adding staff'); });
        }
        function submitEditStaff() {
            var fd = new FormData(document.getElementById('editStaffForm'));
            fd.append('csrf_token', window.CSRF_TOKEN);
            fetch(window.location.href, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) { window.location.reload(); }
                    else { alert('Error: ' + (d.error || 'Failed')); }
                })
                .catch(function(e) { alert('Error updating staff'); });
        }
        </script>
        <?php break;
    default: ?>
        <?php header('Location: head-midwifery.php?section=overview'); exit; ?>
        <?php break;
endswitch; ?>

</div>

<!-- â•â•â• AJAX MODULE LOADING â•â•â• -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.mid-content');
    var loadingOverlay = document.getElementById('ajaxLoadingOverlay');
    var isAjaxLoading = false;

    function showLoading() { if (loadingOverlay) loadingOverlay.style.display = 'flex'; isAjaxLoading = true; }
    function hideLoading() { if (loadingOverlay) loadingOverlay.style.display = 'none'; isAjaxLoading = false; }

    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (!href || href.indexOf('?') === -1) return;
            if (isAjaxLoading) return;

            e.preventDefault();
            showLoading();
            history.pushState({}, '', href);
            document.querySelectorAll('.child-link').forEach(function(l) { l.classList.remove('active'); });
            this.classList.add('active');

            var section = href.split('section=')[1] || href.split('page=')[1] || 'overview';
            fetch('head-midwifery.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.mid-content');
                if (newContent && contentArea) {
                    contentArea.innerHTML = newContent.innerHTML;
                    contentArea.querySelectorAll('script').forEach(function(oldScript) {
                        var newScript = document.createElement('script');
                        if (oldScript.src) { newScript.src = oldScript.src; }
                        else { newScript.textContent = oldScript.textContent; }
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                }
                hideLoading();
            })
            .catch(function(err) {
                console.error('[AJAX Load Error]', err);
                hideLoading();
                window.location.href = href;
            });
        });
    });

    window.addEventListener('popstate', function() { window.location.reload(); });
})();

function editStudent(s) {
    document.getElementById('editStudentId').value = s.id;
    document.getElementById('editStudentSid').value = s.student_id;
    document.getElementById('editStudentName').value = s.student_name;
    document.getElementById('editStudentProg').value = s.program || '';
    document.getElementById('editStudentYear').value = s.year_of_study || 1;
    document.getElementById('editStudentStatus').value = s.status || 'Active';
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}
function editPlacement(p) {
    document.getElementById('editPlId').value = p.id;
    document.getElementById('editPlSid').value = p.student_id;
    document.getElementById('editPlFacility').value = p.facility_name;
    document.getElementById('editPlDept').value = p.department || '';
    document.getElementById('editPlSup').value = p.supervisor || '';
    document.getElementById('editPlStart').value = p.start_date || '';
    document.getElementById('editPlEnd').value = p.end_date || '';
    document.getElementById('editPlObs').value = p.deliveries_observed || 0;
    document.getElementById('editPlAss').value = p.deliveries_assisted || 0;
    document.getElementById('editPlStatus').value = p.status || 'Active';
    document.getElementById('editPlNotes').value = p.notes || '';
    new bootstrap.Modal(document.getElementById('editPlacementModal')).show();
}

function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
        }
        tr[i].style.display = found ? "" : "none";
    }
}
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
