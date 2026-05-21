<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';

// Database connections using standard functions
$students_conn = getStudentsConnection();
$staff_conn = getStaffConnection();

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

// Check authentication
if (!$auth_service->isAuthenticated()) {
    header('Location: staff-login.php');
    exit();
}

// Get user information
$staff_id = $_SESSION['user_id'] ?? 0;
$staff_email = $_SESSION['email'] ?? '';

// Check if user has permission to generate transcripts
$staff_role = $_SESSION['role'] ?? '';
$can_generate_transcripts = false;

if (stripos($staff_role, 'Academic Registrar') !== false ||
    stripos($staff_role, 'Director') !== false ||
    stripos($staff_role, 'General') !== false ||
    stripos($staff_role, 'Principal') !== false) {
    $can_generate_transcripts = true;
}

if (!$can_generate_transcripts) {
    $_SESSION['error'] = "You don't have permission to generate transcripts.";
    header("Location: staff_transcript_generation.php");
    exit();
}

// Handle transcript generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_transcript'])) {
    $student_id = $_POST['student_id'] ?? 0;
    $transcript_type = $_POST['transcript_type'] ?? 'Official';
    $academic_year = $_POST['academic_year'] ?? '';
    $semester = $_POST['semester'] ?? '';
    
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
    $student_result = $student_stmt->get_result();
    $student = $student_result->fetch_assoc();
    
    if (!$student) {
        $_SESSION['error'] = "Student not found.";
        header("Location: staff_transcript_generation.php");
        exit();
    }
    
    // Get detailed academic records
    $records_sql = "SELECT ar.*, 
                         c.course_name,
                         sr.full_name as lecturer_name
                  FROM academic_records ar
                  LEFT JOIN courses c ON ar.course_code = c.course_code
                  LEFT JOIN staff sr ON ar.graded_by = sr.id
                  WHERE ar.student_id = ? 
                  ORDER BY ar.academic_year DESC, ar.semester DESC, ar.grading_date DESC";
    
    $records_stmt = $students_conn->prepare($records_sql);
    $records_stmt->bind_param("i", $student_id);
    $records_stmt->execute();
    $records_result = $records_stmt->get_result();
    $academic_records = $records_result->fetch_all(MYSQLI_ASSOC);
    
    // Generate transcript content
    $transcript_content = generateTranscriptContent($student, $academic_records, $transcript_type, $academic_year, $semester);
    
    // Save transcript to database
    $transcript_number = 'TRANS_' . date('YmdHis') . str_pad($student_id, 4, '0', STR_PAD_LEFT);
    $access_code = uniqid('TRANS_' . date('Ymd'));
    
    $save_sql = "INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, access_code, generation_date) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $save_stmt = $students_conn->prepare($save_sql);
    $save_stmt->bind_param("sissss", 'Transcript', $student_id, $staff_id, 'Academic Transcript', $transcript_content, $access_code);
    
    if ($save_stmt->execute()) {
        $_SESSION['success'] = "Transcript generated successfully! Transcript Number: $transcript_number";
    } else {
        $_SESSION['error'] = "Failed to generate transcript.";
    }
    
    header("Location: staff_transcript_generation.php");
    exit();
}

// Function to generate transcript content
function generateTranscriptContent($student, $academic_records, $transcript_type, $academic_year, $semester) {
    $content = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Transcript - ISNM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .transcript-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .school-info {
            margin-bottom: 30px;
        }
        .student-info {
            margin-bottom: 30px;
        }
        .academic-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .academic-table th,
        .academic-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .academic-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
            font-style: italic;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            color: rgba(0, 0, 0, 0.1);
            z-index: -1;
        }
        @media print {
            .watermark {
                        display: none;
                    }
        }
    </style>
</head>
<body>
    <div class="watermark">ISNM</div>
    
    <div class="transcript-header">
        <h1>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h1>
        <h2>ACADEMIC TRANSCRIPT</h2>
        <p><strong>Transcript Type:</strong> ' . $transcript_type . '</p>
        <p><strong>Transcript Number:</strong> ' . uniqid() . '</p>
    </div>
    
    <div class="school-info">
        <h3>School Information</h3>
        <p><strong>School:</strong> Iganga School of Nursing and Midwifery</p>
        <p><strong>Address:</strong> Iganga, Uganda</p>
        <p><strong>Phone:</strong> +256 XXX XXX XXX</p>
        <p><strong>Email:</strong> info@isnm.ug</p>
    </div>
    
    <div class="student-info">
        <h3>Student Information</h3>
        <p><strong>Name:</strong> ' . htmlspecialchars($student['full_name']) . '</p>
        <p><strong>Registration Number:</strong> ' . htmlspecialchars($student['registration_number']) . '</p>
        <p><strong>Program:</strong> ' . htmlspecialchars($student['course']) . '</p>
        <p><strong>Year:</strong> ' . htmlspecialchars($academic_year) . '</p>
        <p><strong>Cumulative GPA:</strong> ' . number_format($student['cumulative_gpa'], 2) . '</p>
        <p><strong>Total Courses:</strong> ' . $student['total_courses'] . '</p>
        <p><strong>Last Updated:</strong> ' . date('Y-m-d', strtotime($student['last_updated'])) . '</p>
    </div>
    
    <div class="academic-table">
        <h3>Academic Records</h3>
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Semester</th>
                    <th>Academic Year</th>
                    <th>Assessment Type</th>
                    <th>Marks</th>
                    <th>Grade</th>
                    <th>Credits</th>
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
                    <td>' . htmlspecialchars($record['semester']) . '</td>
                    <td>' . htmlspecialchars($record['academic_year']) . '</td>
                    <td>' . htmlspecialchars($record['assessment_type']) . '</td>
                    <td>' . htmlspecialchars($record['marks']) . '</td>
                    <td>' . htmlspecialchars($record['grade']) . '</td>
                    <td>' . htmlspecialchars($record['credits']) . '</td>
                    <td>' . htmlspecialchars($record['gpa']) . '</td>
                    <td>' . htmlspecialchars($record['lecturer_name']) . '</td>
                </tr>';
    }
    
    $content .= '
            </tbody>
        </table>
    </div>
    
    <div class="signature">
        <p>This transcript is generated electronically and is valid without signature.</p>
        <p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Generated by:</strong> ' . $_SESSION['full_name'] . '</p>
    </div>
</body>
</html>';
    
    return $content;
}

// Get students for dropdown
function getStudentsForDropdown() {
    global $students_conn;
    
    $sql = "SELECT id, full_name, registration_number, course, year, status 
             FROM students 
             WHERE status = 'Active' 
             ORDER BY full_name";
    
    $result = $students_conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get academic years for dropdown
function getAcademicYears() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT academic_year FROM academic_records ORDER BY academic_year DESC";
    $result = $students_conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get semesters for dropdown
function getSemesters() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT semester FROM academic_records ORDER BY semester";
    $result = $students_conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transcript Generation - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .transcript-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .transcript-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="transcript-container">
        <div class="transcript-header">
            <h2><i class="fas fa-graduation-cap me-2"></i>Transcript Generation</h2>
            <p>Generate official academic transcripts for students</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <form method="POST">
                <h4>Generate Transcript</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="student_id" class="form-label">Student:</label>
                        <select class="form-control" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php
                            $students = getStudentsForDropdown();
                            foreach ($students as $student) {
                                echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['full_name']) . ' - ' . htmlspecialchars($student['registration_number']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="transcript_type" class="form-label">Transcript Type:</label>
                        <select class="form-control" id="transcript_type" name="transcript_type" required>
                            <option value="Official">Official</option>
                            <option value="Unofficial">Unofficial</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="academic_year" class="form-label">Academic Year:</label>
                        <select class="form-control" id="academic_year" name="academic_year" required>
                            <option value="">Select Year</option>
                            <?php
                            $years = getAcademicYears();
                            foreach ($years as $year) {
                                echo '<option value="' . $year['academic_year'] . '">' . $year['academic_year'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="semester" class="form-label">Semester:</label>
                        <select class="form-control" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <?php
                            $semesters = getSemesters();
                            foreach ($semesters as $semester) {
                                echo '<option value="' . $semester['semester'] . '">' . $semester['semester'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" name="generate_transcript" class="btn-primary">Generate Transcript</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

