<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';

$ctx = bootstrapStaffDashboard(['lecturer']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';

$studentsConn = getStudentsConnection();

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';

// User data already available from bootstrapStaffDashboard session

// Set statistics from database
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
    $ca_result = $conn->query("SELECT COUNT(*) as cnt FROM course_assignments WHERE lecturer_id=" . (int)$user_id . " AND status='Active'");
    if ($ca_result) $assigned_courses = (int)$ca_result->fetch_assoc()['cnt'];
    $tt_result = $conn->query("SELECT COUNT(*) as cnt FROM academic_timetable WHERE lecturer_id=" . (int)$user_id . " AND day_of_week=DAYNAME(CURDATE()) AND timetable_status='Published'");
    if ($tt_result) $lectures_this_week = (int)$tt_result->fetch_assoc()['cnt'];
    $ar_result = $conn->query("SELECT COUNT(*) as cnt FROM academic_records WHERE lecturer_id=" . (int)$user_id . " AND grade IS NULL");
    if ($ar_result) $pending_grades = (int)$ar_result->fetch_assoc()['cnt'];
    $stu_result = $conn->query("SELECT COUNT(DISTINCT student_id) as cnt FROM academic_records WHERE lecturer_id=" . (int)$user_id);
    if ($stu_result) $total_students_taught = (int)$stu_result->fetch_assoc()['cnt'];
    $avg_result = $conn->query("SELECT AVG(marks) as avg FROM academic_records WHERE lecturer_id=" . (int)$user_id . " AND marks IS NOT NULL");
    if ($avg_result) $average_grade = (int)round($avg_result->fetch_assoc()['avg'] ?? 0);
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
    } catch (Exception $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; ?>
    
    <div class="page-content">
      <div class="top-bar">
        <div>
          <strong><i class="fas fa-chalkboard me-2 text-primary"></i>Lecturer Dashboard</strong>
          <div class="text-muted small">Classroom Teaching &amp; Student Development | <?php echo htmlspecialchars($user_name); ?></div>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span class="text-muted small d-none d-md-block" id="currentDate"></span>
          <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
        </div>
      </div>

      <div class="content-area">
                <?php include_once __DIR__ . '/../views/student_search_component.php'; ?>
                <!-- Teaching Overview -->
                <section id="overview" class="section-card">
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
                <section id="courses" class="section-card">
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
                <section id="schedule" class="section-card">
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
                <section id="students" class="section-card">
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
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                <section id="assessments" class="section-card">
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
                            <div class="assessment-item">
                                <div class="assessment-header">
                                    <h4>Basic Nursing Skills , Practical Test</h4>
                                    <span class="assessment-date">Apr 18, 2026</span>
                                </div>
                                <div class="assessment-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Practical Examination</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>25 submitted</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-warning">Grading in Progress</strong>
                                    </div>
                                </div>
                                <div class="assessment-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Submissions</button>
                                    <button class="btn btn-sm btn-outline-success">Continue Grading</button>
                                </div>
                            </div>
                            
                            <div class="assessment-item">
                                <div class="assessment-header">
                                    <h4>Anatomy & Physiology , Quiz</h4>
                                    <span class="assessment-date">Apr 12, 2026</span>
                                </div>
                                <div class="assessment-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Quiz</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>30 submitted</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-success">Graded</strong>
                                    </div>
                                </div>
                                <div class="assessment-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Results</button>
                                    <button class="btn btn-sm btn-outline-info">Publish Grades</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Grade Management -->
                <section id="grades" class="section-card">
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
                <section id="resources" class="section-card">
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
                            <div class="resource-item">
                                <div class="resource-header">
                                    <h4>Basic Nursing Skills Lecture Notes</h4>
                                    <span class="resource-type">PDF</span>
                                </div>
                                <div class="resource-details">
                                    <div class="detail">
                                        <span>Size:</span>
                                        <strong>3.8 MB</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Uploaded:</span>
                                        <strong>Apr 5, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Downloads:</span>
                                        <strong>25 times</strong>
                                    </div>
                                </div>
                                <div class="resource-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                    <button class="btn btn-sm btn-outline-info">Share</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Communications -->
                <section id="communications" class="section-card">
                    <h2>Student Communications</h2>
                    <div class="communication-actions">
                        <button class="btn btn-primary" onclick="openModal('sendMessage')">
                            <i class="fas fa-envelope"></i> Send Message
                        </button>
                        <button class="btn btn-success" onclick="openModal('classAnnouncement')">
                            <i class="fas fa-bullhorn"></i> Class Announcement
                        </button>
                        <button class="btn btn-info" onclick="openModal('messageHistory')">
                            <i class="fas fa-history"></i> Message History
                        </button>
                        <button class="btn btn-warning" onclick="openModal('officeHours')">
                            <i class="fas fa-clock"></i> Office Hours
                        </button>
                    </div>
                    
                    <div class="communications-overview">
                        <h3>Recent Messages</h3>
                        <div class="message-list">
                            <div class="message-item">
                                <div class="message-header">
                                    <h4>To: Basic Nursing Skills Class</h4>
                                    <span class="message-date">Apr 20, 2026</span>
                                </div>
                                <div class="message-content">
                                    <p>Reminder: Practical test on vital signs tomorrow at 10 AM. Please bring your stethoscopes and practice materials.</p>
                                </div>
                                <div class="message-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-info">Send Reminder</button>
                                </div>
                            </div>
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
        <section id="student-records" class="section-card">
            <?php renderStudentSetViewer($studentsConn, [
                'title' => 'Student Records',
                'icon' => 'fa-user-graduate',
                'show_all' => true,
                'per_page' => 50,
                'show_statement_link' => false
            ]); ?>
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
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href').substring(1);
                document.querySelectorAll('.section-card').forEach(section => {
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
                                            <option value="basic-nursing">Basic Nursing Skills</option>
                                            <option value="anatomy">Anatomy & Physiology</option>
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
                                            <option value="practical">Practical Test</option>
                                            <option value="exam">Examination</option>
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
                case 'sendMessage':
                    modalTitle.textContent = 'Send Message to Students';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Recipient</label>
                                <select class="form-control" required>
                                    <option value="">Select Recipients</option>
                                    <option value="all-students">All My Students</option>
                                    <option value="basic-nursing">Basic Nursing Skills Class</option>
                                    <option value="anatomy">Anatomy & Physiology Class</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select class="form-control" required>
                                    <option value="normal">Normal</option>
                                    <option value="important">Important</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Delivery Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="email-notification" checked>
                                    <label class="form-check-label" for="email-notification">Send email notification</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sms-notification">
                                    <label class="form-check-label" for="sms-notification">Send SMS notification</label>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'classAnnouncement':
                    modalTitle.textContent = 'Class Announcement';
                    modalBody.innerHTML = `
                        <form id="sendAnnouncementForm">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input id="annTitle" type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Class / Recipient</label>
                                <select id="annTarget" class="form-control">
                                    <option value="all">All My Students</option>
                                    <option value="class-basic">Basic Nursing</option>
                                    <option value="class-anatomy">Anatomy</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select id="annPriority" class="form-control">
                                    <option value="normal">Normal</option>
                                    <option value="important">Important</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea id="annContent" class="form-control" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input id="annExpiry" type="date" class="form-control">
                            </div>
                        </form>`;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }

        // Attach modalAction handler for class announcements
        document.addEventListener('DOMContentLoaded', function() {
            const modalActionBtn = document.getElementById('modalAction');
            if (!modalActionBtn) return;
            modalActionBtn.addEventListener('click', function() {
                const modalTitle = document.getElementById('modalTitle').textContent || '';
                if (modalTitle.includes('Class Announcement')) {
                    const title = document.getElementById('annTitle').value.trim();
                    const content = document.getElementById('annContent').value.trim();
                    const target = document.getElementById('annTarget').value;
                    const priority = document.getElementById('annPriority').value;
                    const expiry = document.getElementById('annExpiry').value || '';
                    if (!title || !content) { alert('Title and message required.'); return; }

                    const fd = new FormData();
                    fd.append('title', title);
                    fd.append('content', content);
                    fd.append('announcement_type', 'class');
                    fd.append('target_audience', target);
                    fd.append('priority', priority);
                    fd.append('expiry_date', expiry);
                    fd.append('status', 'published');

                    const modalBody = document.getElementById('modalBody');
                    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><p class="mt-3">Publishing announcement...</p></div>';

                    fetch('../includes/ajax_publish_announcement.php', { method: 'POST', body: fd })
                        .then(r => r.json()).then(resp => {
                            if (resp.success) { modalBody.innerHTML = '<div class="alert alert-success">Published.</div>'; setTimeout(()=>location.reload(),900); }
                            else { modalBody.innerHTML = '<div class="alert alert-danger">Failed: ' + (resp.message||'Unknown') + '</div>'; }
                        }).catch(()=>{ modalBody.innerHTML = '<div class="alert alert-danger">Network error.</div>'; });
                }
            });
        });
    </script>
    </div><!-- /content-area -->
</div><!-- /page-content -->
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

