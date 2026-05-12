<?php
// ISNM Enhanced Academic Registrar Dashboard
// Professional 3D graphics with student search, filtering, and comprehensive functionality

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include unified authentication system
require_once '../auth-service.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

// Strict dashboard protection - only academic registrar allowed
if (!$auth_service->isAuthenticated()) {
    header('Location: ../staff-login.php');
    exit();
}

// Check if user has the correct role
$userRole = $_SESSION['role'] ?? '';
if (stripos($userRole, 'academic') === false && stripos($userRole, 'registrar') === false) {
    header('Location: ../staff-login.php?error=unauthorized');
    exit();
}

// Database connections
$staff_conn = new mysqli('localhost', 'root', '', 'staffs_db');
$students_conn = new mysqli('localhost', 'root', '', 'students_db');

if ($staff_conn->connect_error) {
    die("Staff DB connection failed: " . $staff_conn->connect_error);
}

if ($students_conn->connect_error) {
    die("Students DB connection failed: " . $students_conn->connect_error);
}

// Set charset
$staff_conn->set_charset("utf8mb4");
$students_conn->set_charset("utf8mb4");

// Get user information from session
$staff_id = $_SESSION['user_id'] ?? 0;
$staff_email = $_SESSION['email'] ?? '';
$staff_name = $_SESSION['full_name'] ?? '';

// Handle search functionality
$search_term = $_GET['search'] ?? '';
$filter_course = $_GET['course'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_semester = $_GET['semester'] ?? '';

// Handle student management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_student':
            handleAddStudent();
            break;
        case 'update_student':
            handleUpdateStudent();
            break;
        case 'delete_student':
            handleDeleteStudent();
            break;
        case 'generate_transcript':
            handleGenerateTranscript();
            break;
        case 'generate_results':
            handleGenerateResults();
            break;
    }
}

// Function to add student
function handleAddStudent() {
    global $students_conn;
    
    $full_name = sanitizeInput($_POST['full_name']);
    $registration_number = sanitizeInput($_POST['registration_number']);
    $course = sanitizeInput($_POST['course']);
    $year = sanitizeInput($_POST['year']);
    $semester = sanitizeInput($_POST['semester']);
    $intake_date = sanitizeInput($_POST['intake_date']);
    $national_id = sanitizeInput($_POST['national_id'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    $sql = "INSERT INTO students (full_name, registration_number, course, year, semester, intake_date, national_student_id_number, email, phone, address, status, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $students_conn->prepare($sql);
    $stmt->bind_param("sssssssss", $full_name, $registration_number, $course, $year, $semester, $intake_date, $national_id, $email, $phone, $address, 'Active', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add student.";
    }
    
    header("Location: academic-registrar-enhanced.php");
    exit();
}

// Function to update student
function handleUpdateStudent() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    $full_name = sanitizeInput($_POST['full_name']);
    $registration_number = sanitizeInput($_POST['registration_number']);
    $course = sanitizeInput($_POST['course']);
    $year = sanitizeInput($_POST['year']);
    $semester = sanitizeInput($_POST['semester']);
    $status = sanitizeInput($_POST['status']);
    
    $sql = "UPDATE students SET full_name = ?, registration_number = ?, course = ?, year = ?, semester = ?, status = ?, updated_by = NOW() WHERE id = ?";
    
    $stmt = $students_conn->prepare($sql);
    $stmt->bind_param("sssssi", $full_name, $registration_number, $course, $year, $semester, $status, $_SESSION['user_id'], $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update student.";
    }
    
    header("Location: academic-registrar-enhanced.php");
    exit();
}

// Function to delete student
function handleDeleteStudent() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $students_conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete student.";
    }
    
    header("Location: academic-registrar-enhanced.php");
    exit();
}

// Function to generate transcript
function handleGenerateTranscript() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    $academic_year = $_POST['academic_year'];
    $semester = $_POST['semester'];
    
    // Get student information
    $student_sql = "SELECT s.*, 
                          GROUP_CONCAT(DISTINCT ar.semester, ' - ', ar.academic_year) as academic_history,
                          ROUND(AVG(ar.gpa), 2) as cumulative_gpa,
                          COUNT(DISTINCT ar.course_code) as total_courses,
                          MAX(ar.grading_date) as last_updated
                   FROM students s
                   LEFT JOIN academic_records ar ON s.id = ar.student_id
                   WHERE s.id = ? AND s.status = 'Active'";
    
    $student_stmt = $students_conn->prepare($student_sql);
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student = $student_stmt->get_result()->fetch_assoc();
    
    if (!$student) {
        $_SESSION['error'] = "Student not found.";
        header("Location: academic-registrar-enhanced.php");
        exit();
    }
    
    // Get detailed academic records
    $records_sql = "SELECT ar.*, c.course_name, sr.full_name as lecturer_name
                    FROM academic_records ar
                    LEFT JOIN courses c ON ar.course_code = c.course_code
                    LEFT JOIN staff sr ON ar.graded_by = sr.id
                    WHERE ar.student_id = ? AND ar.academic_year = ? AND ar.semester = ?
                    ORDER BY ar.academic_year DESC, ar.semester DESC, ar.grading_date DESC";
    
    $records_stmt = $students_conn->prepare($records_sql);
    $records_stmt->bind_param("iss", $student_id, $academic_year, $semester);
    $records_stmt->execute();
    $academic_records = $records_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Generate transcript content
    $transcript_content = generateTranscriptContent($student, $academic_records, $academic_year, $semester);
    
    // Save transcript
    $transcript_number = 'TRANS_' . date('YmdHis') . str_pad($student_id, 4, '0', STR_PAD_LEFT);
    $access_code = 'TRANS_' . uniqid();
    
    $save_sql = "INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, access_code, generation_date) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $save_stmt = $students_conn->prepare($save_sql);
    $save_stmt->bind_param("sissss", 'Transcript', $student_id, $_SESSION['user_id'], 'Academic Transcript - ' . $student['full_name'], $transcript_content, $access_code);
    
    if ($save_stmt->execute()) {
        $_SESSION['success'] = "Transcript generated successfully! Transcript Number: $transcript_number";
    } else {
        $_SESSION['error'] = "Failed to generate transcript.";
    }
    
    header("Location: academic-registrar-enhanced.php");
    exit();
}

// Function to generate results
function handleGenerateResults() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    $academic_year = $_POST['academic_year'];
    $semester = $_POST['semester'];
    
    // Get student information
    $student_sql = "SELECT s.*, ROUND(AVG(ar.gpa), 2) as cumulative_gpa
                   FROM students s
                   LEFT JOIN academic_records ar ON s.id = ar.student_id
                   WHERE s.id = ? AND s.status = 'Active'";
    
    $student_stmt = $students_conn->prepare($student_sql);
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student = $student_stmt->get_result()->fetch_assoc();
    
    if (!$student) {
        $_SESSION['error'] = "Student not found.";
        header("Location: academic-registrar-enhanced.php");
        exit();
    }
    
    // Get results for the specified period
    $results_sql = "SELECT ar.*, c.course_name, sr.full_name as lecturer_name
                    FROM academic_records ar
                    LEFT JOIN courses c ON ar.course_code = c.course_code
                    LEFT JOIN staff sr ON ar.graded_by = sr.id
                    WHERE ar.student_id = ? AND ar.academic_year = ? AND ar.semester = ?
                    ORDER BY ar.grading_date DESC";
    
    $results_stmt = $students_conn->prepare($results_sql);
    $results_stmt->bind_param("iss", $student_id, $academic_year, $semester);
    $results_stmt->execute();
    $results = $results_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Generate results content
    $results_content = generateResultsContent($student, $results, $academic_year, $semester);
    
    // Save results
    $results_number = 'RES_' . date('YmdHis') . str_pad($student_id, 4, '0', STR_PAD_LEFT);
    $access_code = 'RES_' . uniqid();
    
    $save_sql = "INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, access_code, generation_date) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $save_stmt = $students_conn->prepare($save_sql);
    $save_stmt->bind_param("sissss", 'Result Slip', $student_id, $_SESSION['user_id'], 'Academic Results - ' . $student['full_name'], $results_content, $access_code);
    
    if ($save_stmt->execute()) {
        $_SESSION['success'] = "Results generated successfully! Results Number: $results_number";
    } else {
        $_SESSION['error'] = "Failed to generate results.";
    }
    
    header("Location: academic-registrar-enhanced.php");
    exit();
}

// Generate transcript content
function generateTranscriptContent($student, $academic_records, $academic_year, $semester) {
    $content = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Transcript - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/staff_dashboard_enhanced.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="transcript-container">
        <div class="transcript-header">
            <img src="../images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <div class="transcript-title">ACADEMIC TRANSCRIPT</div>
            <div class="transcript-subtitle">Official Academic Record</div>
        </div>
        
        <div class="transcript-body">
            <div class="section">
                <div class="section-title">Student Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Full Name:</span>
                        <span class="info-value">' . htmlspecialchars($student['full_name']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Registration Number:</span>
                        <span class="info-value">' . htmlspecialchars($student['registration_number']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Program:</span>
                        <span class="info-value">' . htmlspecialchars($student['course']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Year:</span>
                        <span class="info-value">' . htmlspecialchars($academic_year) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Semester:</span>
                        <span class="info-value">' . htmlspecialchars($semester) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Cumulative GPA:</span>
                        <span class="info-value">' . number_format($student['cumulative_gpa'], 2) . '</span>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Academic Records</div>
                <table class="academic-table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>GPA</th>
                            <th>Lecturer</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($academic_records as $record) {
        $content .= '
                        <tr>
                            <td>' . htmlspecialchars($record['course_code']) . '</td>
                            <td>' . htmlspecialchars($record['course_name']) . '</td>
                            <td>' . htmlspecialchars($record['credits']) . '</td>
                            <td>' . htmlspecialchars($record['semester']) . '</td>
                            <td>' . htmlspecialchars($record['academic_year']) . '</td>
                            <td>' . htmlspecialchars($record['marks']) . '</td>
                            <td>' . htmlspecialchars($record['grade']) . '</td>
                            <td>' . htmlspecialchars($record['gpa']) . '</td>
                            <td>' . htmlspecialchars($record['lecturer_name']) . '</td>
                        </tr>';
    }
    
    $content .= '
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="signature-section">
            <div class="signature-line">
                <p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p><strong>Generated by:</strong> ' . $_SESSION['full_name'] . '</p>
                <p><strong>Position:</strong> ' . $_SESSION['position'] . '</p>
                <p><em>This is an electronically generated academic transcript and is valid without signature.</em></p>
            </div>
        </div>
    </div>
</body>
</html>';
    
    return $content;
}

// Generate results content
function generateResultsContent($student, $results, $academic_year, $semester) {
    $content = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Results - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/staff_dashboard_enhanced.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="transcript-container">
        <div class="transcript-header">
            <img src="../images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <div class="transcript-title">ACADEMIC RESULTS</div>
            <div class="transcript-subtitle">Official Academic Results</div>
        </div>
        
        <div class="transcript-body">
            <div class="section">
                <div class="section-title">Student Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Full Name:</span>
                        <span class="info-value">' . htmlspecialchars($student['full_name']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Registration Number:</span>
                        <span class="info-value">' . htmlspecialchars($student['registration_number']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Program:</span>
                        <span class="info-value">' . htmlspecialchars($student['course']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Year:</span>
                        <span class="info-value">' . htmlspecialchars($academic_year) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Semester:</span>
                        <span class="info-value">' . htmlspecialchars($semester) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Cumulative GPA:</span>
                        <span class="info-value">' . number_format($student['cumulative_gpa'], 2) . '</span>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Academic Results</div>
                <table class="academic-table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>GPA</th>
                            <th>Lecturer</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($results as $result) {
        $content .= '
                        <tr>
                            <td>' . htmlspecialchars($result['course_code']) . '</td>
                            <td>' . htmlspecialchars($result['course_name']) . '</td>
                            <td>' . htmlspecialchars($result['credits']) . '</td>
                            <td>' . htmlspecialchars($result['semester']) . '</td>
                            <td>' . htmlspecialchars($result['academic_year']) . '</td>
                            <td>' . htmlspecialchars($result['marks']) . '</td>
                            <td>' . htmlspecialchars($result['grade']) . '</td>
                            <td>' . htmlspecialchars($result['gpa']) . '</td>
                            <td>' . htmlspecialchars($result['lecturer_name']) . '</td>
                        </tr>';
    }
    
    $content .= '
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="signature-section">
            <div class="signature-line">
                <p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p><strong>Generated by:</strong> ' . $_SESSION['full_name'] . '</p>
                <p><strong>Position:</strong> ' . $_SESSION['position'] . '</p>
                <p><em>This is an electronically generated academic results and is valid without signature.</em></p>
            </div>
        </div>
    </div>
</body>
</html>';
    
    return $content;
}

// Get students data with filters
function getStudentsData($search_term, $filter_course, $filter_year, $filter_semester) {
    global $students_conn;
    
    $sql = "SELECT s.*, 
                    GROUP_CONCAT(DISTINCT ar.semester, ' - ', ar.academic_year) as academic_history,
                    ROUND(AVG(ar.gpa), 2) as cumulative_gpa,
                    COUNT(DISTINCT ar.course_code) as total_courses,
                    MAX(ar.grading_date) as last_updated
             FROM students s
             LEFT JOIN academic_records ar ON s.id = ar.student_id
             WHERE s.status = 'Active'";
    
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $sql .= " AND (s.full_name LIKE ? OR s.registration_number LIKE ? OR s.email LIKE ?)";
        $params = array_merge($params, ["%$search_term%", "%$search_term%", "%$search_term%"]);
        $types .= "sss";
    }
    
    if (!empty($filter_course)) {
        $sql .= " AND s.course = ?";
        $params = array_merge($params, [$filter_course]);
        $types .= "s";
    }
    
    if (!empty($filter_year)) {
        $sql .= " AND s.year = ?";
        $params = array_merge($params, [$filter_year]);
        $types .= "s";
    }
    
    if (!empty($filter_semester)) {
        $sql .= " AND s.semester = ?";
        $params = array_merge($params, [$filter_semester]);
        $types .= "s";
    }
    
    $sql .= " GROUP BY s.id ORDER BY s.full_name";
    
    $stmt = $students_conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->execute();
    }
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get courses for filter
function getCourses() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT course FROM students WHERE course IS NOT NULL ORDER BY course";
    $result = $students_conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get years for filter
function getYears() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT year FROM students WHERE year IS NOT NULL ORDER BY year DESC";
    $result = $students_conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get semesters for filter
function getSemesters() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT semester FROM students WHERE semester IS NOT NULL ORDER BY semester";
    $result = $students_conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
function getStatistics() {
    global $students_conn;
    
    $stats = [];
    
    // Student statistics
    $student_sql = "SELECT COUNT(*) as total FROM students WHERE status = 'Active'";
    $student_result = $students_conn->query($student_sql);
    $stats['total_students'] = $student_result->fetch_assoc()['total'];
    
    // Course statistics
    $course_sql = "SELECT COUNT(DISTINCT course) as total FROM students WHERE status = 'Active'";
    $course_result = $students_conn->query($course_sql);
    $stats['total_courses'] = $course_result->fetch_assoc()['total'];
    
    // Academic records statistics
    $records_sql = "SELECT COUNT(*) as total FROM academic_records WHERE grading_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $records_result = $students_conn->query($records_sql);
    $stats['recent_records'] = $records_result ? $records_result->fetch_assoc()['total'] : 0;
    
    // Generated documents statistics
    $docs_sql = "SELECT COUNT(*) as total FROM generated_documents WHERE generation_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $docs_result = $students_conn->query($docs_sql);
    $stats['recent_documents'] = $docs_result ? $docs_result->fetch_assoc()['total'] : 0;
    
    return $stats;
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Registrar Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/staff_dashboard_enhanced.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- School Header with Logo -->
        <div class="school-header">
            <img src="../images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <div>
                <h1>ISNM School Management System</h1>
                <h3>Academic Registrar Management</h3>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- Search and Filter Section -->
            <div class="panel-3d">
                <h3><i class="fas fa-search me-2"></i>Search & Filter Students</h3>
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <input type="text" class="form-control" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <select class="form-control" name="course">
                                <option value="">All Courses</option>
                                <?php
                                $courses = getCourses();
                                foreach ($courses as $course) {
                                    echo '<option value="' . $course['course'] . '">' . htmlspecialchars($course['course']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <select class="form-control" name="year">
                                <option value="">All Years</option>
                                <?php
                                $years = getYears();
                                foreach ($years as $year) {
                                    echo '<option value="' . $year['year'] . '">' . $year['year'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <select class="form-control" name="semester">
                                <option value="">All Semesters</option>
                                <?php
                                $semesters = getSemesters();
                                foreach ($semesters as $semester) {
                                    echo '<option value="' . $semester['semester'] . '">' . $semester['semester'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-1 mb-3">
                            <button type="submit" class="btn-3d">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <?php
            $stats = getStatistics();
            ?>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_students']); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_courses']); ?></div>
                <div class="stat-label">Total Courses</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['recent_records']); ?></div>
                <div class="stat-label">Recent Records</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['recent_documents']); ?></div>
                <div class="stat-label">Recent Documents</div>
            </div>
            
            <!-- Student Management Actions -->
            <div class="panel-3d">
                <h3><i class="fas fa-user-plus me-2"></i>Student Management</h3>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="action">Action:</label>
                            <select class="form-control" id="action" name="action">
                                <option value="add_student">Add Student</option>
                                <option value="update_student">Update Student</option>
                                <option value="delete_student">Delete Student</option>
                                <option value="generate_transcript">Generate Transcript</option>
                                <option value="generate_results">Generate Results</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="student_id">Student:</label>
                            <select class="form-control" id="student_id" name="student_id">
                                <option value="">Select Student</option>
                                <?php
                                $students_sql = "SELECT id, full_name, registration_number, course FROM students WHERE status = 'Active' ORDER BY full_name";
                                $students_result = $students_conn->query($students_sql);
                                
                                while ($student = $students_result->fetch_assoc()) {
                                    echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['full_name']) . ' - ' . htmlspecialchars($student['registration_number']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn-3d">Execute Action</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Student List -->
            <div class="panel-3d">
                <h3><i class="fas fa-users me-2"></i>Student List</h3>
                <div class="table-3d">
                    <table class="academic-table">
                        <thead>
                            <tr>
                                <th>Registration Number</th>
                                <th>Full Name</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>GPA</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $students = getStudentsData($search_term, $filter_course, $filter_year, $filter_semester);
                            
                            foreach ($students as $student) {
                                echo '
                                    <tr>
                                        <td>' . htmlspecialchars($student['registration_number']) . '</td>
                                        <td>' . htmlspecialchars($student['full_name']) . '</td>
                                        <td>' . htmlspecialchars($student['course']) . '</td>
                                        <td>' . htmlspecialchars($student['year']) . '</td>
                                        <td>' . htmlspecialchars($student['semester']) . '</td>
                                        <td>' . number_format($student['cumulative_gpa'], 2) . '</td>
                                        <td><span class="badge bg-success">' . htmlspecialchars($student['status']) . '</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editStudent(' . $student['id'] . ')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteStudent(' . $student['id'] . ')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize 3D effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transform = 'translateY(0)';
                    card.style.opacity = '1';
                }, index * 100);
            });
        });
        
        // Student management functions
        function editStudent(studentId) {
            if (confirm('Are you sure you want to edit this student?')) {
                document.getElementById('action').value = 'update_student';
                document.getElementById('student_id').value = studentId;
                document.querySelector('form').submit();
            }
        }
        
        function deleteStudent(studentId) {
            if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                document.getElementById('action').value = 'delete_student';
                document.getElementById('student_id').value = studentId;
                document.querySelector('form').submit();
            }
        }
    </script>
</body>
</html>
