<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: student-login.php'); exit;
}
$isStaff = ($_SESSION['type'] ?? '') === 'staff';
$isStudent = ($_SESSION['type'] ?? '') === 'student';
if (!$isStaff && !$isStudent) {
    header('Location: student-login.php'); exit;
}
$auth_service = new AuthenticationService();
$user = $auth_service->getCurrentUser();
$staffDb = getStaffConnection();
$studentsDb = getStudentsConnection();
$userId = (int)($user['id'] ?? 0);
$studentNumber = $user['student_number'] ?? ($_SESSION['student_number'] ?? '');

$studentInfo = [];
$examResults = [];
$semesters = [];
$gpaBySemester = [];

if ($studentsDb) {
    $sid = $studentsDb->real_escape_string($studentNumber);
    $sr = $studentsDb->query("SELECT * FROM students WHERE student_number='$sid' OR id=$userId LIMIT 1");
    $studentInfo = $sr ? $sr->fetch_assoc() : [];
    $sidInt = (int)($studentInfo['id'] ?? $userId);

    $er = $studentsDb->query("SELECT * FROM examination_records WHERE student_id=$sidInt ORDER BY academic_year DESC, FIELD(semester,'Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6')");
    $examResults = $er ? $er->fetch_all(MYSQLI_ASSOC) : [];

    $se = $studentsDb->query("SELECT DISTINCT academic_year, semester FROM examination_records WHERE student_id=$sidInt ORDER BY academic_year DESC, FIELD(semester,'Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6')");
    $semesters = $se ? $se->fetch_all(MYSQLI_ASSOC) : [];

    $gp = $studentsDb->query("SELECT semester, academic_year, ROUND(AVG(CASE WHEN grade='A' THEN 4.0 WHEN grade='B' THEN 3.0 WHEN grade='C' THEN 2.0 WHEN grade='D' THEN 1.0 ELSE 0 END),2) as gpa FROM examination_records WHERE student_id=$sidInt GROUP BY academic_year, semester ORDER BY academic_year DESC, FIELD(semester,'Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6')");
    $gpaBySemester = $gp ? $gp->fetch_all(MYSQLI_ASSOC) : [];

    // Fallback to student_academic_records if examination_records is empty
    if (empty($examResults)) {
        $er2 = $studentsDb->query("SELECT * FROM student_academic_records WHERE student_id=$sidInt ORDER BY academic_year DESC, FIELD(semester,'Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6')");
        $examResults = $er2 ? $er2->fetch_all(MYSQLI_ASSOC) : [];
    }
    if (empty($semesters)) {
        $se2 = $studentsDb->query("SELECT DISTINCT academic_year, semester FROM student_academic_records WHERE student_id=$sidInt ORDER BY academic_year DESC, FIELD(semester,'Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6')");
        $semesters = $se2 ? $se2->fetch_all(MYSQLI_ASSOC) : [];
    }
}

$fullName = $studentInfo ? htmlspecialchars(($studentInfo['surname']??'') . ' ' . ($studentInfo['firstname']??'')) : 'Student';
$program = $studentInfo ? htmlspecialchars($studentInfo['program']??'N/A') : 'N/A';
$programCode = $studentInfo ? htmlspecialchars($studentInfo['program_code']??'') : '';
$yearOfStudy = $studentInfo ? (int)($studentInfo['year_of_study']??1) : 1;

$activeSem = $_GET['semester'] ?? ($semesters[0]['academic_year']??'') . '|' . ($semesters[0]['semester']??'');
$filterAcademicYear = explode('|', $activeSem)[0] ?? '';
$filterSemester = explode('|', $activeSem)[1] ?? '';

$filteredResults = array_filter($examResults, function($r) use ($filterAcademicYear, $filterSemester) {
    return (!$filterAcademicYear || ($r['academic_year']??'') === $filterAcademicYear)
        && (!$filterSemester || ($r['semester']??'') === $filterSemester);
});

$pageTitle = 'Student Results Portal';
require_once __DIR__ . '/includes/dashboard_head.php';
include_once __DIR__ . '/includes/sidebar.php';
?>
<style>
:root{--primary:#2c5f8a;--accent:#1a9e6e}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif}
.result-card{border:none;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08);margin-bottom:24px}
.result-card .card-header{background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:20px 28px;border:none;color:#fff}
.result-card .card-body{padding:24px 28px}
.sem-tab{padding:8px 18px;border-radius:8px;cursor:pointer;transition:all .2s;border:1px solid transparent;font-size:.9rem}
.sem-tab:hover{background:rgba(44,95,138,.08);border-color:rgba(44,95,138,.2)}
.sem-tab.active{background:#2c5f8a;color:#fff;font-weight:600}
.grade-A{color:#1a9e6e;font-weight:700}
.grade-B{color:#2c5f8a;font-weight:600}
.grade-C{color:#d97706;font-weight:600}
.grade-D{color:#dc2626;font-weight:600}
.grade-F{color:#991b1b;font-weight:700}
.stat-box{background:#f8fafc;border-radius:12px;padding:16px;text-align:center}
.stat-box .num{font-size:1.8rem;font-weight:700;color:#2c5f8a}
.stat-box .lbl{font-size:.8rem;color:#64748b;margin-top:4px}
</style>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-file-lines me-2" style="color:#2c5f8a"></i>My Results</h2>
            <p class="text-muted mb-0">Examination results & academic performance</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold"><?= $fullName ?></div>
            <small class="text-muted"><?= $program ?></small>
        </div>
    </div>

    <?php if (empty($semesters)): ?>
    <div class="result-card">
        <div class="card-body text-center py-5">
            <i class="fas fa-file-lines fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No results published yet</h5>
            <p class="text-muted mb-0">Check back after examinations are graded and approved.</p>
        </div>
    </div>
    <?php else: ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-box"><div class="num"><?= count($examResults) ?></div><div class="lbl">Courses Taken</div></div></div>
        <div class="col-md-3"><div class="stat-box"><div class="num" style="color:<?= $gpaBySemester[0]['gpa'] >= 3.0 ? '#1a9e6e' : '#d97706' ?>"><?= $gpaBySemester[0]['gpa'] ?? '—' ?></div><div class="lbl">Current GPA</div></div></div>
        <div class="col-md-3"><div class="stat-box"><div class="num"><?= count(array_filter($examResults, fn($r)=>($r['grade']??'')==='A')) ?></div><div class="lbl">Grade A's</div></div></div>
        <div class="col-md-3"><div class="stat-box"><div class="num" style="color:#1a9e6e"><?= $programCode ?: '—' ?></div><div class="lbl"><?= htmlspecialchars(explode(' ',$program)[0]??'') ?></div></div></div>
    </div>

    <div class="result-card">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-filter me-2"></i>Select Semester</h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($semesters as $s): 
                        $val = ($s['academic_year']??'') . '|' . ($s['semester']??'');
                        $isActive = $val === $activeSem;
                    ?>
                    <a href="?semester=<?= urlencode($val) ?>" class="sem-tab <?= $isActive ? 'active' : '' ?> bg-white text-dark"><?= htmlspecialchars($s['semester']??'') ?> (<?= htmlspecialchars($s['academic_year']??'') ?>)</a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Course</th>
                            <th>Course Code</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Credits</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filteredResults)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No results for this semester</td></tr>
                        <?php else: $i=1; ?>
                        <?php foreach ($filteredResults as $r): 
                            $g = $r['grade']??'';
                            $gClass = match(true) { $g==='A'||$g==='A+'||$g==='A-' => 'grade-A', $g==='B'||$g==='B+'||$g==='B-' => 'grade-B', $g==='C'||$g==='C+'||$g==='C-' => 'grade-C', $g==='D'||$g==='D+'||$g==='D-' => 'grade-D', $g==='F' => 'grade-F', default => '' };
                            $marks = $r['marks_obtained'] ?? $r['final_exam_marks'] ?? $r['marks'] ?? '—';
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($r['course_name'] ?? $r['subject'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($r['course_code']??'') ?></td>
                            <td><?= is_numeric($marks) ? $marks : $marks ?></td>
                            <td class="<?= $gClass ?>"><?= htmlspecialchars($g) ?></td>
                            <td><?= (int)($r['credits']??0) ?></td>
                            <td><?= htmlspecialchars($r['remarks']??'') ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($gpaBySemester)): ?>
    <div class="result-card">
        <div class="card-header">
            <h5 class="mb-0 fw-semibold"><i class="fas fa-chart-line me-2"></i>GPA Progression</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($gpaBySemester as $g): 
                    $gpa = (float)($g['gpa']??0);
                    $color = $gpa >= 3.0 ? '#1a9e6e' : ($gpa >= 2.0 ? '#d97706' : '#dc2626');
                ?>
                <div class="col-md-4 col-lg-3">
                    <div class="stat-box" style="border-left:4px solid <?= $color ?>">
                        <div class="num" style="color:<?= $color ?>"><?= number_format($gpa, 2) ?></div>
                        <div class="lbl"><?= htmlspecialchars($g['semester']??'') ?> (<?= htmlspecialchars($g['academic_year']??'') ?>)</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/dashboard_footer.php'; ?>