<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

$ctx = bootstrapStaffDashboard(['head nursing', 'head of nursing']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Head of Nursing';
$user_id = (int)($user['id'] ?? 0);
$students_db_name = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

// â”€â”€ POST Handlers â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (!verifyCsrfToken()) { die('Invalid CSRF token'); }
    $action = $_POST['action'] ?? '';
    $formError = '';

    if ($action === 'add_student') {
        $student_id = trim($_POST['student_id'] ?? '');
        $student_name = trim($_POST['student_name'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $year_of_study = intval($_POST['year_of_study'] ?? 1);
        if ($student_id && $student_name && $program) {
            $stmt = $conn->prepare("INSERT INTO nursing_students (student_id, student_name, program, year_of_study, clinical_hours, status) VALUES (?, ?, ?, ?, 0, 'Active') ON DUPLICATE KEY UPDATE student_name=VALUES(student_name), program=VALUES(program), year_of_study=VALUES(year_of_study)");
            if (!$stmt) { $_SESSION['error'] = 'Database error: ' . $conn->error; header('Location: head-nursing.php?page=students'); exit; }
            $stmt->bind_param("sssi", $student_id, $student_name, $program, $year_of_study);
            if (!$stmt->execute()) { $formError = 'Database error: ' . ($stmt->error ?? 'unknown'); error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            if ($formError) { $_SESSION['error'] = $formError; } else { $_SESSION['success'] = 'Nursing student added successfully.'; }
        } else {
            $_SESSION['error'] = 'All student fields are required.';
        }
        header('Location: head-nursing.php?page=students');
        exit;
    }

    if ($action === 'update_student') {
        $id = intval($_POST['id'] ?? 0);
        $student_name = trim($_POST['student_name'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $year_of_study = intval($_POST['year_of_study'] ?? 1);
        $status = trim($_POST['status'] ?? 'Active');
        if ($id && $student_name && $program) {
            $stmt = $conn->prepare("UPDATE nursing_students SET student_name=?, program=?, year_of_study=?, status=? WHERE id=?");
            if (!$stmt) { $_SESSION['error'] = 'Database error: ' . $conn->error; header('Location: head-nursing.php?page=students'); exit; }
            $stmt->bind_param("ssisi", $student_name, $program, $year_of_study, $status, $id);
            if (!$stmt->execute()) { $formError = 'Database error: ' . ($stmt->error ?? 'unknown'); error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            if ($formError) { $_SESSION['error'] = $formError; } else { $_SESSION['success'] = 'Student updated successfully.'; }
        }
        header('Location: head-nursing.php?page=students');
        exit;
    }

    if ($action === 'delete_student') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM nursing_students WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { $formError = 'Database error: ' . ($stmt->error ?? 'unknown'); error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            if ($formError) { $_SESSION['error'] = $formError; } else { $_SESSION['success'] = 'Student deleted.'; }
        }
        header('Location: head-nursing.php?page=students');
        exit;
    }

    if ($action === 'add_placement') {
        $student_id = trim($_POST['student_id'] ?? '');
        $facility_name = trim($_POST['facility_name'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $supervisor = trim($_POST['supervisor'] ?? '');
        if ($student_id && $facility_name && $start_date) {
            $stmt = $conn->prepare("INSERT INTO nursing_clinical_placements (student_id, facility_name, department, start_date, end_date, supervisor, hours_completed, status, notes) VALUES (?, ?, ?, ?, ?, ?, 0, 'Active', '')");
            if (!$stmt) { $_SESSION['error'] = 'Database error: ' . $conn->error; header('Location: head-nursing.php?page=clinical'); exit; }
            $stmt->bind_param("ssssss", $student_id, $facility_name, $department, $start_date, $end_date, $supervisor);
            if (!$stmt->execute()) { $formError = 'Database error: ' . ($stmt->error ?? 'unknown'); error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            if ($formError) { $_SESSION['error'] = $formError; } else { $_SESSION['success'] = 'Clinical placement added.'; }
        } else {
            $_SESSION['error'] = 'Student ID, facility, and start date are required.';
        }
        header('Location: head-nursing.php?page=clinical');
        exit;
    }

    if ($action === 'update_placement') {
        $id = intval($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Active');
        $notes = trim($_POST['notes'] ?? '');
        $hours_completed = intval($_POST['hours_completed'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("UPDATE nursing_clinical_placements SET status=?, notes=?, hours_completed=? WHERE id=?");
            if (!$stmt) { $_SESSION['error'] = 'Database error: ' . $conn->error; header('Location: head-nursing.php?page=clinical'); exit; }
            $stmt->bind_param("ssii", $status, $notes, $hours_completed, $id);
            if (!$stmt->execute()) { $formError = 'Database error: ' . ($stmt->error ?? 'unknown'); error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            if ($formError) { $_SESSION['error'] = $formError; } else { $_SESSION['success'] = 'Placement updated.'; }
        }
        header('Location: head-nursing.php?page=clinical');
        exit;
    }

    if ($action === 'delete_placement') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM nursing_clinical_placements WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { $formError = 'Database error: ' . ($stmt->error ?? 'unknown'); error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            if ($formError) { $_SESSION['error'] = $formError; } else { $_SESSION['success'] = 'Placement deleted.'; }
        }
        header('Location: head-nursing.php?page=clinical');
        exit;
    }

    if ($action === 'add_assessment') {
        $student_id = trim($_POST['student_id'] ?? '');
        $skill_id = intval($_POST['skill_id'] ?? 0);
        $assessment_date = $_POST['assessment_date'] ?? date('Y-m-d');
        $score = floatval($_POST['score'] ?? 0);
        $grade = trim($_POST['grade'] ?? '');
        $assessor = trim($_POST['assessor'] ?? '');
        $comments = trim($_POST['comments'] ?? '');
        if ($student_id && $skill_id) {
            $stmt = $conn->prepare("INSERT INTO nursing_practical_assessment (student_id, skill_id, assessment_date, score, grade, assessor, comments, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Completed')");
            if (!$stmt) { $_SESSION['error'] = 'Database error: ' . $conn->error; header('Location: head-nursing.php?page=students'); exit; }
            $stmt->bind_param("sisdsss", $student_id, $skill_id, $assessment_date, $score, $grade, $assessor, $comments);
            if (!$stmt->execute()) { $formError = 'Database error: ' . ($stmt->error ?? 'unknown'); error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            if ($formError) { $_SESSION['error'] = $formError; } else { $_SESSION['success'] = 'Assessment recorded.'; }
        } else {
            $_SESSION['error'] = 'Student ID and skill are required.';
        }
        header('Location: head-nursing.php?page=students');
        exit;
    }

    if ($action === 'update_assessment') {
        $id = intval($_POST['id'] ?? 0);
        $score = floatval($_POST['score'] ?? 0);
        $grade = trim($_POST['grade'] ?? '');
        $comments = trim($_POST['comments'] ?? '');
        $assessor = trim($_POST['assessor'] ?? '');
        if ($id) {
            $stmt = $conn->prepare("UPDATE nursing_practical_assessment SET score=?, grade=?, comments=?, assessor=? WHERE id=?");
            if (!$stmt) { $_SESSION['error'] = 'Database error: ' . $conn->error; header('Location: head-nursing.php?page=students'); exit; }
            $stmt->bind_param("dsssi", $score, $grade, $comments, $assessor, $id);
            if (!$stmt->execute()) { $formError = 'Database error: ' . ($stmt->error ?? 'unknown'); error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            if ($formError) { $_SESSION['error'] = $formError; } else { $_SESSION['success'] = 'Assessment updated.'; }
        }
        header('Location: head-nursing.php?page=students');
        exit;
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
                $stmt = $conn->prepare("INSERT INTO staff (full_name, email, phone, department, position, status) VALUES (?, ?, ?, 'Nursing', ?, 'Active')");
                if (!$stmt) { $response['error'] = 'Database error: ' . $conn->error; echo json_encode($response); exit; }
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
                if (!$stmt) { $response['error'] = 'Database error: ' . $conn->error; echo json_encode($response); exit; }
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
                if (!$stmt) { $response['error'] = 'Database error: ' . $conn->error; echo json_encode($response); exit; }
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
}

// â”€â”€ Page routing â”€â”€
$pageToSection = [
    'home'       => 'overview',
    'overview'   => 'overview',
    'students'   => 'students',
    'clinical'   => 'clinical',
    'timetable'  => 'timetable',
    'courses'    => 'courses',
    'staff'      => 'staff',
    'incidents'  => 'overview',
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
$nursing_courses = 0;

try {
    if ($ctx['students']) {
        $result = $ctx['students']->query("SELECT COUNT(*) as cnt FROM students");
        if ($result) $total_students = (int)$result->fetch_assoc()['cnt'];
    }
    $staff_result = $conn->query("SELECT COUNT(*) as cnt FROM staff");
    if ($staff_result) $total_staff = (int)$staff_result->fetch_assoc()['cnt'];
    $prog_result = $conn->query("SELECT COUNT(*) as cnt FROM academic_programs WHERE department LIKE '%Nursing%' AND status='Active'");
    if ($prog_result) $active_programs = (int)$prog_result->fetch_assoc()['cnt'];
    $course_result = $conn->query("SELECT COUNT(DISTINCT course_code) as cnt FROM course_assignments WHERE course_name LIKE '%Nursing%' AND status='Active'");
    if ($course_result) $nursing_courses = (int)$course_result->fetch_assoc()['cnt'];
} catch (Exception $e) {
    error_log('head-nursing stats: ' . $e->getMessage());
}

// Get nursing students (from nursing_students table)
$nursing_students = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM nursing_students ORDER BY student_name LIMIT 100");
        if ($r) $nursing_students = isnm_fetch_all($r);
    } catch (Exception $e) {
        error_log('head-nursing students: ' . $e->getMessage());
    }
}

// Get nursing students from students DB as fallback
$enrolled_students = [];
if ($ctx['students']) {
    try {
        $r = $ctx['students']->query("SELECT id, first_name, surname, student_number, program, level, status FROM students WHERE program LIKE '%Nursing%' ORDER BY first_name LIMIT 100");
        if ($r) $enrolled_students = isnm_fetch_all($r);
    } catch (Exception $e) { error_log('head-nursing context: ' . $e->getMessage()); }
}

// Get clinical placements
$clinical_placements = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM nursing_clinical_placements ORDER BY start_date DESC LIMIT 100");
        if ($r) $clinical_placements = isnm_fetch_all($r);
    } catch (Exception $e) {
        error_log('head-nursing placements: ' . $e->getMessage());
    }
}

// Get practical assessments
$practical_assessments = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT pa.*, ns.skill_name FROM nursing_practical_assessment pa LEFT JOIN nursing_skills_training ns ON pa.skill_id = ns.id ORDER BY pa.assessment_date DESC LIMIT 100");
        if ($r) $practical_assessments = isnm_fetch_all($r);
    } catch (Exception $e) {
        error_log('head-nursing assessments: ' . $e->getMessage());
    }
}

// Get skills list for assessment form
$skills_list = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT id, skill_name, category FROM nursing_skills_training ORDER BY skill_name");
        if ($r) $skills_list = isnm_fetch_all($r);
    } catch (Exception $e) { error_log('head-nursing context: ' . $e->getMessage()); }
}

// Get programs
$programs_data = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT program_name, duration, (SELECT COUNT(*) FROM {$students_db_name}.students WHERE program LIKE CONCAT('%', program_name, '%')) AS enrolled FROM academic_programs WHERE department LIKE '%Nursing%' AND status='Active'");
        if ($r) $programs_data = isnm_fetch_all($r);
    } catch (Exception $e) { error_log('head-nursing context: ' . $e->getMessage()); }
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
    } catch (Exception $e) { error_log('head-nursing context: ' . $e->getMessage()); }
}
 


$flash_success = $_SESSION['success'] ?? '';
$flash_error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.nurs-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.nurs-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="nurs-content">

<?php if ($flash_success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash_success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash_error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php switch ($section):
    case 'overview': ?>
        <section id="overview" class="content-section dashboard-section active" data-section="overview">
            <h2>Department Overview</h2>
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_students); ?></h3>
                        <p>Total Nursing Students</p>
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
                        <h3><?php echo number_format($nursing_courses); ?></h3>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="fas fa-user-graduate me-2"></i>Nursing Students</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-1"></i> Add Student
                </button>
            </div>

            <?php if (!empty($nursing_students)): ?>
            <div class="d-flex justify-content-between mb-2">
                <input class="form-control form-control-sm" style="max-width:300px" id="nsSearch" type="text" placeholder="Search..." onkeyup="filterTable('nsSearch','nsTable')">
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="nsTable">
                    <thead><tr><th>Student ID</th><th>Student Name</th><th>Program</th><th>Year</th><th>Clinical Hours</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($nursing_students as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['student_id']) ?></td>
                            <td><?= htmlspecialchars($s['student_name']) ?></td>
                            <td><?= htmlspecialchars($s['program'] ?? '-') ?></td>
                            <td>Year <?= htmlspecialchars($s['year_of_study'] ?? '?') ?></td>
                            <td><?= intval($s['clinical_hours'] ?? 0) ?></td>
                            <td><span class="badge bg-<?= ($s['status'] ?? '') === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($s['status'] ?? 'Active') ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editStudent(<?= json_encode($s) ?>)'><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this student?')">
                                    <input type="hidden" name="action" value="delete_student">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center">No nursing students registered. Add one to get started.</p>
            <?php endif; ?>

            <!-- Practical Assessments -->
            <h4 class="mt-5"><i class="fas fa-clipboard-check me-2"></i>Practical Assessments</h4>
            <div class="d-flex justify-content-end mb-2">
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssessmentModal">
                    <i class="fas fa-plus me-1"></i> Add Assessment
                </button>
            </div>
            <?php if (!empty($practical_assessments)): ?>
            <div class="mb-2">
                <input class="form-control form-control-sm" style="max-width:300px" id="paSearch" type="text" placeholder="Search..." onkeyup="filterTable('paSearch','paTable')">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="paTable">
                    <thead><tr><th>Student ID</th><th>Skill</th><th>Date</th><th>Score</th><th>Grade</th><th>Assessor</th><th>Comments</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($practical_assessments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['student_id']) ?></td>
                            <td><?= htmlspecialchars($a['skill_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($a['assessment_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($a['score']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($a['grade'] ?? '-') ?></span></td>
                            <td><?= htmlspecialchars($a['assessor'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($a['comments'] ?? '', 0, 50, '...')) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick='editAssessment(<?= json_encode($a) ?>)'><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center">No assessments recorded yet.</p>
            <?php endif; ?>
        </section>
        <?php break;
    case 'courses': ?>
        <section id="courses" class="content-section dashboard-section active" data-section="courses">
            <h2><i class="fas fa-book-open me-2"></i>Course Management</h2>
            <p class="text-muted">Manage nursing courses, curriculum, and syllabi.</p>
            <?php
            $nursing_course_list = [];
            if ($conn) {
                try {
                    $cr = $conn->query("SELECT * FROM course_assignments WHERE course_name LIKE '%Nursing%' ORDER BY course_name");
                    if ($cr) $nursing_course_list = isnm_fetch_all($cr);
                } catch (Exception $e) { error_log('head-nursing courses: ' . $e->getMessage()); }
            }
            ?>
            <?php if (!empty($nursing_course_list)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Course Code</th><th>Course Name</th><th>Instructor</th><th>Credits</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($nursing_course_list as $c): ?>
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
                <p>No nursing courses found. Courses will appear here once assigned.</p>
            </div>
            <?php endif; ?>
        </section>
        <?php break;
    case 'clinical': ?>
        <section id="clinical" class="content-section dashboard-section active" data-section="clinical">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="fas fa-clinic-medical me-2"></i>Clinical Placements</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlacementModal">
                    <i class="fas fa-plus me-1"></i> Add Placement
                </button>
            </div>

            <?php if (!empty($clinical_placements)): ?>
            <div class="d-flex justify-content-between mb-2">
                <input class="form-control form-control-sm" style="max-width:300px" id="cpSearch" type="text" placeholder="Search..." onkeyup="filterTable('cpSearch','cpTable')">
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="cpTable">
                    <thead><tr><th>Student ID</th><th>Facility</th><th>Department</th><th>Start Date</th><th>End Date</th><th>Supervisor</th><th>Hours</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($clinical_placements as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['student_id']) ?></td>
                            <td><?= htmlspecialchars($p['facility_name']) ?></td>
                            <td><?= htmlspecialchars($p['department'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['start_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['end_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['supervisor'] ?? '-') ?></td>
                            <td><?= intval($p['hours_completed'] ?? 0) ?></td>
                            <td><span class="badge bg-<?= ($p['status'] ?? '') === 'Active' ? 'success' : (($p['status'] ?? '') === 'Completed' ? 'primary' : 'secondary') ?>"><?= htmlspecialchars($p['status'] ?? 'Active') ?></span></td>
                            <td><?= htmlspecialchars(mb_strimwidth($p['notes'] ?? '', 0, 40, '...')) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editPlacement(<?= json_encode($p) ?>)'><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this placement?')">
                                    <input type="hidden" name="action" value="delete_placement">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center">No clinical placements found. Add one to get started.</p>
            <?php endif; ?>
        </section>
        <?php break;
    case 'timetable': ?>
        <section id="timetable" class="content-section dashboard-section active" data-section="timetable">
            <h2><i class="fas fa-calendar-week me-2"></i>Timetable</h2>
            <?php if (file_exists(__DIR__ . '/timetable.php')): ?>
                <?php include __DIR__ . '/timetable.php'; ?>
            <?php else: ?>
            <p class="text-muted">Nursing department timetable will appear here.</p>
            <?php endif; ?>
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
                <input class="form-control form-control-sm" style="max-width:300px" id="staffSearch" type="text" placeholder="Search..." onkeyup="filterTable('staffSearch','staffTable')">
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="staffTable">
                    <thead><tr><th>Name</th><th>Position</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                        $staff_list = [];
                        if ($conn) {
                            $sr = $conn->query("SELECT id, full_name, position, email, phone, status FROM staff WHERE department LIKE '%Nursing%' ORDER BY full_name");
                            if ($sr) $staff_list = isnm_fetch_all($sr);
                        }
                        if (empty($staff_list)): ?>
                        <tr><td colspan="6" class="text-center text-muted">No nursing staff found</td></tr>
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
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add Nursing Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
        <?php header('Location: head-nursing.php?section=overview'); exit; ?>
        <?php break;
endswitch; ?>

</div>

<!-- â•â•â• ADD STUDENT MODAL â•â•â• -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_student">
                <?php csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add Nursing Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student ID <span class="text-danger">*</span></label>
                        <input type="text" name="student_id" class="form-control" required placeholder="e.g. NUR-2024-001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="student_name" class="form-control" required placeholder="e.g. Jane Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program <span class="text-danger">*</span></label>
                        <select name="program" class="form-select" required>
                            <option value="">Select Program</option>
                            <option value="Bachelor of Science in Nursing">Bachelor of Science in Nursing</option>
                            <option value="Diploma in Nursing">Diploma in Nursing</option>
                            <option value="Registered Nursing">Registered Nursing</option>
                            <option value="Midwifery">Midwifery</option>
                            <option value="Clinical Nursing">Clinical Nursing</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year of Study <span class="text-danger">*</span></label>
                        <select name="year_of_study" class="form-select" required>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                            <option value="5">Year 5</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- â•â•â• EDIT STUDENT MODAL â•â•â• -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_student">
                <?php csrfField(); ?>
                <input type="hidden" name="id" id="edit_student_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Nursing Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="student_name" id="edit_student_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program <span class="text-danger">*</span></label>
                        <select name="program" id="edit_student_program" class="form-select" required>
                            <option value="">Select Program</option>
                            <option value="Bachelor of Science in Nursing">Bachelor of Science in Nursing</option>
                            <option value="Diploma in Nursing">Diploma in Nursing</option>
                            <option value="Registered Nursing">Registered Nursing</option>
                            <option value="Midwifery">Midwifery</option>
                            <option value="Clinical Nursing">Clinical Nursing</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year of Study</label>
                        <select name="year_of_study" id="edit_student_year" class="form-select">
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                            <option value="5">Year 5</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_student_status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Graduated">Graduated</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- â•â•â• ADD PLACEMENT MODAL â•â•â• -->
<div class="modal fade" id="addPlacementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_placement">
                <?php csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-hospital me-2"></i>Add Clinical Placement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student ID <span class="text-danger">*</span></label>
                        <input type="text" name="student_id" class="form-control" required placeholder="e.g. NUR-2024-001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Facility Name <span class="text-danger">*</span></label>
                        <input type="text" name="facility_name" class="form-control" required placeholder="e.g. National Hospital">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control" placeholder="e.g. Maternity Ward">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supervisor</label>
                        <input type="text" name="supervisor" class="form-control" placeholder="e.g. Dr. Smith">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Placement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- â•â•â• EDIT PLACEMENT MODAL â•â•â• -->
<div class="modal fade" id="editPlacementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_placement">
                <?php csrfField(); ?>
                <input type="hidden" name="id" id="edit_placement_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-hospital me-2"></i>Edit Clinical Placement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Hours Completed</label>
                        <input type="number" name="hours_completed" id="edit_placement_hours" class="form-control" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_placement_status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Deferred">Deferred</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="edit_placement_notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Placement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- â•â•â• ADD ASSESSMENT MODAL â•â•â• -->
<div class="modal fade" id="addAssessmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_assessment">
                <?php csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Add Practical Assessment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student ID <span class="text-danger">*</span></label>
                        <input type="text" name="student_id" class="form-control" required placeholder="e.g. NUR-2024-001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Skill <span class="text-danger">*</span></label>
                        <select name="skill_id" class="form-select" required>
                            <option value="">Select Skill</option>
                            <?php foreach ($skills_list as $sk): ?>
                            <option value="<?= $sk['id'] ?>"><?= htmlspecialchars($sk['skill_name']) ?> (<?= htmlspecialchars($sk['category'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assessment Date</label>
                        <input type="date" name="assessment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Score</label>
                            <input type="number" name="score" class="form-control" step="0.1" min="0" max="100" placeholder="e.g. 85">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Grade</label>
                            <select name="grade" class="form-select">
                                <option value="">Select Grade</option>
                                <option value="A">A - Excellent</option>
                                <option value="B">B - Good</option>
                                <option value="C">C - Average</option>
                                <option value="D">D - Below Average</option>
                                <option value="F">F - Fail</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assessor</label>
                        <input type="text" name="assessor" class="form-control" placeholder="e.g. Dr. Johnson">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comments</label>
                        <textarea name="comments" class="form-control" rows="3" placeholder="Assessment comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Assessment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- â•â•â• EDIT ASSESSMENT MODAL â•â•â• -->
<div class="modal fade" id="editAssessmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_assessment">
                <?php csrfField(); ?>
                <input type="hidden" name="id" id="edit_assessment_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Edit Assessment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Score</label>
                            <input type="number" name="score" id="edit_assessment_score" class="form-control" step="0.1" min="0" max="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Grade</label>
                            <select name="grade" id="edit_assessment_grade" class="form-select">
                                <option value="">Select Grade</option>
                                <option value="A">A - Excellent</option>
                                <option value="B">B - Good</option>
                                <option value="C">C - Average</option>
                                <option value="D">D - Below Average</option>
                                <option value="F">F - Fail</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assessor</label>
                        <input type="text" name="assessor" id="edit_assessment_assessor" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comments</label>
                        <textarea name="comments" id="edit_assessment_comments" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Assessment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- â•â•â• AJAX MODULE LOADING â•â•â• -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
function editStudent(s) {
    document.getElementById('edit_student_id').value = s.id;
    document.getElementById('edit_student_name').value = s.student_name;
    document.getElementById('edit_student_program').value = s.program || '';
    document.getElementById('edit_student_year').value = s.year_of_study || 1;
    document.getElementById('edit_student_status').value = s.status || 'Active';
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}
function editPlacement(p) {
    document.getElementById('edit_placement_id').value = p.id;
    document.getElementById('edit_placement_hours').value = p.hours_completed || 0;
    document.getElementById('edit_placement_status').value = p.status || 'Active';
    document.getElementById('edit_placement_notes').value = p.notes || '';
    new bootstrap.Modal(document.getElementById('editPlacementModal')).show();
}
function editAssessment(a) {
    document.getElementById('edit_assessment_id').value = a.id;
    document.getElementById('edit_assessment_score').value = a.score || '';
    document.getElementById('edit_assessment_grade').value = a.grade || '';
    document.getElementById('edit_assessment_assessor').value = a.assessor || '';
    document.getElementById('edit_assessment_comments').value = a.comments || '';
    new bootstrap.Modal(document.getElementById('editAssessmentModal')).show();
}
(function(){
    var contentArea = document.querySelector('.nurs-content');
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
            fetch('head-nursing.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.nurs-content');
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

    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                var sidebar = document.querySelector('.isnm-sidebar');
                if (sidebar) sidebar.classList.remove('open', 'mobile-show');
            }
        });
    });
})();

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
