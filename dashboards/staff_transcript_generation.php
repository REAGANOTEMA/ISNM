<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$students_conn = $ctx['students'];
$staff_conn = $ctx['staff'];

// Check if user has permission to generate transcripts
$staff_role = $ctx['user']['role'] ?? '';
$staff_id = $ctx['user']['id'] ?? 0;
$staff_email = $ctx['user']['email'] ?? '';

$can_generate_transcripts = false;

if (stripos($staff_role, 'Academic Registrar') !== false ||
    stripos($staff_role, 'Director') !== false ||
    stripos($staff_role, 'General') !== false ||
    stripos($staff_role, 'Principal') !== false) {
    $can_generate_transcripts = true;
}

if (!$can_generate_transcripts) {
    $_SESSION['error'] = "You don't have permission to generate transcripts.";
    header("Location: staff-login.php");
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
    if (!$student_stmt) {
        $_SESSION['error'] = 'Database error: student query prepare failed.';
        header("Location: staff_transcript_generation.php");
        exit();
    }
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    $student = $student_result ? $student_result->fetch_assoc() : null;
    
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
    if (!$records_stmt) {
        $_SESSION['error'] = 'Database error: records query prepare failed.';
        header("Location: staff_transcript_generation.php");
        exit();
    }
    $records_stmt->bind_param("i", $student_id);
    $records_stmt->execute();
    $records_result = $records_stmt->get_result();
    $academic_records = $records_result ? $records_result->fetch_all(MYSQLI_ASSOC) : [];
    
    // Generate transcript content
    $transcript_content = generateTranscriptContent($student, $academic_records, $transcript_type, $academic_year, $semester);
    
    // Save transcript to database
    $transcript_number = 'TRANS_' . date('YmdHis') . str_pad($student_id, 4, '0', STR_PAD_LEFT);
    $access_code = uniqid('TRANS_' . date('Ymd'));
    
    $save_sql = "INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, access_code, generation_date) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $save_stmt = $students_conn->prepare($save_sql);
    if (!$save_stmt) {
        $_SESSION['error'] = 'Database error: save prepare failed.';
        header("Location: staff_transcript_generation.php");
        exit();
    }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Academic Transcript | ISNM</title>
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
    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
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
        <p><strong>Generated by:</strong> ' . ($_SESSION['full_name'] ?? 'System') . '</p>
    </div>
</body>
</html>';
    
    return $content;
}

// Get students for dropdown
function getStudentsForDropdown() {
    global $ctx;
    
    $sql = "SELECT id, full_name, registration_number, course, year, status 
             FROM students 
             WHERE status = 'Active' 
             ORDER BY full_name";
    
    $result = $ctx['students']->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get academic years for dropdown
function getAcademicYears() {
    global $ctx;
    
    $sql = "SELECT DISTINCT academic_year FROM academic_records ORDER BY academic_year DESC";
    $result = $ctx['students']->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get semesters for dropdown
function getSemesters() {
    global $ctx;
    
    $sql = "SELECT DISTINCT semester FROM academic_records ORDER BY semester";
    $result = $ctx['students']->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="transcript-container" style="margin-left:270px">
        <div class="transcript-header">
            <h2><i class="fas fa-graduation-cap me-2"></i>Transcript Generation</h2>
            <p>Generate official academic transcripts for students</p>
            <div class="text-center mb-3">
                <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
                <a href="../store_request.php" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                <a href="../news.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-newspaper me-1"></i>News</a>
            </div>
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
                                echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['full_name']) . ' , ' . htmlspecialchars($student['registration_number']) . '</option>';
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
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

