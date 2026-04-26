<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';
include_once 'includes/photo_upload.php';
include_once 'includes/student_profile_component.php';

// Check if user is logged in and has appropriate access level
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 6) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_marks':
                handleAddMarks();
                break;
            case 'update_marks':
                handleUpdateMarks();
                break;
            case 'delete_marks':
                handleDeleteMarks();
                break;
            case 'generate_transcript':
                handleGenerateTranscript();
                break;
            case 'bulk_import_marks':
                handleBulkImportMarks();
                break;
        }
    }
}

// Handle adding marks
function handleAddMarks() {
    global $conn;
    
    $student_id = sanitizeInput($_POST['student_id']);
    $academic_year = sanitizeInput($_POST['academic_year']);
    $semester = sanitizeInput($_POST['semester']);
    $year = sanitizeInput($_POST['year']);
    $course_code = sanitizeInput($_POST['course_code']);
    $course_name = sanitizeInput($_POST['course_name']);
    $course_type = sanitizeInput($_POST['course_type']);
    $credits = sanitizeInput($_POST['credits']);
    $assessment_marks = sanitizeInput($_POST['assessment_marks']);
    $exam_marks = sanitizeInput($_POST['exam_marks']);
    $total_marks = sanitizeInput($_POST['total_marks']);
    $grade = sanitizeInput($_POST['grade']);
    $grade_points = sanitizeInput($_POST['grade_points']);
    $lecturer = sanitizeInput($_POST['lecturer']);
    
    // Calculate GPA contribution
    $gpa_contribution = $grade_points * $credits;
    
    $sql = "INSERT INTO academic_records (student_id, academic_year, semester, year, course_code, course_name, course_type, credits, assessment_marks, exam_marks, total_marks, grade, grade_points, gpa_contribution, lecturer, entered_by, entry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssddddddssss", $student_id, $academic_year, $semester, $year, $course_code, $course_name, $course_type, $credits, $assessment_marks, $exam_marks, $total_marks, $grade, $grade_points, $gpa_contribution, $lecturer, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Update student's cumulative GPA
        updateStudentGPA($student_id, $academic_year, $semester);
        
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Marks Added', "Added marks for $student_id - $course_name", 'academic_records', $student_id);
        $_SESSION['success'] = "Marks added successfully!";
    } else {
        $_SESSION['error'] = "Error adding marks: " . $conn->error;
    }
    
    header("Location: academic_records_management.php");
    exit();
}

// Handle updating marks
function handleUpdateMarks() {
    global $conn;
    
    $record_id = sanitizeInput($_POST['record_id']);
    $assessment_marks = sanitizeInput($_POST['assessment_marks']);
    $exam_marks = sanitizeInput($_POST['exam_marks']);
    $total_marks = sanitizeInput($_POST['total_marks']);
    $grade = sanitizeInput($_POST['grade']);
    $grade_points = sanitizeInput($_POST['grade_points']);
    
    // Get record details for GPA calculation
    $get_record_sql = "SELECT * FROM academic_records WHERE id = ?";
    $record_result = executeQuery($get_record_sql, [$record_id], 'i');
    $record = $record_result[0];
    
    // Calculate new GPA contribution
    $gpa_contribution = $grade_points * $record['credits'];
    
    $sql = "UPDATE academic_records SET assessment_marks = ?, exam_marks = ?, total_marks = ?, grade = ?, grade_points = ?, gpa_contribution = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddddddsds", $assessment_marks, $exam_marks, $total_marks, $grade, $grade_points, $gpa_contribution, $_SESSION['user_id'], $record_id);
    
    if ($stmt->execute()) {
        // Update student's cumulative GPA
        updateStudentGPA($record['student_id'], $record['academic_year'], $record['semester']);
        
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Marks Updated', "Updated marks for record ID: $record_id", 'academic_records', $record['student_id']);
        $_SESSION['success'] = "Marks updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating marks: " . $conn->error;
    }
    
    header("Location: academic_records_management.php");
    exit();
}

// Handle deleting marks
function handleDeleteMarks() {
    global $conn;
    
    $record_id = sanitizeInput($_POST['record_id']);
    
    // Get record details before deletion
    $get_record_sql = "SELECT * FROM academic_records WHERE id = ?";
    $record_result = executeQuery($get_record_sql, [$record_id], 'i');
    $record = $record_result[0];
    
    $sql = "DELETE FROM academic_records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $record_id);
    
    if ($stmt->execute()) {
        // Update student's cumulative GPA
        updateStudentGPA($record['student_id'], $record['academic_year'], $record['semester']);
        
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Marks Deleted', "Deleted marks for record ID: $record_id", 'academic_records', $record['student_id']);
        $_SESSION['success'] = "Marks deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting marks: " . $conn->error;
    }
    
    header("Location: academic_records_management.php");
    exit();
}

// Update student GPA
function updateStudentGPA($student_id, $academic_year, $semester) {
    global $conn;
    
    // Get all records for the student in the specified academic year and semester
    $records_sql = "SELECT * FROM academic_records WHERE student_id = ? AND academic_year = ? AND semester = ?";
    $records_result = executeQuery($records_sql, [$student_id, $academic_year, $semester], 'sss');
    
    if (empty($records_result)) {
        return;
    }
    
    $total_credits = 0;
    $total_grade_points = 0;
    
    foreach ($records_result as $record) {
        $total_credits += $record['credits'];
        $total_grade_points += $record['gpa_contribution'];
    }
    
    $gpa = $total_credits > 0 ? $total_grade_points / $total_credits : 0;
    
    // Calculate class position
    $position_sql = "SELECT student_id, (SUM(gpa_contribution) / SUM(credits)) as calculated_gpa 
                     FROM academic_records 
                     WHERE academic_year = ? AND semester = ? 
                     GROUP BY student_id 
                     ORDER BY calculated_gpa DESC";
    $all_students = executeQuery($position_sql, [$academic_year, $semester], 'ss');
    
    $position = 1;
    foreach ($all_students as $student) {
        if ($student['student_id'] === $student_id) {
            break;
        }
        $position++;
    }
    
    $total_students = count($all_students);
    
    // Update or insert academic summary
    $summary_sql = "INSERT INTO academic_summary (student_id, academic_year, semester, gpa, class_position, total_students, total_credits, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                     ON DUPLICATE KEY UPDATE 
                     gpa = VALUES(gpa), 
                     class_position = VALUES(class_position), 
                     total_students = VALUES(total_students), 
                     total_credits = VALUES(total_credits), 
                     updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $conn->prepare($summary_sql);
    $stmt->bind_param("sssddii", $student_id, $academic_year, $semester, $gpa, $position, $total_students, $total_credits);
    $stmt->execute();
}

// Get filter parameters
$student_filter = isset($_GET['student']) ? $_GET['student'] : '';
$academic_year_filter = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = '';

if (!empty($student_filter)) {
    $where_conditions[] = "ar.student_id LIKE ?";
    $params[] = "%$student_filter%";
    $types .= 's';
}

if (!empty($academic_year_filter)) {
    $where_conditions[] = "ar.academic_year = ?";
    $params[] = $academic_year_filter;
    $types .= 's';
}

if (!empty($semester_filter)) {
    $where_conditions[] = "ar.semester = ?";
    $params[] = $semester_filter;
    $types .= 's';
}

if (!empty($program_filter)) {
    $where_conditions[] = "s.program = ?";
    $params[] = $program_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get academic records
$records_sql = "SELECT ar.*, s.first_name, s.surname, s.program, s.level 
               FROM academic_records ar 
               JOIN students s ON ar.student_id = s.student_id 
               $where_clause 
               ORDER BY ar.academic_year DESC, ar.semester DESC, ar.student_id ASC";
$academic_records = executeQuery($records_sql, $params, $types);

// Get unique academic years for filter
$years_sql = "SELECT DISTINCT academic_year FROM academic_records ORDER BY academic_year DESC";
$academic_years = executeQuery($years_sql);

// Get unique semesters for filter
$semesters_sql = "SELECT DISTINCT semester FROM academic_records ORDER BY semester";
$semesters = executeQuery($semesters_sql);

// Get unique programs for filter
$programs_sql = "SELECT DISTINCT program FROM students ORDER BY program";
$programs = executeQuery($programs_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Records Management - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--accent-color) !important;
        }

        .main-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .content-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
        }

        .grade-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .grade-A { background: #4caf50; color: white; }
        .grade-B { background: #2196f3; color: white; }
        .grade-C { background: #ff9800; color: white; }
        .grade-D { background: #f44336; color: white; }
        .grade-F { background: #9e9e9e; color: white; }

        .gpa-display {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .action-buttons {
            display: flex;
            gap: 0.25rem;
        }

        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .transcript-preview {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 2rem;
            margin: 1rem 0;
        }

        .transcript-header {
            text-align: center;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .transcript-school-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .transcript-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 1rem 0;
        }

        .transcript-student-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .transcript-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .transcript-table th,
        .transcript-table td {
            border: 1px solid #ddd;
            padding: 0.75rem;
            text-align: left;
        }

        .transcript-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
        }

        .transcript-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .transcript-student-info {
                grid-template-columns: 1fr;
            }

            .transcript-summary {
                grid-template-columns: 1fr;
            }
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> ISNM Academic Records
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="academic_records_management.php">
                            <i class="fas fa-graduation-cap"></i> Academic Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="student_accounts_management.php">
                            <i class="fas fa-users"></i> Student Accounts
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="h3 mb-3">
                <i class="fas fa-graduation-cap text-primary"></i> Academic Records Management
            </h1>
            <p class="text-muted mb-0">Manage student marks, grades, and academic performance</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($academic_records); ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $unique_students = array_unique(array_column($academic_records, 'student_id'));
                    echo count($unique_students);
                    ?>
                </div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($academic_years); ?></div>
                <div class="stat-label">Academic Years</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $avg_gpa = 0;
                    if (!empty($academic_records)) {
                        $total_gpa = array_sum(array_column($academic_records, 'grade_points'));
                        $avg_gpa = $total_gpa / count($academic_records);
                    }
                    echo number_format($avg_gpa, 2);
                    ?>
                </div>
                <div class="stat-label">Average GPA</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <h5 class="mb-3">Filter Records</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Student ID/Name</label>
                    <input type="text" class="form-control" name="student" value="<?php echo htmlspecialchars($student_filter); ?>" placeholder="Search student...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Academic Year</label>
                    <select class="form-select" name="academic_year">
                        <option value="">All Years</option>
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?php echo htmlspecialchars($year['academic_year']); ?>" <?php echo $academic_year_filter === $year['academic_year'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year['academic_year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Semester</label>
                    <select class="form-select" name="semester">
                        <option value="">All Semesters</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?php echo htmlspecialchars($semester['semester']); ?>" <?php echo $semester_filter === $semester['semester'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($semester['semester']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Program</label>
                    <select class="form-select" name="program">
                        <option value="">All Programs</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?php echo htmlspecialchars($program['program']); ?>" <?php echo $program_filter === $program['program'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($program['program']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="academic_records_management.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Academic Records Table -->
        <div class="content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title">
                    <i class="fas fa-graduation-cap"></i> Academic Records
                </h2>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMarksModal">
                        <i class="fas fa-plus"></i> Add Marks
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                        <i class="fas fa-upload"></i> Bulk Import
                    </button>
                    <button class="btn btn-info" onclick="generateTranscript()">
                        <i class="fas fa-file-alt"></i> Generate Transcript
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Course</th>
                            <th>Credits</th>
                            <th>Assessment</th>
                            <th>Exam</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>GPA</th>
                            <th>Lecturer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($academic_records)): ?>
                            <tr>
                                <td colspan="15" class="text-center py-4">
                                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No academic records found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($academic_records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($record['surname'] . ', ' . $record['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['program']); ?></td>
                                    <td><?php echo htmlspecialchars($record['academic_year']); ?></td>
                                    <td><?php echo htmlspecialchars($record['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($record['course_code'] . ' - ' . $record['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['credits']); ?></td>
                                    <td><?php echo htmlspecialchars($record['assessment_marks']); ?></td>
                                    <td><?php echo htmlspecialchars($record['exam_marks']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($record['total_marks']); ?></strong></td>
                                    <td>
                                        <span class="grade-badge grade-<?php echo substr($record['grade'], 0, 1); ?>">
                                            <?php echo htmlspecialchars($record['grade']); ?>
                                        </span>
                                    </td>
                                    <td class="gpa-display"><?php echo htmlspecialchars($record['grade_points']); ?></td>
                                    <td><?php echo htmlspecialchars($record['lecturer']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-warning" onclick="editMarks(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteMarks(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Marks Modal -->
    <div class="modal fade" id="addMarksModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #1a237e, #3949ab);">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Add Academic Marks
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="academic_records_management.php">
                    <input type="hidden" name="action" value="add_marks">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Student ID *</label>
                                <input type="text" class="form-control" name="student_id" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Academic Year *</label>
                                <input type="text" class="form-control" name="academic_year" value="<?php echo date('Y') . '/' . (date('Y') + 1); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester *</label>
                                <select class="form-select" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="1">Semester 1</option>
                                    <option value="2">Semester 2</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Year *</label>
                                <input type="number" class="form-control" name="year" min="1" max="4" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Course Code *</label>
                                <input type="text" class="form-control" name="course_code" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Course Name *</label>
                                <input type="text" class="form-control" name="course_name" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Course Type *</label>
                                <select class="form-select" name="course_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Core">Core</option>
                                    <option value="Elective">Elective</option>
                                    <option value="Practical">Practical</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Credits *</label>
                                <input type="number" class="form-control" name="credits" min="1" max="10" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Assessment Marks</label>
                                <input type="number" class="form-control" name="assessment_marks" min="0" max="100">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Exam Marks</label>
                                <input type="number" class="form-control" name="exam_marks" min="0" max="100">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Total Marks</label>
                                <input type="number" class="form-control" name="total_marks" min="0" max="100" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Grade</label>
                                <input type="text" class="form-control" name="grade" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lecturer *</label>
                                <input type="text" class="form-control" name="lecturer" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Marks
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Import Modal -->
    <div class="modal fade" id="bulkImportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <h5 class="modal-title">
                        <i class="fas fa-upload"></i> Bulk Import Marks
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="academic_records_management.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="bulk_import_marks">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select CSV File *</label>
                            <input type="file" class="form-control" name="marks_file" accept=".csv" required>
                            <div class="form-text">
                                CSV format: Student ID, Academic Year, Semester, Course Code, Course Name, Credits, Assessment Marks, Exam Marks, Lecturer
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            The system will automatically calculate total marks, grades, and GPA based on the provided data.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload"></i> Import Marks
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Marks Modal -->
    <div class="modal fade" id="editMarksModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Academic Marks
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="academic_records_management.php">
                    <input type="hidden" name="action" value="update_marks">
                    <input type="hidden" id="edit_record_id" name="record_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Assessment Marks</label>
                                <input type="number" class="form-control" id="edit_assessment_marks" name="assessment_marks" min="0" max="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Exam Marks</label>
                                <input type="number" class="form-control" id="edit_exam_marks" name="exam_marks" min="0" max="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Marks</label>
                                <input type="number" class="form-control" id="edit_total_marks" name="total_marks" min="0" max="100" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Grade</label>
                                <input type="text" class="form-control" id="edit_grade" name="grade" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update Marks
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Transcript Preview Modal -->
    <div class="modal fade" id="transcriptModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #17a2b8, #20c997);">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt"></i> Academic Transcript
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="transcriptContent">
                        <!-- Transcript content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printTranscript()">
                        <i class="fas fa-print"></i> Print Transcript
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Calculate total marks and grade automatically
        document.addEventListener('DOMContentLoaded', function() {
            // For add marks modal
            const assessmentInput = document.querySelector('input[name="assessment_marks"]');
            const examInput = document.querySelector('input[name="exam_marks"]');
            const totalInput = document.querySelector('input[name="total_marks"]');
            const gradeInput = document.querySelector('input[name="grade"]');

            if (assessmentInput && examInput && totalInput && gradeInput) {
                function calculateGrade() {
                    const assessment = parseFloat(assessmentInput.value) || 0;
                    const exam = parseFloat(examInput.value) || 0;
                    const total = assessment + exam;
                    
                    totalInput.value = total;
                    
                    let grade = '';
                    let gradePoints = 0;
                    
                    if (total >= 80) {
                        grade = 'A';
                        gradePoints = 4.0;
                    } else if (total >= 75) {
                        grade = 'B+';
                        gradePoints = 3.5;
                    } else if (total >= 70) {
                        grade = 'B';
                        gradePoints = 3.0;
                    } else if (total >= 65) {
                        grade = 'C+';
                        gradePoints = 2.5;
                    } else if (total >= 60) {
                        grade = 'C';
                        gradePoints = 2.0;
                    } else if (total >= 55) {
                        grade = 'D+';
                        gradePoints = 1.5;
                    } else if (total >= 50) {
                        grade = 'D';
                        gradePoints = 1.0;
                    } else {
                        grade = 'F';
                        gradePoints = 0.0;
                    }
                    
                    gradeInput.value = grade;
                    // Store grade points in a hidden field if needed
                }
                
                assessmentInput.addEventListener('input', calculateGrade);
                examInput.addEventListener('input', calculateGrade);
            }
            
            // For edit marks modal
            const editAssessmentInput = document.getElementById('edit_assessment_marks');
            const editExamInput = document.getElementById('edit_exam_marks');
            const editTotalInput = document.getElementById('edit_total_marks');
            const editGradeInput = document.getElementById('edit_grade');

            if (editAssessmentInput && editExamInput && editTotalInput && editGradeInput) {
                function calculateEditGrade() {
                    const assessment = parseFloat(editAssessmentInput.value) || 0;
                    const exam = parseFloat(editExamInput.value) || 0;
                    const total = assessment + exam;
                    
                    editTotalInput.value = total;
                    
                    let grade = '';
                    
                    if (total >= 80) {
                        grade = 'A';
                    } else if (total >= 75) {
                        grade = 'B+';
                    } else if (total >= 70) {
                        grade = 'B';
                    } else if (total >= 65) {
                        grade = 'C+';
                    } else if (total >= 60) {
                        grade = 'C';
                    } else if (total >= 55) {
                        grade = 'D+';
                    } else if (total >= 50) {
                        grade = 'D';
                    } else {
                        grade = 'F';
                    }
                    
                    editGradeInput.value = grade;
                }
                
                editAssessmentInput.addEventListener('input', calculateEditGrade);
                editExamInput.addEventListener('input', calculateEditGrade);
            }
        });

        // Edit marks function
        function editMarks(recordId) {
            // Load record data and populate form
            fetch('includes/ajax_get_record.php?id=' + recordId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_record_id').value = recordId;
                        document.getElementById('edit_assessment_marks').value = data.record.assessment_marks;
                        document.getElementById('edit_exam_marks').value = data.record.exam_marks;
                        document.getElementById('edit_total_marks').value = data.record.total_marks;
                        document.getElementById('edit_grade').value = data.record.grade;
                        
                        const modal = new bootstrap.Modal(document.getElementById('editMarksModal'));
                        modal.show();
                    } else {
                        alert('Error loading record data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading record data');
                });
        }

        // Delete marks function
        function deleteMarks(recordId) {
            if (confirm('Are you sure you want to delete this academic record? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'academic_records_management.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_marks';
                
                const recordIdInput = document.createElement('input');
                recordIdInput.type = 'hidden';
                recordIdInput.name = 'record_id';
                recordIdInput.value = recordId;
                
                form.appendChild(actionInput);
                form.appendChild(recordIdInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Generate transcript function
        function generateTranscript() {
            const studentId = prompt('Enter Student ID:');
            if (studentId) {
                fetch('includes/ajax_generate_transcript.php?student_id=' + studentId)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('transcriptContent').innerHTML = html;
                        const modal = new bootstrap.Modal(document.getElementById('transcriptModal'));
                        modal.show();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error generating transcript');
                    });
            }
        }

        // Print transcript function
        function printTranscript() {
            const printContent = document.getElementById('transcriptContent').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            
            // Re-show the modal
            const modal = new bootstrap.Modal(document.getElementById('transcriptModal'));
            modal.show();
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.style.display !== 'none') {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>

<?php
// Display alerts
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($_SESSION['error']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['error']);
}
?>
