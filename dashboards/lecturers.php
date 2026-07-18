<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';

$ctx = bootstrapStaffDashboard(['lecturer']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$students_db_name = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

if (stripos($userRole, 'Senior') !== false) {
    session_write_close();
    header('Location: senior-lecturers.php');
    exit();
}

$studentsConn = $ctx['students'];

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';

// User data already available from bootstrapStaffDashboard session

// â”€â”€ POST handlers â”€â”€
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid security token.']);
        exit;
    }
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // â”€â”€ Add assessment â”€â”€
    if ($action === 'add_assessment' && $conn) {
        $stmt = $conn->prepare("INSERT INTO teaching_assessments (lecturer_id, student_id, course_name, assessment_type, title, total_marks, marks_obtained, assessment_date, comments) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iisssiiis",
            $user_id,
            $_POST['student_id'],
            $_POST['course_name'],
            $_POST['assessment_type'],
            $_POST['title'],
            $_POST['total_marks'],
            $_POST['marks_obtained'],
            $_POST['assessment_date'],
            $_POST['comments']
        );
        $ok = $stmt->execute(); if (!$ok) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Assessment added']);
        $stmt->close();
        exit;
    }

    // â”€â”€ Update assessment â”€â”€
    if ($action === 'update_assessment' && $conn) {
        $stmt = $conn->prepare("UPDATE teaching_assessments SET course_name=?, assessment_type=?, title=?, total_marks=?, marks_obtained=?, assessment_date=?, comments=? WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("sssiiisii",
            $_POST['course_name'],
            $_POST['assessment_type'],
            $_POST['title'],
            $_POST['total_marks'],
            $_POST['marks_obtained'],
            $_POST['assessment_date'],
            $_POST['comments'],
            $_POST['assessment_id'],
            $user_id
        );
        $ok = $stmt->execute(); if (!$ok) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Assessment updated']);
        $stmt->close();
        exit;
    }

    // â”€â”€ Delete assessment â”€â”€
    if ($action === 'delete_assessment' && $conn) {
        $stmt = $conn->prepare("DELETE FROM teaching_assessments WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("ii", $_POST['assessment_id'], $user_id);
        $ok = $stmt->execute(); if (!$ok) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Assessment deleted']);
        $stmt->close();
        exit;
    }

    // â”€â”€ Add resource â”€â”€
    if ($action === 'add_resource' && $conn) {
        $stmt = $conn->prepare("INSERT INTO teaching_resources (lecturer_id, title, resource_type, file_path, url, description, course_name) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss",
            $user_id,
            $_POST['title'],
            $_POST['resource_type'],
            $_POST['file_path'],
            $_POST['url'],
            $_POST['description'],
            $_POST['course_name']
        );
        $ok = $stmt->execute(); if (!$ok) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Resource added']);
        $stmt->close();
        exit;
    }

    // â”€â”€ Delete resource â”€â”€
    if ($action === 'delete_resource' && $conn) {
        $stmt = $conn->prepare("DELETE FROM teaching_resources WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("ii", $_POST['resource_id'], $user_id);
        $ok = $stmt->execute(); if (!$ok) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Resource deleted']);
        $stmt->close();
        exit;
    }

    // â”€â”€ Add announcement â”€â”€
    if ($action === 'add_announcement' && $conn) {
        $stmt = $conn->prepare("INSERT INTO teaching_announcements (lecturer_id, title, content, target_audience, is_published) VALUES (?,?,?,?,?)");
        $stmt->bind_param("isssi",
            $user_id,
            $_POST['title'],
            $_POST['content'],
            $_POST['target_audience'],
            $_POST['is_published']
        );
        $ok = $stmt->execute(); if (!$ok) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Announcement added']);
        $stmt->close();
        exit;
        }    // courseMaterials - store as teaching_resource
    if ($action === 'add_course_material' && $conn) {
        $stmt = $conn->prepare("INSERT INTO teaching_resources (lecturer_id, title, resource_type, description) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $user_id, $_POST['title'], $_POST['file_type'], $_POST['description']);
        $ok = $stmt->execute(); if (!$ok) { error_log('material: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Material added']); $stmt->close(); exit;
    }
    // syllabus - store in course_syllabi
    if ($action === 'add_syllabus' && $conn) {
        $stmt = $conn->prepare("INSERT INTO course_syllabi (lecturer_id, course_id, course_name, semester, topics, learning_outcomes) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssss", $user_id, $_POST['course_id'], $_POST['course_name'], $_POST['semester'], $_POST['topics'], $_POST['learning_outcomes']);
        $ok = $stmt->execute(); if (!$ok) { error_log('syllabus: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Syllabus added']); $stmt->close(); exit;
    }
    // lessonPlan - store in lesson_plans
    if ($action === 'add_lesson_plan' && $conn) {
        $stmt = $conn->prepare("INSERT INTO lesson_plans (lecturer_id, course_id, week_number, topic, objectives, activities) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isisss", $user_id, $_POST['course_id'], $_POST['week_number'], $_POST['topic'], $_POST['objectives'], $_POST['activities']);
        $ok = $stmt->execute(); if (!$ok) { error_log('lesson_plan: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Lesson plan added']); $stmt->close(); exit;
    }
    // courseEvaluation - store in course_evaluations
    if ($action === 'add_evaluation' && $conn) {
        $stmt = $conn->prepare("INSERT INTO course_evaluations (lecturer_id, course_id, course_name, semester, questions, feedback) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssss", $user_id, $_POST['course_id'], $_POST['course_c_name'], $_POST['semester'], $_POST['questions'], $_POST['feedback']);
        $ok = $stmt->execute(); if (!$ok) { error_log('evaluation: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Evaluation added']); $stmt->close(); exit;
    }
    // addLecture - store in lecture_schedule
    if ($action === 'add_lecture' && $conn) {
        $stmt = $conn->prepare("INSERT INTO lecture_schedule (lecturer_id, course_id, topic, lecture_date, start_time, end_time, venue, status) VALUES (?,?,?,?,?,?,?,'scheduled')");
        $stmt->bind_param("issssss", $user_id, $_POST['course_id'], $_POST['topic'], $_POST['lecture_date'], $_POST['start_time'], $_POST['end_time'], $_POST['venue']);
        $ok = $stmt->execute(); if (!$ok) { error_log('lecture: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Lecture added']); $stmt->close(); exit;
    }
    // rescheduleLecture - update lecture_schedule
    if ($action === 'reschedule_lecture' && $conn) {
        $stmt = $conn->prepare("UPDATE lecture_schedule SET lecture_date=?, start_time=?, end_time=?, reason=? WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("ssssii", $_POST['new_date'], $_POST['start_time'], $_POST['end_time'], $_POST['reason'], $_POST['lecture_id'], $user_id);
        $ok = $stmt->execute(); if (!$ok) { error_log('reschedule: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Lecture rescheduled']); $stmt->close(); exit;
    }
    // cancelLecture - update lecture_schedule
    if ($action === 'cancel_lecture' && $conn) {
        $stmt = $conn->prepare("UPDATE lecture_schedule SET status='cancelled', reason=? WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("sii", $_POST['reason'], $_POST['lecture_id'], $user_id);
        $ok = $stmt->execute(); if (!$ok) { error_log('cancel: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Lecture cancelled']); $stmt->close(); exit;
    }
    // attendance - store in student_attendance
    if ($action === 'add_attendance' && $conn) {
        $stmt = $conn->prepare("INSERT INTO student_attendance (course_id, student_id, date, status, notes) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssiss", $_POST['course_name'], $_POST['student_id'], $_POST['date'], $_POST['status'], $_POST['notes']);
        $ok = $stmt->execute(); if (!$ok) { error_log('attendance: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'error' => $stmt->error ?? null]); $stmt->close(); exit;
    }
    // studentCounseling - store in lecturer_counseling
    if ($action === 'add_counseling' && $conn) {
        $stmt = $conn->prepare("INSERT INTO lecturer_counseling (lecturer_id, student_id, concern, action_taken, follow_up) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iisss", $user_id, $_POST['student_id'], $_POST['concern'], $_POST['action_taken'], $_POST['follow_up']);
        $ok = $stmt->execute(); if (!$ok) { error_log('counseling: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Counseling record added']); $stmt->close(); exit;
    }
    // gradeSubmission - store in academic_records
    if ($action === 'submit_grade' && $conn) {
        $stmt = $conn->prepare("INSERT INTO academic_records (lecturer_id, student_id, course_id, assessment_type, marks, grade) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iissss", $user_id, $_POST['student_id'], $_POST['course_name'], $_POST['assessment_type'], $_POST['score'], $_POST['grade']);
        $ok = $stmt->execute(); if (!$ok) { error_log('grade: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Grade submitted']); $stmt->close(); exit;
    }
}

// Ensure needed tables exist
$create_table_sql = [
    "CREATE TABLE IF NOT EXISTS course_syllabi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        course_id VARCHAR(50) DEFAULT NULL,
        course_name VARCHAR(255) DEFAULT NULL,
        semester VARCHAR(100) DEFAULT NULL,
        topics TEXT DEFAULT NULL,
        learning_outcomes TEXT CLEAR DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS course_evaluations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        course_id VARCHAR(50) course_id DEFAULT NULL,
        course_name VARCHAR(255) DEFAULT NULL,
        semester VARCHAR(100) DEFAULT NULL,
        questions TEXT DEFAULT NULL,
        feedback TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS lecture_schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        course_id VARCHAR(50) DEFAULT NULL,
        topic VARCHAR(255) DEFAULT NULL,
        lecture_date DATE DEFAULT NULL,
        start_time TIME DEFAULT NULL,
        end_time TIME DEFAULT TIME,
        venue VARCHAR(255) DEFAULT NULL,
        status VARCHAR(50) DEFAULT ''scheduled'',
        reason TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS lecturer_counseling (
        id INT s AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        student_id INT DEFAULT NULL,
        concern TEXT DEFAULT NULL,
        action_outcome TEXT DEFAULT NULL,
        follow_up TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];
foreach ($create_table_sql as $sql) {
    if ($conn) {
        try {
            $conn->query($sql);
        } catch (Exception $e) {
            error_log('lecturers table create: ' . $e->getMessage());
        }
    }
}

// Set statistics from database// Set statistics from database
$total_students = 0;
$total_staff = 0;
$total_applications = 0;
$active_programs = 0;
$assigned_courses = 0;
$lectures_this_week = 0;
$pending_grades = 0;
$total_students_taught = 0;
$average_grade = 0;

try {
    if ($studentsConn) {
        $result = $studentsConn->query("SELECT COUNT(*) as cnt FROM students");
        if ($result) $total_students = (int)$result->fetch_assoc()['cnt'];
        $app_result = $studentsConn->query("SELECT COUNT(*) as cnt FROM applications");
        if ($app_result) $total_applications = (int)$app_result->fetch_assoc()['cnt'];
    }
    $staff_result = $conn->query("SELECT COUNT(*) as cnt FROM staff");
    if ($staff_result) $total_staff = (int)$staff_result->fetch_assoc()['cnt'];
    $prog_result = $conn->query("SELECT COUNT(*) as cnt FROM academic_programs WHERE status='Active'");
    if ($prog_result) $active_programs = (int)$prog_result->fetch_assoc()['cnt'];
    $ca_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM course_assignments WHERE lecturer_id=? AND status='Active'");
    if ($ca_stmt) { $ca_stmt->bind_param('i', $user_id); if ($ca_stmt->execute()) { $assigned_courses = (int)$ca_stmt->get_result()->fetch_assoc()['cnt']; } $ca_stmt->close(); }
    $tt_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM academic_timetable WHERE lecturer_id=? AND day_of_week=DAYNAME(CURDATE()) AND timetable_status='Published'");
    if ($tt_stmt) { $tt_stmt->bind_param('i', $user_id); if ($tt_stmt->execute()) { $lectures_this_week = (int)$tt_stmt->get_result()->fetch_assoc()['cnt']; } $tt_stmt->close(); }
    $ar_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM academic_records WHERE lecturer_id=? AND grade IS NULL");
    if ($ar_stmt) { $ar_stmt->bind_param('i', $user_id); if ($ar_stmt->execute()) { $pending_grades = (int)$ar_stmt->get_result()->fetch_assoc()['cnt']; } $ar_stmt->close(); }
    $stu_stmt = $conn->prepare("SELECT COUNT(DISTINCT student_id) as cnt FROM academic_records WHERE lecturer_id=?");
    if ($stu_stmt) { $stu_stmt->bind_param('i', $user_id); if ($stu_stmt->execute()) { $total_students_taught = (int)$stu_stmt->get_result()->fetch_assoc()['cnt']; } $stu_stmt->close(); }
    $avg_stmt = $conn->prepare("SELECT AVG(marks) as avg FROM academic_records WHERE lecturer_id=? AND marks IS NOT NULL");
    if ($avg_stmt) { $avg_stmt->bind_param('i', $user_id); if ($avg_stmt->execute()) { $average_grade = (int)round($avg_stmt->get_result()->fetch_assoc()['avg'] ?? 0); } $avg_stmt->close(); }
} catch (Exception $e) {
    error_log('lecturers stats: ' . $e->getMessage());
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
    } catch (Exception $e) { error_log('lecturers context: ' . $e->getMessage()); }
}
 
// Determine active section from ?page= parameter
$pageToSection = [
    'home'           => 'overview',
    'overview'       => 'overview',
    'my-courses'     => 'courses',
    'timetable'      => 'schedule',
    'attendance'     => 'attendance',
    'cat-marks'      => 'cat-marks',
    'exam-marks'     => 'exam-marks',
    'materials'      => 'resources',
    'results'        => 'results',
    'reports'        => 'reports',
    'lesson-plans'   => 'lesson-plans',
    'assignments'    => 'assignments',
    'students'       => 'students',
    'assessments'    => 'assessments',
    'grades'         => 'grades',
    'communications' => 'communications',
    'student-records'=> 'student-records',
];
$requestedPage = $_GET['page'] ?? 'home';
$section = $pageToSection[$requestedPage] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>.lec-content{margin-left:270px;padding:24px;min-height:100vh}@media(max-width:768px){.lec-content{margin-left:0!important;padding:12px!important}}</style>
</head>
<body class="ent-layout">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
    
    
    <div class="lec-content">
      <div class="content-area">
                <?php include_once __DIR__ . '/../views/student_search_component.php'; ?>
                <!-- Teaching Overview -->
                <section id="overview" class="content-section dashboard-section section-card<?= $section==='overview'?' active':'' ?>" data-section="overview">
                    <h2>Teaching Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $assigned_courses; ?></h3>
                                <p>Assigned Courses</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_students; ?></h3>
                                <p>Total Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $lectures_this_week; ?></h3>
                                <p>Lectures This Week</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clipboard"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_grades; ?></h3>
                                <p>Pending Grades</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- My Courses -->
                <section id="courses" class="content-section dashboard-section section-card<?= $section==='courses'?' active':'' ?>" data-section="courses">
                    <h2>My Courses</h2>
                    <div class="course-actions">
                        <button class="btn btn-primary" onclick="openModal('courseMaterials')">
                            <i class="fas fa-folder"></i> Course Materials
                        </button>
                        <button class="btn btn-success" onclick="openModal('syllabus')">
                            <i class="fas fa-list-alt"></i> Syllabus
                        </button>
                        <button class="btn btn-info" onclick="openModal('lessonPlan')">
                            <i class="fas fa-calendar-alt"></i> Lesson Plan
                        </button>
                        <button class="btn btn-warning" onclick="openModal('courseEvaluation')">
                            <i class="fas fa-chart-line"></i> Course Evaluation
                        </button>
                    </div>
                    
                    <div class="courses-overview">
                        <h3>Current Course Assignments</h3>
                        <div class="courses-grid">
                            <div class="course-card">
                                <div class="course-header">
                                    <h4>Basic Nursing Skills</h4>
                                    <span class="course-code">NUR-102</span>
                                </div>
                                <div class="course-details">
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>25</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Credits:</span>
                                        <strong>3</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Level:</span>
                                        <strong>Year 1</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Progress:</span>
                                        <strong>70%</strong>
                                    </div>
                                </div>
                                <div class="course-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Manage</button>
                                </div>
                            </div>
                            
                            <div class="course-card">
                                <div class="course-header">
                                    <h4>Anatomy & Physiology</h4>
                                    <span class="course-code">MED-101</span>
                                </div>
                                <div class="course-details">
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>30</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Credits:</span>
                                        <strong>3</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Level:</span>
                                        <strong>Year 1</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Progress:</span>
                                        <strong>55%</strong>
                                    </div>
                                </div>
                                <div class="course-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Manage</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Teaching Schedule -->
                <section id="schedule" class="content-section dashboard-section section-card<?= $section==='schedule'?' active':'' ?>" data-section="schedule">
                    <h2>Teaching Schedule</h2>
                    <div class="schedule-actions">
                        <button class="btn btn-primary" onclick="openModal('addLecture')">
                            <i class="fas fa-plus"></i> Add Lecture
                        </button>
                        <button class="btn btn-success" onclick="openModal('weeklySchedule')">
                            <i class="fas fa-calendar-week"></i> Weekly Schedule
                        </button>
                        <button class="btn btn-info" onclick="openModal('rescheduleLecture')">
                            <i class="fas fa-exchange-alt"></i> Reschedule
                        </button>
                        <button class="btn btn-warning" onclick="openModal('cancelLecture')">
                            <i class="fas fa-times"></i> Cancel Lecture
                        </button>
                    </div>
                    
                    <div class="schedule-overview">
                        <h3>Today's Schedule</h3>
                        <div class="schedule-list">
                            <div class="schedule-item">
                                <div class="schedule-header">
                                    <h4>Basic Nursing Skills</h4>
                                    <span class="schedule-time">10:00 AM to 12:00 PM</span>
                                </div>
                                <div class="schedule-details">
                                    <div class="detail">
                                        <span>Room:</span>
                                        <strong>Classroom B</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Topic:</span>
                                        <strong>Vital Signs Measurement</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>25 enrolled</strong>
                                    </div>
                                </div>
                                <div class="schedule-actions">
                                    <button class="btn btn-sm btn-outline-primary">Start Class</button>
                                    <button class="btn btn-sm btn-outline-info">Take Attendance</button>
                                </div>
                            </div>
                            
                            <div class="schedule-item">
                                <div class="schedule-header">
                                    <h4>Anatomy & Physiology</h4>
                                    <span class="schedule-time">2:00 PM to 4:00 PM</span>
                                </div>
                                <div class="schedule-details">
                                    <div class="detail">
                                        <span>Room:</span>
                                        <strong>Laboratory 2</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Topic:</span>
                                        <strong>Circulatory System</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>30 enrolled</strong>
                                    </div>
                                </div>
                                <div class="schedule-actions">
                                    <button class="btn btn-sm btn-outline-primary">Start Class</button>
                                    <button class="btn btn-sm btn-outline-info">Take Attendance</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Management -->
                <section id="students" class="content-section dashboard-section section-card<?= $section==='students'?' active':'' ?>" data-section="students">
                    <h2>Student Management</h2>
                    <div class="student-actions">
                        <button class="btn btn-primary" onclick="openModal('studentList')">
                            <i class="fas fa-list"></i> Student List
                        </button>
                        <button class="btn btn-success" onclick="openModal('attendance')">
                            <i class="fas fa-user-check"></i> Attendance
                        </button>
                        <button class="btn btn-info" onclick="openModal('studentProgress')">
                            <i class="fas fa-chart-line"></i> Student Progress
                        </button>
                        <button class="btn btn-warning" onclick="openModal('studentCounseling')">
                            <i class="fas fa-comments"></i> Student Counseling
                        </button>
                    </div>
                    
                    <div class="student-overview">
                        <h3>Student Performance Summary</h3>
                        <div class="performance-stats">
                            <div class="perf-stat">
                                <h4>Class Average</h4>
                                <div class="avg-grade">B</div>
                                <small>Overall performance</small>
                            </div>
                            <div class="perf-stat">
                                <h4>Attendance Rate</h4>
                                <div class="attendance-rate">90%</div>
                                <small>Department average</small>
                            </div>
                            <div class="perf-stat">
                                <h4>Assignment Completion</h4>
                                <div class="completion-rate">85%</div>
                                <small>On time submission</small>
                            </div>
                            <div class="perf-stat">
                                <h4>At Risk Students</h4>
                                <div class="at-risk-count">3</div>
                                <small>Need attention</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="students-table">
                        <h3>My Students</h3>
                        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchCUSE" type="text" placeholder="Search..." onkeyup="filterTable('srchCUSE','tblCUSE')"></div>
<div class="table-responsive">
                            <table id="tblCUSE" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Course</th>
                                        <th>Attendance</th>
                                        <th>Current Grade</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>STU-2024-001</td>
                                        <td>Alice Student</td>
                                        <td>Basic Nursing Skills</td>
                                        <td>92%</td>
                                        <td>B+</td>
                                        <td><span class="status-badge active">Good</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-info">Message</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>STU-2024-002</td>
                                        <td>Bob Student</td>
                                        <td>Basic Nursing Skills</td>
                                        <td>85%</td>
                                        <td>B</td>
                                        <td><span class="status-badge active">Good</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-info">Message</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Assessments -->
                <section id="assessments" class="content-section dashboard-section section-card<?= $section==='assessments'?' active':'' ?>" data-section="assessments">
                    <h2>Assessment Management</h2>
                    <div class="assessment-actions">
                        <button class="btn btn-primary" onclick="openModal('addAssessment')">
                            <i class="fas fa-plus"></i> Add Assessment
                        </button>
                    </div>

                    <div class="assessment-overview">
                        <h3>Teaching Assessments</h3>
                        <?php
                        $assessments = [];
                        if ($conn) {
                            $as2_stmt = $conn->prepare("SELECT * FROM teaching_assessments WHERE lecturer_id=? ORDER BY assessment_date DESC");
                            if ($as2_stmt) { $as2_stmt->bind_param('i', $user_id); $ar2 = $as2_stmt->execute() ? $as2_stmt->get_result() : null; if ($ar2) { while ($r = $ar2->fetch_assoc()) $assessments[] = $r; } $as2_stmt->close(); }
                        }
                        ?>
                        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchXLPG" type="text" placeholder="Search..." onkeyup="filterTable('srchXLPG','tblXLPG')"></div>
<div class="table-responsive">
                            <table class="table table-hover" id="assessmentsTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Course</th>
                                        <th>Type</th>
                                        <th>Marks</th>
                                        <th>Obtained</th>
                                        <th>Date</th>
                                        <th>Comments</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($assessments)): ?>
                                    <tr><td colspan="8" class="text-center text-muted">No assessments found.</td></tr>
                                    <?php else: foreach ($assessments as $a): ?>
                                    <tr data-id="<?= (int)$a['id'] ?>">
                                        <td><?= htmlspecialchars($a['title']) ?></td>
                                        <td><?= htmlspecialchars($a['course_name']) ?></td>
                                        <td><?= htmlspecialchars($a['assessment_type']) ?></td>
                                        <td><?= (int)$a['total_marks'] ?></td>
                                        <td><?= (int)$a['marks_obtained'] ?></td>
                                        <td><?= htmlspecialchars($a['assessment_date']) ?></td>
                                        <td><?= htmlspecialchars($a['comments'] ?? '') ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editAssessment(this)">Edit</button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAssessment(<?= (int)$a['id'] ?>)">Delete</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Grade Management -->
                <section id="grades" class="content-section dashboard-section section-card<?= $section==='grades'?' active':'' ?>" data-section="grades">
                    <h2>Grade Management</h2>
                    <div class="grade-actions">
                        <button class="btn btn-primary" onclick="openModal('gradebook')">
                            <i class="fas fa-book"></i> Gradebook
                        </button>
                        <button class="btn btn-success" onclick="openModal('gradeSubmission')">
                            <i class="fas fa-upload"></i> Submit Grades
                        </button>
                        <button class="btn btn-info" onclick="openModal('gradeAnalysis')">
                            <i class="fas fa-chart-line"></i> Grade Analysis
                        </button>
                        <button class="btn btn-warning" onclick="openModal('gradeAppeals')">
                            <i class="fas fa-gavel"></i> Grade Appeals
                        </button>
                    </div>
                    
                    <div class="grade-overview">
                        <h3>Grade Summary</h3>
                        <div class="grade-distribution">
                            <div class="grade-category">
                                <h4>A Range (85 , 100%)</h4>
                                <div class="grade-count">8 students</div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: 25%">25%</div>
                                </div>
                            </div>
                            <div class="grade-category">
                                <h4>B Range (70 , 84%)</h4>
                                <div class="grade-count">18 students</div>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: 56%">56%</div>
                                </div>
                            </div>
                            <div class="grade-category">
                                <h4>C Range (55 , 69%)</h4>
                                <div class="grade-count">4 students</div>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: 13%">13%</div>
                                </div>
                            </div>
                            <div class="grade-category">
                                <h4>D/F Range (Below 55%)</h4>
                                <div class="grade-count">2 students</div>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" style="width: 6%">6%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Teaching Resources -->
                <section id="resources" class="content-section dashboard-section section-card<?= $section==='resources'?' active':'' ?>" data-section="resources">
                    <h2>Teaching Resources</h2>
                    <div class="resource-actions">
                        <button class="btn btn-primary" onclick="openModal('addResource')">
                            <i class="fas fa-upload"></i> Add Resource
                        </button>
                    </div>

                    <div class="resources-overview">
                        <h3>My Teaching Resources</h3>
                        <?php
                        $resources = [];
                        if ($conn) {
                            $res_stmt = $conn->prepare("SELECT * FROM teaching_resources WHERE lecturer_id=? ORDER BY id DESC");
                            if ($res_stmt) { $res_stmt->bind_param('i', $user_id); $rr = $res_stmt->execute() ? $res_stmt->get_result() : null; if ($rr) { while ($r = $rr->fetch_assoc()) $resources[] = $r; } $res_stmt->close(); }
                        }
                        ?>
                        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchTLNY" type="text" placeholder="Search..." onkeyup="filterTable('srchTLNY','tblTLNY')"></div>
<div class="table-responsive">
                            <table class="table table-hover" id="resourcesTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Course</th>
                                        <th>URL</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($resources)): ?>
                                    <tr><td colspan="6" class="text-center text-muted">No resources found.</td></tr>
                                    <?php else: foreach ($resources as $res): ?>
                                    <tr data-id="<?= (int)$res['id'] ?>">
                                        <td><?= htmlspecialchars($res['title']) ?></td>
                                        <td><?= htmlspecialchars($res['resource_type']) ?></td>
                                        <td><?= htmlspecialchars($res['course_name']) ?></td>
                                        <td><?= $res['url'] ? '<a href="'.htmlspecialchars($res['url']).'" target="_blank">Link</a>' : '-' ?></td>
                                        <td><?= htmlspecialchars($res['description'] ?? '') ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteResource(<?= (int)$res['id'] ?>)">Delete</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Announcements -->
                <section id="communications" class="content-section dashboard-section section-card<?= $section==='communications'?' active':'' ?>" data-section="communications">
                    <h2>Teaching Announcements</h2>
                    <div class="communication-actions">
                        <button class="btn btn-primary" onclick="openModal('addAnnouncement')">
                            <i class="fas fa-bullhorn"></i> Add Announcement
                        </button>
                    </div>

                    <div class="communications-overview">
                        <h3>My Announcements</h3>
                        <?php
                        $announcements = [];
                        if ($conn) {
                            $an_stmt = $conn->prepare("SELECT * FROM teaching_announcements WHERE lecturer_id=? ORDER BY id DESC");
                            if ($an_stmt) { $an_stmt->bind_param('i', $user_id); $anr = $an_stmt->execute() ? $an_stmt->get_result() : null; if ($anr) { while ($r = $anr->fetch_assoc()) $announcements[] = $r; } $an_stmt->close(); }
                        }
                        ?>
                        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchFUGV" type="text" placeholder="Search..." onkeyup="filterTable('srchFUGV','tblFUGV')"></div>
<div class="table-responsive">
                            <table class="table table-hover" id="announcementsTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Content</th>
                                        <th>Target Audience</th>
                                        <th>Published</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($announcements)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No announcements found.</td></tr>
                                    <?php else: foreach ($announcements as $an): ?>
                                    <tr data-id="<?= (int)$an['id'] ?>">
                                        <td><?= htmlspecialchars($an['title']) ?></td>
                                        <td><?= htmlspecialchars($an['content']) ?></td>
                                        <td><?= htmlspecialchars($an['target_audience']) ?></td>
                                        <td><?= $an['is_published'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                        <td>-</td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Teaching Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon'] ?? 'check-circle'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['action'] ?? $activity['activity'] ?? 'Activity'; ?></strong></p>
                                <small><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Student Records -->
    <div class="content-area" style="padding-top:0">
        <section id="student-records" class="content-section dashboard-section section-card<?= $section==='student-records'?' active':'' ?>" data-section="student-records">
            <?php renderStudentSetViewer($studentsConn, [
                'title' => 'Student Records',
                'icon' => 'fa-user-graduate',
                'show_all' => true,
                'per_page' => 50,
                'show_statement_link' => false
            ]); ?>
        </section>
    </div>

    <!-- Attendance -->
    <section id="attendance" class="content-section dashboard-section section-card<?= $section==='attendance'?' active':'' ?>" data-section="attendance">
        <h2><i class="fas fa-calendar-check me-2"></i>Attendance Records</h2>
        <?php
        $attendanceRecords = [];
        if ($studentsConn) {
            $att_stmt = $studentsConn->prepare("SELECT sa.*, s.full_name, s.student_number FROM student_attendance sa JOIN students s ON sa.student_id=s.id WHERE sa.course_id IN (SELECT course_id FROM course_assignments WHERE lecturer_id=?) ORDER BY sa.date DESC LIMIT 20");
            if ($att_stmt) { $att_stmt->bind_param('i', $user_id); $r = $att_stmt->execute() ? $att_stmt->get_result() : null; if ($r) $attendanceRecords = $r->fetch_all(MYSQLI_ASSOC); $att_stmt->close(); }
        }
        if (empty($attendanceRecords)): ?><p class="text-muted text-center py-3">No attendance records found for your courses.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchNSIR" type="text" placeholder="Search..." onkeyup="filterTable('srchNSIR','tblNSIR')"></div>
<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>Date</th><th>Student</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($attendanceRecords as $a): ?><tr><td><?= htmlspecialchars($a['date']??'') ?></td><td><?= htmlspecialchars($a['full_name']??$a['student_number']??'') ?></td><td><span class="badge bg-<?= ($a['status']??'')==='Present'?'success':(($a['status']??'')==='Absent'?'danger':'warning') ?>"><?= htmlspecialchars($a['status']??'N/A') ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <!-- CAT Marks -->
    <section id="cat-marks" class="content-section dashboard-section section-card<?= $section==='cat-marks'?' active':'' ?>" data-section="cat-marks">
        <h2><i class="fas fa-pen me-2"></i>CAT Marks Entry</h2>
        <?php
        $catRecords = [];
        if ($conn) {
        $stmt = $conn->prepare("SELECT ar.*, c.course_name, c.course_code, s.full_name as student_name FROM academic_records ar LEFT JOIN courses c ON ar.course_id=c.id LEFT JOIN {$students_db_name}.students s ON ar.student_id=s.id WHERE ar.lecturer_id=? AND ar.assessment_type IN ('CAT','Assignment','Quiz') ORDER BY ar.created_at DESC LIMIT 20");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $catRecords = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        }
        if (empty($catRecords)): ?><p class="text-muted text-center py-3">No CAT marks recorded yet.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchPWIR" type="text" placeholder="Search..." onkeyup="filterTable('srchPWIR','tblPWIR')"></div>
<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>Student</th><th>Course</th><th>Score</th><th>Grade</th></tr></thead><tbody>
        <?php foreach ($catRecords as $c): ?><tr><td><?= htmlspecialchars($c['student_name']??$c['student_id']??'-') ?></td><td><?= htmlspecialchars($c['course_code']??$c['course_name']??'-') ?></td><td><?= htmlspecialchars($c['marks']??$c['score']??$c['total_marks']??'-') ?></td><td><?= htmlspecialchars($c['grade']??'-') ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <!-- Exam Marks -->
    <section id="exam-marks" class="content-section dashboard-section section-card<?= $section==='exam-marks'?' active':'' ?>" data-section="exam-marks">
        <h2><i class="fas fa-file-alt me-2"></i>Exam Marks</h2>
        <?php
        $examRecords = [];
        if ($conn) {
        $stmt = $conn->prepare("SELECT ar.*, c.course_name, c.course_code, s.full_name as student_name FROM academic_records ar LEFT JOIN courses c ON ar.course_id=c.id LEFT JOIN {$students_db_name}.students s ON ar.student_id=s.id WHERE ar.lecturer_id=? AND ar.assessment_type='Exam' ORDER BY ar.created_at DESC LIMIT 20");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $examRecords = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        }
        if (empty($examRecords)): ?><p class="text-muted text-center py-3">No exam marks recorded yet.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchYZQT" type="text" placeholder="Search..." onkeyup="filterTable('srchYZQT','tblYZQT')"></div>
<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>Student</th><th>Course</th><th>Score</th><th>Grade</th></tr></thead><tbody>
        <?php foreach ($examRecords as $e): ?><tr><td><?= htmlspecialchars($e['student_name']??$e['student_id']??'-') ?></td><td><?= htmlspecialchars($e['course_code']??$e['course_name']??'-') ?></td><td><?= htmlspecialchars($e['marks']??$e['score']??$e['total_marks']??'-') ?></td><td><?= htmlspecialchars($e['grade']??'-') ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <!-- Results -->
    <section id="results" class="content-section dashboard-section section-card<?= $section==='results'?' active':'' ?>" data-section="results">
        <h2><i class="fas fa-chart-bar me-2"></i>Student Results</h2>
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card"><div class="card-body text-center"><h6>Total Records</h6><h3><?= count($grade_distribution??[]) > 0 ? array_sum($grade_distribution) : 0 ?></h3></div></div></div>
            <div class="col-md-4"><div class="card border-success"><div class="card-body text-center"><h6 class="text-success">Pass Rate</h6><h3 class="text-success"><?= ($total_grades = array_sum($grade_distribution ?? [])) > 0 ? round(($grade_distribution['A']+$grade_distribution['B']+$grade_distribution['C'])/$total_grades*100) : 0 ?>%</h3></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><p class="mb-0 text-muted">View detailed results in <a href="exams-results.php">Exams & Results</a> module.</p></div></div></div>
        </div>
    </section>

    <!-- Reports -->
    <section id="reports" class="content-section dashboard-section section-card<?= $section==='reports'?' active':'' ?>" data-section="reports">
        <h2><i class="fas fa-file-invoice me-2"></i>Reports Center</h2>
        <div class="row g-3">
            <div class="col-md-6"><div class="card"><div class="card-body"><h5>Teaching Summary</h5><p>Courses: <?= $assigned_courses ?> | Students: <?= $total_students ?> | Pending Grades: <?= $pending_grades ?></p><a href="?page=overview" class="btn btn-sm btn-outline-primary">View Overview</a></div></div></div>
            <div class="col-md-6"><div class="card"><div class="card-body"><h5>Quick Actions</h5><a href="staff_transcript_generation.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-file-pdf me-1"></i>Transcripts</a><a href="staff_receipt_printing.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-print me-1"></i>Print</a></div></div></div>
        </div>
    </section>

    <!-- Lesson Plans -->
    <section id="lesson-plans" class="content-section dashboard-section section-card<?= $section==='lesson-plans'?' active':'' ?>" data-section="lesson-plans">
        <h2><i class="fas fa-clipboard-list me-2"></i>Lesson Plans</h2>
        <?php
        $lessonPlans = [];
        if ($conn) {
            $stmt = $conn->prepare("SELECT * FROM lesson_plans WHERE lecturer_id=? ORDER BY created_at DESC LIMIT 10");
            $stmt->bind_param('i', $user_id);
            $r = $stmt->execute() ? $stmt->get_result() : null;
            if ($r) $lessonPlans = $r->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        if (empty($lessonPlans)): ?><p class="text-muted text-center py-3">No lesson plans created yet.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchMAUI" type="text" placeholder="Search..." onkeyup="filterTable('srchMAUI','tblMAUI')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Title</th><th>Course</th><th>Date</th></tr></thead><tbody>
        <?php foreach ($lessonPlans as $lp): ?><tr><td><?= htmlspecialchars($lp['title']??$lp['topic']??'-') ?></td><td><?= htmlspecialchars($lp['course_name']??$lp['course']??'-') ?></td><td><?= htmlspecialchars($lp['created_at']??'') ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <!-- Assignments -->
    <section id="assignments" class="content-section dashboard-section section-card<?= $section==='assignments'?' active':'' ?>" data-section="assignments">
        <h2><i class="fas fa-tasks me-2"></i>Assignments</h2>
        <?php
        $assignments = [];
        if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM assignments WHERE lecturer_id=? ORDER BY created_at DESC LIMIT 10");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $assignments = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        }
        if (empty($assignments)): ?><p class="text-muted text-center py-3">No assignments created yet.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchJXNP" type="text" placeholder="Search..." onkeyup="filterTable('srchJXNP','tblJXNP')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Title</th><th>Course</th><th>Due Date</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($assignments as $as): ?><tr><td><?= htmlspecialchars($as['title']??'-') ?></td><td><?= htmlspecialchars($as['course_name']??$as['course_id']??'-') ?></td><td><?= htmlspecialchars($as['due_date']??$as['deadline']??'') ?></td><td><span class="badge bg-<?= ($as['status']??'')==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($as['status']??'Active') ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <!-- Modals -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Dynamic content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modalAction">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current date/time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Section navigation - use global switchToSection for sidebar compatibility
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var sec = this.getAttribute('href').substring(1);
                if (typeof switchToSection === 'function') {
                    switchToSection(sec);
                } else {
                    document.querySelectorAll('.section-card').forEach(function(s) { s.style.display = 'none'; });
                    var t = document.getElementById(sec);
                    if (t) t.style.display = 'block';
                }
            });
        });
        // Handle hash-based deep linking from sidebar
        (function() {
            var hash = location.hash.replace('#', '');
            if (hash && document.getElementById(hash)) {
                if (typeof switchToSection === 'function') {
                    switchToSection(hash);
                }
            }
        })();
        window.addEventListener('hashchange', function() {
            var h = location.hash.replace('#', '');
            if (h && document.getElementById(h)) {
                if (typeof switchToSection === 'function') {
                    switchToSection(h);
                }
            }
        });

        // Modal functions
        var currentModalAction = '';
        var editRow = null;

        function openModal(action) {
            currentModalAction = action;
            editRow = null;
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            const modalActionBtn = document.getElementById('modalAction');
            modalActionBtn.style.display = 'inline-block';

            switch(action) {
                case 'addAssessment':
                    modalTitle.textContent = 'Add Assessment';
                    modalBody.innerHTML = `
                        <form id="assessmentForm">
                            <input type="hidden" name="action" value="add_assessment">
                            <div class="mb-3"><label class="form-label">Student ID</label><input name="student_id" type="number" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Course Name</label><input name="course_name" type="text" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Assessment Type</label>
                                <select name="assessment_type" class="form-control" required>
                                    <option value="">Select</option>
                                    <option value="Quiz">Quiz</option>
                                    <option value="Assignment">Assignment</option>
                                    <option value="Practical Test">Practical Test</option>
                                    <option value="Examination">Examination</option>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Title</label><input name="title" type="text" class="form-control" required></div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Total Marks</label><input name="total_marks" type="number" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Marks Obtained</label><input name="marks_obtained" type="number" class="form-control" value="0" required></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Assessment Date</label><input name="assessment_date" type="date" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Comments</label><textarea name="comments" class="form-control" rows="3"></textarea></div>
                        </form>`;
                    break;

                case 'editAssessment':
                    modalTitle.textContent = 'Edit Assessment';
                    modalBody.innerHTML = `
                        <form id="assessmentForm">
                            <input type="hidden" name="action" value="update_assessment">
                            <input type="hidden" name="assessment_id" id="editAssessmentId">
                            <div class="mb-3"><label class="form-label">Student ID</label><input name="student_id" type="number" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Course Name</label><input name="course_name" type="text" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Assessment Type</label>
                                <select name="assessment_type" class="form-control" required>
                                    <option value="">Select</option>
                                    <option value="Quiz">Quiz</option>
                                    <option value="Assignment">Assignment</option>
                                    <option value="Practical Test">Practical Test</option>
                                    <option value="Examination">Examination</option>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Title</label><input name="title" type="text" class="form-control" required></div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Total Marks</label><input name="total_marks" type="number" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Marks Obtained</label><input name="marks_obtained" type="number" class="form-control" required></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Assessment Date</label><input name="assessment_date" type="date" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Comments</label><textarea name="comments" class="form-control" rows="3"></textarea></div>
                        </form>`;
                    break;

                case 'addResource':
                    modalTitle.textContent = 'Add Teaching Resource';
                    modalBody.innerHTML = `
                        <form id="resourceForm">
                            <input type="hidden" name="action" value="add_resource">
                            <div class="mb-3"><label class="form-label">Title</label><input name="title" type="text" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Resource Type</label>
                                <select name="resource_type" class="form-control" required>
                                    <option value="">Select</option>
                                    <option value="PDF">PDF</option>
                                    <option value="Video">Video</option>
                                    <option value="Document">Document</option>
                                    <option value="Link">Link</option>
                                    <option value="Presentation">Presentation</option>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Course Name</label><input name="course_name" type="text" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">File Path</label><input name="file_path" type="text" class="form-control" placeholder="/uploads/file.pdf"></div>
                            <div class="mb-3"><label class="form-label">URL</label><input name="url" type="url" class="form-control" placeholder="https://..."></div>
                            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                        </form>`;
                    break;

                case 'addAnnouncement':
                    modalTitle.textContent = 'Add Announcement';
                    modalBody.innerHTML = `
                        <form id="announcementForm">
                            <input type="hidden" name="action" value="add_announcement">
                            <div class="mb-3"><label class="form-label">Title</label><input name="title" type="text" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="5" required></textarea></div>
                            <div class="mb-3"><label class="form-label">Target Audience</label>
                                <select name="target_audience" class="form-control" required>
                                    <option value="all">All Students</option>
                                    <option value="class-basic">Basic Nursing Skills</option>
                                    <option value="class-anatomy">Anatomy & Physiology</option>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Published</label>
                                <select name="is_published" class="form-control" required>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </form>`;
                    break;
                case 'courseMaterials':
                    modalTitle.textContent = 'Course Materials';
                    modalBody.innerHTML = '<form id="materialForm"><input type="hidden" name="action" value="add_course_material"><div class="mb-3"><label class="form-label">Title</label><input name="title" type="text" class="form-control"></div><div class="mb-3"><label class="form-label">File Type</label><select name="file_type" class="form-control"><option value="">Select</option><option value="PDF">PDF</option><option value="Video">Video</option></select></div><div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div></form>';
                    break;

                case 'syllabus':
                    modalTitle.textContent = 'Course Syllabus';
                    modalBody.innerHTML = '<form id="syllabusForm"><input type="hidden" name="action" value="add_syllabus"><div class="mb-3"><label class="form-label">Course ID</label><input name="course_id" class="form-control"></div><div class="mb-3"><label class="form-label">Course Name</label><input name="course_name" class="form-control"></div><div style="mb-3"><label class="form-label">Semester</label><input name="semester" class="form-control"></div><div class="mb-3"><label class="form-label">Topics</label><textarea name="topics" class="form-control" rows="3"></textarea></div><div class="mb-3"><label class="form-label">Learning Outcomes</label><textarea name="learning_outcomes" class="form-control" rows="3"></textarea></div></form>';
                    break;

                case 'lessonPlan':
                    modalTitle.textContext = 'Letson Plan';
                    modalBody.innerHTML = '<form id="lessonPlanForm"><input type="hidden" name="action" value="add_lesson_plan"><div class="md-3"><label class="form-label">Course ID</label><input name="course_id" class="form-control"></div><div class="mb-3"><label class="form-label">Week Number</label><input name="week_number" type="number" class="form-control"></div><div class="mb-3"><label class="form-label">Topic</label><input name="topic" class="form-control"></div><div class="mb-3"><label class="form-label">objectives</label><textarea name="objectives" class="form-control" rows="3"></textarea></div><div class="mb-3"><label class="form-label">Activities</label><textarea name="activities" class="form-control" rows="3"></textarea></div></form>';
                    break;

                case 'courseEvaluation':
                    modalTitle.textContent = 'Course Evalution';
                    modalBody.innerHTML = '<form id="evalForm"><input type="hidden" action="add_evaluation"><div class="mb-3"><label class="form-label">Course ID</label><input name="course_id" class="form-control"></div><div class="mb-3"><label class="form-label">Course Name</label><input name="course_name" class="form-control"></div><div class="mb-3"><label class="form-label">Semester</label><input name="semester" class="form-control"></div><div class="mb-3"><label class="form-label">Questions</label><textarea name="questions" class="form-control" rows="3"></textarea></div><div class="mb-3"><label class="form-label">Feedback</label><textarea name="feedback" class="form-control" rows="3"></textarea></div></form>';
                    break;

                case 'addLecture':
                    modalTitle.textContent = 'Add Lecture';
                    modalBody.innerHTML = '<form id="lectureForm"><input type="hidden" name="action" value="add_lecture"><div class="mb-3"><label class="form-label">Course ID</label><input name="course_id" class="form-control"></div><div class="mb-3"><label class="form-label">Topic</label><input name="topic" class="form-control"></div><div class="mb-3"><label class="form-label">Date</label><input name="lecture_date" type="date" class="form-control"></div><div class="mb-3"><label class="form-label">Start Time</label><input name="start_time" type="time" class="form-control"></div><div class="mb-3"><label class="form-label">End Time</label><input name="end_time" type="time" class="form-control"></div><div class="mb-3"><label class="form-label">Venue</label><input name="venue" class="form-control"></div></form>';
                    break;

                case 'weeklySchedule':
                    modalTitle.textContent = 'Weekly Schedule';
                    modalBody.innerHTML = '<div id="scheduleRes">Loading schedule...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/lecturer_schedule.php').then(function(r) { return r.json(); }).then(function(data) {
                        var h = '<table class="table"><thead><tr><th>Course</th><th>Day</th><th>Time</th></tr></thead><tbody>';
                        data.forEach(function(s) { h += '<tr><td>' + s.course_name + '</td><td>' + s.day_of_week + '</td><td>' + s.start_time + '-' + s.end_time + '</td></tr>'; });
                        h += '</tbody></table>';
                        if (!data.length) { h = '<p class="text-muted">No schedule.</p>'; }
                        document.getElementById('scheduleRes').innerHTML = h;
                    });
                    break;

                case 'rescheduleLecture':
                    modalTitle.textContent = 'Reschedule Lecture';
                    modalBody.innerHTML = '<form id="rescheduleForm"><input type="hidden" name="action" value="reschedule_lecture"><div class="mb-3"><label class="form-label">Lecture ID</label><input name="lecture_id" type="number" class="form-control" required></div><div class="mb-3"><label class="form-label">New Date</label><input name="new_date" type="date" class="form-control" required></div><div class="mb-3"><label class="form-label">Start Time</label><input name="new_time" type="time" class="form-control" required></div><div class="mb-3"><label class="form-label">End Time</label><input name="end_time" type="time" class="form-control"></div><div class="mb-3"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="3"></textarea></div></form>';
                    break;

                case 'cancelLecture':
                    modalBody = 'Cancel Lecture';
                    modalBody.innerHTML = '<form id="cancelLectureForm"><input type="hidden" name="action" value="cancel_lecture"><div class="mb-3"><label class="form-label">Lecture ID</label><input name="lecture_id" type="number" class="form-control" required></div><div class="mb-3"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="3"></textarea></div></form>';
                    break;

                case 'studentList':
                    modalTitle.textContent = 'Enrolled Students';
                    modalBody.innerHTML = '<div id="studlistRes">Loading...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/enrolled_students.php').then(function(r) { return r.json(); }).then(function(d) {
                        var h = '<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Course</th></tr></thead><tbody>';
                        d.forEach(function(s) { h += '<tr><td>' + (s.student_number || s.id) + '</td><td>' + s.full_name + '</td><td>' + s.course_name + '</td></tr>'; });
                        h += '</tbody></table>';
                        if (!d.length) { h = '<p class="text-muted">No students.</p>'; }
                        document.getElementById('studlistRes').innerHTML = h;
                    });
                    break;

                case 'attendance':
                    modalTitle.textContent = 'Attendance';
                    modalBody.innerHTML = '<form id="attendanceForm"><input type="hidden" name="action" value="add_attendance"><div class="mb-3"><label class="form-label">Course</label><input name="course_name" class="form-control"></div><div class="mb-3"><label class="form-label">Student ID</label><input name="student_id" type="number" class="form-control"></div><div class="mb-3"><label class="form-label">Date</label><input name="date" type="date" class="form-control"></div><div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-control"><option value="Present">Present</option><option value="Absent">Absent</option></select></div><div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div></form>';
                    break;

                case 'studentProgress':
                    modalTitle = 'Student Progress';
                    modalBody = '<div id="progressRes">Loading progress...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/student_progress.php').then(function(r) { return r.json(); }).then(function(d) {
                        var h = '<table class="table"><thead><tr><th>Student</th><th>Marks</th><th>Grade</th></tr></thead><tbody>';
                        d.forEach(function(p) { h += '<tr><td>' + p.student_name + '</td><td>' + (p.marks || p.score) + '</td><td>' + p.grade + '</td></tr>'; });
                        h += '</tbody></table>';
                        document.getElementById('progressRes').innerHTML = h;
                    });
                    break;

                case 'studentCounseling':
                    modalTitle = 'Student Counseling';
                    modalBody = '<form id="counselForm"><input type="hidden" name="action" value="add_counseling"><div class="mb-3"><label class="form-label">Student ID</label><input name="student_id" type="number" class="form-control"></div><div class="mb-3"><label class="form-label">Concern</label><textarea name="concern" class="form-control" rows="3"></textarea></div><div class="mb-3"><label class="form-label">Action Taken</label><textarea name="action_taken" class="form-control" rows="3"></textarea></div><div class="mb-3"><label class="form-label">Follow Up</label><textarea name="follow_up" class="form-control" rows="3"></textarea></div></form>';
                    break;

                case 'gradebook':
                    modalTitle = 'Gradebook';
                    modalBody = '<div id="gradeRes">Loading...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/gradebook.php').then(function(r) { return r.json(); }).then(function(d) {
                        var h = '<table class="table"><thead><tr><th>Student</th><th>Assessment</th><th>Marks</th><th>Grade</th></tr></thead><tbody>';
                        d.forEach(function(g) { h += '<tr><td>' + g.student_name + '</td><td>' + g.assessment_type + '</td><td>' + g.marks + '</td><td>' + g.grade + '</td></tr>'; });
                        h += '</tbody></table>';
                        document.getElementById('gradeRes').innerHTML = h;
                    });
                    break;

                case 'gradeSubmission':
                    modalTitle = 'Submit Grade';
                    modalBody = '<form id="gradeForm"><input type="hidden" name="action" value="submit_grade"><div class="mb-3"><label class="form-label">Course</label><input name="course_name" class="form-control"></div><div class="mb-3"><label class="form-label">Student ID</label><input name="student_id" type="number" class="form-control"></div><div class="mb-3"><label class="form-label">Assessment Type</label><select name="assessment_type" class="form-control"><option value="CAT">CAT</option><option value="Exam">Exam</option></select></div><div class="mb-3"><label class="form-label">Score</label><input name="text" step="0.01" class="form-control"></div><div class="mb-3"><label class="form-label">Grade</label><select name="grade" class="form-control"><option value="">Select</option><option>A</option><option>B</option><option>C</option><option>F</option></select></div></form>';
                    break;

                case 'gradeAnalysis':
                    modalTitle = 'Grade Analysis';
                    modalBody = '<div id="analysisRes">Loading grade distribution...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/grade_analysis.php').then(function(r) { return r.json(); }).then(function(d) {
                        var h = '<p>Distribution</p><table class="table"><thead><tr><th>Grade</th><th>Count</th></tr></thead><tbody>';
                        for (var k in (d.distibution || d)) { h += '<tr><td>' + k + '</td><td>' + d[k] + '</td></tr>'; }
                        h += '</tbody></table>';
                        document.getElementById('analysisRes').innerHTML = h;
                    });
                    break;

                case 'gradeAppeals':
                    modalTitle = 'Grade Appeals';
                    modalBody = '<div id="appealRes">Loading appeals...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/grade_appeals.php').then(function(r) { return r.json(); }).then(function(d) {
                        var l = d.appeals || d;
                        var h = '<table class="table"><thead><tr><th>Student</th><th>Reason</th><th>Status</th></tr></thead><tbody>';
                        l.forEach(function(v) { h += '<tr><td>' + v.student_name + '</td><td>' + (v.reason || v.notes).substring(0, 50) + '</td><td>' + (v.status||'Pending') + '</td></tr>'; });
                        h += '</tbody></table>';
                        document.getElementById('appealRes').innerHTML = h;
                    });
                    break;

            }

            modal.show();
        }

        function editAssessment(btn) {
            var row = btn.closest('tr');
            var cells = row.querySelectorAll('td');
            openModal('editAssessment');
            var form = document.getElementById('assessmentForm');
            document.getElementById('editAssessmentId').value = row.dataset.id;
            form.student_id && (form.student_id.value = '');
            form.course_name.value = cells[1].textContent.trim();
            form.assessment_type.value = cells[2].textContent.trim();
            form.title.value = cells[0].textContent.trim();
            form.total_marks.value = cells[3].textContent.trim();
            form.marks_obtained.value = cells[4].textContent.trim();
            form.assessment_date.value = cells[5].textContent.trim();
            form.comments.value = cells[6].textContent.trim();
        }

        function deleteAssessment(id) {
            if (!confirm('Delete this assessment?')) return;
            var fd = new FormData();
            fd.append('action', 'delete_assessment');
            fd.append('assessment_id', id);
            fd.append('csrf_token', window.CSRF_TOKEN);
            fetch('lecturers.php', { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(resp){
                    if (resp.success) location.reload();
                    else alert(resp.message || 'Failed');
                });
        }

        function deleteResource(id) {
            if (!confirm('Delete this resource?')) return;
            var fd = new FormData();
            fd.append('action', 'delete_resource');
            fd.append('resource_id', id);
            fd.append('csrf_token', window.CSRF_TOKEN);
            fetch('lecturers.php', { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(resp){
                    if (resp.success) location.reload();
                    else alert(resp.message || 'Failed');
                });
        }

        // Attach modalAction handler for all forms
        document.addEventListener('DOMContentLoaded', function() {
            var modalActionBtn = document.getElementById('modalAction');
            if (!modalActionBtn) return;
            modalActionBtn.addEventListener('click', function() {
                var formId = '';
                if (currentModalAction === 'addAssessment' || currentModalAction === 'editAssessment') formId = 'assessmentForm';
                else if (currentModalAction === 'addResource') formId = 'resourceForm';
                else if (currentModalAction === 'addAnnouncement') formId = 'announcementForm';
                if (!formId) return;

                var form = document.getElementById(formId);
                if (!form || !form.checkValidity()) { form.reportValidity(); return; }

                var fd = new FormData(form);
                fd.append('csrf_token', window.CSRF_TOKEN);
                var modalBody = document.getElementById('modalBody');
                modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><p class="mt-3">Saving...</p></div>';

                fetch('lecturers.php', { method: 'POST', body: fd })
                    .then(function(r){ return r.json(); })
                    .then(function(resp){
                        if (resp.success) { modalBody.innerHTML = '<div class="alert alert-success">' + resp.message + '</div>'; setTimeout(function(){ location.reload(); }, 800); }
                        else { modalBody.innerHTML = '<div class="alert alert-danger">' + (resp.message || 'Failed') + '</div>'; }
                    })
                    .catch(function(){ modalBody.innerHTML = '<div class="alert alert-danger">Network error.</div>'; });
            });
        });
    </script>
    </div><!-- /content-area -->
</div><!-- /lec-content -->

<!-- â•â•â• AJAX MODULE LOADING â•â•â• -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.lec-content');
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
            fetch('lecturers.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.lec-content');
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

