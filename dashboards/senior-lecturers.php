<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['senior lecturer']);
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

$studentsConn = $ctx['students'];

// Get lecturer statistics from database
$total_students = 0;
$total_staff = 0;
$total_applications = 0;
$active_programs = 0;
$assigned_courses = 0;
$lectures_this_week = 0;
$pending_grades = 0;

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
    $ca_result = $conn->query("SELECT COUNT(*) as cnt FROM course_assignments WHERE lecturer_id=" . (int)$user_id . " AND status='Active'");
    if ($ca_result) $assigned_courses = (int)$ca_result->fetch_assoc()['cnt'];
    $tt_result = $conn->query("SELECT COUNT(*) as cnt FROM academic_timetable WHERE lecturer_id=" . (int)$user_id . " AND day_of_week=DAYNAME(CURDATE()) AND timetable_status='Published'");
    if ($tt_result) $lectures_this_week = (int)$tt_result->fetch_assoc()['cnt'];
    $ar_result = $conn->query("SELECT COUNT(*) as cnt FROM academic_records WHERE lecturer_id=" . (int)$user_id . " AND grade IS NULL");
    if ($ar_result) $pending_grades = (int)$ar_result->fetch_assoc()['cnt'];
} catch (Exception $e) {
    error_log('senior-lecturers stats: ' . $e->getMessage());
}

// Get assigned courses from course_assignments
$assigned_courses_list = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT ca.*, c.course_name, c.course_code, c.credits, c.level, (SELECT COUNT(*) FROM student_course_registrations scr WHERE scr.course_id=ca.course_id AND scr.status='Active') as student_count FROM course_assignments ca LEFT JOIN courses c ON ca.course_id=c.id WHERE ca.lecturer_id=$user_id AND ca.status='Active'");
        if ($r) $assigned_courses_list = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

// Get today's schedule from academic_timetable
$today_schedule = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT tt.*, c.course_name, c.course_code FROM academic_timetable tt LEFT JOIN courses c ON tt.course_id=c.id WHERE tt.lecturer_id=$user_id AND tt.day_of_week=DAYNAME(CURDATE()) AND tt.timetable_status='Published' ORDER BY tt.start_time");
        if ($r) $today_schedule = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

// Get my students
$my_students = [];
if ($studentsConn) {
    try {
        $r = $studentsConn->query("SELECT scr.*, s.student_id, s.full_name, s.first_name, s.surname, s.program, c.course_name FROM student_course_registrations scr JOIN students s ON scr.student_id=s.id JOIN course_assignments ca ON scr.course_id=ca.course_id LEFT JOIN courses c ON scr.course_id=c.id WHERE ca.lecturer_id=$user_id AND scr.status='Active' LIMIT 30");
        if ($r) $my_students = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

// Get recent assessments
$recent_assessments = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT a.*, c.course_name FROM assessments a LEFT JOIN courses c ON a.course_id=c.id WHERE a.created_by=$user_id ORDER BY a.created_at DESC LIMIT 5");
        if ($r) $recent_assessments = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

// Get grade distribution
$grade_distribution = ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'F'=>0];
$total_grades = 0;
if ($conn) {
    try {
        $r = $conn->query("SELECT grade, COUNT(*) as c FROM academic_records WHERE lecturer_id=$user_id AND grade IS NOT NULL GROUP BY grade");
        if ($r) while ($row = $r->fetch_assoc()) { $g = strtoupper(trim($row['grade'])); if (isset($grade_distribution[$g])) $grade_distribution[$g] = (int)$row['c']; $total_grades += (int)$row['c']; }
    } catch (Exception $e) {}
}

// Get teaching resources
$teaching_resources = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM teaching_resources WHERE uploaded_by=$user_id ORDER BY created_at DESC LIMIT 5");
        if ($r) $teaching_resources = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

// Get research projects
$research_projects = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM research_projects WHERE principal_investigator=$user_id ORDER BY start_date DESC LIMIT 3");
        if ($r) $research_projects = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
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
    } catch (Exception $e) {}
}

// Determine active section from ?page= parameter
$pageToSection = [
    'home'         => 'overview',
    'overview'     => 'overview',
    'my-courses'   => 'courses',
    'timetable'    => 'timetable',
    'attendance'   => 'attendance',
    'cat-marks'    => 'cat-marks',
    'exam-marks'   => 'exam-marks',
    'materials'    => 'materials',
    'results'      => 'results',
    'reports'      => 'reports',
    'lesson-plans' => 'lesson-plans',
    'assignments'  => 'assignments',
];
$requestedPage = $_GET['page'] ?? 'home';
$section = $pageToSection[$requestedPage] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.srl-topbar{background:linear-gradient(135deg,#b45309,#92400e,#78350f);padding:0 32px;height:64px;display:flex;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.15)}.srl-topbar-content{width:100%;display:flex;align-items:center;justify-content:space-between}.srl-topbar-left{display:flex;flex-direction:column}.srl-topbar-title{color:#fff;font-size:18px;font-weight:700;letter-spacing:.3px}.srl-topbar-subtitle{color:#fde68a;font-size:12px;margin-top:-2px}.srl-topbar-right{display:flex;align-items:center;gap:12px}.srl-date-badge{background:rgba(255,255,255,.15);color:#fff;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;backdrop-filter:blur(4px)}.srl-print-btn,.srl-logout-btn{color:#fde68a;font-size:16px;padding:6px 10px;border-radius:8px;transition:all .2s;text-decoration:none}.srl-print-btn:hover,.srl-logout-btn:hover{background:rgba(255,255,255,.2);color:#fff}
.srl-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.srl-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="srl-topbar"><div class="srl-topbar-content"><div class="srl-topbar-left"><div class="srl-topbar-title">Senior Lecturers</div><div class="srl-topbar-subtitle">Academic Staff &amp; Curriculum Delivery</div></div><div class="srl-topbar-right"><span class="srl-date-badge"><i class="fas fa-calendar-alt me-1"></i><?= date('l, F j, Y') ?></span><a href="#" class="srl-print-btn" onclick="window.print()"><i class="fas fa-print"></i></a><a href="../auth-handler.php?action=logout" class="srl-logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div></div>
<div class="srl-content">
                <!-- Teaching Overview -->
                <section id="overview" class="content-section dashboard-section<?= $section==='overview'?' active':'' ?>" data-section="overview">
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
                <section id="courses" class="content-section dashboard-section<?= $section==='courses'?' active':'' ?>" data-section="courses">
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
                            <?php if (empty($assigned_courses_list)): ?>
                            <div class="text-center text-muted py-4 col-12">No courses assigned yet</div>
                            <?php else: ?>
                            <?php foreach ($assigned_courses_list as $course): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <h4><?= htmlspecialchars($course['course_name'] ?? 'Course') ?></h4>
                                    <span class="course-code"><?= htmlspecialchars($course['course_code'] ?? '-') ?></span>
                                </div>
                                <div class="course-details">
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong><?= (int)($course['student_count'] ?? 0) ?></strong>
                                    </div>
                                    <div class="detail">
                                        <span>Credits:</span>
                                        <strong><?= (int)($course['credits'] ?? 0) ?></strong>
                                    </div>
                                    <div class="detail">
                                        <span>Level:</span>
                                        <strong><?= htmlspecialchars($course['level'] ?? '-') ?></strong>
                                    </div>
                                    <div class="detail">
                                        <span>Semester:</span>
                                        <strong><?= htmlspecialchars($course['semester'] ?? $course['academic_year'] ?? '-') ?></strong>
                                    </div>
                                </div>
                                <div class="course-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Manage</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Teaching Schedule -->
                <section id="schedule" class="content-section dashboard-section<?= $section==='schedule'?' active':'' ?>" data-section="schedule">
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
                            <?php if (empty($today_schedule)): ?>
                            <div class="text-center text-muted py-4"><i class="fas fa-calendar-day fa-2x mb-2"></i><p>No classes scheduled for today</p></div>
                            <?php else: ?>
                            <?php foreach ($today_schedule as $slot): ?>
                            <div class="schedule-item">
                                <div class="schedule-header">
                                    <h4><?= htmlspecialchars($slot['course_name'] ?? $slot['course_code'] ?? 'Lecture') ?></h4>
                                    <span class="schedule-time"><?= htmlspecialchars($slot['start_time'] ?? '-') ?> to <?= htmlspecialchars($slot['end_time'] ?? '-') ?></span>
                                </div>
                                <div class="schedule-details">
                                    <div class="detail">
                                        <span>Room:</span>
                                        <strong><?= htmlspecialchars($slot['room'] ?? $slot['venue'] ?? '-') ?></strong>
                                    </div>
                                    <div class="detail">
                                        <span>Topic:</span>
                                        <strong><?= htmlspecialchars($slot['topic'] ?? $slot['description'] ?? 'Lecture') ?></strong>
                                    </div>
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong><?= htmlspecialchars($slot['session_type'] ?? $slot['class_type'] ?? 'Lecture') ?></strong>
                                    </div>
                                </div>
                                <div class="schedule-actions">
                                    <button class="btn btn-sm btn-outline-primary">Start Class</button>
                                    <button class="btn btn-sm btn-outline-info">Take Attendance</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Student Management -->
                <section id="students" class="content-section dashboard-section<?= $section==='students'?' active':'' ?>" data-section="students">
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
                                <div class="avg-grade">B+</div>
                                <small>Overall performance</small>
                            </div>
                            <div class="perf-stat">
                                <h4>Attendance Rate</h4>
                                <div class="attendance-rate">92%</div>
                                <small>Department average</small>
                            </div>
                            <div class="perf-stat">
                                <h4>Assignment Completion</h4>
                                <div class="completion-rate">87%</div>
                                <small>On time submission</small>
                            </div>
                            <div class="perf-stat">
                                <h4>At Risk Students</h4>
                                <div class="at-risk-count">5</div>
                                <small>Need attention</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Records Viewer -->
                    <?php
                    require_once __DIR__ . '/../includes/student_set_viewer.php';
                    renderStudentSetViewer($studentsConn, [
                        'title' => 'My Students',
                        'icon' => 'fa-user-graduate',
                        'show_all' => true,
                        'per_page' => 20,
                        'show_statement_link' => false
                    ]);
                    ?>
                </section>

                <!-- Assessments -->
                <section id="assessments" class="content-section dashboard-section<?= $section==='assessments'?' active':'' ?>" data-section="assessments">
                    <h2>Assessment Management</h2>
                    <div class="assessment-actions">
                        <button class="btn btn-primary" onclick="openModal('createAssessment')">
                            <i class="fas fa-plus"></i> Create Assessment
                        </button>
                        <button class="btn btn-success" onclick="openModal('gradeAssessment')">
                            <i class="fas fa-clipboard-check"></i> Grade Assessments
                        </button>
                        <button class="btn btn-info" onclick="openModal('assessmentReport')">
                            <i class="fas fa-chart-bar"></i> Assessment Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('feedback')">
                            <i class="fas fa-comment"></i> Student Feedback
                        </button>
                    </div>
                    
                    <div class="assessment-overview">
                        <h3>Recent Assessments</h3>
                        <div class="assessment-list">
                            <?php if (empty($recent_assessments)): ?>
                            <div class="text-center text-muted py-4">No assessments created yet</div>
                            <?php else: ?>
                            <?php foreach ($recent_assessments as $asm): ?>
                            <div class="assessment-item">
                                <div class="assessment-header">
                                    <h4><?= htmlspecialchars($asm['title'] ?? ($asm['course_name'] ?? 'Assessment')) ?></h4>
                                    <span class="assessment-date"><?= !empty($asm['created_at']) ? date('M j, Y', strtotime($asm['created_at'])) : '-' ?></span>
                                </div>
                                <div class="assessment-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong><?= htmlspecialchars($asm['assessment_type'] ?? 'Exam') ?></strong>
                                    </div>
                                    <div class="detail">
                                        <span>Course:</span>
                                        <strong><?= htmlspecialchars($asm['course_name'] ?? '-') ?></strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-<?= ($asm['status']??'draft')==='published'?'success':'warning' ?>"><?= ucfirst(htmlspecialchars($asm['status'] ?? 'Draft')) ?></strong>
                                    </div>
                                </div>
                                <div class="assessment-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Submissions</button>
                                    <button class="btn btn-sm btn-outline-success">Continue Grading</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Grade Management -->
                <section id="grades" class="content-section dashboard-section<?= $section==='grades'?' active':'' ?>" data-section="grades">
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
                        <?php if ($total_grades === 0): ?>
                        <div class="text-center text-muted py-3">No grades recorded yet</div>
                        <?php else: ?>
                        <?php
                        $grade_ranges = [
                            'A' => ['label'=>'A Range (80-100%)', 'color'=>'success', 'students'=>$grade_distribution['A']],
                            'B' => ['label'=>'B Range (70-79%)', 'color'=>'info', 'students'=>$grade_distribution['B']],
                            'C' => ['label'=>'C Range (60-69%)', 'color'=>'warning', 'students'=>$grade_distribution['C']],
                            'D' => ['label'=>'D Range (50-59%)', 'color'=>'secondary', 'students'=>$grade_distribution['D']],
                            'F' => ['label'=>'F Range (Below 50%)', 'color'=>'danger', 'students'=>$grade_distribution['F']],
                        ];
                        ?>
                        <div class="grade-distribution">
                            <?php foreach ($grade_ranges as $gr): $pct = $total_grades > 0 ? round($gr['students']/$total_grades*100) : 0; ?>
                            <div class="grade-category">
                                <h4><?= $gr['label'] ?></h4>
                                <div class="grade-count"><?= $gr['students'] ?> student<?= $gr['students']!==1?'s':'' ?></div>
                                <div class="progress">
                                    <div class="progress-bar bg-<?= $gr['color'] ?>" style="width: <?= $pct ?>%"><?= $pct ?>%</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Teaching Resources -->
                <section id="resources" class="content-section dashboard-section<?= $section==='resources'?' active':'' ?>" data-section="resources">
                    <h2>Teaching Resources</h2>
                    <div class="resource-actions">
                        <button class="btn btn-primary" onclick="openModal('uploadResource')">
                            <i class="fas fa-upload"></i> Upload Resource
                        </button>
                        <button class="btn btn-success" onclick="openModal('resourceLibrary')">
                            <i class="fas fa-folder"></i> Resource Library
                        </button>
                        <button class="btn btn-info" onclick="openModal('shareResource')">
                            <i class="fas fa-share"></i> Share Resources
                        </button>
                        <button class="btn btn-warning" onclick="openModal('resourceArchive')">
                            <i class="fas fa-archive"></i> Resource Archive
                        </button>
                    </div>
                    
                    <div class="resources-overview">
                        <h3>My Teaching Resources</h3>
                        <div class="resources-list">
                            <?php if (empty($teaching_resources)): ?>
                            <div class="text-center text-muted py-4">No resources uploaded yet</div>
                            <?php else: ?>
                            <?php foreach ($teaching_resources as $res): ?>
                            <div class="resource-item">
                                <div class="resource-header">
                                    <h4><?= htmlspecialchars($res['title'] ?? $res['file_name'] ?? 'Resource') ?></h4>
                                    <span class="resource-type"><?= htmlspecialchars(strtoupper(pathinfo($res['file_name']??'', PATHINFO_EXTENSION) ?: 'FILE')) ?></span>
                                </div>
                                <div class="resource-details">
                                    <?php if (!empty($res['file_size'])): ?>
                                    <div class="detail"><span>Size:</span><strong><?= number_format((float)$res['file_size']/1048576, 1) ?> MB</strong></div>
                                    <?php endif; ?>
                                    <div class="detail"><span>Uploaded:</span><strong><?= !empty($res['created_at']) ? date('M j, Y', strtotime($res['created_at'])) : '-' ?></strong></div>
                                    <?php if (!empty($res['download_count'])): ?>
                                    <div class="detail"><span>Downloads:</span><strong><?= (int)$res['download_count'] ?> times</strong></div>
                                    <?php endif; ?>
                                </div>
                                <div class="resource-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                    <button class="btn btn-sm btn-outline-info">Share</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Research Activities -->
                <section id="research" class="content-section dashboard-section<?= $section==='research'?' active':'' ?>" data-section="research">
                    <h2>Research Activities</h2>
                    <div class="research-actions">
                        <button class="btn btn-primary" onclick="openModal('researchProject')">
                            <i class="fas fa-flask"></i> Research Project
                        </button>
                        <button class="btn btn-success" onclick="openModal('publications')">
                            <i class="fas fa-book-open"></i> Publications
                        </button>
                        <button class="btn btn-info" onclick="openModal('conferences')">
                            <i class="fas fa-users"></i> Conferences
                        </button>
                        <button class="btn btn-warning" onclick="openModal('researchSupervision')">
                            <i class="fas fa-user-graduate"></i> Student Supervision
                        </button>
                    </div>
                    
                    <div class="research-overview">
                        <h3>Current Research Projects</h3>
                        <div class="research-projects">
                            <?php if (empty($research_projects)): ?>
                            <div class="text-center text-muted py-4">No active research projects</div>
                            <?php else: ?>
                            <?php foreach ($research_projects as $proj): ?>
                            <div class="project-card">
                                <div class="project-header">
                                    <h4><?= htmlspecialchars($proj['title'] ?? 'Research Project') ?></h4>
                                    <span class="status-badge active"><?= htmlspecialchars($proj['status'] ?? 'In Progress') ?></span>
                                </div>
                                <div class="project-details">
                                    <div class="detail"><span>Role:</span><strong><?= htmlspecialchars($proj['role'] ?? 'Investigator') ?></strong></div>
                                    <?php if (!empty($proj['start_date']) && !empty($proj['end_date'])): ?>
                                    <div class="detail"><span>Duration:</span><strong><?= date('M Y', strtotime($proj['start_date'])) ?> to <?= date('M Y', strtotime($proj['end_date'])) ?></strong></div>
                                    <?php endif; ?>
                                    <?php if (!empty($proj['team_size'])): ?>
                                    <div class="detail"><span>Team:</span><strong><?= (int)$proj['team_size'] ?> members</strong></div>
                                    <?php endif; ?>
                                </div>
                                <div class="project-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Progress Report</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section id="activities" class="activities-section dashboard-section<?= $section==='activities'?' active':'' ?>" data-section="activities">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current date/time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Navigation
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
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
            });
        });

        // Modal functions
        function openModal(action) {
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            switch(action) {
                case 'createAssessment':
                    modalTitle.textContent = 'Create New Assessment';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Assessment Title</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Course</label>
                                        <select class="form-control" required>
                                            <option value="">Select Course</option>
                                            <option value="nursing-fundamentals">Nursing Fundamentals</option>
                                            <option value="medical-surgical">Medical Surgical Nursing</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Assessment Type</label>
                                        <select class="form-control" required>
                                            <option value="">Select Type</option>
                                            <option value="quiz">Quiz</option>
                                            <option value="assignment">Assignment</option>
                                            <option value="exam">Examination</option>
                                            <option value="project">Project</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Total Marks</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Duration (minutes)</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instructions</label>
                                <textarea class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assessment Criteria</label>
                                <textarea class="form-control" rows="3" required></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'gradeAssessment':
                    modalTitle.textContent = 'Grade Assessment';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Select Assessment</label>
                                <select class="form-control" required>
                                    <option value="">Select Assessment</option>
                                    <option value="midterm-nursing">Nursing Fundamentals , Midterm Exam</option>
                                    <option value="assignment-medical">Medical Surgical , Case Study</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Student Submissions</label>
                                <div class="submissions-list">
                                    <div class="submission-item border p-3 mb-2">
                                        <div class="student-info">
                                            <strong>John Student (STU-2023-001)</strong>
                                            <span class="submission-date">Submitted: Apr 15, 2026 2:30 PM</span>
                                        </div>
                                        <div class="grade-inputs">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label class="form-label">Score</label>
                                                    <input type="number" class="form-control" max="100">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Grade</label>
                                                    <select class="form-control">
                                                        <option value="">Select Grade</option>
                                                        <option value="A">A</option>
                                                        <option value="B">B</option>
                                                        <option value="C">C</option>
                                                        <option value="D">D</option>
                                                        <option value="F">F</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Comments</label>
                                                    <textarea class="form-control" rows="1"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }
    </script>

<!-- ═══ AJAX MODULE LOADING ═══ -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.srl-content');
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
            fetch('senior-lecturers.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.srl-content');
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
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<?php include_once __DIR__ . '/../includes/enterprise_control_panel.php'; ?>
</body>
</html>

