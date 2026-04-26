<?php
// AJAX handler for generating academic transcript
include_once 'config.php';
include_once 'functions.php';
include_once 'photo_upload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit();
}

$student_id = $_GET['student_id'] ?? '';

if (empty($student_id)) {
    echo '<div class="alert alert-warning">Student ID not provided</div>';
    exit();
}

// Get student information
$student_sql = "SELECT * FROM students WHERE student_id = ?";
$student_result = executeQuery($student_sql, [$student_id], 's');
$student = $student_result[0] ?? null;

if (!$student) {
    echo '<div class="alert alert-danger">Student not found</div>';
    exit();
}

// Get academic records
$records_sql = "SELECT * FROM academic_records WHERE student_id = ? ORDER BY academic_year ASC, semester ASC";
$academic_records = executeQuery($records_sql, [$student_id], 's');

// Get academic summary
$summary_sql = "SELECT * FROM academic_summary WHERE student_id = ? ORDER BY academic_year ASC, semester ASC";
$academic_summary = executeQuery($summary_sql, [$student_id], 's');

// Get photo URL
$photo_url = getPassportPhotoUrl($student['profile_image']);

// Calculate cumulative statistics
$total_credits = 0;
$total_grade_points = 0;
$semesters_completed = 0;

foreach ($academic_summary as $summary) {
    $total_credits += $summary['total_credits'];
    $total_grade_points += $summary['gpa'] * $summary['total_credits'];
    $semesters_completed++;
}

$cumulative_gpa = $total_credits > 0 ? $total_grade_points / $total_credits : 0;

// Generate transcript HTML
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Academic Transcript - <?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name']); ?></title>
    <style>
        @page {
            margin: 1cm;
            size: A4;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        
        .transcript-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .school-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .school-address {
            font-size: 11px;
            margin-bottom: 15px;
        }
        
        .transcript-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 10px 0;
        }
        
        .student-info {
            margin-bottom: 25px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .student-photo {
            position: absolute;
            top: 120px;
            right: 50px;
            width: 80px;
            height: 100px;
            border: 2px solid #000;
        }
        
        .academic-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .academic-table th,
        .academic-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        
        .academic-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .academic-table td.course-code {
            text-align: center;
        }
        
        .academic-table td.credits,
        .academic-table td.marks,
        .academic-table td.grade,
        .academic-table td.gpa {
            text-align: center;
        }
        
        .semester-header {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        
        .summary-section {
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 20px;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .summary-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .summary-table td {
            text-align: center;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 30px;
        }
        
        .signature-title {
            font-size: 10px;
            font-weight: bold;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.1);
            font-weight: bold;
            z-index: -1;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="watermark">OFFICIAL TRANSCRIPT</div>
    
    <!-- Transcript Header -->
    <div class="transcript-header">
        <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
            <img src="../images/school-logo.png" alt="ISNM Logo" style="width: 80px; height: 80px; margin-right: 20px;">
            <div>
                <div class="school-name">IGANGA SCHOOL OF NURSING AND MIDWIFERY</div>
                <div class="school-address">P.O. Box 123, Iganga, Uganda | Tel: +256 456 789012 | Email: info@isnm.ac.ug</div>
            </div>
        </div>
        <div class="transcript-title">ACADEMIC TRANSCRIPT</div>
        <div style="text-align: center; margin-top: 10px; font-size: 10px; color: #666;">
            Official Document - For Academic Purposes Only
        </div>
    </div>
    
    <!-- Student Photo -->
    <img src="<?php echo $photo_url; ?>" alt="Student Photo" class="student-photo">
    
    <!-- Student Information -->
    <div class="student-info">
        <div class="info-row">
            <span class="info-label">Student Name:</span>
            <span><?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name'] . ' ' . ($student['other_name'] ?? '')); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Student ID:</span>
            <span><?php echo htmlspecialchars($student['student_id']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Date of Birth:</span>
            <span><?php echo formatDate($student['date_of_birth']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Program:</span>
            <span><?php echo htmlspecialchars($student['program']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Level:</span>
            <span><?php echo htmlspecialchars($student['level']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Intake Year:</span>
            <span><?php echo htmlspecialchars($student['intake_year']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Date Issued:</span>
            <span><?php echo date('F j, Y'); ?></span>
        </div>
    </div>
    
    <!-- Academic Records -->
    <div class="academic-records">
        <?php
        if (empty($academic_records)) {
            echo '<p style="text-align: center; font-style: italic;">No academic records available</p>';
        } else {
            // Group records by academic year and semester
            $grouped_records = [];
            foreach ($academic_records as $record) {
                $key = $record['academic_year'] . '_Semester_' . $record['semester'];
                if (!isset($grouped_records[$key])) {
                    $grouped_records[$key] = [];
                }
                $grouped_records[$key][] = $record;
            }
            
            foreach ($grouped_records as $key => $records) {
                list($academic_year, $semester) = explode('_Semester_', $key);
                
                // Get semester summary
                $semester_summary = null;
                foreach ($academic_summary as $summary) {
                    if ($summary['academic_year'] === $academic_year && $summary['semester'] == $semester) {
                        $semester_summary = $summary;
                        break;
                    }
                }
        ?>
        
        <h4 style="margin-bottom: 10px; font-size: 14px;">
            Academic Year <?php echo htmlspecialchars($academic_year); ?> - Semester <?php echo $semester; ?>
        </h4>
        
        <table class="academic-table">
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Credits</th>
                    <th>Assessment</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>GPA</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $semester_credits = 0;
                $semester_grade_points = 0;
                
                foreach ($records as $record) {
                    $semester_credits += $record['credits'];
                    $semester_grade_points += $record['gpa_contribution'];
                ?>
                <tr>
                    <td class="course-code"><?php echo htmlspecialchars($record['course_code']); ?></td>
                    <td><?php echo htmlspecialchars($record['course_name']); ?></td>
                    <td class="credits"><?php echo htmlspecialchars($record['credits']); ?></td>
                    <td class="marks"><?php echo htmlspecialchars($record['assessment_marks']); ?></td>
                    <td class="marks"><?php echo htmlspecialchars($record['exam_marks']); ?></td>
                    <td class="marks"><?php echo htmlspecialchars($record['total_marks']); ?></td>
                    <td class="grade"><?php echo htmlspecialchars($record['grade']); ?></td>
                    <td class="gpa"><?php echo htmlspecialchars($record['grade_points']); ?></td>
                </tr>
                <?php } ?>
                
                <!-- Semester Summary Row -->
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="3">Semester Summary</td>
                    <td class="credits"><?php echo $semester_credits; ?></td>
                    <td colspan="2"></td>
                    <td class="gpa"><?php echo $semester_summary ? number_format($semester_summary['gpa'], 2) : 'N/A'; ?></td>
                    <td class="gpa">Position: <?php echo $semester_summary ? $semester_summary['class_position'] . '/' . $semester_summary['total_students'] : 'N/A'; ?></td>
                </tr>
            </tbody>
        </table>
        
        <br><br>
        <?php
            }
        }
        ?>
    </div>
    
    <!-- Academic Summary -->
    <div class="summary-section">
        <h4 style="margin-bottom: 15px; font-size: 14px;">ACADEMIC SUMMARY</h4>
        
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Credits</th>
                    <th>Semester GPA</th>
                    <th>Class Position</th>
                    <th>Total Students</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($academic_summary)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; font-style: italic;">No summary data available</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($academic_summary as $summary): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($summary['academic_year']); ?></td>
                        <td><?php echo htmlspecialchars($summary['semester']); ?></td>
                        <td><?php echo htmlspecialchars($summary['total_credits']); ?></td>
                        <td><?php echo number_format($summary['gpa'], 2); ?></td>
                        <td><?php echo htmlspecialchars($summary['class_position']); ?></td>
                        <td><?php echo htmlspecialchars($summary['total_students']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Cumulative Summary -->
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="3">CUMULATIVE SUMMARY</td>
                    <td><?php echo $total_credits; ?></td>
                    <td><?php echo number_format($cumulative_gpa, 2); ?></td>
                    <td><?php echo $semesters_completed; ?> Semesters</td>
                </tr>
            </tbody>
        </table>
        
        <!-- Grade Distribution -->
        <h4 style="margin-top: 20px; margin-bottom: 10px; font-size: 14px;">GRADE DISTRIBUTION</h4>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Grade</th>
                    <th>A</th>
                    <th>B+</th>
                    <th>B</th>
                    <th>C+</th>
                    <th>C</th>
                    <th>D+</th>
                    <th>D</th>
                    <th>F</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Count</td>
                    <?php
                    $grade_counts = ['A' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 'D+' => 0, 'D' => 0, 'F' => 0];
                    foreach ($academic_records as $record) {
                        if (isset($grade_counts[$record['grade']])) {
                            $grade_counts[$record['grade']]++;
                        }
                    }
                    foreach ($grade_counts as $count) {
                        echo '<td>' . $count . '</td>';
                    }
                    ?>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-title">ACADEMIC REGISTRAR</div>
            <div style="font-size: 9px;">Date: _______________</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-title">SCHOOL PRINCIPAL</div>
            <div style="font-size: 9px;">Date: _______________</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-title">DIRECTOR GENERAL</div>
            <div style="font-size: 9px;">Date: _______________</div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        This transcript is an official document of Iganga School of Nursing and Midwifery.
        Printed on <?php echo date('F j, Y H:i:s'); ?> | Page 1 of 1
    </div>
</body>
</html>
<?php
echo ob_get_clean();
?>
