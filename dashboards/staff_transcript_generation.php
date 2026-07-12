<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar','director','academics','principal']);
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
    header("Location: ../staff-login.php");
    exit();
}

// Handle transcript generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_transcript'])) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $transcript_type = $_POST['transcript_type'] ?? 'Official';
    $academic_year_filter = $_POST['academic_year'] ?? '';
    $semester_filter = $_POST['semester'] ?? '';
    
    if ($student_id <= 0) {
        $_SESSION['error'] = 'Invalid student ID.';
        header("Location: staff_transcript_generation.php");
        exit();
    }
    
    // Get student information
    $student_stmt = $students_conn->prepare("SELECT * FROM students WHERE id = ? AND status = 'Active' LIMIT 1");
    if (!$student_stmt) {
        $_SESSION['error'] = 'Database error: student query prepare failed.';
        header("Location: staff_transcript_generation.php");
        exit();
    }
    $student_stmt->bind_param("i", $student_id);
    if (!$student_stmt->execute()) { error_log('$student_stmt execute failed: ' . ($student_stmt->error ?? 'unknown')); };
    $student_result = $student_stmt->get_result();
    $student = $student_result ? $student_result->fetch_assoc() : null;
    $student_stmt->close();
    
    if (!$student) {
        $_SESSION['error'] = "Student not found or not active.";
        header("Location: staff_transcript_generation.php");
        exit();
    }
    $student['full_name'] = $student['full_name'] ?: trim(($student['first_name']??'') . ' ' . ($student['surname']??'') . ($student['other_name'] ? ' ' . $student['other_name'] : ''));
    $student['registration_number'] = $student['registration_number'] ?: $student['student_number'] ?: '';
    
    // Get detailed academic records (from staffs DB where academic_records table lives)
    $staff_conn = $ctx['staff'];
    $params = [$student_id];
    $types = "i";
    $records_sql = "SELECT ar.*, cc.course_title AS course_name, cc.credits AS catalog_credits
                    FROM igangaschool_staffs.academic_records ar
                    LEFT JOIN igangaschool_staffs.academic_course_catalog cc ON ar.course_code = cc.course_code
                    WHERE ar.student_id = ?";
    
    if (!empty($academic_year_filter)) {
        $records_sql .= " AND ar.academic_year = ?";
        $params[] = $academic_year_filter;
        $types .= "s";
    }
    if (!empty($semester_filter)) {
        $records_sql .= " AND ar.semester = ?";
        $params[] = $semester_filter;
        $types .= "s";
    }
    $records_sql .= " ORDER BY ar.academic_year ASC, ar.semester ASC";
    
    $records_stmt = $staff_conn->prepare($records_sql);
    if (!$records_stmt) {
        $_SESSION['error'] = 'Database error: records query prepare failed.';
        header("Location: staff_transcript_generation.php");
        exit();
    }
    $records_stmt->bind_param($types, ...$params);
    if (!$records_stmt->execute()) { error_log('$records_stmt execute failed: ' . ($records_stmt->error ?? 'unknown')); };
    $records_result = $records_stmt->get_result();
    $academic_records = $records_result ? $records_result->fetch_all(MYSQLI_ASSOC) : [];
    $records_stmt->close();
    
    // Merge catalog credits into records (prefer record's own credits if set)
    foreach ($academic_records as &$ar) {
        if (empty($ar['credits']) || (int)$ar['credits'] === 0) {
            $ar['credits'] = $ar['catalog_credits'] ?? 0;
        }
    }
    unset($ar);
    
    // Generate transcript content
    $transcript_content = generateTranscriptContent($student, $academic_records, $transcript_type, $academic_year_filter, $semester_filter);
    
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
        $_SESSION['generated_transcript_content'] = $transcript_content;
        $_SESSION['generated_student_name'] = $student['full_name'];
        $_SESSION['generated_student_id'] = $student_id;
    } else {
        $_SESSION['error'] = "Failed to generate transcript.";
    }
    $save_stmt->close();
    
    header("Location: staff_transcript_generation.php");
    exit();
}

function gradePoint($grade) {
    $g = strtoupper(trim($grade));
    if ($g === 'A') return 4.0;
    if ($g === 'B' || $g === 'B+') return 3.5;
    if ($g === 'C' || $g === 'C+') return 3.0;
    if ($g === 'D') return 2.0;
    if ($g === 'E') return 1.0;
    return 0.0;
}

// Function to generate transcript content
function generateTranscriptContent($student, $academic_records, $transcript_type, $academic_year_filter, $semester_filter) {
    // Group records by semester for per-semester GPA
    $semesters = [];
    foreach ($academic_records as $r) {
        $key = ($r['academic_year'] ?? '') . '|' . ($r['semester'] ?? '');
        $semesters[$key][] = $r;
    }
    
    $rows = '';
    $cumulative_points = 0;
    $cumulative_credits = 0;
    $course_count = 0;
    
    foreach ($semesters as $key => $sem_records) {
        [$sem_year, $sem_name] = explode('|', $key, 2);
        $sem_points = 0;
        $sem_credits = 0;
        
        foreach ($sem_records as $r) {
            $cred = (int)($r['credits'] ?? 0);
            $grade = $r['grade'] ?? '';
            $gp = gradePoint($grade);
            $marks = $r['marks'] ?? $r['total_marks'] ?? '-';
            
            $sem_points += $gp * $cred;
            $sem_credits += $cred;
            $course_count++;
            
            $rows .= '<tr>
                <td>' . htmlspecialchars($r['course_code'] ?? '') . '</td>
                <td>' . htmlspecialchars($r['course_name'] ?? '') . '</td>
                <td>' . $cred . '</td>
                <td>' . htmlspecialchars($sem_name) . '</td>
                <td>' . htmlspecialchars($sem_year) . '</td>
                <td>' . (is_numeric($marks) ? number_format((float)$marks, 1) : $marks) . '</td>
                <td>' . $grade . '</td>
                <td>' . number_format($gp, 1) . '</td>
            </tr>';
        }
        
        $sem_gpa = $sem_credits > 0 ? round($sem_points / $sem_credits, 2) : 0;
        $cumulative_points += $sem_points;
        $cumulative_credits += $sem_credits;
        
        $rows .= '<tr style="background:#e8ecff;font-weight:700">
            <td colspan="8" style="text-align:right;padding:6px 10px;color:#1a237e;font-size:11px">
                End of ' . htmlspecialchars($sem_name) . ' (' . htmlspecialchars($sem_year) . ') â€” GPA: ' . number_format($sem_gpa, 2) . ' | Credits: ' . $sem_credits . '
            </td>
        </tr>';
    }
    
    $cgpa = $cumulative_credits > 0 ? round($cumulative_points / $cumulative_credits, 2) : 0;
    $standing = $cgpa >= 3.5 ? 'Excellent' : ($cgpa >= 3.0 ? 'Good' : ($cgpa >= 2.0 ? 'Satisfactory' : 'Probation'));
    
    $content = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Transcript | ISNM</title>
    <style>
        @page { margin: 15mm; size: A4 portrait; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Georgia", "Times New Roman", serif; background: #f0f0f0; padding: 20px; }
        .wrapper { max-width: 210mm; margin: 0 auto; background: #fff; border: 2px solid #1a237e; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.1); position: relative; }
        .header { background: linear-gradient(135deg, #1a237e, #283593); color: #fff; padding: 25px 30px 18px; text-align: center; }
        .header h1 { font-size: 18px; letter-spacing: 2px; text-transform: uppercase; }
        .header p { font-size: 11px; opacity: 0.8; margin-top: 4px; }
        .title-bar { text-align: center; padding: 10px; background: linear-gradient(135deg, #c0a060, #e8d5a0, #c0a060); color: #1a237e; font-size: 15px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; }
        .body { padding: 22px 28px; }
        .section { margin-bottom: 18px; }
        .section-title { font-size: 13px; font-weight: 700; color: #1a237e; border-bottom: 2px solid #c0a060; padding-bottom: 5px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .info-item { background: #f8f9ff; padding: 6px 10px; border-radius: 4px; border-left: 3px solid #c0a060; }
        .info-item .lbl { font-size: 9px; text-transform: uppercase; color: #666; }
        .info-item .val { font-size: 13px; font-weight: 600; color: #1a237e; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th { background: #1a237e; color: #fff; padding: 7px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 6px 8px; border-bottom: 1px solid #e0e0e0; }
        tr:nth-child(even) td { background: #f8f9ff; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 12px; }
        .summary-item { background: linear-gradient(135deg, #1a237e, #283593); color: #fff; padding: 8px; border-radius: 6px; text-align: center; }
        .summary-item .lbl { font-size: 9px; text-transform: uppercase; opacity: 0.8; }
        .summary-item .val { font-size: 15px; font-weight: 700; }
        .standing { display: inline-block; background: #dc3545; color: #fff; padding: 3px 12px; border-radius: 10px; font-size: 10px; font-weight: 700; letter-spacing: 1px; margin-top: 8px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 12px; border-top: 2px solid #c0a060; }
        .sig { text-align: center; width: 160px; }
        .sig-line { width: 120px; height: 1px; background: #333; margin: 0 auto 4px; }
        .sig-label { font-size: 9px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .sig-name { font-size: 10px; font-weight: 700; color: #1a237e; }
        .footer { text-align: center; padding: 10px; font-size: 9px; color: #999; border-top: 1px solid #e0e0e0; margin-top: 8px; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-30deg); font-size: 80px; color: rgba(26,35,126,0.04); font-weight: 700; letter-spacing: 10px; z-index: -1; pointer-events: none; }
        @media print { body { background: #fff; padding: 0; } .wrapper { box-shadow: none; border: 1px solid #000; } }
    </style>
</head>
<body>
<div class="watermark">OFFICIAL</div>
<div class="wrapper">
    <div class="header">
        <h1>Iganga School of Nursing &amp; Midwifery</h1>
        <p>Official Academic Transcript</p>
    </div>
    <div class="title-bar">' . htmlspecialchars($transcript_type) . ' TRANSCRIPT</div>
    <div class="body">
        <div class="section">
            <div class="section-title">Student Information</div>
            <div class="info-grid">
                <div class="info-item"><div class="lbl">Full Name</div><div class="val">' . htmlspecialchars($student['full_name'] ?? '') . '</div></div>
                <div class="info-item"><div class="lbl">Reg. Number</div><div class="val">' . htmlspecialchars($student['registration_number'] ?? '') . '</div></div>
                <div class="info-item"><div class="lbl">Program</div><div class="val">' . htmlspecialchars($student['program'] ?: $student['course'] ?: '') . '</div></div>
                <div class="info-item"><div class="lbl">Academic Year</div><div class="val">' . htmlspecialchars($academic_year_filter ?: 'All Years') . '</div></div>
            </div>
        </div>
        <div class="section">
            <div class="section-title">Academic Records</div>
            <table>
                <thead><tr><th>Course Code</th><th>Course Name</th><th>Credit</th><th>Semester</th><th>Year</th><th>Marks</th><th>Grade</th><th>GP</th></tr></thead>
                <tbody>' . $rows . '</tbody>
            </table>
        </div>
        <div class="section">
            <div class="section-title">Performance Summary</div>
            <div class="summary">
                <div class="summary-item"><div class="lbl">Total Courses</div><div class="val">' . $course_count . '</div></div>
                <div class="summary-item"><div class="lbl">Total Credits</div><div class="val">' . $cumulative_credits . '</div></div>
                <div class="summary-item"><div class="lbl">Total Grade Points</div><div class="val">' . number_format($cumulative_points, 2) . '</div></div>
                <div class="summary-item"><div class="lbl">CGPA</div><div class="val">' . number_format($cgpa, 2) . '</div></div>
            </div>
            <div style="text-align:center;margin-top:8px;"><span class="standing">' . strtoupper($standing) . ' STANDING</span></div>
        </div>
        <div class="signatures">
            <div class="sig"><div class="sig-line"></div><div class="sig-label">Academic Registrar</div><div class="sig-name">________________</div></div>
            <div class="sig"><div class="sig-line"></div><div class="sig-label">Principal</div><div class="sig-name">________________</div></div>
            <div class="sig"><div class="sig-line"></div><div class="sig-label">Director General</div><div class="sig-name">________________</div></div>
        </div>
    </div>
    <div class="footer">This is a computer-generated document. | Ref: ISNM/TR/' . date('Ymd') . '/' . strtoupper(substr(uniqid(), -6)) . ' | Generated: ' . date('F j, Y, H:i') . '</div>
</div>
</body>
</html>';
    
    return $content;
}

// Get students for dropdown
function getStudentsForDropdown() {
    global $ctx;
    
    $stmt = $ctx['students']->prepare("SELECT id, full_name, registration_number, COALESCE(program, course) AS program FROM students WHERE status = 'Active' ORDER BY full_name");
    if (!$stmt) return [];
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $result = $stmt->get_result();
    $students = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $students;
}

// Get academic years for dropdown
function getAcademicYears() {
    global $ctx;
    
    $staff_conn = $ctx['staff'];
    $stmt = $staff_conn->prepare("SELECT DISTINCT academic_year FROM igangaschool_staffs.academic_records ORDER BY academic_year DESC");
    if (!$stmt) return [];
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $result = $stmt->get_result();
    $years = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $years;
}

// Get semesters for dropdown
function getSemesters() {
    global $ctx;
    
    $staff_conn = $ctx['staff'];
    $stmt = $staff_conn->prepare("SELECT DISTINCT semester FROM igangaschool_staffs.academic_records ORDER BY semester");
    if (!$stmt) return [];
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $result = $stmt->get_result();
    $semesters = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $semesters;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<?php require_once __DIR__ . '/../includes/dashboard_back_button.php'; renderDashboardBackButton('Academic Registrar', '../dashboards/academic-registrar.php'); ?>
    <div class="transcript-container" style="margin-left:270px;padding:20px">
        <div class="page-title-card d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="fas fa-graduation-cap me-2"></i>Transcript Generation</h3>
                <p class="mb-0 text-muted small">Generate official academic transcripts for students</p>
            </div>
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
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <h4>Generate Transcript</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="student_id" class="form-label">Student:</label>
                        <select class="form-control" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php
                            $students = getStudentsForDropdown();
                            foreach ($students as $s) {
                                echo '<option value="' . (int)$s['id'] . '">' . htmlspecialchars($s['full_name']) . ' â€” ' . htmlspecialchars($s['registration_number'] ?? '') . ' (' . htmlspecialchars($s['program'] ?? '') . ')</option>';
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
                        <label for="academic_year" class="form-label">Academic Year (optional):</label>
                        <select class="form-control" id="academic_year" name="academic_year">
                            <option value="">All Years</option>
                            <?php
                            $years = getAcademicYears();
                            foreach ($years as $y) {
                                echo '<option value="' . htmlspecialchars($y['academic_year']) . '">' . htmlspecialchars($y['academic_year']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="semester" class="form-label">Semester (optional):</label>
                        <select class="form-control" id="semester" name="semester">
                            <option value="">All Semesters</option>
                            <?php
                            $semesters = getSemesters();
                            foreach ($semesters as $sem) {
                                echo '<option value="' . htmlspecialchars($sem['semester']) . '">' . htmlspecialchars($sem['semester']) . '</option>';
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
        
        <?php if (isset($_SESSION['generated_transcript_content'])): ?>
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Generated Transcript</h4>
                <div>
                    <span class="badge bg-success me-2"><?php echo htmlspecialchars($_SESSION['generated_student_name'] ?? ''); ?></span>
                    <button class="btn btn-sm btn-primary" onclick="window.open('print_transcript.php?action=preview&student_id=<?php echo (int)($_SESSION['generated_student_id'] ?? 0); ?>','_blank')"><i class="fas fa-eye"></i> Preview</button>
                    <button class="btn btn-sm btn-success" onclick="printTranscriptContent()"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
            <div class="card-body p-0" style="max-height:600px;overflow:auto">
                <iframe id="transcriptResult" style="width:100%;height:600px;border:none"></iframe>
            </div>
        </div>
        <script>
        document.getElementById('transcriptResult').srcdoc = <?php echo json_encode($_SESSION['generated_transcript_content']); ?>;
        function printTranscriptContent() {
            var w = window.open('', '_blank');
            w.document.write(<?php echo json_encode($_SESSION['generated_transcript_content']); ?>);
            w.document.close();
            w.onload = function() { w.print(); };
        }
        </script>
        <?php
        unset($_SESSION['generated_transcript_content']);
        unset($_SESSION['generated_student_name']);
        unset($_SESSION['generated_student_id']);
        endif;
        ?>
    </div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

