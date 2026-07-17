<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

$ctx = bootstrapStaffDashboard(['senior lecturer']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_email = $user['email'] ?? '';
$students_db_name = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';
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

// ─── POST Handlers ───
$flash_msg = '';
$flash_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken()) { die('Invalid CSRF token'); }
    $act = $_POST['action'];

    if ($act === 'add_assessment' && $conn) {
        $sid  = (int)($_POST['student_id'] ?? 0);
        $coursename = trim($_POST['course_name'] ?? '');
        $type = trim($_POST['assessment_type'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $total = (int)($_POST['total_marks'] ?? 0);
        $obtained = (int)($_POST['marks_obtained'] ?? 0);
        $date = trim($_POST['assessment_date'] ?? date('Y-m-d'));
        $comments = trim($_POST['comments'] ?? '');
        $stmt = $conn->prepare("INSERT INTO teaching_assessments (lecturer_id,student_id,course_name,assessment_type,title,total_marks,marks_obtained,assessment_date,comments) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iisssiiis", $user_id, $sid, $coursename, $type, $title, $total, $obtained, $date, $comments);
        if ($stmt->execute()) { $flash_msg = 'Assessment added.'; } else { $flash_msg = 'Failed to add assessment.'; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'update_assessment' && $conn) {
        $id   = (int)($_POST['assessment_id'] ?? 0);
        $sid  = (int)($_POST['student_id'] ?? 0);
        $coursename = trim($_POST['course_name'] ?? '');
        $type = trim($_POST['assessment_type'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $total = (int)($_POST['total_marks'] ?? 0);
        $obtained = (int)($_POST['marks_obtained'] ?? 0);
        $date = trim($_POST['assessment_date'] ?? date('Y-m-d'));
        $comments = trim($_POST['comments'] ?? '');
        $stmt = $conn->prepare("UPDATE teaching_assessments SET student_id=?,course_name=?,assessment_type=?,title=?,total_marks=?,marks_obtained=?,assessment_date=?,comments=? WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("isssiiisii", $sid, $coursename, $type, $title, $total, $obtained, $date, $comments, $id, $user_id);
        if ($stmt->execute()) { $flash_msg = 'Assessment updated.'; } else { $flash_msg = 'Failed to update assessment.'; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'delete_assessment' && $conn) {
        $id = (int)($_POST['assessment_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM teaching_assessments WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) { $flash_msg = 'Assessment deleted.'; } else { $flash_msg = 'Failed to delete assessment.'; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_resource' && $conn) {
        $title = trim($_POST['title'] ?? '');
        $rtype = trim($_POST['resource_type'] ?? '');
        $fpath = trim($_POST['file_path'] ?? '');
        $url   = trim($_POST['url'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $coursename = trim($_POST['course_name'] ?? '');
        $stmt = $conn->prepare("INSERT INTO teaching_resources (lecturer_id,title,resource_type,file_path,url,description,course_name) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $user_id, $title, $rtype, $fpath, $url, $desc, $coursename);
        if ($stmt->execute()) { $flash_msg = 'Resource added.'; } else { $flash_msg = 'Failed to add resource.'; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'delete_resource' && $conn) {
        $id = (int)($_POST['resource_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM teaching_resources WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) { $flash_msg = 'Resource deleted.'; } else { $flash_msg = 'Failed to delete resource.'; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_announcement' && $conn) {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $audience = trim($_POST['target_audience'] ?? 'All');
        $published = isset($_POST['is_published']) ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO teaching_announcements (lecturer_id,title,content,target_audience,is_published) VALUES (?,?,?,?,?)");
        $stmt->bind_param("isssi", $user_id, $title, $content, $audience, $published);
        if ($stmt->execute()) { $flash_msg = 'Announcement added.'; } else { $flash_msg = 'Failed to add announcement.'; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_lesson_plan' && $conn) {
        $courseId = trim($_POST['course_id'] ?? '');
        $week = (int)($_POST['week_number'] ?? 1);
        $topic = trim($_POST['topic'] ?? '');
        $objectives = trim($_POST['objectives'] ?? '');
        $activities = trim($_POST['activities'] ?? '');
        $conn->query("CREATE TABLE IF NOT EXISTS lesson_plans (id INT AUTO_INCREMENT PRIMARY KEY, lecturer_id INT, course_id VARCHAR(100), week_number INT, topic VARCHAR(255), objectives TEXT, activities TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO lesson_plans (lecturer_id, course_id, week_number, topic, objectives, activities) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isisss", $user_id, $courseId, $week, $topic, $objectives, $activities);
        if ($stmt->execute()) { $flash_msg = 'Lesson plan saved.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_syllabus' && $conn) {
        $cname = trim($_POST['course_name'] ?? '');
        $sem = trim($_POST['semester'] ?? '1');
        $topics = trim($_POST['topics'] ?? '');
        $outcomes = trim($_POST['learning_outcomes'] ?? '');
        $conn->query("CREATE TABLE IF NOT EXISTS course_syllabi (id INT AUTO_INCREMENT PRIMARY KEY, lecturer_id INT, course_name VARCHAR(255), semester VARCHAR(20), topics TEXT, learning_outcomes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO course_syllabi (lecturer_id, course_name, semester, topics, learning_outcomes) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $user_id, $cname, $sem, $topics, $outcomes);
        if ($stmt->execute()) { $flash_msg = 'Syllabus saved.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_evaluation' && $conn) {
        $courseId = trim($_POST['course_id'] ?? '');
        $sem = trim($_POST['semester'] ?? '1');
        $feedback = trim($_POST['feedback'] ?? '');
        $conn->query("CREATE TABLE IF NOT EXISTS course_evaluations (id INT AUTO_INCREMENT PRIMARY KEY, lecturer_id INT, course_id VARCHAR(100), semester VARCHAR(20), feedback TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO course_evaluations (lecturer_id, course_id, semester, feedback) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $user_id, $courseId, $sem, $feedback);
        if ($stmt->execute()) { $flash_msg = 'Evaluation submitted.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_lecture' && $conn) {
        $courseId = trim($_POST['course_id'] ?? '');
        $topic = trim($_POST['topic'] ?? '');
        $date = trim($_POST['lecture_date'] ?? '');
        $start = trim($_POST['start_time'] ?? '');
        $end = trim($_POST['end_time'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $conn->query("CREATE TABLE IF NOT EXISTS lecture_schedule (id INT AUTO_INCREMENT PRIMARY KEY, lecturer_id INT, course_id VARCHAR(100), topic VARCHAR(255), lecture_date DATE, start_time TIME, end_time TIME, venue VARCHAR(100), status VARCHAR(20) DEFAULT 'scheduled', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO lecture_schedule (lecturer_id, course_id, topic, lecture_date, start_time, end_time, venue) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $user_id, $courseId, $topic, $date, $start, $end, $venue);
        if ($stmt->execute()) { $flash_msg = 'Lecture scheduled.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'reschedule_lecture' && $conn) {
        $lid = (int)($_POST['lecture_id'] ?? 0);
        $newDate = trim($_POST['new_date'] ?? '');
        $newTime = trim($_POST['new_time'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $stmt = $conn->prepare("UPDATE lecture_schedule SET lecture_date=?, start_time=?, status='rescheduled' WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("ssii", $newDate, $newTime, $lid, $user_id);
        if ($stmt->execute()) { $flash_msg = 'Lecture rescheduled.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'cancel_lecture' && $conn) {
        $lid = (int)($_POST['lecture_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $stmt = $conn->prepare("UPDATE lecture_schedule SET status='cancelled' WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("ii", $lid, $user_id);
        if ($stmt->execute()) { $flash_msg = 'Lecture cancelled.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'mark_attendance' && $conn) {
        $courseId = trim($_POST['course_id'] ?? '');
        $sid = (int)($_POST['student_id'] ?? 0);
        $attDate = trim($_POST['att_date'] ?? date('Y-m-d'));
        $status = trim($_POST['status'] ?? 'present');
        $notes = trim($_POST['notes'] ?? '');
        if (!$studentsConn) $studentsConn = getStudentsConnection();
        if ($studentsConn) {
            $conn->query("CREATE TABLE IF NOT EXISTS student_attendance (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, course_id VARCHAR(100), att_date DATE, status VARCHAR(20), notes TEXT, marked_by INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $stmt = $conn->prepare("INSERT INTO student_attendance (student_id, course_id, att_date, status, notes, marked_by) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("issssi", $sid, $courseId, $attDate, $status, $notes, $user_id);
            if ($stmt->execute()) { $flash_msg = 'Attendance recorded.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
            $stmt->close();
        }
    }

    if ($act === 'add_counseling' && $conn) {
        $sid = (int)($_POST['student_id'] ?? 0);
        $concern = trim($_POST['concern'] ?? '');
        $action = trim($_POST['action_taken'] ?? '');
        $conn->query("CREATE TABLE IF NOT EXISTS lecturer_counseling (id INT AUTO_INCREMENT PRIMARY KEY, lecturer_id INT, student_id INT, concern TEXT, action_taken TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO lecturer_counseling (lecturer_id, student_id, concern, action_taken) VALUES (?,?,?,?)");
        $stmt->bind_param("iiss", $user_id, $sid, $concern, $action);
        if ($stmt->execute()) { $flash_msg = 'Counseling record saved.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'submit_grade' && $conn) {
        $courseId = trim($_POST['course_id'] ?? '');
        $sid = (int)($_POST['student_id'] ?? 0);
        $assessType = trim($_POST['assessment_type'] ?? '');
        $score = (int)($_POST['score'] ?? 0);
        $grade = trim($_POST['grade'] ?? '');
        $conn->query("CREATE TABLE IF NOT EXISTS lecturer_grades (id INT AUTO_INCREMENT PRIMARY KEY, lecturer_id INT, student_id INT, course_id VARCHAR(100), assessment_type VARCHAR(50), score INT, grade VARCHAR(5), submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO lecturer_grades (lecturer_id, student_id, course_id, assessment_type, score, grade) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iisssi", $user_id, $sid, $courseId, $assessType, $score, $grade);
        if ($stmt->execute()) { $flash_msg = 'Grade submitted.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_research_project' && $conn) {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $startD = trim($_POST['start_date'] ?? '');
        $endD = trim($_POST['end_date'] ?? '');
        $budget = (float)($_POST['budget'] ?? 0);
        $status = trim($_POST['status'] ?? 'Proposal');
        $conn->query("CREATE TABLE IF NOT EXISTS research_projects (id INT AUTO_INCREMENT PRIMARY KEY, researcher_id INT, title VARCHAR(255), description TEXT, start_date DATE, end_date DATE, budget DECIMAL(15,2), status VARCHAR(30), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO research_projects (researcher_id, title, description, start_date, end_date, budget, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssds", $user_id, $title, $desc, $startD, $endD, $budget, $status);
        if ($stmt->execute()) { $flash_msg = 'Research project saved.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_publication' && $conn) {
        $title = trim($_POST['title'] ?? '');
        $authors = trim($_POST['authors'] ?? '');
        $journal = trim($_POST['journal'] ?? '');
        $pubDate = trim($_POST['publication_date'] ?? '');
        $doi = trim($_POST['doi'] ?? '');
        $status = trim($_POST['status'] ?? 'Published');
        $conn->query("CREATE TABLE IF NOT EXISTS research_publications (id INT AUTO_INCREMENT PRIMARY KEY, researcher_id INT, title VARCHAR(255), authors TEXT, journal VARCHAR(255), publication_date DATE, doi VARCHAR(100), status VARCHAR(30), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO research_publications (researcher_id, title, authors, journal, publication_date, doi, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $user_id, $title, $authors, $journal, $pubDate, $doi, $status);
        if ($stmt->execute()) { $flash_msg = 'Publication recorded.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_conference' && $conn) {
        $paper = trim($_POST['paper_title'] ?? '');
        $confName = trim($_POST['conference_name'] ?? '');
        $loc = trim($_POST['location'] ?? '');
        $confDate = trim($_POST['conf_date'] ?? '');
        $status = trim($_POST['status'] ?? 'Presented');
        $conn->query("CREATE TABLE IF NOT EXISTS research_conferences (id INT AUTO_INCREMENT PRIMARY KEY, researcher_id INT, paper_title VARCHAR(255), conference_name VARCHAR(255), location VARCHAR(255), conference_date DATE, status VARCHAR(30), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO research_conferences (researcher_id, paper_title, conference_name, location, conference_date, status) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssss", $user_id, $paper, $confName, $loc, $confDate, $status);
        if ($stmt->execute()) { $flash_msg = 'Conference record saved.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    if ($act === 'add_supervision' && $conn) {
        $sid = (int)($_POST['student_id'] ?? 0);
        $rtitle = trim($_POST['research_title'] ?? '');
        $notes = trim($_POST['supervisor_notes'] ?? '');
        $status = trim($_POST['status'] ?? 'In Progress');
        $conn->query("CREATE TABLE IF NOT EXISTS research_supervisions (id INT AUTO_INCREMENT PRIMARY KEY, supervisor_id INT, student_id INT, research_title VARCHAR(255), supervisor_notes TEXT, status VARCHAR(30), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO research_supervisions (supervisor_id, student_id, research_title, supervisor_notes, status) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iisss", $user_id, $sid, $rtitle, $notes, $status);
        if ($stmt->execute()) { $flash_msg = 'Supervision record saved.'; } else { $flash_msg = 'Failed: ' . $stmt->error; $flash_type = 'danger'; }
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . urlencode($_GET['page'] ?? 'home'));
    exit;
}

// Get lecturer statistics from database
$total_students = 0;
$total_staff = 0;
$total_applications = 0;
$active_programs = 0;
$assigned_courses = 0;
$lectures_this_week = 0;
$pending_grades = 0;

if ($conn) {
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
}

// Get assigned courses from course_assignments
$assigned_courses_list = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT ca.*, c.course_name, c.course_code, c.credits, c.level, (SELECT COUNT(*) FROM student_course_registrations scr WHERE scr.course_id=ca.course_id AND scr.status='Active') as student_count FROM course_assignments ca LEFT JOIN courses c ON ca.course_id=c.id WHERE ca.lecturer_id=? AND ca.status='Active'");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $assigned_courses_list = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
}

// Get today's schedule from academic_timetable
$today_schedule = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT tt.*, c.course_name, c.course_code FROM academic_timetable tt LEFT JOIN courses c ON tt.course_id=c.id WHERE tt.lecturer_id=? AND tt.day_of_week=DAYNAME(CURDATE()) AND tt.timetable_status='Published' ORDER BY tt.start_time");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $today_schedule = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
}

// Get my students
$my_students = [];
if ($studentsConn) {
    try {
        $stmt = $studentsConn->prepare("SELECT scr.*, s.student_id, s.full_name, s.first_name, s.surname, s.program, c.course_name FROM student_course_registrations scr JOIN students s ON scr.student_id=s.id JOIN course_assignments ca ON scr.course_id=ca.course_id LEFT JOIN courses c ON scr.course_id=c.id WHERE ca.lecturer_id=? AND scr.status='Active' LIMIT 30");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $my_students = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
}

// Get recent assessments
$recent_assessments = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT a.*, c.course_name FROM assessments a LEFT JOIN courses c ON a.course_id=c.id WHERE a.created_by=? ORDER BY a.created_at DESC LIMIT 5");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $recent_assessments = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
}

// Get grade distribution
$grade_distribution = ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'F'=>0];
$total_grades = 0;
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT grade, COUNT(*) as c FROM academic_records WHERE lecturer_id=? AND grade IS NOT NULL GROUP BY grade");
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            $r = $stmt->get_result();
            if ($r) while ($row = $r->fetch_assoc()) { $g = strtoupper(trim($row['grade'])); if (isset($grade_distribution[$g])) $grade_distribution[$g] = (int)$row['c']; $total_grades += (int)$row['c']; }
        }
        $stmt->close();
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
}

// Get teaching resources
$teaching_resources = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM teaching_resources WHERE lecturer_id=? ORDER BY created_at DESC");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $teaching_resources = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
}

// Get all teaching assessments for this lecturer
$all_assessments = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT ta.*, st.full_name as student_name FROM teaching_assessments ta LEFT JOIN students st ON ta.student_id=st.id WHERE ta.lecturer_id=? ORDER BY ta.assessment_date DESC");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $all_assessments = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
}

// Get teaching announcements
$all_announcements = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM teaching_announcements WHERE lecturer_id=? ORDER BY created_at DESC");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $all_announcements = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
}

// Get research projects
$research_projects = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM research_projects WHERE principal_investigator=? ORDER BY start_date DESC LIMIT 3");
        $stmt->bind_param('i', $user_id);
        $r = $stmt->execute() ? $stmt->get_result() : null;
        if ($r) $research_projects = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
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
    } catch (Exception $e) { error_log('senior-lecturers context: ' . $e->getMessage()); }
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
    'announcements'  => 'announcements',
    'students'       => 'students',
    'assessments'    => 'assessments',
    'grades'         => 'grades',
    'research'       => 'research',
    'activities'     => 'activities',
];
$requestedPage = $_GET['page'] ?? 'home';
$section = $pageToSection[$requestedPage] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>

.srl-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.srl-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

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
                    <?php if ($flash_msg && ($_GET['page'] ?? '') === 'cat-marks'): ?><div class="alert alert-<?= $flash_type ?>"><?= htmlspecialchars($flash_msg) ?></div><?php endif; ?>
                    <div class="mb-3">
                        <button class="btn btn-primary" onclick="document.getElementById('addAssessmentForm').style.display=document.getElementById('addAssessmentForm').style.display==='none'?'block':'none'">
                            <i class="fas fa-plus"></i> Add Assessment
                        </button>
                    </div>
                    <div id="addAssessmentForm" style="display:none" class="card card-body mb-4">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_assessment">
                            <?php csrfField(); ?>
                            <div class="row">
                                <div class="col-md-4 mb-2"><label>Student ID</label><input type="number" name="student_id" class="form-control" required></div>
                                <div class="col-md-4 mb-2"><label>Course Name</label><input type="text" name="course_name" class="form-control" required></div>
                                <div class="col-md-4 mb-2"><label>Assessment Type</label><select name="assessment_type" class="form-control" required><option value="CAT">CAT</option><option value="Assignment">Assignment</option><option value="Exam">Exam</option><option value="Project">Project</option></select></div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-2"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                                <div class="col-md-2 mb-2"><label>Total Marks</label><input type="number" name="total_marks" class="form-control" required></div>
                                <div class="col-md-2 mb-2"><label>Marks Obtained</label><input type="number" name="marks_obtained" class="form-control" required></div>
                                <div class="col-md-2 mb-2"><label>Date</label><input type="date" name="assessment_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                                <div class="col-md-2 mb-2"><label>&nbsp;</label><button type="submit" class="btn btn-success w-100">Save</button></div>
                            </div>
                            <div class="mb-2"><label>Comments</label><textarea name="comments" class="form-control" rows="2"></textarea></div>
                        </form>
                    </div>
                    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchXHSL" type="text" placeholder="Search..." onkeyup="filterTable('srchXHSL','tblXHSL')"></div>
<div class="table-responsive">
                        <table id="tblXHSL" class="table table-bordered table-hover">
                            <thead><tr><th>#</th><th>Student</th><th>Course</th><th>Type</th><th>Title</th><th>Total</th><th>Obtained</th><th>Date</th><th>Comments</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($all_assessments)): ?>
                            <tr><td colspan="10" class="text-center text-muted">No assessments found</td></tr>
                            <?php else: foreach ($all_assessments as $i => $asm): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= htmlspecialchars($asm['student_name'] ?? 'ID: '.$asm['student_id']) ?></td>
                                <td><?= htmlspecialchars($asm['course_name']) ?></td>
                                <td><?= htmlspecialchars($asm['assessment_type']) ?></td>
                                <td><?= htmlspecialchars($asm['title']) ?></td>
                                <td><?= (int)$asm['total_marks'] ?></td>
                                <td><?= (int)$asm['marks_obtained'] ?></td>
                                <td><?= htmlspecialchars($asm['assessment_date']) ?></td>
                                <td><?= htmlspecialchars($asm['comments']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick='editAssessment(<?= json_encode($asm) ?>)'><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this assessment?')">
                                        <input type="hidden" name="action" value="delete_assessment">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="assessment_id" value="<?= (int)$asm['id'] ?>">
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
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
                    <?php if ($flash_msg && ($_GET['page'] ?? '') === 'materials'): ?><div class="alert alert-<?= $flash_type ?>"><?= htmlspecialchars($flash_msg) ?></div><?php endif; ?>
                    <div class="mb-3">
                        <button class="btn btn-primary" onclick="document.getElementById('addResourceForm').style.display=document.getElementById('addResourceForm').style.display==='none'?'block':'none'">
                            <i class="fas fa-plus"></i> Add Resource
                        </button>
                    </div>
                    <div id="addResourceForm" style="display:none" class="card card-body mb-4">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_resource">
                            <?php csrfField(); ?>
                            <div class="row">
                                <div class="col-md-4 mb-2"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                                <div class="col-md-4 mb-2"><label>Resource Type</label><select name="resource_type" class="form-control" required><option value="PDF">PDF</option><option value="Video">Video</option><option value="Document">Document</option><option value="Link">Link</option><option value="Slide">Slide</option></select></div>
                                <div class="col-md-4 mb-2"><label>Course Name</label><input type="text" name="course_name" class="form-control" required></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2"><label>File Path</label><input type="text" name="file_path" class="form-control" placeholder="/uploads/file.pdf"></div>
                                <div class="col-md-6 mb-2"><label>URL</label><input type="url" name="url" class="form-control" placeholder="https://..."></div>
                            </div>
                            <div class="mb-2"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                            <button type="submit" class="btn btn-success">Save Resource</button>
                        </form>
                    </div>
                    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchAFUN" type="text" placeholder="Search..." onkeyup="filterTable('srchAFUN','tblAFUN')"></div>
<div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>#</th><th>Title</th><th>Type</th><th>Course</th><th>File/URL</th><th>Description</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($teaching_resources)): ?>
                            <tr><td colspan="7" class="text-center text-muted">No resources found</td></tr>
                            <?php else: foreach ($teaching_resources as $i => $res): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= htmlspecialchars($res['title']) ?></td>
                                <td><?= htmlspecialchars($res['resource_type']) ?></td>
                                <td><?= htmlspecialchars($res['course_name']) ?></td>
                                <td><?= htmlspecialchars($res['file_path'] ?: $res['url'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($res['description']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this resource?')">
                                        <input type="hidden" name="action" value="delete_resource">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="resource_id" value="<?= (int)$res['id'] ?>">
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
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

                <!-- Announcements -->
                <section id="announcements" class="content-section dashboard-section<?= $section==='announcements'?' active':'' ?>" data-section="announcements">
                    <h2>Teaching Announcements</h2>
                    <?php if ($flash_msg && ($_GET['page'] ?? '') === 'announcements'): ?><div class="alert alert-<?= $flash_type ?>"><?= htmlspecialchars($flash_msg) ?></div><?php endif; ?>
                    <div class="mb-3">
                        <button class="btn btn-primary" onclick="document.getElementById('addAnnouncementForm').style.display=document.getElementById('addAnnouncementForm').style.display==='none'?'block':'none'">
                            <i class="fas fa-plus"></i> Add Announcement
                        </button>
                    </div>
                    <div id="addAnnouncementForm" style="display:none" class="card card-body mb-4">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_announcement">
                            <?php csrfField(); ?>
                            <div class="row">
                                <div class="col-md-6 mb-2"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                                <div class="col-md-4 mb-2"><label>Target Audience</label><select name="target_audience" class="form-control"><option value="All">All</option><option value="Students">Students</option><option value="Staff">Staff</option><option value="Department">Department</option></select></div>
                                <div class="col-md-2 mb-2"><label>&nbsp;</label><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_published" value="1" checked><label class="form-check-label">Published</label></div></div>
                            </div>
                            <div class="mb-2"><label>Content</label><textarea name="content" class="form-control" rows="3" required></textarea></div>
                            <button type="submit" class="btn btn-success">Post Announcement</button>
                        </form>
                    </div>
                    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchZXOP" type="text" placeholder="Search..." onkeyup="filterTable('srchZXOP','tblZXOP')"></div>
<div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>#</th><th>Title</th><th>Content</th><th>Audience</th><th>Published</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php if (empty($all_announcements)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No announcements found</td></tr>
                            <?php else: foreach ($all_announcements as $i => $ann): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= htmlspecialchars($ann['title']) ?></td>
                                <td><?= htmlspecialchars($ann['content']) ?></td>
                                <td><?= htmlspecialchars($ann['target_audience']) ?></td>
                                <td><span class="badge bg-<?= $ann['is_published'] ? 'success' : 'secondary' ?>"><?= $ann['is_published'] ? 'Yes' : 'No' ?></span></td>
                                <td><?= !empty($ann['created_at']) ? date('M j, Y', strtotime($ann['created_at'])) : '-' ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
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

    <!-- Attendance -->
    <section id="attendance" class="content-section dashboard-section section-card<?= $section==='attendance'?' active':'' ?>" data-section="attendance" style="<?= $section==='attendance'?'':'display:none' ?>">
        <h2><i class="fas fa-calendar-check me-2"></i>Attendance Records</h2>
        <?php
        $attendanceRecords = [];
        if ($studentsConn) {
            $att_stmt = $studentsConn->prepare("SELECT sa.*, s.full_name, s.student_number FROM student_attendance sa JOIN students s ON sa.student_id=s.id WHERE sa.course_id IN (SELECT course_id FROM course_assignments WHERE lecturer_id=?) ORDER BY sa.date DESC LIMIT 20");
            if ($att_stmt) { $att_stmt->bind_param('i', $user_id); $r = $att_stmt->execute() ? $att_stmt->get_result() : null; if ($r) $attendanceRecords = $r->fetch_all(MYSQLI_ASSOC); $att_stmt->close(); }
        }
        if (empty($attendanceRecords)): ?><p class="text-muted text-center py-3">No attendance records found for your courses.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchPYFT" type="text" placeholder="Search..." onkeyup="filterTable('srchPYFT','tblPYFT')"></div>
<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>Date</th><th>Student</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($attendanceRecords as $a): ?><tr><td><?= htmlspecialchars($a['date']??'') ?></td><td><?= htmlspecialchars($a['full_name']??$a['student_number']??'') ?></td><td><span class="badge bg-<?= ($a['status']??'')==='Present'?'success':(($a['status']??'')==='Absent'?'danger':'warning') ?>"><?= htmlspecialchars($a['status']??'N/A') ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <!-- CAT Marks -->
    <section id="cat-marks" class="content-section dashboard-section section-card<?= $section==='cat-marks'?' active':'' ?>" data-section="cat-marks" style="<?= $section==='cat-marks'?'':'display:none' ?>">
        <h2><i class="fas fa-pen me-2"></i>CAT Marks Entry</h2>
        <?php
        $catRecords = [];
        if ($conn) {
            $cat_stmt = $conn->prepare("SELECT ar.*, c.course_name, c.course_code, s.full_name as student_name FROM academic_records ar LEFT JOIN courses c ON ar.course_id=c.id LEFT JOIN {$students_db_name}.students s ON ar.student_id=s.id WHERE ar.lecturer_id=? AND ar.assessment_type IN ('CAT','Assignment','Quiz') ORDER BY ar.created_at DESC LIMIT 20");
            if ($cat_stmt) { $cat_stmt->bind_param('i', $user_id); $r = $cat_stmt->execute() ? $cat_stmt->get_result() : null; if ($r) $catRecords = $r->fetch_all(MYSQLI_ASSOC); $cat_stmt->close(); }
        }
        if (empty($catRecords)): ?><p class="text-muted text-center py-3">No CAT marks recorded yet.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchFHMT" type="text" placeholder="Search..." onkeyup="filterTable('srchFHMT','tblFHMT')"></div>
<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>Student</th><th>Course</th><th>Score</th><th>Grade</th></tr></thead><tbody>
        <?php foreach ($catRecords as $c): ?><tr><td><?= htmlspecialchars($c['student_name']??$c['student_id']??'-') ?></td><td><?= htmlspecialchars($c['course_code']??$c['course_name']??'-') ?></td><td><?= htmlspecialchars($c['marks']??$c['score']??$c['total_marks']??'-') ?></td><td><?= htmlspecialchars($c['grade']??'-') ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <!-- Exam Marks -->
    <section id="exam-marks" class="content-section dashboard-section section-card<?= $section==='exam-marks'?' active':'' ?>" data-section="exam-marks" style="<?= $section==='exam-marks'?'':'display:none' ?>">
        <h2><i class="fas fa-file-alt me-2"></i>Exam Marks</h2>
        <?php
        $examRecords = [];
        if ($conn) {
            $exam_stmt = $conn->prepare("SELECT ar.*, c.course_name, c.course_code, s.full_name as student_name FROM academic_records ar LEFT JOIN courses c ON ar.course_id=c.id LEFT JOIN {$students_db_name}.students s ON ar.student_id=s.id WHERE ar.lecturer_id=? AND ar.assessment_type='Exam' ORDER BY ar.created_at DESC LIMIT 20");
            if ($exam_stmt) { $exam_stmt->bind_param('i', $user_id); $r = $exam_stmt->execute() ? $exam_stmt->get_result() : null; if ($r) $examRecords = $r->fetch_all(MYSQLI_ASSOC); $exam_stmt->close(); }
        }
        if (empty($examRecords)): ?><p class="text-muted text-center py-3">No exam marks recorded yet.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchZEDC" type="text" placeholder="Search..." onkeyup="filterTable('srchZEDC','tblZEDC')"></div>
<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>Student</th><th>Course</th><th>Score</th><th>Grade</th></tr></thead><tbody>
        <?php foreach ($examRecords as $e): ?><tr><td><?= htmlspecialchars($e['student_name']??$e['student_id']??'-') ?></td><td><?= htmlspecialchars($e['course_code']??$e['course_name']??'-') ?></td><td><?= htmlspecialchars($e['marks']??$e['score']??$e['total_marks']??'-') ?></td><td><?= htmlspecialchars($e['grade']??'-') ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <!-- Results -->
    <section id="results" class="content-section dashboard-section section-card<?= $section==='results'?' active':'' ?>" data-section="results" style="<?= $section==='results'?'':'display:none' ?>">
        <h2><i class="fas fa-chart-bar me-2"></i>Student Results</h2>
        <?php
        $totalGrades = array_sum($grade_distribution??[]);
        $passCount = ($grade_distribution['A']??0)+($grade_distribution['B']??0)+($grade_distribution['C']??0);
        ?>
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card"><div class="card-body text-center"><h6>Total Records</h6><h3><?= $totalGrades ?></h3></div></div></div>
            <div class="col-md-4"><div class="card border-success"><div class="card-body text-center"><h6 class="text-success">Pass Rate</h6><h3 class="text-success"><?= $totalGrades > 0 ? round($passCount/$totalGrades*100) : 0 ?>%</h3></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><p class="mb-0 text-muted">View details: <a href="exams-results.php">Exams & Results</a></p></div></div></div>
        </div>
    </section>

    <!-- Reports -->
    <section id="reports" class="content-section dashboard-section section-card<?= $section==='reports'?' active':'' ?>" data-section="reports" style="<?= $section==='reports'?'':'display:none' ?>">
        <h2><i class="fas fa-file-invoice me-2"></i>Reports Center</h2>
        <div class="row g-3">
            <div class="col-md-6"><div class="card"><div class="card-body"><h5>Teaching Summary</h5><p>Courses: <?= $assigned_courses ?> | Students: <?= $total_students ?> | Pending: <?= $pending_grades ?></p><a href="?page=overview" class="btn btn-sm btn-outline-primary">Overview</a></div></div></div>
            <div class="col-md-6"><div class="card"><div class="card-body"><h5>Quick Actions</h5><a href="staff_transcript_generation.php" class="btn btn-sm btn-outline-info"><i class="fas fa-file-pdf me-1"></i>Transcripts</a></div></div></div>
        </div>
    </section>

    <!-- Lesson Plans -->
    <section id="lesson-plans" class="content-section dashboard-section section-card<?= $section==='lesson-plans'?' active':'' ?>" data-section="lesson-plans" style="<?= $section==='lesson-plans'?'':'display:none' ?>">
        <h2><i class="fas fa-clipboard-list me-2"></i>Lesson Plans</h2>
        <?php
        $lessonPlans = [];
        if ($conn) {
            $lp_stmt = $conn->prepare("SELECT * FROM lesson_plans WHERE lecturer_id=? ORDER BY created_at DESC LIMIT 10");
            if ($lp_stmt) { $lp_stmt->bind_param('i', $user_id); $r = $lp_stmt->execute() ? $lp_stmt->get_result() : null; if ($r) $lessonPlans = $r->fetch_all(MYSQLI_ASSOC); $lp_stmt->close(); }
        }
        if (empty($lessonPlans)): ?><p class="text-muted text-center py-3">No lesson plans created yet.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchCHFJ" type="text" placeholder="Search..." onkeyup="filterTable('srchCHFJ','tblCHFJ')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Title</th><th>Course</th><th>Date</th></tr></thead><tbody>
        <?php foreach ($lessonPlans as $lp): ?><tr><td><?= htmlspecialchars($lp['title']??$lp['topic']??'-') ?></td><td><?= htmlspecialchars($lp['course_name']??$lp['course']??'-') ?></td><td><?= htmlspecialchars($lp['created_at']??'') ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <!-- Assignments -->
    <section id="assignments" class="content-section dashboard-section section-card<?= $section==='assignments'?' active':'' ?>" data-section="assignments" style="<?= $section==='assignments'?'':'display:none' ?>">
        <h2><i class="fas fa-tasks me-2"></i>Assignments</h2>
        <?php
        $assignments = [];
        if ($conn) {
            $as_stmt = $conn->prepare("SELECT * FROM assignments WHERE lecturer_id=? ORDER BY created_at DESC LIMIT 10");
            if ($as_stmt) { $as_stmt->bind_param('i', $user_id); $r = $as_stmt->execute() ? $as_stmt->get_result() : null; if ($r) $assignments = $r->fetch_all(MYSQLI_ASSOC); $as_stmt->close(); }
        }
        if (empty($assignments)): ?><p class="text-muted text-center py-3">No assignments created yet.</p>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchACKD" type="text" placeholder="Search..." onkeyup="filterTable('srchACKD','tblACKD')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Title</th><th>Course</th><th>Due Date</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($assignments as $as): ?><tr><td><?= htmlspecialchars($as['title']??'-') ?></td><td><?= htmlspecialchars($as['course_name']??$as['course_id']??'-') ?></td><td><?= htmlspecialchars($as['due_date']??$as['deadline']??'') ?></td><td><span class="badge bg-<?= ($as['status']??'')==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($as['status']??'Active') ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';
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
                case 'courseMaterials':
                    modalTitle.textContent = 'Upload Course Materials';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_resource"><div class="mb-3"><label class="form-label">Course</label><select name="course_id" class="form-control" required><option value="">Select Course</option><option value="nursing-fundamentals">Nursing Fundamentals</option><option value="medical-surgical">Medical Surgical Nursing</option></select></div><div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div><div class="mb-3"><label class="form-label">Type</label><select name="file_type" class="form-control" required><option value="lecture_notes">Lecture Notes</option><option value="slides">Slides</option><option value="video">Video</option><option value="document">Document</option></select></div><div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div><button type="submit" class="btn btn-primary">Upload</button></form>`;
                    break;
                case 'syllabus':
                    modalTitle.textContent = 'Course Syllabus';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_syllabus"><div class="mb-3"><label class="form-label">Course Name</label><input type="text" name="course_name" class="form-control" required></div><div class="mb-3"><label class="form-label">Semester</label><select name="semester" class="form-control"><option value="1">Semester 1</option><option value="2">Semester 2</option></select></div><div class="mb-3"><label class="form-label">Topics (comma separated)</label><textarea name="topics" class="form-control" rows="3" required></textarea></div><div class="mb-3"><label class="form-label">Learning Outcomes</label><textarea name="learning_outcomes" class="form-control" rows="3" required></textarea></div><button type="submit" class="btn btn-primary">Save Syllabus</button></form>`;
                    break;
                case 'lessonPlan':
                    modalTitle.textContent = 'Create Lesson Plan';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_lesson_plan"><div class="row mb-3"><div class="col-md-6"><label class="form-label">Course</label><select name="course_id" class="form-control" required><option value="">Select</option><option value="nursing-fundamentals">Nursing Fundamentals</option><option value="medical-surgical">Medical Surgical</option></select></div><div class="col-md-6"><label class="form-label">Week Number</label><input type="number" name="week_number" class="form-control" min="1" required></div></div><div class="mb-3"><label class="form-label">Topic</label><input type="text" name="topic" class="form-control" required></div><div class="mb-3"><label class="form-label">Objectives</label><textarea name="objectives" class="form-control" rows="2" required></textarea></div><div class="mb-3"><label class="form-label">Activities</label><textarea name="activities" class="form-control" rows="2" required></textarea></div><button type="submit" class="btn btn-primary">Save Lesson Plan</button></form>`;
                    break;
                case 'courseEvaluation':
                    modalTitle.textContent = 'Course Evaluation';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_evaluation"><div class="mb-3"><label class="form-label">Course</label><select name="course_id" class="form-control" required><option value="">Select</option><option value="nursing-fundamentals">Nursing Fundamentals</option><option value="medical-surgical">Medical Surgical</option></select></div><div class="mb-3"><label class="form-label">Semester</label><select name="semester" class="form-control"><option value="1">Semester 1</option><option value="2">Semester 2</option></select></div><div class="mb-3"><label class="form-label">Feedback</label><textarea name="feedback" class="form-control" rows="4" required></textarea></div><button type="submit" class="btn btn-primary">Submit Evaluation</button></form>`;
                    break;
                case 'addLecture':
                    modalTitle.textContent = 'Schedule Lecture';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_lecture"><div class="mb-3"><label class="form-label">Course</label><select name="course_id" class="form-control" required><option value="">Select</option><option value="nursing-fundamentals">Nursing Fundamentals</option><option value="medical-surgical">Medical Surgical</option></select></div><div class="mb-3"><label class="form-label">Topic</label><input type="text" name="topic" class="form-control" required></div><div class="row mb-3"><div class="col-md-6"><label class="form-label">Date</label><input type="date" name="lecture_date" class="form-control" required></div><div class="col-md-3"><label class="form-label">Start</label><input type="time" name="start_time" class="form-control" required></div><div class="col-md-3"><label class="form-label">End</label><input type="time" name="end_time" class="form-control" required></div></div><div class="mb-3"><label class="form-label">Venue</label><input type="text" name="venue" class="form-control"></div><button type="submit" class="btn btn-primary">Schedule</button></form>`;
                    break;
                case 'weeklySchedule':
                    modalTitle.textContent = 'Weekly Schedule';
                    modalBody.innerHTML = `<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Day</th><th>Time</th><th>Course</th><th>Venue</th></tr></thead><tbody id="weeklyScheduleBody"><tr><td colspan="4" class="text-center text-muted">Loading schedule...</td></tr></tbody></table></div>`;
                    fetch('../ajax/weekly_schedule.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=get_weekly'}).then(r=>r.json()).then(d=>{var h='';if(d.success&&d.data){d.data.forEach(r=>{h+=`<tr><td>${r.day}</td><td>${r.start_time}-${r.end_time}</td><td>${r.course}</td><td>${r.venue||'TBD'}</td></tr>`;});}else{h='<tr><td colspan="4" class="text-center text-muted">No lectures scheduled</td></tr>';}document.getElementById('weeklyScheduleBody').innerHTML=h;}).catch(()=>{document.getElementById('weeklyScheduleBody').innerHTML='<tr><td colspan="4" class="text-center text-muted">No lectures scheduled</td></tr>';});
                    break;
                case 'rescheduleLecture':
                    modalTitle.textContent = 'Reschedule Lecture';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="reschedule_lecture"><div class="mb-3"><label class="form-label">Lecture ID</label><input type="number" name="lecture_id" class="form-control" required></div><div class="row mb-3"><div class="col-md-6"><label class="form-label">New Date</label><input type="date" name="new_date" class="form-control" required></div><div class="col-md-6"><label class="form-label">New Time</label><input type="time" name="new_time" class="form-control" required></div></div><div class="mb-3"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="2" required></textarea></div><button type="submit" class="btn btn-warning">Reschedule</button></form>`;
                    break;
                case 'cancelLecture':
                    modalTitle.textContent = 'Cancel Lecture';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="cancel_lecture"><div class="mb-3"><label class="form-label">Lecture ID</label><input type="number" name="lecture_id" class="form-control" required></div><div class="mb-3"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="3" required></textarea></div><button type="submit" class="btn btn-danger">Cancel Lecture</button></form>`;
                    break;
                case 'studentList':
                    modalTitle.textContent = 'Student List';
                    modalBody.innerHTML = `<div class="mb-3"><label class="form-label">Select Course</label><select class="form-control" id="studentListCourse"><option value="">All Courses</option></select></div><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>Status</th></tr></thead><tbody id="studentListBody"><tr><td colspan="4" class="text-center text-muted">Select a course</td></tr></tbody></table></div>`;
                    break;
                case 'attendance':
                    modalTitle.textContent = 'Mark Attendance';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="mark_attendance"><div class="mb-3"><label class="form-label">Course</label><select name="course_id" class="form-control" required><option value="">Select</option><option value="nursing-fundamentals">Nursing Fundamentals</option><option value="medical-surgical">Medical Surgical</option></select></div><div class="mb-3"><label class="form-label">Student ID</label><input type="number" name="student_id" class="form-control" required></div><div class="mb-3"><label class="form-label">Date</label><input type="date" name="att_date" class="form-control" required></div><div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-control"><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="excused">Excused</option></select></div><div class="mb-3"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control"></div><button type="submit" class="btn btn-primary">Save Attendance</button></form>`;
                    break;
                case 'studentProgress':
                    modalTitle.textContent = 'Student Progress';
                    modalBody.innerHTML = `<div class="mb-3"><label class="form-label">Student ID</label><div class="input-group"><input type="number" class="form-control" id="progressStudentId"><button class="btn btn-primary" onclick="loadStudentProgress()">Load</button></div></div><div id="progressContent"><p class="text-muted text-center">Enter student ID to view progress</p></div>`;
                    break;
                case 'studentCounseling':
                    modalTitle.textContent = 'Student Counseling';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_counseling"><div class="mb-3"><label class="form-label">Student ID</label><input type="number" name="student_id" class="form-control" required></div><div class="mb-3"><label class="form-label">Concern</label><textarea name="concern" class="form-control" rows="3" required></textarea></div><div class="mb-3"><label class="form-label">Action Taken</label><textarea name="action_taken" class="form-control" rows="2"></textarea></div><button type="submit" class="btn btn-primary">Save Record</button></form>`;
                    break;
                case 'gradebook':
                    modalTitle.textContent = 'Grade Book';
                    modalBody.innerHTML = `<div class="mb-3"><label class="form-label">Select Course</label><select class="form-control" id="gradebookCourse"><option value="">Select</option><option value="nursing-fundamentals">Nursing Fundamentals</option><option value="medical-surgical">Medical Surgical</option></select></div><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Student</th><th>CAT 1</th><th>CAT 2</th><th>Exam</th><th>Total</th><th>Grade</th></tr></thead><tbody id="gradebookBody"><tr><td colspan="6" class="text-center text-muted">Select a course</td></tr></tbody></table></div>`;
                    break;
                case 'gradeSubmission':
                    modalTitle.textContent = 'Submit Grades';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="submit_grade"><div class="row mb-3"><div class="col-md-6"><label class="form-label">Course</label><select name="course_id" class="form-control" required><option value="">Select</option><option value="nursing-fundamentals">Nursing Fundamentals</option><option value="medical-surgical">Medical Surgical</option></select></div><div class="col-md-6"><label class="form-label">Student ID</label><input type="number" name="student_id" class="form-control" required></div></div><div class="row mb-3"><div class="col-md-4"><label class="form-label">Assessment Type</label><select name="assessment_type" class="form-control"><option value="CAT1">CAT 1</option><option value="CAT2">CAT 2</option><option value="Exam">Exam</option></select></div><div class="col-md-4"><label class="form-label">Score</label><input type="number" name="score" class="form-control" min="0" max="100" required></div><div class="col-md-4"><label class="form-label">Grade</label><select name="grade" class="form-control"><option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option><option value="F">F</option></select></div></div><button type="submit" class="btn btn-primary">Submit Grade</button></form>`;
                    break;
                case 'gradeAnalysis':
                    modalTitle.textContent = 'Grade Analysis';
                    modalBody.innerHTML = `<div class="mb-3"><label class="form-label">Select Course</label><select class="form-control" id="analysisCourse"><option value="">Select</option><option value="nursing-fundamentals">Nursing Fundamentals</option><option value="medical-surgical">Medical Surgical</option></select></div><div id="analysisContent"><p class="text-muted text-center">Select a course to view analysis</p></div>`;
                    break;
                case 'gradeAppeals':
                    modalTitle.textContent = 'Grade Appeals';
                    modalBody.innerHTML = `<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Student</th><th>Course</th><th>Assessment</th><th>Current Grade</th><th>Reason</th><th>Status</th></tr></thead><tbody id="appealsBody"><tr><td colspan="6" class="text-center text-muted">No pending appeals</td></tr></tbody></table></div>`;
                    break;
                case 'researchProject':
                    modalTitle.textContent = 'New Research Project';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_research_project"><div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div><div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" required></textarea></div><div class="row mb-3"><div class="col-md-6"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control"></div><div class="col-md-6"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control"></div></div><div class="row mb-3"><div class="col-md-6"><label class="form-label">Budget (UGX)</label><input type="number" name="budget" class="form-control"></div><div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-control"><option value="Proposal">Proposal</option><option value="In Progress">In Progress</option><option value="Completed">Completed</option></select></div></div><button type="submit" class="btn btn-primary">Save Project</button></form>`;
                    break;
                case 'publications':
                    modalTitle.textContent = 'Record Publication';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_publication"><div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div><div class="mb-3"><label class="form-label">Authors</label><input type="text" name="authors" class="form-control" required></div><div class="mb-3"><label class="form-label">Journal</label><input type="text" name="journal" class="form-control"></div><div class="row mb-3"><div class="col-md-6"><label class="form-label">Publication Date</label><input type="date" name="publication_date" class="form-control"></div><div class="col-md-6"><label class="form-label">DOI</label><input type="text" name="doi" class="form-control"></div></div><div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-control"><option value="Published">Published</option><option value="Under Review">Under Review</option><option value="Draft">Draft</option></select></div><button type="submit" class="btn btn-primary">Save Publication</button></form>`;
                    break;
                case 'conferences':
                    modalTitle.textContent = 'Record Conference';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_conference"><div class="mb-3"><label class="form-label">Paper Title</label><input type="text" name="paper_title" class="form-control" required></div><div class="mb-3"><label class="form-label">Conference Name</label><input type="text" name="conference_name" class="form-control" required></div><div class="row mb-3"><div class="col-md-6"><label class="form-label">Location</label><input type="text" name="location" class="form-control"></div><div class="col-md-6"><label class="form-label">Date</label><input type="date" name="conf_date" class="form-control"></div></div><div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-control"><option value="Presented">Presented</option><option value="Accepted">Accepted</option><option value="Submitted">Submitted</option></select></div><button type="submit" class="btn btn-primary">Save Record</button></form>`;
                    break;
                case 'researchSupervision':
                    modalTitle.textContent = 'Research Supervision';
                    modalBody.innerHTML = `<form method="POST"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}"><input type="hidden" name="action" value="add_supervision"><div class="mb-3"><label class="form-label">Student ID</label><input type="number" name="student_id" class="form-control" required></div><div class="mb-3"><label class="form-label">Research Title</label><input type="text" name="research_title" class="form-control" required></div><div class="mb-3"><label class="form-label">Supervisor Notes</label><textarea name="supervisor_notes" class="form-control" rows="3"></textarea></div><div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-control"><option value="In Progress">In Progress</option><option value="Completed">Completed</option><option value="On Hold">On Hold</option></select></div><button type="submit" class="btn btn-primary">Save Supervision</button></form>`;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }

        function editAssessment(a) {
            var m = new bootstrap.Modal(document.getElementById('actionModal'));
            document.getElementById('modalTitle').textContent = 'Edit Assessment';
            document.getElementById('modalBody').innerHTML = `
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
                    <input type="hidden" name="action" value="update_assessment">
                    <input type="hidden" name="assessment_id" value="${a.id}">
                    <div class="row">
                        <div class="col-md-4 mb-2"><label>Student ID</label><input type="number" name="student_id" class="form-control" value="${a.student_id}" required></div>
                        <div class="col-md-4 mb-2"><label>Course Name</label><input type="text" name="course_name" class="form-control" value="${a.course_name||''}" required></div>
                        <div class="col-md-4 mb-2"><label>Assessment Type</label><select name="assessment_type" class="form-control" required>
                            <option value="CAT" ${a.assessment_type==='CAT'?'selected':''}>CAT</option>
                            <option value="Assignment" ${a.assessment_type==='Assignment'?'selected':''}>Assignment</option>
                            <option value="Exam" ${a.assessment_type==='Exam'?'selected':''}>Exam</option>
                            <option value="Project" ${a.assessment_type==='Project'?'selected':''}>Project</option>
                        </select></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-2"><label>Title</label><input type="text" name="title" class="form-control" value="${a.title||''}" required></div>
                        <div class="col-md-2 mb-2"><label>Total Marks</label><input type="number" name="total_marks" class="form-control" value="${a.total_marks}" required></div>
                        <div class="col-md-2 mb-2"><label>Marks Obtained</label><input type="number" name="marks_obtained" class="form-control" value="${a.marks_obtained}" required></div>
                        <div class="col-md-2 mb-2"><label>Date</label><input type="date" name="assessment_date" class="form-control" value="${a.assessment_date||''}" required></div>
                    </div>
                    <div class="mb-2"><label>Comments</label><textarea name="comments" class="form-control" rows="2">${a.comments||''}</textarea></div>
                </form>`;
            document.getElementById('modalAction').textContent = 'Update';
            document.getElementById('modalAction').onclick = function() {
                document.getElementById('modalBody').querySelector('form').submit();
            };
            m.show();
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

<script>
document.querySelectorAll('form[method="POST"]').forEach(function(form) {
    if (!form.querySelector('input[name="csrf_token"]')) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
        form.appendChild(input);
    }
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

