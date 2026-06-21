<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/registrar_document_templates.php';
$ctx = bootstrapStaffDashboard(['registrar']);
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$students_conn = getStudentsConnection();
$staff_conn    = getStaffConnection();
$website_conn  = $ctx['website'];

// -- AJAX endpoints (exit before HTML) -----------------------------
$ajaxAction = $_GET['ajax'] ?? '';
$ajaxSid    = intval($_GET['student_id'] ?? 0);
// Helper (must be before report/AJAX handlers)
function safeCount($conn, $sql) {
    $r = $conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return intval($row['c'] ?? 0);
}

// -- Report generation (exit before HTML) --------------------------
$reportType = $_GET['report'] ?? '';
if ($reportType) {
    require_once __DIR__ . '/../config/database.php';
    $students_conn = getStudentsConnection(); $staff_conn = getStaffConnection();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#f3f4f6}h2{color:#1f2937}.text-right{text-align:right}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print"><button onclick="window.print()" style="padding:6px 16px;margin-bottom:12px">Print</button> <button onclick="window.close()" style="padding:6px 16px">Close</button></div>';
    if ($reportType === 'student_list') {
        $r = $students_conn->query("SELECT student_number,registration_number,full_name,course,current_year,gender,status,phone,email FROM students ORDER BY surname,first_name");
        echo '<h2>Student List</h2><table><thead><tr><th>#</th><th>Reg No</th><th>Full Name</th><th>Program</th><th>Year</th><th>Gender</th><th>Status</th><th>Phone</th></tr></thead><tbody>';
        $i=1; if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['current_year'].'</td><td>'.htmlspecialchars($row['gender']).'</td><td>'.htmlspecialchars($row['status']).'</td><td>'.htmlspecialchars($row['phone']).'</td></tr>'; }
        echo '</tbody></table><p style="margin-top:8px"><em>Total: '.($i-1).' students</em></p>';
    } elseif ($reportType === 'by_program') {
        $r = $students_conn->query("SELECT course,COUNT(*) c FROM students WHERE status='Active' GROUP BY course ORDER BY c DESC");
        echo '<h2>Students by Program</h2><table><thead><tr><th>Program</th><th>Count</th></tr></thead><tbody>';
        $total=0; if ($r) while ($row=$r->fetch_assoc()) { $total+=$row['c']; echo '<tr><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['c'].'</td></tr>'; }
        echo '<tr><th>Total</th><th>'.$total.'</th></tr></tbody></table>';
    } elseif ($reportType === 'fee_summary') {
        $r = $students_conn->query("SELECT s.id,s.full_name,s.student_number,s.registration_number,s.course,COALESCE((SELECT SUM(total_amount) FROM student_invoices WHERE student_id=s.id),0) total_inv,COALESCE((SELECT SUM(amount_received) FROM payments WHERE student_id=s.id),0) total_paid FROM students s WHERE s.status='Active' ORDER BY s.full_name");
        echo '<h2>Fee Summary</h2><table><thead><tr><th>#</th><th>Name</th><th>Reg No</th><th>Program</th><th>Invoiced</th><th>Paid</th><th>Balance</th></tr></thead><tbody>';
        $i=1; if ($r) while ($row=$r->fetch_assoc()) { $bal=$row['total_inv']-$row['total_paid']; echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.htmlspecialchars($row['course']).'</td><td class="text-right">'.number_format($row['total_inv'],0).'</td><td class="text-right">'.number_format($row['total_paid'],0).'</td><td class="text-right">'.number_format($bal,0).'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'academic') {
        echo '<h2>Academic Report</h2><table><thead><tr><th>Student</th><th>Reg No</th><th>Courses Registered</th><th>Avg Marks</th><th>Attendance %</th></tr></thead><tbody>';
        $r = $students_conn->query("SELECT id,full_name,student_number,registration_number,course FROM students WHERE status='Active' ORDER BY full_name LIMIT 100");
        if ($r) while ($row=$r->fetch_assoc()) {
            $sid=$row['id'];
            $cr = safeCount($staff_conn,"SELECT COUNT(*) c FROM course_registrations WHERE student_id=$sid AND status='Registered'");
            $am = $staff_conn->query("SELECT AVG(marks_obtained) m FROM examination_records WHERE student_id=$sid");
            $avg = ($am && $amr=$am->fetch_assoc()) ? round($amr['m'],1) : '-';
            $at = $students_conn->query("SELECT COUNT(*) t,SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) p FROM student_attendance WHERE student_id=$sid");
            $apc = '-'; if ($at && ($atr=$at->fetch_assoc()) && $atr['t']>0) $apc = round(($atr['p']/$atr['t'])*100,1).'%';
            echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.$cr.'</td><td>'.$avg.'</td><td>'.$apc.'</td></tr>';
        }
        echo '</tbody></table>';
    } elseif ($reportType === 'enrollment') {
        $r = $students_conn->query("SELECT course,current_year,COUNT(*) c FROM students WHERE status='Active' GROUP BY course,current_year ORDER BY course,current_year");
        echo '<h2>Enrollment Report</h2><table><thead><tr><th>Program</th><th>Year</th><th>Students</th></tr></thead><tbody>';
        $gt=0; if ($r) while ($row=$r->fetch_assoc()) { $gt+=$row['c']; echo '<tr><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['current_year'].'</td><td>'.$row['c'].'</td></tr>'; }
        echo '<tr><th colspan="2">Grand Total</th><th>'.$gt.'</th></tr></tbody></table>';
    } elseif ($reportType === 'results') {
        echo '<h2>Results Report</h2><table><thead><tr><th>Student</th><th>Course</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th></tr></thead><tbody>';
        $r = $staff_conn->query("SELECT er.student_id,er.course_code,er.continuous_assessment_marks,er.final_exam_marks,er.marks_obtained,er.total_marks,er.grade,s.full_name FROM examination_records er LEFT JOIN igangaschoolofl_students_db.students s ON er.student_id=s.id ORDER BY s.surname LIMIT 200");
        if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.htmlspecialchars($row['full_name']??'ID:'.$row['student_id']).'</td><td>'.htmlspecialchars($row['course_code']).'</td><td>'.($row['continuous_assessment_marks']??'-').'</td><td>'.($row['final_exam_marks']??'-').'</td><td>'.$row['marks_obtained'].'/'.$row['total_marks'].'</td><td><strong>'.($row['grade']??'-').'</strong></td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'graduation') {
        $r = $students_conn->query("SELECT id,full_name,student_number,registration_number,course,current_year,status FROM students WHERE status IN('Graduated','graduation_candidate') ORDER BY surname,first_name");
        echo '<h2>Graduation Report</h2><table><thead><tr><th>#</th><th>Name</th><th>Reg No</th><th>Program</th><th>Status</th></tr></thead><tbody>';
        $i=1; if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.htmlspecialchars($row['course']).'</td><td>'.htmlspecialchars($row['status']).'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'registration') {
        $r = $staff_conn->query("SELECT cr.id,cr.academic_year,cr.semester,cr.status,cr.registration_date,s.full_name,s.registration_number,s.student_number,cc.course_code,cc.course_name FROM course_registrations cr LEFT JOIN igangaschoolofl_students_db.students s ON cr.student_id=s.id LEFT JOIN academic_course_catalog cc ON cr.course_id=cc.id ORDER BY cr.registration_date DESC LIMIT 200");
        echo '<h2>Course Registration Report</h2><table><thead><tr><th>#</th><th>Student</th><th>Course</th><th>Year</th><th>Semester</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        $i=1; if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['full_name']??$row['student_number']).'</td><td>'.htmlspecialchars($row['course_code']??$row['course_name']??'-').'</td><td>'.htmlspecialchars($row['academic_year']).'</td><td>'.htmlspecialchars($row['semester']).'</td><td>'.$row['status'].'</td><td>'.$row['registration_date'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'grades' || $reportType === 'results') {
        echo '<h2>Grades Report</h2><table><thead><tr><th>Student</th><th>Reg No</th><th>Course</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Status</th></tr></thead><tbody>';
        $r = $staff_conn->query("SELECT er.student_id,er.course_code,er.continuous_assessment_marks,er.final_exam_marks,er.marks_obtained,er.total_marks,er.grade,er.grade_status,s.full_name,s.registration_number,s.student_number FROM examination_records er LEFT JOIN igangaschoolofl_students_db.students s ON er.student_id=s.id ORDER BY s.surname,er.course_code LIMIT 500");
        if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.htmlspecialchars($row['full_name']??'ID:'.$row['student_id']).'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.htmlspecialchars($row['course_code']).'</td><td>'.($row['continuous_assessment_marks']??'-').'</td><td>'.($row['final_exam_marks']??'-').'</td><td>'.$row['marks_obtained'].'/'.$row['total_marks'].'</td><td><strong>'.($row['grade']??'-').'</strong></td><td>'.htmlspecialchars($row['grade_status']??'Pending').'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'students') {
        // Same as student_list
        $r = $students_conn->query("SELECT student_number,registration_number,full_name,course,current_year,gender,status,phone,email FROM students ORDER BY surname,first_name");
        echo '<h2>Full Student List</h2><table><thead><tr><th>#</th><th>Reg No</th><th>Full Name</th><th>Program</th><th>Year</th><th>Gender</th><th>Status</th><th>Phone</th></tr></thead><tbody>';
        $i=1; if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['current_year'].'</td><td>'.htmlspecialchars($row['gender']).'</td><td>'.htmlspecialchars($row['status']).'</td><td>'.htmlspecialchars($row['phone']).'</td></tr>'; }
        echo '</tbody></table><p style="margin-top:8px"><em>Total: '.($i-1).' students</em></p>';
    } elseif ($reportType === 'payments') {
        $r = $students_conn->query("SELECT p.id,p.payment_reference,p.amount_received,p.payment_method,p.payment_date,p.status,s.full_name,s.registration_number FROM payments p LEFT JOIN students s ON p.student_id=s.id ORDER BY p.payment_date DESC LIMIT 300");
        echo '<h2>Payments Report</h2><table><thead><tr><th>#</th><th>Ref</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        $i=1; if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['payment_reference']).'</td><td>'.htmlspecialchars($row['full_name']??'').'</td><td class="text-right">'.number_format($row['amount_received'],0).'</td><td>'.htmlspecialchars($row['payment_method']).'</td><td>'.($row['payment_date']??'').'</td><td>'.htmlspecialchars($row['status']).'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'invoices') {
        $r = $students_conn->query("SELECT i.*,s.full_name,s.registration_number FROM student_invoices i LEFT JOIN students s ON i.student_id=s.id ORDER BY i.issue_date DESC LIMIT 300");
        echo '<h2>Invoices Report</h2><table><thead><tr><th>#</th><th>Invoice #</th><th>Student</th><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th></tr></thead><tbody>';
        $i=1; if ($r) while ($row=$r->fetch_assoc()) { $bal=$row['total_amount']-$row['amount_paid']; echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['invoice_number']).'</td><td>'.htmlspecialchars($row['full_name']??'').'</td><td>'.htmlspecialchars($row['fee_type']).'</td><td class="text-right">'.number_format($row['total_amount'],0).'</td><td class="text-right">'.number_format($row['amount_paid'],0).'</td><td class="text-right">'.number_format($bal,0).'</td><td>'.htmlspecialchars($row['status']).'</td><td>'.($row['due_date']??'').'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'attendance') {
        echo '<h2>Attendance Report</h2><table><thead><tr><th>Student</th><th>Reg No</th><th>Total Sessions</th><th>Present</th><th>Absent</th><th>%</th></tr></thead><tbody>';
        $sr = $students_conn->query("SELECT id,full_name,student_number,registration_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 100");
        if ($sr) while ($srow=$sr->fetch_assoc()) {
            $sid=$srow['id'];
            $at=$students_conn->query("SELECT COUNT(*) t,SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) p FROM student_attendance WHERE student_id=$sid");
            if ($at && ($atr=$at->fetch_assoc()) && $atr['t']>0) {
                $pct=round(($atr['p']/$atr['t'])*100,1);
                echo '<tr><td>'.htmlspecialchars($srow['full_name']).'</td><td>'.htmlspecialchars($srow['registration_number']?:$srow['student_number']).'</td><td>'.$atr['t'].'</td><td>'.$atr['p'].'</td><td>'.($atr['t']-$atr['p']).'</td><td>'.$pct.'%</td></tr>';
            }
        }
        echo '</tbody></table>';
    } elseif ($reportType === 'courses') {
        $r = $students_conn->query("SELECT id,course_code,course_name,program,level,semester,is_compulsory,status FROM course_catalog WHERE status='Active' ORDER BY program,level,course_name");
        echo '<h2>Course Enrollments</h2><table><thead><tr><th>Code</th><th>Course</th><th>Program</th><th>Level</th><th>Semester</th><th>Type</th></tr></thead><tbody>';
        if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.htmlspecialchars($row['course_code']).'</td><td>'.htmlspecialchars($row['course_name']).'</td><td>'.htmlspecialchars($row['program']).'</td><td>'.htmlspecialchars($row['level']).'</td><td>'.htmlspecialchars($row['semester']).'</td><td>'.($row['is_compulsory']?'Compulsory':'Elective').'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'calendar') {
        $r = $staff_conn->query("SELECT * FROM academic_calendar ORDER BY semester_start_date DESC");
        echo '<h2>Academic Calendar</h2><table><thead><tr><th>Year</th><th>Semester</th><th>Start</th><th>End</th><th>Exam Start</th><th>Exam End</th><th>Status</th></tr></thead><tbody>';
        if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.htmlspecialchars($row['academic_year']).'</td><td>'.htmlspecialchars($row['semester']).'</td><td>'.$row['semester_start_date'].'</td><td>'.$row['semester_end_date'].'</td><td>'.$row['exam_start_date'].'</td><td>'.$row['exam_end_date'].'</td><td>'.htmlspecialchars($row['status']??'Upcoming').'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'by_year') {
        $r = $students_conn->query("SELECT current_year,COUNT(*) c FROM students WHERE status='Active' GROUP BY current_year ORDER BY current_year");
        echo '<h2>Students by Year</h2><table><thead><tr><th>Year</th><th>Count</th></tr></thead><tbody>';
        $total=0; if ($r) while ($row=$r->fetch_assoc()) { $total+=$row['c']; echo '<tr><td>Year '.htmlspecialchars($row['current_year']).'</td><td>'.$row['c'].'</td></tr>'; }
        echo '<tr><th>Total</th><th>'.$total.'</th></tr></tbody></table>';
    } elseif ($reportType === 'by_set') {
        $r = $students_conn->query("SELECT `set`,COUNT(*) c FROM students WHERE status='Active' AND `set` IS NOT NULL AND `set`!='' GROUP BY `set` ORDER BY `set`");
        echo '<h2>Students by Set</h2><table><thead><tr><th>Set</th><th>Count</th></tr></thead><tbody>';
        $total=0; if ($r) while ($row=$r->fetch_assoc()) { $total+=$row['c']; echo '<tr><td>'.htmlspecialchars($row['set']).'</td><td>'.$row['c'].'</td></tr>'; }
        echo '<tr><th>Total</th><th>'.$total.'</th></tr></tbody></table>';
    } elseif ($reportType === 'by_status') {
        $r = $students_conn->query("SELECT status,COUNT(*) c FROM students GROUP BY status ORDER BY FIELD(status,'Active','Suspended','Withdrawn','Graduated','Deactivated')");
        echo '<h2>Students by Status</h2><table><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>';
        $total=0; if ($r) while ($row=$r->fetch_assoc()) { $total+=$row['c']; echo '<tr><td>'.htmlspecialchars($row['status']).'</td><td>'.$row['c'].'</td></tr>'; }
        echo '<tr><th>Total</th><th>'.$total.'</th></tr></tbody></table>';
    } elseif ($reportType === 'print_student') {
        $sid = intval($_GET['student_id'] ?? 0);
        if ($sid > 0) {
            $sr = $students_conn->query("SELECT * FROM students WHERE id=$sid");
            $stu = $sr ? $sr->fetch_assoc() : null;
            if ($stu) {
                $fin_r = $students_conn->query("SELECT COALESCE(SUM(total_amount),0) ti,COALESCE(SUM(amount_paid),0) ap FROM student_invoices WHERE student_id=$sid");
                $fin = $fin_r ? $fin_r->fetch_assoc() : ['ti'=>0,'ap'=>0];
                $pay_r = $students_conn->query("SELECT COALESCE(SUM(amount_received),0) tp FROM payments WHERE student_id=$sid");
                $pay = $pay_r ? $pay_r->fetch_assoc() : ['tp'=>0];
                $total_inv = $fin['ti']; $total_paid = $pay['tp'] + $fin['ap']; $balance = $total_inv - $total_paid;
                $exam_r = $staff_conn->query("SELECT course_code,marks_obtained,total_marks,grade FROM examination_records WHERE student_id=$sid ORDER BY created_at DESC LIMIT 10");
                $exams = []; if ($exam_r) while ($rw=$exam_r->fetch_assoc()) $exams[] = $rw;
                echo '<h2>Student Profile: '.htmlspecialchars($stu['full_name']??'').'</h2>';
                echo '<table style="width:100%;border-collapse:collapse;margin-bottom:16px"><tr><td style="padding:4px 8px"><strong>Student No:</strong> '.htmlspecialchars($stu['student_number']??'').'</td><td style="padding:4px 8px"><strong>Reg No:</strong> '.htmlspecialchars($stu['registration_number']??'').'</td></tr>';
                echo '<tr><td style="padding:4px 8px"><strong>Program:</strong> '.htmlspecialchars($stu['course']??'').'</td><td style="padding:4px 8px"><strong>Year:</strong> '.($stu['current_year']??'').'</td></tr>';
                echo '<tr><td style="padding:4px 8px"><strong>Gender:</strong> '.htmlspecialchars($stu['gender']??'').'</td><td style="padding:4px 8px"><strong>Status:</strong> '.htmlspecialchars($stu['status']??'').'</td></tr>';
                echo '<tr><td style="padding:4px 8px"><strong>Phone:</strong> '.htmlspecialchars($stu['phone']??'').'</td><td style="padding:4px 8px"><strong>Email:</strong> '.htmlspecialchars($stu['email']??'').'</td></tr></table>';
                echo '<h3>Financial Summary</h3><table><tr><th>Total Invoiced</th><th>Total Paid</th><th>Balance</th></tr><tr><td>'.number_format($total_inv,0).'</td><td>'.number_format($total_paid,0).'</td><td>'.number_format($balance,0).'</td></tr></table>';
                if ($exams) {
                    echo '<h3>Recent Results</h3><table><thead><tr><th>Course</th><th>Marks</th><th>Grade</th></tr></thead><tbody>';
                    foreach ($exams as $ex) echo '<tr><td>'.htmlspecialchars($ex['course_code']).'</td><td>'.$ex['marks_obtained'].'/'.$ex['total_marks'].'</td><td><strong>'.($ex['grade']??'-').'</strong></td></tr>';
                    echo '</tbody></table>';
                }
            } else { echo '<p>Student not found.</p>'; }
        } else { echo '<p>Invalid student.</p>'; }
    }
    echo '</body></html>'; exit;
}if ($ajaxAction && $ajaxSid > 0) {
    header('Content-Type: application/json');
    if ($ajaxAction === 'get_financial') {
        $invoices = []; $payments = [];
        $r = $students_conn->query("SELECT id,invoice_number,fee_type,total_amount,discount_amount,amount_paid,balance,status,due_date,issue_date FROM student_invoices WHERE student_id=$ajaxSid ORDER BY issue_date DESC");
        if ($r) while ($row = $r->fetch_assoc()) $invoices[] = $row;
        $r = $students_conn->query("SELECT id,payment_reference,amount_received,payment_method,payment_date,status FROM payments WHERE student_id=$ajaxSid ORDER BY payment_date DESC");
        if ($r) while ($row = $r->fetch_assoc()) $payments[] = $row;
        $totalInv = array_sum(array_column($invoices,'total_amount'));
        $totalPaid = array_sum(array_column($payments,'amount_received'));
        echo json_encode(['invoices'=>$invoices,'payments'=>$payments,'total_invoiced'=>$totalInv,'total_paid'=>$totalPaid,'balance'=>$totalInv - $totalPaid]);
        exit;
    }
    if ($ajaxAction === 'get_results') {
        $data = [];
        $r = $staff_conn->query("SELECT er.exam_number,er.exam_type,er.course_code,er.marks_obtained,er.total_marks,er.grade,er.continuous_assessment_marks,er.final_exam_marks,er.grade_status,er.created_at FROM examination_records er WHERE er.student_id=$ajaxSid ORDER BY er.created_at DESC");
        if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }
    if ($ajaxAction === 'get_attendance') {
        $data = [];
        $r = $students_conn->query("SELECT id,date,subject,course_code,status,remarks FROM student_attendance WHERE student_id=$ajaxSid ORDER BY date DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }
    if ($ajaxAction === 'get_courses') {
        $data = [];
        $r = $students_conn->query("SELECT scr.id,scr.course_id,scr.academic_year,scr.semester,scr.status,scr.registration_date,cc.course_code,cc.course_name FROM student_course_registrations scr LEFT JOIN igangaschoolofl_staffs_db.academic_course_catalog cc ON scr.course_id=cc.id WHERE scr.student_id=$ajaxSid ORDER BY scr.registration_date DESC");
        if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }
    if ($ajaxAction === 'get_documents') {
        $data = [];
        $r = $staff_conn->query("SELECT id,document_type,document_title,file_path,generation_date FROM generated_documents WHERE student_id=$ajaxSid ORDER BY generation_date DESC");
        if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }
    echo json_encode([]);
    exit;
}
// Stats
$total_students    = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status='Active'");
$new_admissions    = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)");
$pending_approvals = safeCount($staff_conn,    "SELECT COUNT(*) c FROM grading_approval_workflow WHERE current_stage IN('HOD Review','Registrar Approval','Principal Final Approval')");
$exam_pending      = safeCount($staff_conn,    "SELECT COUNT(*) c FROM examination_records WHERE grade IS NULL OR grade=''");
$course_regs       = safeCount($staff_conn,    "SELECT COUNT(*) c FROM course_registrations WHERE status='Registered'");
$grad_candidates   = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status IN('Graduated','graduation_candidate')");
$notifications     = safeCount($students_conn, "SELECT COUNT(*) c FROM student_notifications WHERE is_read=0");
$cal_reminders     = safeCount($staff_conn,    "SELECT COUNT(*) c FROM academic_calendar WHERE semester_start_date BETWEEN NOW() AND DATE_ADD(NOW(),INTERVAL 30 DAY)");
$trash_count       = safeCount($students_conn, "SELECT COUNT(*) c FROM students_trash");

// Search
$search = trim($_GET['q'] ?? '');
$filter_program = $_GET['program'] ?? '';
$filter_status  = $_GET['status'] ?? '';
$filter_year    = $_GET['year'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = ["1=1"];
$params = []; $types = '';
if ($search) {
    $where[] = "(first_name LIKE ? OR surname LIKE ? OR student_number LIKE ? OR registration_number LIKE ? OR national_student_id_number LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s,$s]);
    $types .= 'sssss';
}
if ($filter_program) { $where[] = "course=?"; $params[] = $filter_program; $types .= 's'; }
if ($filter_status)  { $where[] = "status=?";  $params[] = $filter_status;  $types .= 's'; }
if ($filter_year)    { $where[] = "current_year=?"; $params[] = $filter_year; $types .= 'i'; }

$sql_where = implode(' AND ', $where);
$total_found = 0;
$students = [];

$cnt_sql = "SELECT COUNT(*) c FROM students WHERE $sql_where";
$cnt_stmt = $students_conn->prepare($cnt_sql);
if ($types) $cnt_stmt->bind_param($types, ...$params);
$cnt_stmt->execute();
$total_found = $cnt_stmt->get_result()->fetch_assoc()['c'];
$cnt_stmt->close();

$data_sql = "SELECT id,student_number,registration_number,national_student_id_number,first_name,surname,other_name,full_name,course,current_year,current_semester,set_name,gender,status,phone,email,intake_date,created_at FROM students WHERE $sql_where ORDER BY surname,first_name LIMIT $per_page OFFSET $offset";
$d_stmt = $students_conn->prepare($data_sql);
if ($types) $d_stmt->bind_param($types, ...$params);
$d_stmt->execute();
$res = $d_stmt->get_result();
while ($row = $res->fetch_assoc()) $students[] = $row;
$d_stmt->close();
$total_pages = max(1, ceil($total_found / $per_page));

// Calendar
$calendars = [];
$cal_r = $staff_conn->query("SELECT * FROM academic_calendar ORDER BY created_at DESC LIMIT 5");
if ($cal_r) while ($row = $cal_r->fetch_assoc()) $calendars[] = $row;

// Trash
$trash = [];
$tr_r = $students_conn->query("SELECT * FROM students_trash ORDER BY deleted_at DESC LIMIT 20");
if ($tr_r) while ($row = $tr_r->fetch_assoc()) $trash[] = $row;
$trashedStudents = $trash;

// Course catalog for registration
$courses_catalog = [];
$cc_r = $staff_conn->query("SELECT id,course_code,course_name FROM academic_course_catalog ORDER BY course_name");
if ($cc_r) while ($row = $cc_r->fetch_assoc()) $courses_catalog[] = $row;

// Fee types
$fee_types = ['Tuition','Functional Fee','Accommodation','Library','Lab','Examination','Uniform','Activity Fee','Other'];
// Handle POST actions
$redirectSection = $_POST['_section'] ?? '';
function redirectBack($hash = '') {
    $section = $GLOBALS['redirectSection'];
    $loc = 'academic-registrar.php';
    if ($hash) $loc .= '#' . $hash;
    elseif ($section) $loc .= '#' . $section;
    header("Location: $loc");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_student') {
        $fn  = $students_conn->real_escape_string(trim($_POST['first_name']  ?? ''));
        $sn  = $students_conn->real_escape_string(trim($_POST['surname']     ?? ''));
        $on  = $students_conn->real_escape_string(trim($_POST['other_name']  ?? ''));
        $dob = $students_conn->real_escape_string(trim($_POST['dob']         ?? ''));
        $gen = $students_conn->real_escape_string(trim($_POST['gender']      ?? 'Other'));
        $crs = $students_conn->real_escape_string(trim($_POST['course']      ?? ''));
        $yr  = intval($_POST['year'] ?? 1);
        $sem = $students_conn->real_escape_string(trim($_POST['semester']    ?? ''));
        $ph  = $students_conn->real_escape_string(trim($_POST['phone']       ?? ''));
        $em  = $students_conn->real_escape_string(trim($_POST['email']       ?? ''));
        $nat = $students_conn->real_escape_string(trim($_POST['nationality'] ?? 'Ugandan'));
        $snum = 'STU'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
        $full = trim("$fn $on $sn");
        $students_conn->query("INSERT INTO students (student_number,first_name,surname,other_name,full_name,date_of_birth,gender,course,program,current_year,year,current_semester,phone,mobile_number,email,nationality,status,created_at) VALUES ('$snum','$fn','$sn','$on','$full','$dob','$gen','$crs','$crs',$yr,$yr,'$sem','$ph','$ph','$em','$nat','Active',NOW())");
        if ($students_conn->affected_rows > 0) {
            $students_conn->query("INSERT INTO academic_registrar_activity_log (activity,created_by,created_at) VALUES ('Added new student: $full',{$_SESSION['user_id']},NOW())");
            $_SESSION['success'] = "Student $full added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add student: ".$students_conn->error;
        }
        redirectBack(); exit;
    }

    if ($action === 'edit_student') {
        $id  = intval($_POST['id']);
        $fn  = $students_conn->real_escape_string(trim($_POST['first_name']  ?? ''));
        $sn  = $students_conn->real_escape_string(trim($_POST['surname']     ?? ''));
        $on  = $students_conn->real_escape_string(trim($_POST['other_name']  ?? ''));
        $crs = $students_conn->real_escape_string(trim($_POST['course']      ?? ''));
        $yr  = intval($_POST['year'] ?? 1);
        $sem = $students_conn->real_escape_string(trim($_POST['semester']    ?? ''));
        $ph  = $students_conn->real_escape_string(trim($_POST['phone']       ?? ''));
        $em  = $students_conn->real_escape_string(trim($_POST['email']       ?? ''));
        $st  = $students_conn->real_escape_string(trim($_POST['status']      ?? 'Active'));
        $full = trim("$fn $on $sn");
        $students_conn->query("UPDATE students SET first_name='$fn',surname='$sn',other_name='$on',full_name='$full',course='$crs',program='$crs',current_year=$yr,year=$yr,current_semester='$sem',phone='$ph',mobile_number='$ph',email='$em',status='$st',updated_at=NOW() WHERE id=$id");
        $_SESSION['success'] = "Student updated.";
        redirectBack(); exit;
    }

    if ($action === 'trash_student') {
        $id = intval($_POST['id']);
        $row_r = $students_conn->query("SELECT * FROM students WHERE id=$id");
        if ($row_r && $orig = $row_r->fetch_assoc()) {
            $snap = $students_conn->real_escape_string(json_encode($orig));
            $snum = $students_conn->real_escape_string($orig['student_number']);
            $fname = $students_conn->real_escape_string($orig['full_name'] ?? $orig['first_name'].' '.$orig['surname']);
            $em = $students_conn->real_escape_string($orig['email']);
            $crs = $students_conn->real_escape_string($orig['course']);
            $students_conn->query("INSERT INTO students_trash (original_id,student_number,full_name,email,course,snapshot_data,deleted_by,deleted_at) VALUES ($id,'$snum','$fname','$em','$crs','$snap',{$_SESSION['user_id']},NOW())");
            $students_conn->query("UPDATE students SET status='deleted' WHERE id=$id");
            $students_conn->query("INSERT INTO academic_registrar_activity_log (activity,created_by,created_at) VALUES ('Moved to trash: $fname',{$_SESSION['user_id']},NOW())");
            $_SESSION['success'] = "Student moved to trash.";
        }
        header("Location: academic-registrar.php#recycle-bin"); exit;
    }

    if ($action === 'restore_student') {
        $tid = intval($_POST['trash_id']);
        $tr = $students_conn->query("SELECT * FROM students_trash WHERE id=$tid");
        if ($tr && $t = $tr->fetch_assoc()) {
            $oid = $t['original_id'];
            $students_conn->query("UPDATE students SET status='Active',updated_at=NOW() WHERE id=$oid");
            $students_conn->query("UPDATE students_trash SET restored_at=NOW() WHERE id=$tid");
            $students_conn->query("DELETE FROM students_trash WHERE id=$tid");
            $_SESSION['success'] = "Student restored.";
        }
        header("Location: academic-registrar.php#recycle-bin"); exit;
    }

    if ($action === 'delete_permanent') {
        $tid = intval($_POST['trash_id']);
        $tr = $students_conn->query("SELECT original_id,full_name FROM students_trash WHERE id=$tid");
        if ($tr && $t = $tr->fetch_assoc()) {
            $oid = $t['original_id'];
            $nm = $students_conn->real_escape_string($t['full_name']);
            $students_conn->query("DELETE FROM students WHERE id=$oid");
            $students_conn->query("DELETE FROM students_trash WHERE id=$tid");
            $students_conn->query("INSERT INTO academic_registrar_activity_log (activity,created_by,created_at) VALUES ('Permanently deleted: $nm',{$_SESSION['user_id']},NOW())");
            $_SESSION['success'] = "Student permanently deleted.";
        }
        header("Location: academic-registrar.php#recycle-bin"); exit;
    }

    if ($action === 'record_payment') {
        $sid = intval($_POST['student_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $method = $students_conn->real_escape_string($_POST['payment_method'] ?? 'Cash');
        $ref = $students_conn->real_escape_string($_POST['reference'] ?? '');
        $notes = $students_conn->real_escape_string($_POST['notes'] ?? '');
        $pdate = $students_conn->real_escape_string($_POST['payment_date'] ?? date('Y-m-d'));
        if ($sid > 0 && $amount > 0) {
            $pref = 'PAY-'.date('Ymd').'-'.mt_rand(10000,99999);
            $stmt = $students_conn->prepare("INSERT INTO payments (payment_reference,student_id,amount_received,payment_method,payment_date,status,notes) VALUES (?,?,?,?,?,'Completed',?)");
            $stmt->bind_param("sidsss", $pref, $sid, $amount, $method, $pdate, $notes);
            if ($stmt->execute()) {
                $pid = $stmt->insert_id;
                $stmt->close();
                $rno = 'RCT-'.date('Ymd').'-'.str_pad($pid,4,'0',STR_PAD_LEFT);
                $students_conn->query("INSERT INTO payment_receipts (receipt_number,payment_id,student_id,amount,payment_method,issued_by) VALUES ('$rno',$pid,$sid,$amount,'$method',{$_SESSION['user_id']})");
                $students_conn->query("UPDATE student_invoices SET amount_paid=amount_paid+$amount,status=CASE WHEN (balance-$amount)<=0 THEN 'Paid' WHEN amount_paid+$amount>0 THEN 'Partially Paid' ELSE status END WHERE student_id=$sid AND status IN('Pending','Partially Paid','Overdue')");
                $_SESSION['success'] = "Payment recorded. Receipt: $rno";
            } else {
                $_SESSION['error'] = 'Payment failed: '.$stmt->error;
                $stmt->close();
            }
        } else { $_SESSION['error'] = 'Valid student and amount required.'; }
        redirectBack(); exit;
    }

    if ($action === 'create_invoice') {
        $sid = intval($_POST['student_id'] ?? 0);
        $fee_type = $students_conn->real_escape_string($_POST['fee_type'] ?? 'Tuition');
        $amount = floatval($_POST['total_amount'] ?? 0);
        $due_date = $students_conn->real_escape_string($_POST['due_date'] ?? '');
        if ($sid > 0 && $amount > 0) {
            $invNo = 'INV-'.date('Y').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $stmt = $students_conn->prepare("INSERT INTO student_invoices (invoice_number,student_id,fee_type,total_amount,amount_paid,due_date,status,created_by) VALUES (?,?,?,?,0,?,?,{$_SESSION['user_id']})");
            $stmt->bind_param("sisds", $invNo, $sid, $fee_type, $amount, $due_date);
            if ($stmt->execute()) { $_SESSION['success'] = "Invoice $invNo created."; } else { $_SESSION['error'] = 'Invoice failed: '.$stmt->error; }
            $stmt->close();
        } else { $_SESSION['error'] = 'Student and amount required.'; }
        redirectBack(); exit;
    }

    if ($action === 'upload_document') {
        $sid = intval($_POST['student_id'] ?? 0);
        $docType = $staff_conn->real_escape_string($_POST['document_type'] ?? 'Other');
        $title = $staff_conn->real_escape_string($_POST['document_title'] ?? '');
        $fpath = '';
        if ($sid > 0 && $title) {
            if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
                $dir = __DIR__ . '/../uploads/student_docs/' . $sid;
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
                $fname = time() . '_' . preg_replace('/[^a-z0-9]/i', '_', $title) . '.' . $ext;
                $dest = $dir . '/' . $fname;
                if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $dest)) {
                    $fpath = "uploads/student_docs/$sid/$fname";
                }
            }
            $stmt = $staff_conn->prepare("INSERT INTO generated_documents (document_type,student_id,generated_by,document_title,file_path) VALUES (?,?,{$_SESSION['user_id']},?,?)");
            $stmt->bind_param("siss", $docType, $sid, $title, $fpath);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = "Document '$title' " . ($fpath ? 'uploaded' : 'generated') . ".";
        } else { $_SESSION['error'] = 'Student and title required.'; }
        redirectBack(); exit;
    }

    if ($action === 'register_course') {
        $sid = intval($_POST['student_id'] ?? 0);
        $courseId = intval($_POST['course_id'] ?? 0);
        $ay = $staff_conn->real_escape_string($_POST['academic_year'] ?? date('Y'));
        $sem = $staff_conn->real_escape_string($_POST['semester'] ?? 'Semester 1');
        if ($sid > 0 && $courseId > 0) {
            $students_conn->query("INSERT IGNORE INTO student_course_registrations (student_id,course_id,academic_year,semester,status) VALUES ($sid,$courseId,'$ay','$sem','Registered')");
            $_SESSION['success'] = 'Course registered.';
        } else { $_SESSION['error'] = 'Student and course required.'; }
        redirectBack(); exit;
    }

    if ($action === 'add_calendar') {
        $ay  = $staff_conn->real_escape_string($_POST['academic_year'] ?? date('Y').'-'.date('Y',strtotime('+1 year')));
        $sem = $staff_conn->real_escape_string($_POST['semester'] ?? '');
        $ss  = $_POST['semester_start'] ?? date('Y-m-d');
        $se  = $_POST['semester_end']   ?? date('Y-m-d',strtotime('+4 months'));
        $es  = $_POST['exam_start']     ?? date('Y-m-d',strtotime('+3 months'));
        $ee  = $_POST['exam_end']       ?? date('Y-m-d',strtotime('+4 months'));
        $rd  = $_POST['result_date']    ?? '';
        $rg  = $_POST['reg_deadline']   ?? date('Y-m-d');
        $cid = 'CAL-'.$ay.'-'.substr($sem,0,2).'-'.mt_rand(100,999);
        $staff_conn->query("INSERT INTO academic_calendar (calendar_id,academic_year,semester,semester_start_date,semester_end_date,exam_start_date,exam_end_date,result_publication_date,registration_deadline,status,created_by,created_at) VALUES ('$cid','$ay','$sem','$ss','$se','$es','$ee','$rd','$rg','Upcoming',{$_SESSION['user_id']},NOW())");
        $_SESSION['success'] = "Academic calendar entry added.";
        header("Location: academic-registrar.php#academic-calendar"); exit;
    }
}
// ── NEW DATA FOR NEW SECTIONS ──
// New admissions data
$new_applicants_30d = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)");
$approved_applications = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status='Active'");
$rejected_applications = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status='Rejected'");
$pending_applications_count = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status='Pending'");
$total_applications = safeCount($students_conn, "SELECT COUNT(*) c FROM students");

$recent_applications = [];
$ra_r = $students_conn->query("SELECT id,full_name,student_number,registration_number,course,current_year,gender,status,created_at FROM students ORDER BY created_at DESC LIMIT 20");
if ($ra_r) while ($row = $ra_r->fetch_assoc()) $recent_applications[] = $row;

// Programs
$programs_list = [];
$pl_r = $students_conn->query("SELECT DISTINCT course AS program_name,COUNT(*) AS student_count FROM students WHERE status='Active' GROUP BY course ORDER BY course");
if ($pl_r) while ($row = $pl_r->fetch_assoc()) $programs_list[] = $row;

$course_catalog = [];
$cat_r = $staff_conn->query("SELECT * FROM academic_course_catalog ORDER BY department,course_name");
if ($cat_r) while ($row = $cat_r->fetch_assoc()) $course_catalog[] = $row;

$departments = [];
$dep_r = $staff_conn->query("SELECT DISTINCT department FROM academic_course_catalog WHERE department IS NOT NULL AND department != '' ORDER BY department");
if ($dep_r) while ($row = $dep_r->fetch_assoc()) $departments[] = $row['department'];

// Examinations
$exam_records_list = [];
$ex_r = $staff_conn->query("SELECT er.*,s.full_name,s.student_number,s.registration_number FROM examination_records er LEFT JOIN igangaschoolofl_students_db.students s ON er.student_id=s.id ORDER BY er.created_at DESC LIMIT 100");
if ($ex_r) while ($row = $ex_r->fetch_assoc()) $exam_records_list[] = $row;

$exam_timetable = [];
$et_r = $staff_conn->query("SELECT * FROM academic_calendar WHERE exam_start_date IS NOT NULL ORDER BY exam_start_date DESC LIMIT 10");
if ($et_r) while ($row = $et_r->fetch_assoc()) $exam_timetable[] = $row;

$missing_results = [];
$mr_r = $staff_conn->query("SELECT er.student_id,er.course_code,er.exam_type,er.exam_number,s.full_name,s.registration_number FROM examination_records er LEFT JOIN igangaschoolofl_students_db.students s ON er.student_id=s.id WHERE (er.marks_obtained IS NULL OR er.grade IS NULL OR er.grade='') ORDER BY er.created_at DESC LIMIT 50");
if ($mr_r) while ($row = $mr_r->fetch_assoc()) $missing_results[] = $row;

// Graduation
$grad_candidates_list = [];
$gc_r = $students_conn->query("SELECT id,full_name,student_number,registration_number,course,current_year,gender,status,created_at FROM students WHERE status IN('Graduated','graduation_candidate') ORDER BY surname,first_name");
if ($gc_r) while ($row = $gc_r->fetch_assoc()) $grad_candidates_list[] = $row;

// Notifications
$announcements = [];
$an_r = $students_conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 20");
if ($an_r) while ($row = $an_r->fetch_assoc()) $announcements[] = $row;

$system_alerts = [];
$sa_r = $students_conn->query("SELECT * FROM student_notifications ORDER BY created_at DESC LIMIT 20");
if ($sa_r) while ($row = $sa_r->fetch_assoc()) $system_alerts[] = $row;

// Approvals
$approval_items = [];
$ap_r = $staff_conn->query("SELECT * FROM grading_approval_workflow ORDER BY created_at DESC LIMIT 20");
if ($ap_r) while ($row = $ap_r->fetch_assoc()) $approval_items[] = $row;

$course_reg_approvals = [];
$cra_r = $staff_conn->query("SELECT cr.*,s.full_name,s.registration_number,cc.course_code,cc.course_name FROM course_registrations cr LEFT JOIN igangaschoolofl_students_db.students s ON cr.student_id=s.id LEFT JOIN academic_course_catalog cc ON cr.course_id=cc.id WHERE cr.status IN('Pending','Submitted') ORDER BY cr.registration_date DESC LIMIT 20");
if ($cra_r) while ($row = $cra_r->fetch_assoc()) $course_reg_approvals[] = $row;

// Audit log
$audit_log = [];
$al_r = $students_conn->query("SELECT * FROM academic_registrar_activity_log ORDER BY created_at DESC LIMIT 100");
if ($al_r) while ($row = $al_r->fetch_assoc()) $audit_log[] = $row;

// Settings data
$academic_years = [];
$ay_r = $staff_conn->query("SELECT DISTINCT academic_year FROM academic_calendar ORDER BY academic_year DESC");
if ($ay_r) while ($row = $ay_r->fetch_assoc()) $academic_years[] = $row['academic_year'];

$recent_admissions = [];
$ra2_r = $students_conn->query("SELECT id,full_name,student_number,registration_number,course,created_at,status FROM students ORDER BY created_at DESC LIMIT 10");
if ($ra2_r) while ($row = $ra2_r->fetch_assoc()) $recent_admissions[] = $row;

$upcoming_events = $calendars;

// ── NEW DATA FOR GROUPING ──
// Student grouping by program
$student_group_by_program = [];
$sgp_r = $students_conn->query("SELECT course AS program,COUNT(*) AS student_count,SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) AS active_count FROM students WHERE full_name IS NOT NULL AND full_name != '' AND LENGTH(full_name) > 3 AND full_name NOT LIKE '%MINISTRY%' AND full_name NOT LIKE '%ACCOUNTABILITY%' AND full_name NOT LIKE '%VERIFICATION%' AND full_name NOT LIKE '%HEALTH EDUCATION%' AND full_name NOT LIKE '%……………………………………………………%' GROUP BY course ORDER BY student_count DESC");
if ($sgp_r) while ($row = $sgp_r->fetch_assoc()) $student_group_by_program[] = $row;

// Student grouping by set
$student_group_by_set = [];
$sgs_r = $students_conn->query("SELECT set_name,COUNT(*) AS student_count FROM students WHERE set_name IS NOT NULL AND set_name != '' AND full_name IS NOT NULL AND full_name != '' AND LENGTH(full_name) > 3 AND full_name NOT LIKE '%MINISTRY%' AND full_name NOT LIKE '%ACCOUNTABILITY%' AND full_name NOT LIKE '%VERIFICATION%' AND full_name NOT LIKE '%HEALTH EDUCATION%' AND full_name NOT LIKE '%……………………………………………………%' GROUP BY set_name ORDER BY set_name DESC");
if ($sgs_r) while ($row = $sgs_r->fetch_assoc()) $student_group_by_set[] = $row;

// Student grouping by year
$student_group_by_year = [];
$sgy_r = $students_conn->query("SELECT current_year,COUNT(*) AS student_count FROM students WHERE full_name IS NOT NULL AND full_name != '' AND LENGTH(full_name) > 3 AND full_name NOT LIKE '%MINISTRY%' AND full_name NOT LIKE '%ACCOUNTABILITY%' AND full_name NOT LIKE '%VERIFICATION%' AND full_name NOT LIKE '%HEALTH EDUCATION%' AND full_name NOT LIKE '%……………………………………………………%' GROUP BY current_year ORDER BY current_year");
if ($sgy_r) while ($row = $sgy_r->fetch_assoc()) $student_group_by_year[] = $row;

// Student grouping by status
$student_group_by_status = [];
$sgs2_r = $students_conn->query("SELECT status,COUNT(*) AS student_count FROM students WHERE full_name IS NOT NULL AND full_name != '' AND LENGTH(full_name) > 3 AND full_name NOT LIKE '%MINISTRY%' AND full_name NOT LIKE '%ACCOUNTABILITY%' AND full_name NOT LIKE '%VERIFICATION%' AND full_name NOT LIKE '%HEALTH EDUCATION%' AND full_name NOT LIKE '%……………………………………………………%' GROUP BY status ORDER BY student_count DESC");
if ($sgs2_r) while ($row = $sgs2_r->fetch_assoc()) $student_group_by_status[] = $row;

// Program grouping by department
$program_group_by_department = [];
$pgd_r = $staff_conn->query("SELECT department,COUNT(*) AS course_count,GROUP_CONCAT(DISTINCT course_code SEPARATOR ', ') AS course_codes FROM academic_course_catalog WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY department");
if ($pgd_r) while ($row = $pgd_r->fetch_assoc()) $program_group_by_department[] = $row;

// Document grouping by type
$document_group_by_type = [];
$dgt_r = $staff_conn->query("SELECT document_type,COUNT(*) AS doc_count,COUNT(DISTINCT student_id) AS student_count FROM generated_documents WHERE document_type IS NOT NULL AND document_type != '' GROUP BY document_type ORDER BY doc_count DESC");
if ($dgt_r) while ($row = $dgt_r->fetch_assoc()) $document_group_by_type[] = $row;

// Document grouping by status
$document_group_by_status = [];
$dgs_r = $staff_conn->query("SELECT status,COUNT(*) AS doc_count FROM generated_documents WHERE status IS NOT NULL GROUP BY status ORDER BY doc_count DESC");
if ($dgs_r) while ($row = $dgs_r->fetch_assoc()) $document_group_by_status[] = $row;

// Course grouping by department
$course_group_by_department = [];
$cgd_r = $staff_conn->query("SELECT department,COUNT(*) AS course_count,SUM(credit_hours) AS total_credits FROM academic_course_catalog WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY department");
if ($cgd_r) while ($row = $cgd_r->fetch_assoc()) $course_group_by_department[] = $row;

// New stats for expanded overview cards
$total_pending_applications = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status='Pending'");
$total_registered_students = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status='Active' AND registration_number IS NOT NULL AND registration_number != ''");
$total_pending_registrations = safeCount($staff_conn, "SELECT COUNT(*) c FROM course_registrations WHERE status IN('Pending','Submitted')");
$total_transcripts_issued = safeCount($staff_conn, "SELECT COUNT(*) c FROM registrar_transcripts WHERE transcript_status IN('Issued','Collected')");
$total_certificates_issued = safeCount($staff_conn, "SELECT COUNT(*) c FROM registrar_certificates WHERE status IN('Issued','Collected')");
$total_active_programmes = safeCount($students_conn, "SELECT COUNT(DISTINCT course) c FROM students WHERE status='Active'");
$total_graduation_candidates = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status IN('Graduated','graduation_candidate')");
$total_pending_approvals = safeCount($staff_conn, "SELECT COUNT(*) c FROM grading_approval_workflow WHERE current_stage IN('HOD Review','Registrar Approval','Principal Final Approval')");

// Transcript requests
$transcript_requests = [];
$tr_r2 = $staff_conn->query("SELECT rt.*,s.full_name AS student_name,s.registration_number,s.course,(SELECT id FROM generated_documents WHERE student_id=rt.student_id AND document_type='Transcript' ORDER BY id DESC LIMIT 1) AS doc_id FROM registrar_transcripts rt LEFT JOIN igangaschoolofl_students_db.students s ON rt.student_id=s.id ORDER BY rt.request_date DESC LIMIT 50");
if ($tr_r2) while ($row = $tr_r2->fetch_assoc()) $transcript_requests[] = $row;

// Certificate records
$certificate_records = [];
$cr_r2 = $staff_conn->query("SELECT rc.*,s.full_name AS student_name,s.registration_number,s.course,(SELECT id FROM generated_documents WHERE student_id=rc.student_id AND document_type='Certificate' ORDER BY id DESC LIMIT 1) AS doc_id FROM registrar_certificates rc LEFT JOIN igangaschoolofl_students_db.students s ON rc.student_id=s.id ORDER BY rc.created_at DESC LIMIT 50");
if ($cr_r2) while ($row = $cr_r2->fetch_assoc()) $certificate_records[] = $row;

// Registrar settings
$registrar_settings = [];
$rs_r = $staff_conn->query("SELECT * FROM registrar_settings ORDER BY setting_group,setting_key");
if ($rs_r) while ($row = $rs_r->fetch_assoc()) $registrar_settings[] = $row;

// Student records (for records section with grouping)
$student_records_by_program = [];
$srp_r = $students_conn->query("SELECT course,current_year,set_name,COUNT(*) AS count,SUM(CASE WHEN gender='Male' THEN 1 ELSE 0 END) AS male,SUM(CASE WHEN gender='Female' THEN 1 ELSE 0 END) AS female FROM students WHERE status='Active' AND full_name IS NOT NULL AND full_name != '' AND LENGTH(full_name) > 3 AND full_name NOT LIKE '%MINISTRY%' AND full_name NOT LIKE '%ACCOUNTABILITY%' AND full_name NOT LIKE '%VERIFICATION%' AND full_name NOT LIKE '%HEALTH EDUCATION%' AND full_name NOT LIKE '%……………………………………………………%' GROUP BY course,current_year,set_name ORDER BY course,current_year,set_name");
if ($srp_r) while ($row = $srp_r->fetch_assoc()) $student_records_by_program[] = $row;

// Academic programs with departments
$academic_programs = [];
$ap_r2 = $staff_conn->query("SELECT ap.*,COUNT(s.id) AS enrolled_students FROM academic_programs ap LEFT JOIN igangaschoolofl_students_db.students s ON ap.program_name=s.course AND s.status='Active' GROUP BY ap.id ORDER BY ap.program_name");
if ($ap_r2) while ($row = $ap_r2->fetch_assoc()) $academic_programs[] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_exam_marks') {
        $eid = intval($_POST['exam_id'] ?? 0);
        $ca = floatval($_POST['ca_marks'] ?? 0);
        $exam = floatval($_POST['exam_marks'] ?? 0);
        $total = floatval($_POST['total_marks'] ?? 0);
        $grade = $staff_conn->real_escape_string($_POST['grade'] ?? '');
        if ($eid > 0) {
            $staff_conn->query("UPDATE examination_records SET continuous_assessment_marks=$ca,final_exam_marks=$exam,marks_obtained=$total,grade='$grade',grade_status='Entered' WHERE id=$eid");
            // Auto-generate documents if auto_gen flag is set
            if (!empty($_POST['auto_generate_docs'])) {
                $er = $staff_conn->query("SELECT student_id FROM examination_records WHERE id=$eid");
                if ($er && $erow = $er->fetch_assoc()) {
                    $asid = intval($erow['student_id']);
                    $sr = $students_conn->query("SELECT * FROM students WHERE id=$asid");
                    $student = $sr ? $sr->fetch_assoc() : null;
                    if ($student) {
                        $settings = ['institution_name' => 'ISNM', 'current_academic_year' => date('Y'), 'transcript_fee' => '50000'];
                        // Generate transcript
                        $courses = [];
                        $cr = $staff_conn->query("SELECT er.*, cc.course_name, cc.credit_hours FROM examination_records er LEFT JOIN academic_course_catalog cc ON er.course_code = cc.course_code WHERE er.student_id=$asid AND er.marks_obtained IS NOT NULL ORDER BY er.created_at ASC");
                        if ($cr) while ($row = $cr->fetch_assoc()) $courses[] = $row;
                        $tnum = 'AUTO-T'.date('Ymd').str_pad(mt_rand(100,999),3,'0',STR_PAD_LEFT);
                        $t_html = generateProfessionalTranscript($student, $courses, $settings, $tnum);
                        $t_esc = $staff_conn->real_escape_string($t_html);
                        $t_title = $staff_conn->real_escape_string("Academic Transcript - ".($student['full_name']??''));
                        $staff_conn->query("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Transcript', $asid, {$_SESSION['user_id']}, '$t_title', '$t_esc', NOW())");
                        // Generate certificate
                        $gpa_r = $staff_conn->query("SELECT AVG(marks_obtained) avg_m FROM examination_records WHERE student_id=$asid AND marks_obtained IS NOT NULL");
                        $class_of_award = '';
                        if ($gpa_r && $gpa_row = $gpa_r->fetch_assoc()) {
                            $avg = floatval($gpa_row['avg_m'] ?? 0);
                            if ($avg >= 80) $class_of_award = 'First Class Honours';
                            elseif ($avg >= 70) $class_of_award = 'Second Class Upper Division';
                            elseif ($avg >= 60) $class_of_award = 'Second Class Lower Division';
                            elseif ($avg >= 50) $class_of_award = 'Pass';
                            else $class_of_award = 'Fail';
                        }
                        $cnum = 'AUTO-C'.date('Ymd').str_pad(mt_rand(100,999),3,'0',STR_PAD_LEFT);
                        $c_html = generateProfessionalCertificate($student, $settings, 'Diploma', $cnum, $class_of_award);
                        $c_esc = $staff_conn->real_escape_string($c_html);
                        $c_title = $staff_conn->real_escape_string("Certificate - ".($student['full_name']??''));
                        $staff_conn->query("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Certificate', $asid, {$_SESSION['user_id']}, '$c_title', '$c_esc', NOW())");
                        $_SESSION['success'] = 'Exam marks updated. Transcript & Certificate auto-generated.';
                    }
                }
            } else {
                $_SESSION['success'] = 'Exam marks updated.';
            }
        }
        header("Location: academic-registrar.php#examinations"); exit;
    }

    if ($action === 'approve_admission') {
        $sid = intval($_POST['student_id'] ?? 0);
        if ($sid > 0) {
            $students_conn->query("UPDATE students SET status='Active' WHERE id=$sid");
            $students_conn->query("INSERT INTO academic_registrar_activity_log (activity,created_by,created_at) VALUES ('Approved admission for student ID $sid',{$_SESSION['user_id']},NOW())");
            $_SESSION['success'] = 'Admission approved.';
        }
        header("Location: academic-registrar.php#admissions"); exit;
    }

    if ($action === 'reject_admission') {
        $sid = intval($_POST['student_id'] ?? 0);
        if ($sid > 0) {
            $students_conn->query("UPDATE students SET status='Rejected' WHERE id=$sid");
            $_SESSION['success'] = 'Admission rejected.';
        }
        header("Location: academic-registrar.php#admissions"); exit;
    }

    if ($action === 'approve_course_reg') {
        $rid = intval($_POST['reg_id'] ?? 0);
        if ($rid > 0) {
            $staff_conn->query("UPDATE course_registrations SET status='Approved' WHERE id=$rid");
            $_SESSION['success'] = 'Course registration approved.';
        }
        header("Location: academic-registrar.php#approvals"); exit;
    }

    if ($action === 'approve_graduation') {
        $sid = intval($_POST['student_id'] ?? 0);
        if ($sid > 0) {
            $students_conn->query("UPDATE students SET status='Graduated' WHERE id=$sid");
            $_SESSION['success'] = 'Graduation approved.';
        }
        header("Location: academic-registrar.php#graduation"); exit;
    }

    if ($action === 'compose_announcement') {
        $title = $students_conn->real_escape_string(trim($_POST['ann_title'] ?? ''));
        $msg = $students_conn->real_escape_string(trim($_POST['ann_message'] ?? ''));
        if ($title && $msg) {
            $students_conn->query("INSERT INTO announcements (title,message,created_by,created_at) VALUES ('$title','$msg',{$_SESSION['user_id']},NOW())");
            $_SESSION['success'] = 'Announcement published.';
        } else { $_SESSION['error'] = 'Title and message required.'; }
        header("Location: academic-registrar.php#notifications"); exit;
    }

    if ($action === 'finalize_results') {
        $sid = intval($_POST['student_id'] ?? 0);
        $gstatus = $staff_conn->real_escape_string($_POST['grade_status'] ?? 'Approved');
        if ($sid > 0) {
            $staff_conn->query("UPDATE examination_records SET grade_status='$gstatus' WHERE student_id=$sid");
            $_SESSION['success'] = 'Results finalized.';
        }
        header("Location: academic-registrar.php#results"); exit;
    }

    if ($action === 'save_setting') {
        $skey = $students_conn->real_escape_string($_POST['setting_key'] ?? '');
        $sval = $students_conn->real_escape_string($_POST['setting_value'] ?? '');
        if ($skey) {
            $students_conn->query("INSERT INTO system_settings (setting_key,setting_value,updated_by,updated_at) VALUES ('$skey','$sval',{$_SESSION['user_id']},NOW()) ON DUPLICATE KEY UPDATE setting_value='$sval',updated_by={$_SESSION['user_id']},updated_at=NOW()");
            $_SESSION['success'] = 'Setting saved.';
        }
        header("Location: academic-registrar.php#settings"); exit;
    }

    if ($action === 'generate_transcript') {
        $sid = intval($_POST['student_id'] ?? 0);
        $purpose = $staff_conn->real_escape_string($_POST['purpose'] ?? 'Academic');
        $copies = intval($_POST['copies'] ?? 1);
        if ($sid > 0) {
            $tnum = 'T-'.date('Ymd').'-'.str_pad(mt_rand(1000,9999),4,'0',STR_PAD_LEFT);
            // Fetch student
            $sr = $students_conn->query("SELECT * FROM students WHERE id=$sid");
            $student = $sr ? $sr->fetch_assoc() : null;
            if ($student) {
                // Fetch courses/exam records
                $courses = [];
                $er = $staff_conn->query("SELECT er.*, cc.course_name, cc.credit_hours FROM examination_records er LEFT JOIN academic_course_catalog cc ON er.course_code = cc.course_code WHERE er.student_id=$sid AND er.marks_obtained IS NOT NULL ORDER BY er.created_at ASC");
                if ($er) while ($row = $er->fetch_assoc()) $courses[] = $row;
                if (empty($courses)) {
                    $cr = $staff_conn->query("SELECT cr.*, cc.course_name, cc.course_code, cc.credit_hours FROM course_registrations cr LEFT JOIN academic_course_catalog cc ON cr.course_id = cc.id WHERE cr.student_id=$sid AND cr.status='Approved'");
                    if ($cr) while ($row = $cr->fetch_assoc()) $courses[] = $row;
                }
                $settings = ['institution_name' => 'ISNM', 'current_academic_year' => date('Y'), 'transcript_fee' => '50000'];
                $html = generateProfessionalTranscript($student, $courses, $settings, $tnum);
                $html_escaped = $staff_conn->real_escape_string($html);
                $title = $staff_conn->real_escape_string("Academic Transcript - ".($student['full_name']??''));
                $staff_conn->query("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Transcript', $sid, {$_SESSION['user_id']}, '$title', '$html_escaped', NOW())");
            }
            $stmt = $staff_conn->prepare("INSERT INTO registrar_transcripts (transcript_number,student_id,purpose,copies_requested,transcript_status,request_date) VALUES (?,?,?,?,'Ready',NOW())");
            $stmt->bind_param("sisi", $tnum, $sid, $purpose, $copies);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = "Transcript $tnum generated professionally.";
        } else { $_SESSION['error'] = 'Student required.'; }
        header("Location: academic-registrar.php#transcripts"); exit;
    }

    if ($action === 'approve_transcript') {
        $tid = intval($_POST['transcript_id'] ?? 0);
        if ($tid > 0) {
            $staff_conn->query("UPDATE registrar_transcripts SET transcript_status='Processing',processed_by={$_SESSION['user_id']},processed_date=NOW() WHERE id=$tid");
            $_SESSION['success'] = 'Transcript approved for processing.';
        }
        header("Location: academic-registrar.php#transcripts"); exit;
    }

    if ($action === 'issue_transcript') {
        $tid = intval($_POST['transcript_id'] ?? 0);
        if ($tid > 0) {
            $staff_conn->query("UPDATE registrar_transcripts SET transcript_status='Issued',issued_by={$_SESSION['user_id']},issued_date=NOW() WHERE id=$tid");
            $_SESSION['success'] = 'Transcript marked as issued.';
        }
        header("Location: academic-registrar.php#transcripts"); exit;
    }

    if ($action === 'generate_certificate') {
        $sid = intval($_POST['student_id'] ?? 0);
        $ctype = $staff_conn->real_escape_string($_POST['certificate_type'] ?? 'Certificate');
        $grad_date = $staff_conn->real_escape_string($_POST['graduation_date'] ?? date('Y-m-d'));
        $cnum = 'C-'.date('Ymd').'-'.str_pad(mt_rand(1000,9999),4,'0',STR_PAD_LEFT);
        if ($sid > 0) {
            $sr = $students_conn->query("SELECT * FROM students WHERE id=$sid");
            $srow = $sr ? $sr->fetch_assoc() : null;
            if ($srow) {
                $settings = ['institution_name' => 'ISNM', 'current_academic_year' => date('Y')];
                // Determine class of award
                $gpa_r = $staff_conn->query("SELECT AVG(marks_obtained) avg_m FROM examination_records WHERE student_id=$sid AND marks_obtained IS NOT NULL");
                $class_of_award = '';
                if ($gpa_r && $gpa_row = $gpa_r->fetch_assoc()) {
                    $avg = floatval($gpa_row['avg_m'] ?? 0);
                    if ($avg >= 80) $class_of_award = 'First Class Honours';
                    elseif ($avg >= 70) $class_of_award = 'Second Class Upper Division';
                    elseif ($avg >= 60) $class_of_award = 'Second Class Lower Division';
                    elseif ($avg >= 50) $class_of_award = 'Pass';
                    else $class_of_award = 'Fail';
                }
                $html = generateProfessionalCertificate($srow, $settings, $ctype, $cnum, $class_of_award);
                $html_escaped = $staff_conn->real_escape_string($html);
                $title = $staff_conn->real_escape_string("Certificate of $ctype - ".($srow['full_name']??''));
                $staff_conn->query("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Certificate', $sid, {$_SESSION['user_id']}, '$title', '$html_escaped', NOW())");
            }
            $fname = $srow ? $srow['full_name'] : '';
            $prog = $srow ? $srow['course'] : '';
            $stmt = $staff_conn->prepare("INSERT INTO registrar_certificates (certificate_number,student_id,full_name,program,certificate_type,graduation_date,status,generated_by,generated_date) VALUES (?,?,?,?,?,?,'Generated',{$_SESSION['user_id']},NOW())");
            $stmt->bind_param("sissss", $cnum, $sid, $fname, $prog, $ctype, $grad_date);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = "Certificate $cnum generated professionally.";
        } else { $_SESSION['error'] = 'Student required.'; }
        header("Location: academic-registrar.php#certificates"); exit;
    }

    if ($action === 'issue_certificate') {
        $cid = intval($_POST['certificate_id'] ?? 0);
        if ($cid > 0) {
            $staff_conn->query("UPDATE registrar_certificates SET status='Issued',issued_by={$_SESSION['user_id']},issued_date=NOW() WHERE id=$cid");
            $_SESSION['success'] = 'Certificate marked as issued.';
        }
        header("Location: academic-registrar.php#certificates"); exit;
    }

    if ($action === 'mark_collected') {
        $type = $staff_conn->real_escape_string($_POST['doc_type'] ?? 'transcript');
        $did = intval($_POST['doc_id'] ?? 0);
        $collected_by = $staff_conn->real_escape_string($_POST['collected_by'] ?? '');
        if ($did > 0 && $type === 'transcript') {
            $staff_conn->query("UPDATE registrar_transcripts SET transcript_status='Collected',collected_by='$collected_by',collected_date=NOW() WHERE id=$did");
            $_SESSION['success'] = 'Transcript marked as collected.';
        } elseif ($did > 0 && $type === 'certificate') {
            $staff_conn->query("UPDATE registrar_certificates SET status='Collected',collected_by='$collected_by',collected_date=NOW() WHERE id=$did");
            $_SESSION['success'] = 'Certificate marked as collected.';
        }
        redirectBack(); exit;
    }

    if ($action === 'save_registrar_setting') {
        $skey = $staff_conn->real_escape_string($_POST['setting_key'] ?? '');
        $sval = $staff_conn->real_escape_string($_POST['setting_value'] ?? '');
        if ($skey) {
            $staff_conn->query("INSERT INTO registrar_settings (setting_key,setting_value,updated_by,updated_at) VALUES ('$skey','$sval',{$_SESSION['user_id']},NOW()) ON DUPLICATE KEY UPDATE setting_value='$sval',updated_by={$_SESSION['user_id']},updated_at=NOW()");
            $_SESSION['success'] = 'Setting saved.';
        }
        header("Location: academic-registrar.php#settings"); exit;
    }

    if ($action === 'bulk_approve_admissions') {
        $ids = $_POST['student_ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $count = 0;
            foreach ($ids as $sid) {
                $sid = intval($sid);
                if ($sid > 0) {
                    $students_conn->query("UPDATE students SET status='Active' WHERE id=$sid");
                    $count++;
                }
            }
            $_SESSION['success'] = "$count admissions approved.";
        }
        header("Location: academic-registrar.php#admissions"); exit;
    }
}

// ── New data for directory section ──
$dir_sets = [];
$ds_r = $students_conn->query("SELECT DISTINCT set_name FROM students WHERE set_name IS NOT NULL AND set_name != '' ORDER BY set_name DESC");
if ($ds_r) while ($row = $ds_r->fetch_assoc()) $dir_sets[] = $row['set_name'];

// All registrations for course-registration section
$all_regs = [];
$ar_r = $staff_conn->query("SELECT cr.*,s.full_name,s.registration_number,cc.course_code,cc.course_name FROM course_registrations cr LEFT JOIN igangaschoolofl_students_db.students s ON cr.student_id=s.id LEFT JOIN academic_course_catalog cc ON cr.course_id=cc.id ORDER BY cr.registration_date DESC LIMIT 50");
if ($ar_r) while ($row = $ar_r->fetch_assoc()) $all_regs[] = $row;

// All calendars
$all_calendars = [];
$ac_r = $staff_conn->query("SELECT * FROM academic_calendar ORDER BY created_at DESC");
if ($ac_r) while ($row = $ac_r->fetch_assoc()) $all_calendars[] = $row;

// Pending grade approvals for results section
$pending_grade_approvals = [];
$pga_r = $staff_conn->query("SELECT er.*,s.full_name,s.registration_number FROM examination_records er LEFT JOIN igangaschoolofl_students_db.students s ON er.student_id=s.id WHERE er.grade_status NOT IN('Approved','Finalized') ORDER BY er.created_at DESC LIMIT 20");
if ($pga_r) while ($row = $pga_r->fetch_assoc()) $pending_grade_approvals[] = $row;

// GPA summary
$gpa_summary = [];
$gp_r = $students_conn->query("SELECT s.id,s.full_name,s.registration_number,s.course,AVG(er.marks_obtained) avg_marks,COUNT(er.id) exam_count FROM students s LEFT JOIN igangaschoolofl_staffs_db.examination_records er ON s.id=er.student_id WHERE s.status='Active' GROUP BY s.id HAVING exam_count>0 ORDER BY avg_marks DESC LIMIT 20");
if ($gp_r) while ($row = $gp_r->fetch_assoc()) $gpa_summary[] = $row;

// Staff list for audit filter
$staff_list = [];
$sl_r = $staff_conn->query("SELECT id,full_name,position FROM staff ORDER BY full_name");
if ($sl_r) while ($row = $sl_r->fetch_assoc()) $staff_list[] = $row;
$staff_mgmt = [];
$sm_r = $staff_conn->query("SELECT id,full_name,position,department,email,phone FROM staff WHERE status='Active' ORDER BY full_name");
if ($sm_r) while ($row = $sm_r->fetch_assoc()) $staff_mgmt[] = $row;

$next_calendar_events = $all_calendars;

// Overview section variables
$studentCount      = safeCount($students_conn, "SELECT COUNT(*) c FROM students");
$activeStudents    = $total_students;
$courseCount       = safeCount($students_conn, "SELECT COUNT(DISTINCT course) c FROM students");
$pendingPayments   = safeCount($students_conn, "SELECT COUNT(*) c FROM student_invoices WHERE status IN('Pending','Overdue')");
$totalRevenue      = 0;
$revR = $students_conn->query("SELECT COALESCE(SUM(amount_received),0) t FROM payments");
if ($revR) { $revRow = $revR->fetch_assoc(); $totalRevenue = number_format($revRow['t']); }
$announcementCount = safeCount($students_conn, "SELECT COUNT(*) c FROM announcements");
$recentStudents = [];
$rs_r = $students_conn->query("SELECT id,full_name,student_number,course,status,created_at FROM students WHERE full_name IS NOT NULL AND full_name != '' AND LENGTH(full_name) > 3 AND full_name NOT LIKE '%MINISTRY%' AND full_name NOT LIKE '%ACCOUNTABILITY%' AND full_name NOT LIKE '%VERIFICATION%' AND full_name NOT LIKE '%HEALTH EDUCATION%' AND full_name NOT LIKE '%……………………………………………………%' ORDER BY created_at DESC LIMIT 5");
if ($rs_r) while ($row = $rs_r->fetch_assoc()) $recentStudents[] = $row;
$courseNames = [];
$cn_r = $students_conn->query("SELECT DISTINCT course FROM students WHERE course IS NOT NULL AND course != '' ORDER BY course");
if ($cn_r) while ($row = $cn_r->fetch_assoc()) $courseNames[] = $row['course'];

// All students for directory display — search only
$allStudents = [];
$studentDirSearch = trim($_GET['dir_search'] ?? '');
$studentDirStatus = trim($_GET['dir_status'] ?? '');
$studentDirCourse = trim($_GET['dir_course'] ?? '');
$hasDirSearch = $studentDirSearch !== '' || $studentDirStatus !== '' || $studentDirCourse !== '';
if ($hasDirSearch) {
    $asWhere = "WHERE full_name IS NOT NULL AND full_name != '' AND LENGTH(full_name) > 1";
    if ($studentDirSearch !== '') { $sds = $students_conn->real_escape_string($studentDirSearch); $asWhere .= " AND (full_name LIKE '%$sds%' OR first_name LIKE '%$sds%' OR surname LIKE '%$sds%' OR student_number LIKE '%$sds%' OR registration_number LIKE '%$sds%' OR index_number LIKE '%$sds%' OR phone LIKE '%$sds%')"; }
    if ($studentDirStatus !== '') { $sst = $students_conn->real_escape_string($studentDirStatus); $asWhere .= " AND status='$sst'"; }
    if ($studentDirCourse !== '') { $sc = $students_conn->real_escape_string($studentDirCourse); $asWhere .= " AND course='$sc'"; }
    $as_r = $students_conn->query("SELECT id,student_number,registration_number,national_student_id_number,first_name,surname,other_name,full_name,course,current_year,current_semester,set_name,gender,status,phone,email,intake_date,created_at FROM students $asWhere ORDER BY surname,first_name LIMIT 200");
    if ($as_r) while ($row = $as_r->fetch_assoc()) $allStudents[] = $row;
}
$allStudents = $allStudents ?: $students;

// Documents list
$documents = [];
$doc_r = $staff_conn->query("SELECT gd.*,s.full_name AS student_name FROM generated_documents gd LEFT JOIN igangaschoolofl_students_db.students s ON gd.student_id=s.id ORDER BY gd.created_at DESC LIMIT 100");
if ($doc_r) while ($row = $doc_r->fetch_assoc()) $documents[] = $row;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../includes/dashboard_head.php'; ?>
    <title>Academic Registrar Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root { --primary: #1a237e; --primary-light: #283593; --primary-dark: #0d1452; --accent: #ff6f00; --bg: #f4f6f9; --card-shadow: 0 2px 12px rgba(0,0,0,0.08); --radius: 10px; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 0; color: #333; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .page-header h1 { margin: 0; font-size: 1.75rem; font-weight: 700; color: var(--primary); }
        .page-header h1 i { margin-right: 10px; color: var(--accent); }
        .header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .header-actions .btn { padding: 8px 20px; border-radius: 6px; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: all 0.2s; }
        .header-actions .btn-primary { background: var(--primary); color: #fff; }
        .header-actions .btn-primary:hover { background: var(--primary-light); }
        .header-actions .btn-accent { background: var(--accent); color: #fff; }
        .header-actions .btn-accent:hover { background: #e65100; }
        .header-actions .btn-success { background: #2e7d32; color: #fff; }
        .header-actions .btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(200px,1fr)); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: #fff; border-radius: var(--radius); padding: 20px; box-shadow: var(--card-shadow); text-align: center; transition: transform 0.2s; border-left: 4px solid var(--primary); }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card .stat-icon { font-size: 2rem; color: var(--primary); margin-bottom: 8px; }
        .stat-card .stat-number { font-size: 1.75rem; font-weight: 800; color: var(--primary-dark); margin: 4px 0; }
        .stat-card .stat-label { font-size: 0.8rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card.accent { border-left-color: var(--accent); }
        .stat-card.accent .stat-icon { color: var(--accent); }
        .stat-card.accent .stat-number { color: var(--accent); }
        .stat-card.green { border-left-color: #2e7d32; }
        .stat-card.green .stat-icon { color: #2e7d32; }
        .stat-card.green .stat-number { color: #2e7d32; }
        .stat-card.red { border-left-color: #c62828; }
        .stat-card.red .stat-icon { color: #c62828; }
        .stat-card.red .stat-number { color: #c62828; }
        .stat-card.purple { border-left-color: #6a1b9a; }
        .stat-card.purple .stat-icon { color: #6a1b9a; }
        .stat-card.purple .stat-number { color: #6a1b9a; }
        .stat-card.teal { border-left-color: #00695c; }
        .stat-card.teal .stat-icon { color: #00695c; }
        .stat-card.teal .stat-number { color: #00695c; }
        .section-card { background: #fff; border-radius: var(--radius); box-shadow: var(--card-shadow); margin-bottom: 28px; overflow: hidden; }
        .section-card .card-header { padding: 16px 24px; background: var(--primary); color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .section-card .card-header h5 { margin: 0; font-size: 1.1rem; font-weight: 600; }
        .section-card .card-header .card-actions { display: flex; gap: 8px; }
        .section-card .card-body { padding: 20px 24px; }
        .table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .table th { background: #f0f2f5; padding: 10px 12px; text-align: left; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.3px; color: #555; border-bottom: 2px solid #dee2e6; }
        .table td { padding: 10px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .table tr:hover td { background: #f8f9ff; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .badge-warning { background: #fff3e0; color: #e65100; }
        .badge-danger { background: #ffebee; color: #c62828; }
        .badge-info { background: #e3f2fd; color: #1565c0; }
        .badge-secondary { background: #f5f5f5; color: #616161; }
        .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 16px; font-size: 0.9rem; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-danger { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .alert-info { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 4px; color: #444; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem; transition: border 0.2s; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26,35,126,0.1); }
        .form-control-sm { padding: 6px 10px; font-size: 0.8rem; }
        select.form-control { appearance: auto; }
        .btn { padding: 8px 18px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: all 0.2s; }
        .btn-sm { padding: 5px 12px; font-size: 0.78rem; }
        .btn-xs { padding: 3px 8px; font-size: 0.7rem; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-light); }
        .btn-success { background: #2e7d32; color: #fff; }
        .btn-success:hover { background: #1b5e20; }
        .btn-danger { background: #c62828; color: #fff; }
        .btn-danger:hover { background: #b71c1c; }
        .btn-warning { background: #ef6c00; color: #fff; }
        .btn-warning:hover { background: #e65100; }
        .btn-info { background: #1565c0; color: #fff; }
        .btn-info:hover { background: #0d47a1; }
        .btn-secondary { background: #757575; color: #fff; }
        .btn-secondary:hover { background: #616161; }
        .btn-outline-primary { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); color: #fff; }
        .btn-outline-danger { background: transparent; border: 2px solid #c62828; color: #c62828; }
        .btn-outline-danger:hover { background: #c62828; color: #fff; }
        .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-dialog { background: #fff; border-radius: var(--radius); width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .modal-dialog.modal-lg { max-width: 800px; }
        .modal-dialog.modal-sm { max-width: 400px; }
        .modal-header { padding: 16px 24px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h5 { margin: 0; font-size: 1.1rem; font-weight: 600; }
        .modal-header .close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999; padding: 0; line-height: 1; }
        .modal-header .close:hover { color: #333; }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 12px 24px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 8px; }
        .tabs { display: flex; border-bottom: 2px solid #dee2e6; margin-bottom: 20px; gap: 0; }
        .tabs .tab { padding: 10px 20px; cursor: pointer; font-weight: 600; font-size: 0.9rem; color: #666; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .tabs .tab:hover { color: var(--primary); }
        .tabs .tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .search-box { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: center; }
        .search-box input,.search-box select { flex: 1; min-width: 150px; }
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        .empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }
        .pagination { display: flex; justify-content: center; gap: 4px; margin-top: 16px; }
        .pagination .page-item { padding: 6px 14px; border: 1px solid #dee2e6; border-radius: 4px; cursor: pointer; font-size: 0.85rem; }
        .pagination .page-item.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .pagination .page-item:hover:not(.active) { background: #f0f2f5; }
        .content-section { display: none; }
        .content-section.active { display: block; }
        html, body { overflow-x: hidden; width: 100%; margin: 0; padding: 0; }
        .dashboard-container { margin-left: 270px; width: calc(100vw - 270px); min-height: 100vh; padding: 20px 30px; box-sizing: border-box; background: #f8f9fa; }
        .dashboard-section { display: none; }
        .dashboard-section.active { display: block; }
        .main { margin-left: 0 !important; min-height: auto !important; flex: none !important; }
        @media (max-width: 768px) { .dashboard-container { margin-left: 0; width: 100vw; padding: 16px; } }
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e0e0e0; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: -20px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: var(--primary); border: 2px solid #fff; box-shadow: 0 0 0 2px var(--primary); }
        .timeline-item .timeline-time { font-size: 0.75rem; color: #999; }
        .timeline-item .timeline-text { margin: 2px 0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fill,minmax(220px,1fr)); gap: 16px; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .mt-2 { margin-top: 16px; }
        .mt-3 { margin-top: 24px; }
        .mb-2 { margin-bottom: 16px; }
        .mb-3 { margin-bottom: 24px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-success { color: #2e7d32; }
        .text-danger { color: #c62828; }
        .text-warning { color: #e65100; }
        .text-primary { color: var(--primary); }
        .w-100 { width: 100%; }
        .table-responsive { overflow-x: auto; }
        .clickable { cursor: pointer; }
        .inline-flex { display: inline-flex; align-items: center; gap: 6px; }
        @media (max-width: 768px) {
            .dashboard-section { margin-left: 0; padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2,1fr); }
            .grid-2,.grid-3 { grid-template-columns: 1fr; }
            .header-actions .btn { font-size: 0.75rem; padding: 6px 14px; }
        }
        @media print {
            .dashboard-section { margin-left: 0; }
            .sidebar,.header-actions,.btn { display: none !important; }
        }
        .trash-item { border-left: 4px solid #ff6f00; background: #fffde7; }
        .trash-item td { background: #fffde7 !important; }
        .timeline-approved::before { background: #2e7d32 !important; box-shadow: 0 0 0 2px #2e7d32 !important; }
        .timeline-rejected::before { background: #c62828 !important; box-shadow: 0 0 0 2px #c62828 !important; }
        .timeline-pending::before { background: #e65100 !important; box-shadow: 0 0 0 2px #e65100 !important; }
        .progress { height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden; }
        .progress-bar { height: 100%; border-radius: 3px; transition: width 0.3s; }
        .progress-bar-success { background: #2e7d32; }
        .progress-bar-warning { background: #ef6c00; }
        .progress-bar-primary { background: var(--primary); }
    </style>
    <script>window.addEventListener('unhandledrejection',function(e){e.preventDefault()});</script>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <div class="main content-section dashboard-section active" id="content" data-section="overview">
            <div class="page-header">
                <h1><i class="fas fa-graduation-cap"></i> Academic Registrar Dashboard</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addStudentModal')"><i class="fas fa-user-plus"></i> Add Student</button>
                    <button class="btn btn-accent" onclick="openModal('addPaymentModal')"><i class="fas fa-money-bill"></i> Record Payment</button>
                    <button class="btn btn-success" onclick="openModal('addInvoiceModal')"><i class="fas fa-file-invoice"></i> Create Invoice</button>
                    <button class="btn btn-outline" onclick="openModal('addCalendarModal')"><i class="fas fa-calendar-plus"></i> Add Calendar</button>
                    <a href="?report=students" class="btn btn-info"><i class="fas fa-download"></i> Export</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card accent">
                    <div class="stat-icon" style="background:#ff6f00"><i class="fas fa-file-alt"></i></div>
                    <div><div class="stat-number"><?= $total_pending_applications ?></div><div class="stat-label">Pending Applications</div></div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon" style="background:#2e7d32"><i class="fas fa-user-check"></i></div>
                    <div><div class="stat-number"><?= $total_registered_students ?></div><div class="stat-label">Registered Students</div></div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon" style="background:#c62828"><i class="fas fa-clock"></i></div>
                    <div><div class="stat-number"><?= $total_pending_registrations ?></div><div class="stat-label">Pending Registrations</div></div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-icon" style="background:#6a1b9a"><i class="fas fa-file-pdf"></i></div>
                    <div><div class="stat-number"><?= $total_transcripts_issued ?></div><div class="stat-label">Transcripts Issued</div></div>
                </div>
                <div class="stat-card teal">
                    <div class="stat-icon" style="background:#00695c"><i class="fas fa-award"></i></div>
                    <div><div class="stat-number"><?= $total_certificates_issued ?></div><div class="stat-label">Certificates Issued</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#1a237e"><i class="fas fa-book-open"></i></div>
                    <div><div class="stat-number"><?= $total_active_programmes ?></div><div class="stat-label">Active Programmes</div></div>
                </div>
                <div class="stat-card accent">
                    <div class="stat-icon" style="background:#e65100"><i class="fas fa-graduation-cap"></i></div>
                    <div><div class="stat-number"><?= $total_graduation_candidates ?></div><div class="stat-label">Graduation Candidates</div></div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon" style="background:#b71c1c"><i class="fas fa-check-double"></i></div>
                    <div><div class="stat-number"><?= $total_pending_approvals ?></div><div class="stat-label">Pending Approvals</div></div>
                </div>
            </div>

            <div class="grid-2 mb-3">
                <div class="section-card">
                    <div class="card-header"><h5>Recent Students</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>#</th><th>Name</th><th>Student No</th><th>Course</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php if (empty($recentStudents)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No records found</td></tr>
                                    <?php else: $i=1; ?>
                                        <?php foreach ($recentStudents as $s): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars($s['full_name']) ?></td>
                                                <td><?= htmlspecialchars($s['student_number']) ?></td>
                                                <td><?= htmlspecialchars($s['course']) ?></td>
                                                <td><span class="badge badge-<?= $s['status']==='Active'?'success':($s['status']==='Pending'?'warning':'secondary') ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header"><h5>Quick Actions</h5></div>
                    <div class="card-body">
                        <div class="grid-2">
                            <a href="#students-directory" class="btn btn-primary w-100" onclick="switchToSection('students-directory')"><i class="fas fa-user-graduate"></i> Students</a>
                            <a href="#admissions" class="btn btn-info w-100" onclick="switchToSection('admissions')"><i class="fas fa-user-plus"></i> Admissions</a>
                            <a href="#examinations" class="btn btn-warning w-100" onclick="switchToSection('examinations')"><i class="fas fa-pen"></i> Exams</a>
                            <a href="#results" class="btn btn-success w-100" onclick="switchToSection('results')"><i class="fas fa-check"></i> Results</a>
                            <a href="#reports" class="btn btn-secondary w-100" onclick="switchToSection('reports')"><i class="fas fa-file-alt"></i> Reports</a>
                            <a href="#recycle-bin" class="btn btn-danger w-100" onclick="switchToSection('recycle-bin')"><i class="fas fa-trash"></i> Trash</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($reportType): ?>
                <?php include __DIR__ . '/../includes/dashboard_footer.php'; exit; ?>
            <?php endif; ?>
            <!-- Student Search & All Students -->
            <div class="section-card mb-3" id="students-directory">
                <div class="card-header"><h5><i class="fas fa-search"></i> Student Directory</h5></div>
                <div class="card-body">
                    <form method="GET" class="search-box mb-3">
                        <input type="hidden" name="section" value="students-directory">
                        <div class="row g-2">
                            <div class="col-md-5"><input type="text" name="dir_search" class="form-control" placeholder="Search by name, number, phone..." value="<?= htmlspecialchars($studentDirSearch) ?>"></div>
                            <div class="col-md-2"><select name="dir_status" class="form-select form-select-sm"><option value="">All Status</option><option <?= $studentDirStatus==='Active'?'selected':'' ?>>Active</option><option <?= $studentDirStatus==='Pending'?'selected':'' ?>>Pending</option><option <?= $studentDirStatus==='deleted'?'selected':'' ?>>Deleted</option><option <?= $studentDirStatus==='Graduated'?'selected':'' ?>>Graduated</option></select></div>
                            <div class="col-md-3"><select name="dir_course" class="form-select form-select-sm"><option value="">All Courses</option><?php foreach ($courseNames as $cn): ?><option <?= $studentDirCourse===$cn?'selected':'' ?>><?= htmlspecialchars($cn) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search</button></div>
                        </div>
                        <?php if ($hasDirSearch): ?>
                        <div class="mt-2"><a href="academic-registrar.php?section=students-directory" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i> Clear Search</a></div>
                        <?php endif; ?>
                    </form>
                    <?php if (!$hasDirSearch): ?>
                    <div class="text-center py-5 text-muted"><i class="fas fa-search fa-3x mb-3"></i><p>Use the search fields above to find students in the directory.</p></div>
                    <?php elseif (empty($allStudents)): ?>
                    <div class="text-center py-4 text-muted"><p><i class="fas fa-exclamation-circle me-1"></i>No students match your search criteria.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="studentTable">
                            <thead>
                                <tr>
                                    <th>#</th><th>Student No</th><th>Full Name</th><th>Course</th><th>Year</th><th>Semester</th><th>Gender</th><th>Status</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $cnt=1; foreach ($allStudents as $st): ?>
                                        <tr>
                                            <td><?= $cnt++ ?></td>
                                            <td><?= htmlspecialchars($st['student_number']) ?></td>
                                            <td><?= htmlspecialchars($st['full_name']) ?></td>
                                            <td><?= htmlspecialchars($st['course']) ?></td>
                                            <td><?= $st['current_year'] ?></td>
                                            <td><?= htmlspecialchars($st['current_semester']) ?></td>
                                            <td><?= htmlspecialchars($st['gender']) ?></td>
                                            <td><span class="badge badge-<?= $st['status']==='Active'?'success':($st['status']==='Pending'?'warning':($st['status']==='deleted'?'danger':'secondary')) ?>"><?= htmlspecialchars($st['status']) ?></span></td>
                                            <td>
                                                <button class="btn btn-primary btn-xs" onclick="viewStudent(<?= $st['id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-info btn-xs" onclick="editStudent(<?= $st['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-secondary btn-xs" onclick="printStudent(<?= $st['id'] ?>)" title="Print"><i class="fas fa-print"></i></button>
                                                <button class="btn btn-success btn-xs" onclick="addPayment(<?= $st['id'] ?>)" title="Pay"><i class="fas fa-money-bill"></i></button>
                                                <button class="btn btn-warning btn-xs" onclick="courseReg(<?= $st['id'] ?>)" title="Register Course"><i class="fas fa-book"></i></button>
                                                <button class="btn btn-purple btn-xs" onclick="autoGenerateAll(<?= $st['id'] ?>)" title="Auto-Generate Transcript & Certificate"><i class="fas fa-file-alt"></i></button>
                                                <button class="btn btn-danger btn-xs" onclick="trashStudent(<?= $st['id'] ?>)" title="Trash"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small mt-2"><?= count($allStudents) ?> student(s) found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Academic Calendar -->
            <div class="section-card mb-3">
                <div class="card-header"><h5><i class="fas fa-calendar-alt"></i> Academic Calendar</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Calendar ID</th><th>Academic Year</th><th>Semester</th><th>Start</th><th>End</th><th>Exam Period</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (!empty($calendars)): ?>
                                    <?php foreach ($calendars as $cal): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cal['calendar_id']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['academic_year']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['semester']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['semester_start_date']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['semester_end_date']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['exam_start_date']??'') ?> - <?= htmlspecialchars($cal['exam_end_date']??'') ?></td>
                                            <td><span class="badge badge-<?= ($cal['status']??'')==='Active'?'success':'info' ?>"><?= htmlspecialchars($cal['status']??'') ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center">No calendar entries.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Students Directory Section -->
        <div class="main content-section dashboard-section"  data-section="students-directory">
            <div class="page-header">
                <h1><i class="fas fa-user-graduate"></i> Students Directory</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addStudentModal')"><i class="fas fa-user-plus"></i> Add Student</button>
                    <button class="btn btn-outline" onclick="exportTable('studentsDirectoryTable')"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-list"></i> All Students</h5></div>
                <div class="card-body">
                    <div class="search-box">
                        <input type="text" id="dirSearch" class="form-control" placeholder="Search..." onkeyup="filterTable('dirSearch','studentsDirectoryTable')">
                        <select id="dirSetFilter" class="form-control form-control-sm" style="max-width:180px" onchange="filterTable('dirSearch','studentsDirectoryTable')">
                            <option value="">All Sets</option>
                            <?php foreach ($dir_sets as $ds): ?>
                                <option><?= htmlspecialchars($ds) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="studentsDirectoryTable">
                            <thead>
                                <tr><th>#</th><th>Student No</th><th>Reg No</th><th>Name</th><th>Course</th><th>Year</th><th>Gender</th><th>Set</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($allStudents)): $i=1; ?>
                                    <?php foreach ($allStudents as $s): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($s['student_number']??'') ?></td>
                                            <td><?= htmlspecialchars($s['registration_number']??'') ?></td>
                                            <td><?= htmlspecialchars($s['full_name']??'') ?></td>
                                            <td><?= htmlspecialchars($s['course']??'') ?></td>
                                            <td><?= $s['current_year']??'' ?></td>
                                            <td><?= htmlspecialchars($s['gender']??'') ?></td>
                                            <td><?= htmlspecialchars($s['set_name']??'') ?></td>
                                            <td><span class="badge badge-<?= $s['status']==='Active'?'success':($s['status']==='Pending'?'warning':'danger') ?>"><?= htmlspecialchars($s['status']??'') ?></span></td>
                                            <td>
                                                <button class="btn btn-primary btn-xs" onclick="viewStudent(<?= $s['id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-info btn-xs" onclick="editStudent(<?= $s['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-secondary btn-xs" onclick="printStudent(<?= $s['id'] ?>)" title="Print"><i class="fas fa-print"></i></button>
                                                <button class="btn btn-success btn-xs" onclick="addPayment(<?= $s['id'] ?>)" title="Pay"><i class="fas fa-money-bill"></i></button>
                                                <button class="btn btn-danger btn-xs" onclick="trashStudent(<?= $s['id'] ?>)" title="Trash"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="10" class="text-center">No students found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admissions Section -->
        <div class="main content-section dashboard-section"  data-section="admissions">
            <div class="page-header">
                <h1><i class="fas fa-user-plus"></i> Admissions</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addStudentModal')"><i class="fas fa-user-plus"></i> New Application</button>
                    <a href="?report=students" class="btn btn-outline"><i class="fas fa-download"></i> Export</a>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-file"></i></div><div class="stat-number"><?= $total_applications ?></div><div class="stat-label">Total Applications</div></div>
                <div class="stat-card green"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-number"><?= $approved_applications ?></div><div class="stat-label">Approved</div></div>
                <div class="stat-card red"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-number"><?= $rejected_applications ?></div><div class="stat-label">Rejected</div></div>
                <div class="stat-card accent"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-number"><?= $pending_applications_count ?></div><div class="stat-label">Pending</div></div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-list"></i> Recent Applications</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>#</th><th>Name</th><th>Student No</th><th>Course</th><th>Year</th><th>Gender</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (!empty($recent_applications)): $i=1; ?>
                                    <?php foreach ($recent_applications as $ap): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($ap['full_name']??'') ?></td>
                                            <td><?= htmlspecialchars($ap['student_number']??'') ?></td>
                                            <td><?= htmlspecialchars($ap['course']??'') ?></td>
                                            <td><?= $ap['current_year']??'' ?></td>
                                            <td><?= htmlspecialchars($ap['gender']??'') ?></td>
                                            <td><span class="badge badge-<?= $ap['status']==='Active'?'success':($ap['status']==='Pending'?'warning':'danger') ?>"><?= htmlspecialchars($ap['status']??'') ?></span></td>
                                            <td><?= htmlspecialchars($ap['created_at']??'') ?></td>
                                            <td>
                                                <?php if (($ap['status']??'') === 'Pending'): ?>
                                                    <form method="post" style="display:inline">
                                                        <input type="hidden" name="action" value="approve_admission">
                                                        <input type="hidden" name="student_id" value="<?= $ap['id'] ?>">
                                                        <button class="btn btn-success btn-xs" type="submit"><i class="fas fa-check"></i> Approve</button>
                                                    </form>
                                                    <form method="post" style="display:inline" onsubmit="return confirm('Reject this application?')">
                                                        <input type="hidden" name="action" value="reject_admission">
                                                        <input type="hidden" name="student_id" value="<?= $ap['id'] ?>">
                                                        <button class="btn btn-danger btn-xs" type="submit"><i class="fas fa-times"></i> Reject</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge badge-info"><?= htmlspecialchars($ap['status']??'') ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" class="text-center">No applications found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Programs Section -->
        <div class="main content-section dashboard-section"  data-section="programs">
            <div class="page-header">
                <h1><i class="fas fa-book-open"></i> Programmes</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addCourseModal')"><i class="fas fa-plus"></i> Add Programme</button>
                    <a href="?report=by_program" class="btn btn-outline"><i class="fas fa-download"></i> Export</a>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon" style="background:#1a237e"><i class="fas fa-book-open"></i></div><div><div class="stat-number"><?= count($programs_list) ?></div><div class="stat-label">Programmes</div></div></div>
                <div class="stat-card green"><div class="stat-icon" style="background:#2e7d32"><i class="fas fa-users"></i></div><div><div class="stat-number"><?= array_sum(array_column($programs_list,'student_count')) ?></div><div class="stat-label">Enrolled Students</div></div></div>
                <div class="stat-card purple"><div class="stat-icon" style="background:#6a1b9a"><i class="fas fa-layer-group"></i></div><div><div class="stat-number"><?= count($program_group_by_department) ?></div><div class="stat-label">Departments</div></div></div>
                <div class="stat-card accent"><div class="stat-icon" style="background:#e65100"><i class="fas fa-chart-bar"></i></div><div><div class="stat-number"><?= count($course_catalog) ?></div><div class="stat-label">Total Courses</div></div></div>
            </div>
            <div class="grid-2 mb-3">
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-layer-group"></i> Programmes by Department</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Department</th><th>Courses</th><th>Course Codes</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($program_group_by_department)): ?>
                                        <?php foreach ($program_group_by_department as $pg): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($pg['department']) ?></strong></td>
                                                <td><?= intval($pg['course_count']) ?></td>
                                                <td><small><?= htmlspecialchars(substr($pg['course_codes']??'',0,80)) ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center">No departments found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-list"></i> Programmes Overview</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>#</th><th>Programme Name</th><th>Students</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($programs_list)): $i=1; ?>
                                        <?php foreach ($programs_list as $pr): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><strong><?= htmlspecialchars($pr['program_name']) ?></strong></td>
                                                <td><span class="badge badge-info"><?= intval($pr['student_count']) ?></span></td>
                                                <td>
                                                    <a href="#students-directory" class="btn btn-info btn-xs" onclick="switchToSection('students-directory')"><i class="fas fa-eye"></i> View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center">No programmes found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Registration Section -->
        <div class="main content-section dashboard-section"  data-section="course-registration">
            <div class="page-header">
                <h1><i class="fas fa-pen-square"></i> Course Registration</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('courseRegModal')"><i class="fas fa-plus"></i> Register Course</button>
                </div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-list"></i> Registered Courses</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>#</th><th>Student</th><th>Course Code</th><th>Course Name</th><th>Academic Year</th><th>Semester</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php if (!empty($all_regs)): $i=1; ?>
                                    <?php foreach ($all_regs as $reg): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($reg['full_name']??'') ?> (<?= htmlspecialchars($reg['registration_number']??'') ?>)</td>
                                            <td><?= htmlspecialchars($reg['course_code']??'') ?></td>
                                            <td><?= htmlspecialchars($reg['course_name']??'') ?></td>
                                            <td><?= htmlspecialchars($reg['academic_year']??'') ?></td>
                                            <td><?= htmlspecialchars($reg['semester']??'') ?></td>
                                            <td><span class="badge badge-<?= ($reg['status']??'')==='Approved'?'success':'warning' ?>"><?= htmlspecialchars($reg['status']??'') ?></span></td>
                                            <td><?= htmlspecialchars($reg['registration_date']??'') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center">No registrations found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Examinations Section -->
        <div class="main content-section dashboard-section"  data-section="examinations">
            <div class="page-header">
                <h1><i class="fas fa-pen"></i> Examinations</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addExamModal')"><i class="fas fa-plus"></i> Add Exam Record</button>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card purple"><div class="stat-icon"><i class="fas fa-file-alt"></i></div><div class="stat-number"><?= count($exam_records_list) ?></div><div class="stat-label">Exam Records</div></div>
                <div class="stat-card red"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-number"><?= count($missing_results) ?></div><div class="stat-label">Missing Results</div></div>
                <div class="stat-card green"><div class="stat-icon"><i class="fas fa-check-double"></i></div><div class="stat-number"><?= count($exam_records_list) - count($missing_results) ?></div><div class="stat-label">Completed</div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-check"></i></div><div class="stat-number"><?= count($exam_timetable) ?></div><div class="stat-label">Exam Periods</div></div>
            </div>
            <div class="grid-2 mb-3">
                <div class="section-card">
                    <div class="card-header"><h5><i class="fas fa-list"></i> Exam Records</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>#</th><th>Student</th><th>Course</th><th>Type</th><th>Marks</th><th>Grade</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($exam_records_list)): $i=1; ?>
                                        <?php foreach (array_slice($exam_records_list,0,20) as $ex): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars($ex['full_name']??'') ?></td>
                                                <td><?= htmlspecialchars($ex['course_code']??'') ?></td>
                                                <td><?= htmlspecialchars($ex['exam_type']??'') ?></td>
                                                <td><?= floatval($ex['marks_obtained']??0) ?></td>
                                                <td><?= htmlspecialchars($ex['grade']??'-') ?></td>
                                                <td><span class="badge badge-<?= ($ex['grade_status']??'')==='Approved'?'success':'warning' ?>"><?= htmlspecialchars($ex['grade_status']??'Pending') ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center">No exam records.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header"><h5><i class="fas fa-exclamation-triangle"></i> Missing Results</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>#</th><th>Student</th><th>Course Code</th><th>Type</th><th>Exam No</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($missing_results)): $i=1; ?>
                                        <?php foreach ($missing_results as $mr): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars($mr['full_name']??'') ?></td>
                                                <td><?= htmlspecialchars($mr['course_code']??'') ?></td>
                                                <td><?= htmlspecialchars($mr['exam_type']??'') ?></td>
                                                <td><?= htmlspecialchars($mr['exam_number']??'') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center">All results recorded.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <a href="#results" class="btn btn-warning btn-sm" onclick="switchToSection('results')"><i class="fas fa-arrow-right"></i> Manage Results</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="main content-section dashboard-section"  data-section="results">
            <div class="page-header">
                <h1><i class="fas fa-check-circle"></i> Results Management</h1>
                <div class="header-actions">
                    <a href="?report=grades" class="btn btn-info"><i class="fas fa-download"></i> Export Grades</a>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card green"><div class="stat-icon"><i class="fas fa-check"></i></div><div class="stat-number"><?= count($exam_records_list) - count($pending_grade_approvals) ?></div><div class="stat-label">Finalized</div></div>
                <div class="stat-card accent"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-number"><?= count($pending_grade_approvals) ?></div><div class="stat-label">Pending Approval</div></div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-hourglass-half"></i> Pending Grade Approvals</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>#</th><th>Student</th><th>Reg No</th><th>Course Code</th><th>Exam Type</th><th>Marks</th><th>Grade</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (!empty($pending_grade_approvals)): $i=1; ?>
                                    <?php foreach ($pending_grade_approvals as $ga): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($ga['full_name']??'') ?></td>
                                            <td><?= htmlspecialchars($ga['registration_number']??'') ?></td>
                                            <td><?= htmlspecialchars($ga['course_code']??'') ?></td>
                                            <td><?= htmlspecialchars($ga['exam_type']??'') ?></td>
                                            <td><?= floatval($ga['marks_obtained']??0) ?></td>
                                            <td><?= htmlspecialchars($ga['grade']??'-') ?></td>
                                            <td><span class="badge badge-warning"><?= htmlspecialchars($ga['grade_status']??'Pending') ?></span></td>
                                            <td>
                                                <button class="btn btn-success btn-xs" onclick="openModal('gradeApprovalModal')" data-exam-id="<?= $ga['id'] ?>" data-student="<?= htmlspecialchars($ga['full_name']??'') ?>"><i class="fas fa-check"></i> Approve</button>
                                                <button class="btn btn-info btn-xs" onclick="openModal('editMarksModal')" data-exam-id="<?= $ga['id'] ?>" data-marks="<?= floatval($ga['marks_obtained']??0) ?>" data-grade="<?= htmlspecialchars($ga['grade']??'') ?>"><i class="fas fa-edit"></i> Edit</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" class="text-center">All grades approved.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-trophy"></i> GPA Summary (Top 20)</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>#</th><th>Student</th><th>Reg No</th><th>Course</th><th>Avg Marks</th><th>Exams Taken</th></tr></thead>
                            <tbody>
                                <?php if (!empty($gpa_summary)): $i=1; ?>
                                    <?php foreach ($gpa_summary as $gs): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($gs['full_name']??'') ?></td>
                                            <td><?= htmlspecialchars($gs['registration_number']??'') ?></td>
                                            <td><?= htmlspecialchars($gs['course']??'') ?></td>
                                            <td><?= number_format(floatval($gs['avg_marks']??0),1) ?>%</td>
                                            <td><?= intval($gs['exam_count']??0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">No GPA data available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graduation Section -->
        <div class="main content-section dashboard-section"  data-section="graduation">
            <div class="page-header">
                <h1><i class="fas fa-graduation-cap"></i> Graduation</h1>
                <div class="header-actions">
                    <a href="?report=grades" class="btn btn-info"><i class="fas fa-download"></i> Export</a>
                </div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-user-graduate"></i> Graduation Candidates</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>#</th><th>Name</th><th>Student No</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (!empty($grad_candidates_list)): $i=1; ?>
                                    <?php foreach ($grad_candidates_list as $gc): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($gc['full_name']??'') ?></td>
                                            <td><?= htmlspecialchars($gc['student_number']??'') ?></td>
                                            <td><?= htmlspecialchars($gc['course']??'') ?></td>
                                            <td><?= htmlspecialchars($gc['current_year']??'') ?></td>
                                            <td><span class="badge badge-<?= $gc['status']==='Graduated'?'success':(($gc['status']??'')==='graduation_candidate'?'warning':'info') ?>"><?= htmlspecialchars($gc['status']??'') ?></span></td>
                                            <td>
                                                <?php if (($gc['status']??'') !== 'Graduated'): ?>
                                                    <form method="post" style="display:inline" onsubmit="return confirm('Approve graduation for <?= htmlspecialchars(addslashes($gc['full_name']??'')) ?>?')">
                                                        <input type="hidden" name="action" value="approve_graduation">
                                                        <input type="hidden" name="student_id" value="<?= $gc['id'] ?>">
                                                        <button class="btn btn-success btn-xs"><i class="fas fa-check"></i> Approve</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Graduated</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center">No graduation candidates.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Academic Calendar Section -->
        <div class="main content-section dashboard-section"  data-section="academic-calendar">
            <div class="page-header">
                <h1><i class="fas fa-calendar-alt"></i> Academic Calendar</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addCalendarModal')"><i class="fas fa-plus"></i> Add Entry</button>
                </div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-list"></i> All Calendar Entries</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Calendar ID</th><th>Academic Year</th><th>Semester</th><th>Semester Start</th><th>Semester End</th><th>Exam Start</th><th>Exam End</th><th>Result Date</th><th>Reg Deadline</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (!empty($all_calendars)): ?>
                                    <?php foreach ($all_calendars as $cal): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cal['calendar_id']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['academic_year']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['semester']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['semester_start_date']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['semester_end_date']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['exam_start_date']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['exam_end_date']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['result_publication_date']??'') ?></td>
                                            <td><?= htmlspecialchars($cal['registration_deadline']??'') ?></td>
                                            <td><span class="badge badge-<?= ($cal['status']??'')==='Active'?'success':'info' ?>"><?= htmlspecialchars($cal['status']??'') ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="10" class="text-center">No calendar entries.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timetable Section -->
        <div class="main content-section dashboard-section"  data-section="timetable">
            <div class="page-header">
                <h1><i class="fas fa-clock"></i> Timetable</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addTimetableModal')"><i class="fas fa-plus"></i> Add Event</button>
                </div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-calendar-day"></i> Upcoming Events</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Event</th><th>Date</th><th>Time</th><th>Location</th><th>Type</th></tr></thead>
                            <tbody>
                                <?php if (!empty($next_calendar_events)): ?>
                                    <?php foreach ($next_calendar_events as $ev): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ev['semester']??'Academic Period') ?></td>
                                            <td><?= htmlspecialchars($ev['semester_start_date']??'') ?></td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td><span class="badge badge-info">Calendar</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center">No upcoming events.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="main content-section dashboard-section"  data-section="documents">
            <div class="page-header">
                <h1><i class="fas fa-file-alt"></i> Documents</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('uploadDocModal')"><i class="fas fa-upload"></i> Upload Document</button>
                    <button class="btn btn-success" onclick="openModal('generateDocModal')"><i class="fas fa-file"></i> Generate Document</button>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon" style="background:#1a237e"><i class="fas fa-file-alt"></i></div><div><div class="stat-number"><?= count($documents) ?></div><div class="stat-label">Total Documents</div></div></div>
                <div class="stat-card green"><div class="stat-icon" style="background:#2e7d32"><i class="fas fa-file-pdf"></i></div><div><div class="stat-number"><?= intval(($dgt=array_filter($document_group_by_type,fn($d)=>$d['document_type']==='Transcript')) ? reset($dgt)['doc_count'] : 0) ?></div><div class="stat-label">Transcripts</div></div></div>
                <div class="stat-card purple"><div class="stat-icon" style="background:#6a1b9a"><i class="fas fa-award"></i></div><div><div class="stat-number"><?= intval(($dgc=array_filter($document_group_by_type,fn($d)=>$d['document_type']==='Certificate')) ? reset($dgc)['doc_count'] : 0) ?></div><div class="stat-label">Certificates</div></div></div>
                <div class="stat-card accent"><div class="stat-icon" style="background:#e65100"><i class="fas fa-id-card"></i></div><div><div class="stat-number"><?= intval(($dgi=array_filter($document_group_by_type,fn($d)=>$d['document_type']==='ID Card'||$d['document_type']==='ID')) ? reset($dgi)['doc_count'] : 0) ?></div><div class="stat-label">ID Cards</div></div></div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-list"></i> Generated Documents</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>#</th><th>Title</th><th>Type</th><th>Student</th><th>Generated By</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (!empty($documents)): $i=1; ?>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($doc['document_title']??'') ?></td>
                                            <td><?= htmlspecialchars($doc['document_type']??'') ?></td>
                                            <td><?= htmlspecialchars($doc['student_name']??'Student #'.$doc['student_id']) ?></td>
                                            <td><?= htmlspecialchars($doc['generated_by']??'') ?></td>
                                            <td><?= htmlspecialchars($doc['created_at']??$doc['generated_at']??$doc['generation_date']??'') ?></td>
                                            <td>
                                                <?php if (!empty($doc['file_path'])): ?>
                                                    <a href="<?= htmlspecialchars($doc['file_path']) ?>" class="btn btn-info btn-xs" target="_blank"><i class="fas fa-eye"></i> View</a>
                                                    <a href="<?= htmlspecialchars($doc['file_path']) ?>" class="btn btn-secondary btn-xs" download><i class="fas fa-download"></i> Download</a>
                                                <?php endif; ?>
                                                <button class="btn btn-success btn-xs" onclick="printStudent(<?= intval($doc['student_id']??0) ?>)"><i class="fas fa-print"></i> Print</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center">No documents found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div class="main content-section dashboard-section"  data-section="reports">
            <div class="page-header">
                <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                <div class="header-actions">
                    <select class="form-control form-control-sm" style="max-width:220px" id="reportSelector" onchange="generateReport()">
                        <option value="">Select Report Type</option>
                        <optgroup label="Standard Reports">
                            <option value="students">Full Student List</option>
                            <option value="payments">Payments Report</option>
                            <option value="invoices">Invoices Report</option>
                            <option value="grades">Grades Report</option>
                            <option value="attendance">Attendance Report</option>
                            <option value="courses">Course Enrollments</option>
                            <option value="calendar">Academic Calendar</option>
                            <option value="trash">Deleted Records</option>
                        </optgroup>
                        <optgroup label="Grouping Reports">
                            <option value="by_program">Students by Program</option>
                            <option value="by_year">Students by Year</option>
                            <option value="by_set">Students by Set</option>
                            <option value="by_status">Students by Status</option>
                            <option value="enrollment">Enrollment by Program & Year</option>
                            <option value="by_department">Courses by Department</option>
                            <option value="by_doc_type">Documents by Type</option>
                            <option value="graduation">Graduation Report</option>
                            <option value="registration">Course Registration Report</option>
                        </optgroup>
                    </select>
                    <button class="btn btn-success btn-sm" onclick="exportAsCSV()"><i class="fas fa-file-csv"></i> CSV</button>
                    <button class="btn btn-danger btn-sm" onclick="window.print()"><i class="fas fa-file-pdf"></i> PDF</button>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon" style="background:#1a237e"><i class="fas fa-user-graduate"></i></div><div><div class="stat-number"><?= $studentCount ?></div><div class="stat-label">Total Students</div></div></div>
                <div class="stat-card green"><div class="stat-icon" style="background:#2e7d32"><i class="fas fa-check-circle"></i></div><div><div class="stat-number"><?= $activeStudents ?></div><div class="stat-label">Active</div></div></div>
                <div class="stat-card purple"><div class="stat-icon" style="background:#6a1b9a"><i class="fas fa-layer-group"></i></div><div><div class="stat-number"><?= count($programs_list) ?></div><div class="stat-label">Programmes</div></div></div>
                <div class="stat-card accent"><div class="stat-icon" style="background:#e65100"><i class="fas fa-graduation-cap"></i></div><div><div class="stat-number"><?= $total_graduation_candidates ?></div><div class="stat-label">Graduation Candidates</div></div></div>
            </div>
            <div class="grid-3 mb-3">
                <div class="section-card">
                    <div class="card-header" style="background:#1a237e;color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.9rem"><i class="fas fa-user-graduate"></i> Student Reports</h5></div>
                    <div class="card-body" style="padding:12px">
                        <a href="?report=student_list" class="btn btn-primary w-100 mb-2"><i class="fas fa-list"></i> Full Student List</a>
                        <a href="?report=by_program" class="btn btn-info w-100 mb-2"><i class="fas fa-layer-group"></i> By Program</a>
                        <a href="?report=enrollment" class="btn btn-success w-100 mb-2"><i class="fas fa-chart-bar"></i> Enrollment Report</a>
                        <a href="?report=graduation" class="btn btn-accent w-100"><i class="fas fa-graduation-cap"></i> Graduation Report</a>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header" style="background:#2e7d32;color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.9rem"><i class="fas fa-chart-line"></i> Academic Reports</h5></div>
                    <div class="card-body" style="padding:12px">
                        <a href="?report=academic" class="btn btn-primary w-100 mb-2"><i class="fas fa-chart-line"></i> Academic Performance</a>
                        <a href="?report=results" class="btn btn-info w-100 mb-2"><i class="fas fa-check-circle"></i> Results Report</a>
                        <a href="?report=grades" class="btn btn-success w-100 mb-2"><i class="fas fa-star"></i> Grades Report</a>
                        <a href="?report=registration" class="btn btn-accent w-100"><i class="fas fa-pen-square"></i> Course Registration</a>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header" style="background:#6a1b9a;color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.9rem"><i class="fas fa-download"></i> Export Options</h5></div>
                    <div class="card-body" style="padding:12px">
                        <p class="small text-muted mb-2">Select a report type above, then click an export format below:</p>
                        <button class="btn btn-danger w-100 mb-2" onclick="window.print()"><i class="fas fa-file-pdf"></i> Print / Save as PDF</button>
                        <button class="btn btn-success w-100 mb-2" onclick="exportAsCSV()"><i class="fas fa-file-csv"></i> Export as CSV</button>
                        <button class="btn btn-primary w-100" onclick="exportTable('reportTable')"><i class="fas fa-file-excel"></i> Export as Excel</button>
                    </div>
                </div>
            </div>
            <div class="section-card">
                <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-info-circle"></i> Report Preview</h5></div>
                <div class="card-body" style="padding:16px">
                    <div id="reportPreview" class="text-muted small">Select a report type from the dropdown above and click <strong>Generate</strong>, or click any quick-access button to open the report in a new window.</div>
                </div>
            </div>
        </div>
        <!-- Notifications Section -->
        <div class="main content-section dashboard-section"  data-section="notifications">
            <div class="page-header">
                <h1><i class="fas fa-bell"></i> Notifications & Announcements</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('announcementModal')"><i class="fas fa-plus"></i> New Announcement</button>
                </div>
            </div>
            <div class="grid-2">
                <div class="section-card">
                    <div class="card-header"><h5><i class="fas fa-bullhorn"></i> Announcements</h5></div>
                    <div class="card-body">
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $ann): ?>
                                <div class="section-card mb-2" style="box-shadow:none;border:1px solid #eee;">
                                    <div class="card-body" style="padding:12px 16px;">
                                        <div class="flex-between">
                                            <strong><?= htmlspecialchars($ann['title']??'') ?></strong>
                                            <small class="text-muted"><?= htmlspecialchars($ann['created_at']??'') ?></small>
                                        </div>
                                        <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($ann['message']??'')) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state"><i class="fas fa-bullhorn"></i> No announcements.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header"><h5><i class="fas fa-exclamation-circle"></i> System Alerts</h5></div>
                    <div class="card-body">
                        <?php if (!empty($system_alerts)): ?>
                            <?php foreach ($system_alerts as $alert): ?>
                                <div class="alert alert-info">
                                    <strong><?= htmlspecialchars($alert['subject']??'Alert') ?></strong>
                                    <p class="mb-0 mt-1 small"><?= htmlspecialchars($alert['message']??'') ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($alert['created_at']??'') ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state"><i class="fas fa-check-circle text-success"></i> No alerts.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approvals Section -->
        <div class="main content-section dashboard-section"  data-section="approvals">
            <div class="page-header">
                <h1><i class="fas fa-check-double"></i> Approvals</h1>
            </div>
            <div class="grid-2">
                <div class="section-card">
                    <div class="card-header"><h5><i class="fas fa-clipboard-check"></i> Grade Approvals</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>#</th><th>Type</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($approval_items)): $i=1; ?>
                                        <?php foreach ($approval_items as $ai): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars($ai['approval_type']??'Grade') ?></td>
                                                <td><span class="badge badge-<?= ($ai['status']??'')==='Approved'?'success':'warning' ?>"><?= htmlspecialchars($ai['status']??'') ?></span></td>
                                                <td><?= htmlspecialchars($ai['created_at']??'') ?></td>
                                                <td><span class="badge badge-info">View</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center">No pending approvals.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header"><h5><i class="fas fa-book"></i> Course Registration Approvals</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>#</th><th>Student</th><th>Course</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($course_reg_approvals)): $i=1; ?>
                                        <?php foreach ($course_reg_approvals as $cra): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars($cra['full_name']??'') ?></td>
                                                <td><?= htmlspecialchars($cra['course_code']??'') ?> - <?= htmlspecialchars($cra['course_name']??'') ?></td>
                                                <td><?= htmlspecialchars($cra['registration_date']??'') ?></td>
                                                <td><span class="badge badge-<?= $cra['status']==='Approved'?'success':'warning' ?>"><?= htmlspecialchars($cra['status']??'') ?></span></td>
                                                <td>
                                                    <?php if (($cra['status']??'') !== 'Approved'): ?>
                                                        <form method="post" style="display:inline">
                                                            <input type="hidden" name="action" value="approve_course_reg">
                                                            <input type="hidden" name="reg_id" value="<?= $cra['id'] ?>">
                                                            <button class="btn btn-success btn-xs"><i class="fas fa-check"></i> Approve</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center">No pending registrations.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Section -->
        <div class="main content-section dashboard-section"  data-section="audit">
            <div class="page-header">
                <h1><i class="fas fa-history"></i> Audit Trail</h1>
                <div class="header-actions">
                    <select class="form-control form-control-sm" style="max-width:200px" id="auditFilter" onchange="filterAudit()">
                        <option value="">All Activities</option>
                        <option value="Added">Added</option>
                        <option value="Updated">Updated</option>
                        <option value="Deleted">Deleted</option>
                        <option value="Approved">Approved</option>
                        <option value="Payment">Payment</option>
                    </select>
                </div>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-stream"></i> Activity Log</h5></div>
                <div class="card-body">
                    <div class="timeline" id="auditTimeline">
                        <?php if (!empty($audit_log)): ?>
                            <?php foreach ($audit_log as $log): ?>
                                <div class="timeline-item">
                                    <div class="timeline-time"><?= htmlspecialchars($log['created_at']??'') ?></div>
                                    <div class="timeline-text"><?= htmlspecialchars($log['activity']??'') ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state"><i class="fas fa-history"></i> No activity logged.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="main content-section dashboard-section"  data-section="settings">
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> Settings</h1>
            </div>
            <div class="grid-2">
                <div class="section-card">
                    <div class="card-header"><h5><i class="fas fa-wrench"></i> System Settings</h5></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="save_setting">
                            <div class="form-group">
                                <label>Current Academic Year</label>
                                <select name="setting_value" class="form-control" required>
                                    <?php foreach ($academic_years as $ay): ?>
                                        <option><?= htmlspecialchars($ay) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="setting_key" value="current_academic_year">
                            </div>
                            <div class="form-group">
                                <label>Default Currency</label>
                                <input type="text" name="setting_value" class="form-control" value="UGX" required>
                                <input type="hidden" name="setting_key" value="currency">
                            </div>
                            <div class="form-group">
                                <label>Institution Name</label>
                                <input type="text" name="setting_value" class="form-control" value="ISNM" required>
                                <input type="hidden" name="setting_key" value="institution_name">
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
                        </form>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header"><h5><i class="fas fa-info"></i> System Information</h5></div>
                    <div class="card-body">
                        <p><strong>Total Students:</strong> <?= $studentCount ?></p>
                        <p><strong>Total Staff:</strong> <?= count($staff_mgmt) ?></p>
                        <p><strong>Environment:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></p>
                        <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Recycle Bin Section -->
        <div class="main content-section dashboard-section"  data-section="recycle-bin">
            <div class="page-header">
                <h1><i class="fas fa-trash-alt"></i> Recycle Bin</h1>
            </div>
            <div class="section-card">
                <div class="card-header"><h5><i class="fas fa-trash"></i> Deleted Student Records</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>#</th><th>Student No</th><th>Full Name</th><th>Course</th><th>Deleted Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (!empty($trashedStudents)): $i=1; ?>
                                    <?php foreach ($trashedStudents as $ts): ?>
                                        <tr class="trash-item">
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($ts['student_number']??'') ?></td>
                                            <td><?= htmlspecialchars($ts['full_name']??'') ?></td>
                                            <td><?= htmlspecialchars($ts['course']??'') ?></td>
                                            <td><?= htmlspecialchars($ts['deleted_at']??'') ?></td>
                                            <td>
                                                <form method="post" style="display:inline">
                                                    <input type="hidden" name="action" value="restore_student">
                                                    <input type="hidden" name="trash_id" value="<?= $ts['id'] ?>">
                                                    <button class="btn btn-success btn-xs"><i class="fas fa-undo"></i> Restore</button>
                                                </form>
                                                <form method="post" style="display:inline" onsubmit="return confirm('Permanently delete? This cannot be undone.')">
                                                    <input type="hidden" name="action" value="delete_permanent">
                                                    <input type="hidden" name="trash_id" value="<?= $ts['id'] ?>">
                                                    <button class="btn btn-danger btn-xs"><i class="fas fa-times"></i> Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">Trash is empty.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Records Section -->
        <div class="main content-section dashboard-section" data-section="student-records">
            <div class="page-header">
                <h1><i class="fas fa-address-book"></i> Student Records</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addStudentModal')"><i class="fas fa-user-plus"></i> Add Student</button>
                    <button class="btn btn-success" onclick="window.location.href='?report=student_list'"><i class="fas fa-download"></i> Export All</button>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon" style="background:#1a237e"><i class="fas fa-users"></i></div><div><div class="stat-number"><?= array_sum(array_column($student_group_by_status,'student_count')) ?></div><div class="stat-label">Total Records</div></div></div>
                <div class="stat-card green"><div class="stat-icon" style="background:#2e7d32"><i class="fas fa-check-circle"></i></div><div><div class="stat-number"><?= intval($student_group_by_status[0]['student_count']??0) ?></div><div class="stat-label">Active</div></div></div>
                <div class="stat-card accent"><div class="stat-icon" style="background:#e65100"><i class="fas fa-clock"></i></div><div><div class="stat-number"><?= intval(($sg_found=array_filter($student_group_by_status,fn($s)=>$s['status']==='Pending')) ? reset($sg_found)['student_count'] : 0) ?></div><div class="stat-label">Pending</div></div></div>
                <div class="stat-card purple"><div class="stat-icon" style="background:#6a1b9a"><i class="fas fa-layer-group"></i></div><div><div class="stat-number"><?= count($student_group_by_program) ?></div><div class="stat-label">Programmes</div></div></div>
            </div>
            <div class="grid-2 mb-3">
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-chart-pie"></i> Students by Program</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Program</th><th>Total</th><th>Active</th><th>%</th></tr></thead>
                                <tbody>
                                    <?php $grp_total = array_sum(array_column($student_group_by_program,'student_count')); ?>
                                    <?php foreach ($student_group_by_program as $gp): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($gp['program']) ?></strong></td>
                                            <td><?= intval($gp['student_count']) ?></td>
                                            <td><?= intval($gp['active_count']) ?></td>
                                            <td><?= $grp_total > 0 ? round(intval($gp['student_count'])/$grp_total*100,1).'%' : '0%' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-chart-bar"></i> Students by Status</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Status</th><th>Count</th></tr></thead>
                                <tbody>
                                    <?php foreach ($student_group_by_status as $gs): ?>
                                        <tr>
                                            <td><span class="badge badge-<?= $gs['status']==='Active'?'success':($gs['status']==='Pending'?'warning':($gs['status']==='Graduated'?'info':'danger')) ?>"><?= htmlspecialchars($gs['status'] ?: 'Unknown') ?></span></td>
                                            <td><strong><?= intval($gs['student_count']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid-2 mb-3">
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-layer-group"></i> Students by Set</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Set</th><th>Students</th></tr></thead>
                                <tbody>
                                    <?php foreach ($student_group_by_set as $gs2): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($gs2['set_name'] ?: 'Unassigned') ?></strong></td>
                                            <td><?= intval($gs2['student_count']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-calendar"></i> Students by Year</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Year of Study</th><th>Students</th></tr></thead>
                                <tbody>
                                    <?php foreach ($student_group_by_year as $gy): ?>
                                        <tr>
                                            <td><strong>Year <?= intval($gy['current_year']) ?></strong></td>
                                            <td><?= intval($gy['student_count']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-card">
                <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-list"></i> Detailed Student Records by Program & Year</h5></div>
                <div class="card-body" style="padding:16px">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Program</th><th>Year</th><th>Set</th><th>Total</th><th>Male</th><th>Female</th></tr></thead>
                            <tbody>
                                <?php if (!empty($student_records_by_program)): ?>
                                    <?php foreach ($student_records_by_program as $sr): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($sr['course']) ?></td>
                                            <td>Year <?= intval($sr['current_year']) ?></td>
                                            <td><?= htmlspecialchars($sr['set_name'] ?: '-') ?></td>
                                            <td><?= intval($sr['count']) ?></td>
                                            <td><?= intval($sr['male']) ?></td>
                                            <td><?= intval($sr['female']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">No records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courses Section -->
        <div class="main content-section dashboard-section" data-section="courses">
            <div class="page-header">
                <h1><i class="fas fa-book"></i> Courses</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addCourseModal')"><i class="fas fa-plus"></i> Add Course</button>
                    <button class="btn btn-outline" onclick="exportTable('coursesTable')"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon" style="background:#1a237e"><i class="fas fa-book"></i></div><div><div class="stat-number"><?= count($course_catalog) ?></div><div class="stat-label">Total Courses</div></div></div>
                <div class="stat-card green"><div class="stat-icon" style="background:#2e7d32"><i class="fas fa-layer-group"></i></div><div><div class="stat-number"><?= count($course_group_by_department) ?></div><div class="stat-label">Departments</div></div></div>
            </div>
            <div class="grid-2 mb-3">
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-chart-pie"></i> Courses by Department</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Department</th><th>Courses</th><th>Total Credits</th></tr></thead>
                                <tbody>
                                    <?php foreach ($course_group_by_department as $cg): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($cg['department']) ?></strong></td>
                                            <td><?= intval($cg['course_count']) ?></td>
                                            <td><?= intval($cg['total_credits']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-list"></i> All Courses</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="search-box">
                            <input type="text" class="form-control" placeholder="Search courses..." onkeyup="filterTable('courseSearch','coursesTable')" id="courseSearch">
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="coursesTable">
                                <thead><tr><th>Code</th><th>Course Name</th><th>Department</th><th>Credits</th><th>Level</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($course_catalog)): ?>
                                        <?php foreach ($course_catalog as $cc): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($cc['course_code']??'') ?></td>
                                                <td><?= htmlspecialchars($cc['course_name']??'') ?></td>
                                                <td><?= htmlspecialchars($cc['department']??'') ?></td>
                                                <td><?= intval($cc['credit_hours']??$cc['credits']??0) ?></td>
                                                <td><?= htmlspecialchars($cc['level']??$cc['course_level']??'') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center">No courses found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading Management Section -->
        <div class="main content-section dashboard-section" data-section="grading">
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> Grading Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addExamModal')"><i class="fas fa-plus"></i> Enter Grades</button>
                    <a href="?report=results" class="btn btn-info"><i class="fas fa-download"></i> Export Grades</a>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card green"><div class="stat-icon" style="background:#2e7d32"><i class="fas fa-check-circle"></i></div><div><div class="stat-number"><?= count($exam_records_list) - count($pending_grade_approvals) ?></div><div class="stat-label">Approved Grades</div></div></div>
                <div class="stat-card accent"><div class="stat-icon" style="background:#e65100"><i class="fas fa-clock"></i></div><div><div class="stat-number"><?= count($pending_grade_approvals) ?></div><div class="stat-label">Pending Approval</div></div></div>
                <div class="stat-card red"><div class="stat-icon" style="background:#c62828"><i class="fas fa-exclamation-triangle"></i></div><div><div class="stat-number"><?= count($missing_results) ?></div><div class="stat-label">Missing Grades</div></div></div>
                <div class="stat-card purple"><div class="stat-icon" style="background:#6a1b9a"><i class="fas fa-trophy"></i></div><div><div class="stat-number"><?= count($gpa_summary) ?></div><div class="stat-label">GPA Records</div></div></div>
            </div>
            <div class="grid-2 mb-3">
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-hourglass-half"></i> Pending Grade Approvals</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>#</th><th>Student</th><th>Course</th><th>Marks</th><th>Grade</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($pending_grade_approvals)): $i=1; ?>
                                        <?php foreach ($pending_grade_approvals as $ga): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars($ga['full_name']??'') ?></td>
                                                <td><?= htmlspecialchars($ga['course_code']??'') ?></td>
                                                <td><?= floatval($ga['marks_obtained']??0) ?></td>
                                                <td><strong><?= htmlspecialchars($ga['grade']??'-') ?></strong></td>
                                                <td>
                                                    <button class="btn btn-success btn-xs" onclick="openModal('gradeApprovalModal')" data-exam-id="<?= $ga['id'] ?>" data-student="<?= htmlspecialchars($ga['full_name']??'') ?>"><i class="fas fa-check"></i> Approve</button>
                                                    <button class="btn btn-info btn-xs" onclick="openModal('editMarksModal')" data-exam-id="<?= $ga['id'] ?>" data-marks="<?= floatval($ga['marks_obtained']??0) ?>" data-grade="<?= htmlspecialchars($ga['grade']??'') ?>"><i class="fas fa-edit"></i> Edit</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center">All grades approved.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-trophy"></i> GPA Performance (Top 20)</h5></div>
                    <div class="card-body" style="padding:16px">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>#</th><th>Student</th><th>Program</th><th>Avg Marks</th><th>Exams</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($gpa_summary)): $i=1; ?>
                                        <?php foreach ($gpa_summary as $gs): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars($gs['full_name']??'') ?></td>
                                                <td><?= htmlspecialchars($gs['course']??'') ?></td>
                                                <td><?= number_format(floatval($gs['avg_marks']??0),1) ?>%</td>
                                                <td><?= intval($gs['exam_count']??0) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center">No GPA data.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transcripts Section -->
        <div class="main content-section dashboard-section" data-section="transcripts">
            <div class="page-header">
                <h1><i class="fas fa-file-pdf"></i> Transcripts Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('transcriptModal')"><i class="fas fa-plus"></i> New Request</button>
                    <button class="btn btn-outline" onclick="exportTable('transcriptsTable')"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card accent"><div class="stat-icon" style="background:#e65100"><i class="fas fa-clock"></i></div><div><div class="stat-number"><?= count(array_filter($transcript_requests,fn($t)=>($t['transcript_status']??'')==='Requested'||($t['transcript_status']??'')==='Processing')) ?></div><div class="stat-label">Pending</div></div></div>
                <div class="stat-card green"><div class="stat-icon" style="background:#2e7d32"><i class="fas fa-check-circle"></i></div><div><div class="stat-number"><?= count(array_filter($transcript_requests,fn($t)=>($t['transcript_status']??'')==='Issued')) ?></div><div class="stat-label">Issued</div></div></div>
                <div class="stat-card purple"><div class="stat-icon" style="background:#6a1b9a"><i class="fas fa-hand-peace"></i></div><div><div class="stat-number"><?= count(array_filter($transcript_requests,fn($t)=>($t['transcript_status']??'')==='Collected')) ?></div><div class="stat-label">Collected</div></div></div>
                <div class="stat-card"><div class="stat-icon" style="background:#1a237e"><i class="fas fa-file-pdf"></i></div><div><div class="stat-number"><?= count($transcript_requests) ?></div><div class="stat-label">Total Requests</div></div></div>
            </div>
            <div class="section-card">
                <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-list"></i> Transcript Requests</h5></div>
                <div class="card-body" style="padding:16px">
                    <div class="search-box">
                        <input type="text" class="form-control" placeholder="Search transcripts..." onkeyup="filterTable('transcriptSearch','transcriptsTable')" id="transcriptSearch">
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="transcriptsTable">
                            <thead><tr><th>#</th><th>Request No</th><th>Student</th><th>Program</th><th>Purpose</th><th>Copies</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (!empty($transcript_requests)): $i=1; ?>
                                    <?php foreach ($transcript_requests as $tr): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($tr['transcript_number']??'') ?></td>
                                            <td><?= htmlspecialchars($tr['student_name']??'') ?></td>
                                            <td><?= htmlspecialchars($tr['course']??'') ?></td>
                                            <td><?= htmlspecialchars($tr['purpose']??'') ?></td>
                                            <td><?= intval($tr['copies_requested']??1) ?></td>
                                            <td><span class="badge badge-<?= ($tr['transcript_status']??'')==='Issued'?'success':(($tr['transcript_status']??'')==='Collected'?'info':(in_array($tr['transcript_status']??'',['Processing','Ready'])?'warning':'danger')) ?>"><?= htmlspecialchars($tr['transcript_status']??'Pending') ?></span></td>
                                            <td><?= htmlspecialchars($tr['request_date']??$tr['created_at']??'') ?></td>
                                            <td>
                                                <?php if (!empty($tr['doc_id'])): ?>
                                                    <button class="btn btn-info btn-xs" onclick="previewDocument(<?= intval($tr['doc_id']) ?>)" title="Preview"><i class="fas fa-eye"></i></button>
                                                <?php endif; ?>
                                                <?php if (($tr['transcript_status']??'') === 'Requested'): ?>
                                                    <form method="post" style="display:inline">
                                                        <input type="hidden" name="action" value="approve_transcript">
                                                        <input type="hidden" name="transcript_id" value="<?= $tr['id'] ?>">
                                                        <button class="btn btn-success btn-xs"><i class="fas fa-check"></i> Process</button>
                                                    </form>
                                                <?php elseif (in_array($tr['transcript_status']??'', ['Processing','Ready'])): ?>
                                                    <form method="post" style="display:inline">
                                                        <input type="hidden" name="action" value="issue_transcript">
                                                        <input type="hidden" name="transcript_id" value="<?= $tr['id'] ?>">
                                                        <button class="btn btn-primary btn-xs"><i class="fas fa-file-export"></i> Issue</button>
                                                    </form>
                                                <?php elseif (($tr['transcript_status']??'') === 'Issued'): ?>
                                                    <form method="post" style="display:inline" onsubmit="return confirm('Mark as collected?')">
                                                        <input type="hidden" name="action" value="mark_collected">
                                                        <input type="hidden" name="doc_type" value="transcript">
                                                        <input type="hidden" name="doc_id" value="<?= $tr['id'] ?>">
                                                        <input type="text" name="collected_by" placeholder="Collector name" class="form-control form-control-sm" style="width:120px;display:inline" required>
                                                        <button class="btn btn-info btn-xs"><i class="fas fa-hand-peace"></i> Collect</button>
                                                    </form>
                                                <?php elseif (($tr['transcript_status']??'') === 'Collected'): ?>
                                                    <span class="badge badge-info">Collected</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" class="text-center">No transcript requests.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificates Section -->
        <div class="main content-section dashboard-section" data-section="certificates">
            <div class="page-header">
                <h1><i class="fas fa-award"></i> Certificates Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('certificateModal')"><i class="fas fa-plus"></i> Generate Certificate</button>
                    <button class="btn btn-outline" onclick="exportTable('certificatesTable')"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card green"><div class="stat-icon" style="background:#2e7d32"><i class="fas fa-check-circle"></i></div><div><div class="stat-number"><?= count(array_filter($certificate_records,fn($c)=>($c['status']??'')==='Generated')) ?></div><div class="stat-label">Generated</div></div></div>
                <div class="stat-card accent"><div class="stat-icon" style="background:#e65100"><i class="fas fa-file-export"></i></div><div><div class="stat-number"><?= count(array_filter($certificate_records,fn($c)=>($c['status']??'')==='Issued')) ?></div><div class="stat-label">Issued</div></div></div>
                <div class="stat-card purple"><div class="stat-icon" style="background:#6a1b9a"><i class="fas fa-hand-peace"></i></div><div><div class="stat-number"><?= count(array_filter($certificate_records,fn($c)=>($c['status']??'')==='Collected')) ?></div><div class="stat-label">Collected</div></div></div>
                <div class="stat-card"><div class="stat-icon" style="background:#1a237e"><i class="fas fa-award"></i></div><div><div class="stat-number"><?= count($certificate_records) ?></div><div class="stat-label">Total</div></div></div>
            </div>
            <div class="section-card">
                <div class="card-header" style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:8px 8px 0 0"><h5 style="margin:0;font-size:0.95rem"><i class="fas fa-list"></i> Certificate Records</h5></div>
                <div class="card-body" style="padding:16px">
                    <div class="search-box">
                        <input type="text" class="form-control" placeholder="Search certificates..." onkeyup="filterTable('certSearch','certificatesTable')" id="certSearch">
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="certificatesTable">
                            <thead><tr><th>#</th><th>Cert No</th><th>Student</th><th>Program</th><th>Type</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (!empty($certificate_records)): $i=1; ?>
                                    <?php foreach ($certificate_records as $cr): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($cr['certificate_number']??'') ?></td>
                                            <td><?= htmlspecialchars($cr['student_name']??'') ?></td>
                                            <td><?= htmlspecialchars($cr['course']??$cr['program']??'') ?></td>
                                            <td><?= htmlspecialchars($cr['certificate_type']??'') ?></td>
                                            <td><span class="badge badge-<?= ($cr['status']??'')==='Issued'?'success':(($cr['status']??'')==='Collected'?'info':(($cr['status']??'')==='Generated'?'warning':'danger')) ?>"><?= htmlspecialchars($cr['status']??'Draft') ?></span></td>
                                            <td><?= htmlspecialchars($cr['generated_date']??$cr['created_at']??'') ?></td>
                                            <td>
                                                <?php if (!empty($cr['doc_id'])): ?>
                                                    <button class="btn btn-info btn-xs" onclick="previewDocument(<?= intval($cr['doc_id']) ?>)" title="Preview"><i class="fas fa-eye"></i></button>
                                                <?php endif; ?>
                                                <?php if (($cr['status']??'') === 'Generated'): ?>
                                                    <form method="post" style="display:inline">
                                                        <input type="hidden" name="action" value="issue_certificate">
                                                        <input type="hidden" name="certificate_id" value="<?= $cr['id'] ?>">
                                                        <button class="btn btn-primary btn-xs"><i class="fas fa-file-export"></i> Issue</button>
                                                    </form>
                                                <?php elseif (($cr['status']??'') === 'Issued'): ?>
                                                    <form method="post" style="display:inline" onsubmit="return confirm('Mark as collected?')">
                                                        <input type="hidden" name="action" value="mark_collected">
                                                        <input type="hidden" name="doc_type" value="certificate">
                                                        <input type="hidden" name="doc_id" value="<?= $cr['id'] ?>">
                                                        <input type="text" name="collected_by" placeholder="Collector name" class="form-control form-control-sm" style="width:120px;display:inline" required>
                                                        <button class="btn btn-info btn-xs"><i class="fas fa-hand-peace"></i> Collect</button>
                                                    </form>
                                                <?php elseif (($cr['status']??'') === 'Collected'): ?>
                                                    <span class="badge badge-info">Collected</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center">No certificate records.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== MODALS ====== -->
        <!-- Add Student Modal -->
        <div class="modal" id="addStudentModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-header">
                    <h5>Add New Student</h5>
                    <button class="close" onclick="closeModal('addStudentModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_student">
                        <div class="grid-3">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Other Name</label>
                                <input type="text" name="other_name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Surname *</label>
                                <input type="text" name="surname" class="form-control" required>
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option>Male</option><option>Female</option><option>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="Ugandan">
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Course</label>
                                <select name="course" class="form-control">
                                    <?php foreach ($courseNames as $cn): ?>
                                        <option><?= htmlspecialchars($cn) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Year</label>
                                <select name="year" class="form-control">
                                    <?php for ($y=1;$y<=5;$y++): ?>
                                        <option value="<?= $y ?>">Year <?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester" class="form-control">
                                    <option>Semester 1</option><option>Semester 2</option><option>Semester 3</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addStudentModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Student</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Student Modal -->
        <div class="modal" id="editStudentModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-header">
                    <h5>Edit Student</h5>
                    <button class="close" onclick="closeModal('editStudentModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_student">
                        <input type="hidden" name="id" id="editId">
                        <div class="grid-3">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Other Name</label>
                                <input type="text" name="other_name" id="editOtherName" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Surname</label>
                                <input type="text" name="surname" id="editSurname" class="form-control" required>
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Course</label>
                                <select name="course" id="editCourse" class="form-control">
                                    <?php foreach ($courseNames as $cn): ?>
                                        <option><?= htmlspecialchars($cn) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Year</label>
                                <select name="year" id="editYear" class="form-control">
                                    <?php for ($y=1;$y<=5;$y++): ?>
                                        <option value="<?= $y ?>">Year <?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester" id="editSemester" class="form-control">
                                    <option>Semester 1</option><option>Semester 2</option><option>Semester 3</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" id="editPhone" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" id="editEmail" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="editStatus" class="form-control">
                                    <option>Active</option><option>Pending</option><option>Graduated</option><option>Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editStudentModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Record Payment Modal -->
        <div class="modal" id="addPaymentModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5>Record Payment</h5>
                    <button class="close" onclick="closeModal('addPaymentModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="record_payment">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" class="form-control" id="payStudentId" required>
                                <option value="">Select Student</option>
                                <?php foreach ($allStudents as $st): ?>
                                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']??'') ?> (<?= htmlspecialchars($st['student_number']??'') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount *</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" class="form-control">
                                    <option>Cash</option><option>Mobile Money</option><option>Bank Transfer</option><option>Cheque</option><option>Card</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Reference</label>
                            <input type="text" name="reference" class="form-control" placeholder="Optional reference number">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addPaymentModal')">Cancel</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-money-bill"></i> Record Payment</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Create Invoice Modal -->
        <div class="modal" id="addInvoiceModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5>Create Invoice</h5>
                    <button class="close" onclick="closeModal('addInvoiceModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_invoice">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" class="form-control" required>
                                <option value="">Select Student</option>
                                <?php foreach ($allStudents as $st): ?>
                                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']??'') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fee Type</label>
                            <select name="fee_type" class="form-control">
                                <?php foreach ($fee_types as $ft): ?>
                                    <option><?= htmlspecialchars($ft) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Total Amount *</label>
                                <input type="number" step="0.01" name="total_amount" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" name="due_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addInvoiceModal')">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-file-invoice"></i> Create Invoice</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Course Registration Modal -->
        <div class="modal" id="courseRegModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5>Register Course</h5>
                    <button class="close" onclick="closeModal('courseRegModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="register_course">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" class="form-control" required>
                                <option value="">Select Student</option>
                                <?php foreach ($allStudents as $st): ?>
                                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']??'') ?> (<?= htmlspecialchars($st['student_number']??'') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Course</label>
                            <select name="course_id" class="form-control" required>
                                <option value="">Select Course</option>
                                <?php foreach ($course_catalog as $cc): ?>
                                    <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['course_code']??'') ?> - <?= htmlspecialchars($cc['course_name']??'') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Academic Year</label>
                                <select name="academic_year" class="form-control">
                                    <option><?= date('Y') ?></option>
                                    <option><?= date('Y')-1 ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester" class="form-control">
                                    <option>Semester 1</option><option>Semester 2</option><option>Semester 3</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('courseRegModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Register</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Upload Document Modal -->
        <div class="modal" id="uploadDocModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5>Upload Document</h5>
                    <button class="close" onclick="closeModal('uploadDocModal')">&times;</button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="upload_document">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" class="form-control" required>
                                <option value="">Select Student</option>
                                <?php foreach ($allStudents as $st): ?>
                                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']??'') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Document Type</label>
                                <select name="document_type" class="form-control">
                                    <option>Transcript</option><option>Certificate</option><option>ID Card</option><option>Recommendation Letter</option><option>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Document Title *</label>
                                <input type="text" name="document_title" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>File *</label>
                            <input type="file" name="doc_file" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('uploadDocModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Generate Document Modal -->
        <div class="modal" id="generateDocModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5>Generate Document</h5>
                    <button class="close" onclick="closeModal('generateDocModal')">&times;</button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="upload_document">
                        <div class="form-group">
                            <label>Student *</label>
                            <select name="student_id" class="form-control" required>
                                <option value="">Select Student</option>
                                <?php foreach ($allStudents as $st): ?>
                                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']??'') ?> (<?= htmlspecialchars($st['student_number']??'') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Document Type *</label>
                            <select name="document_type" class="form-control" id="genDocType" onchange="toggleGenDocTitle()">
                                <option value="Transcript">Transcript</option>
                                <option value="Certificate">Certificate</option>
                                <option value="Receipt">Receipt</option>
                                <option value="Recommendation Letter">Recommendation Letter</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Document Title *</label>
                            <input type="text" name="document_title" id="genDocTitle" class="form-control" required placeholder="e.g. Transcript of Records">
                        </div>
                        <div class="form-group">
                            <label>Upload File</label>
                            <input type="file" name="doc_file" class="form-control">
                            <small class="text-muted">Leave empty to generate an auto-document (title-based placeholder).</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('generateDocModal')">Cancel</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-file"></i> Generate</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        function toggleGenDocTitle() {
            var type = document.getElementById('genDocType').value;
            var title = document.getElementById('genDocTitle');
            if (type === 'Transcript') title.value = 'Academic Transcript';
            else if (type === 'Certificate') title.value = 'Certificate of Completion';
            else if (type === 'Receipt') title.value = 'Payment Receipt';
            else if (type === 'Recommendation Letter') title.value = 'Recommendation Letter';
            else title.value = '';
        }
        </script>

        <!-- Add Exam Record Modal -->
        <div class="modal" id="addExamModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5>Add Exam Record</h5>
                    <button class="close" onclick="closeModal('addExamModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_exam_marks">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" class="form-control" required>
                                <option value="">Select Student</option>
                                <?php foreach ($allStudents as $st): ?>
                                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']??'') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Course Code</label>
                                <input type="text" name="course_code" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Exam Type</label>
                                <select name="exam_type" class="form-control">
                                    <option>Final</option><option>Midterm</option><option>Quiz</option><option>Assignment</option><option>Practical</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>CA Marks</label>
                                <input type="number" step="0.01" name="ca_marks" class="form-control" value="0">
                            </div>
                            <div class="form-group">
                                <label>Exam Marks</label>
                                <input type="number" step="0.01" name="exam_marks" class="form-control" value="0">
                            </div>
                            <div class="form-group">
                                <label>Total</label>
                                <input type="number" step="0.01" name="total_marks" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Grade</label>
                            <select name="grade" class="form-control">
                                <option value="">Not Graded</option>
                                <option>A</option><option>B+</option><option>B</option><option>C+</option><option>C</option><option>D</option><option>F</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addExamModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grade Approval Modal -->
        <div class="modal" id="gradeApprovalModal">
            <div class="modal-dialog modal-sm">
                <div class="modal-header">
                    <h5>Approve Grade</h5>
                    <button class="close" onclick="closeModal('gradeApprovalModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="finalize_results">
                        <input type="hidden" name="student_id" id="approveStudentId">
                        <p>Approve grades for <strong id="approveStudentName"></strong>?</p>
                        <div class="form-group">
                            <label>Grade Status</label>
                            <select name="grade_status" class="form-control">
                                <option>Approved</option>
                                <option>Finalized</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('gradeApprovalModal')">Cancel</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Marks Modal -->
        <div class="modal" id="editMarksModal">
            <div class="modal-dialog modal-sm">
                <div class="modal-header">
                    <h5>Edit Marks</h5>
                    <button class="close" onclick="closeModal('editMarksModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_exam_marks">
                        <input type="hidden" name="exam_id" id="editExamId">
                        <div class="form-group">
                            <label>Total Marks</label>
                            <input type="number" step="0.01" name="total_marks" id="editTotalMarks" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Grade</label>
                            <select name="grade" id="editGrade" class="form-control">
                                <option value="">Not Graded</option>
                                <option>A</option><option>B+</option><option>B</option><option>C+</option><option>C</option><option>D</option><option>F</option>
                            </select>
                        </div>
                        <div class="form-group mt-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="auto_generate_docs" value="1" checked>
                                <i class="fas fa-file-alt"></i> Auto-generate Transcript &amp; Certificate
                            </label>
                            <small class="form-text text-muted">When checked, professional transcript and certificate will be automatically generated.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editMarksModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Calendar Modal -->
        <div class="modal" id="addCalendarModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5>Add Calendar Entry</h5>
                    <button class="close" onclick="closeModal('addCalendarModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_calendar">
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Academic Year</label>
                                <input type="text" name="academic_year" class="form-control" value="<?= date('Y') ?>-<?= date('Y',strtotime('+1 year')) ?>">
                            </div>
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester" class="form-control">
                                    <option>Semester 1</option><option>Semester 2</option><option>Semester 3</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Semester Start</label>
                                <input type="date" name="semester_start" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Semester End</label>
                                <input type="date" name="semester_end" class="form-control">
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Exam Start</label>
                                <input type="date" name="exam_start" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Exam End</label>
                                <input type="date" name="exam_end" class="form-control">
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Result Date</label>
                                <input type="date" name="result_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Reg Deadline</label>
                                <input type="date" name="reg_deadline" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addCalendarModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Announcement Modal -->
        <div class="modal" id="announcementModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5>New Announcement</h5>
                    <button class="close" onclick="closeModal('announcementModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="compose_announcement">
                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="ann_title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Message *</label>
                            <textarea name="ann_message" class="form-control" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('announcementModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Publish</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transcript Request Modal (AJAX) -->
        <div class="modal" id="transcriptModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5><i class="fas fa-file-pdf"></i> Generate Professional Transcript</h5>
                    <button class="close" onclick="closeModal('transcriptModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Search Student *</label>
                        <div class="student-lookup-wrapper">
                            <input type="text" id="transcriptStudentSearch" class="form-control" placeholder="Type name, reg no, or phone..." onkeyup="transcriptLookup(this.value)" autocomplete="off">
                            <input type="hidden" id="transcriptStudentId" value="">
                            <div id="transcriptStudentResults" class="lookup-results"></div>
                        </div>
                        <small class="form-text text-muted">Search by name, student number, registration number, or phone.</small>
                    </div>
                    <div id="transcriptSelectedStudent" class="selected-student-info" style="display:none;padding:8px 12px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;margin-bottom:12px">
                        <strong id="transcriptSelectedName"></strong>
                        <span id="transcriptSelectedReg" class="text-muted" style="font-size:12px"></span>
                    </div>
                    <div class="form-group">
                        <label>Purpose</label>
                        <select name="purpose" id="transcriptPurpose" class="form-control">
                            <option value="Academic">Academic</option>
                            <option value="Employment">Employment</option>
                            <option value="Transfer">Transfer</option>
                            <option value="Further Studies">Further Studies</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Copies</label>
                        <input type="number" id="transcriptCopies" class="form-control" value="1" min="1" max="10">
                    </div>
                    <div id="transcriptGenerateResult" style="display:none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('transcriptModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" id="transcriptGenerateBtn" onclick="generateTranscriptAjax()" disabled><i class="fas fa-file-pdf"></i> Generate Transcript</button>
                </div>
            </div>
        </div>

        <!-- Certificate Generation Modal (AJAX) -->
        <div class="modal" id="certificateModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5><i class="fas fa-award"></i> Generate Professional Certificate</h5>
                    <button class="close" onclick="closeModal('certificateModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Search Student *</label>
                        <div class="student-lookup-wrapper">
                            <input type="text" id="certStudentSearch" class="form-control" placeholder="Type name, reg no, or phone..." onkeyup="certLookup(this.value)" autocomplete="off">
                            <input type="hidden" id="certStudentId" value="">
                            <div id="certStudentResults" class="lookup-results"></div>
                        </div>
                        <small class="form-text text-muted">Search by name, student number, registration number, or phone.</small>
                    </div>
                    <div id="certSelectedStudent" class="selected-student-info" style="display:none;padding:8px 12px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;margin-bottom:12px">
                        <strong id="certSelectedName"></strong>
                        <span id="certSelectedReg" class="text-muted" style="font-size:12px"></span>
                    </div>
                    <div class="form-group">
                        <label>Certificate Type</label>
                        <select id="certType" class="form-control">
                            <option value="Certificate">Certificate</option>
                            <option value="Diploma">Diploma</option>
                            <option value="Degree">Degree</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Graduation Date</label>
                        <input type="date" id="certGradDate" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Class of award will be auto-determined from GPA.</div>
                    <div id="certGenerateResult" style="display:none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('certificateModal')">Cancel</button>
                    <button type="button" class="btn btn-success" id="certGenerateBtn" onclick="generateCertificateAjax()" disabled><i class="fas fa-award"></i> Generate Certificate</button>
                </div>
            </div>
        </div>

        <!-- Document Preview Modal -->
        <div class="modal" id="documentPreviewModal">
            <div class="modal-dialog modal-xl" style="max-width:95%;height:95vh">
                <div class="modal-header">
                    <h5 id="docPreviewTitle"><i class="fas fa-file-alt"></i> Document Preview</h5>
                    <button class="close" onclick="closeModal('documentPreviewModal')">&times;</button>
                </div>
                <div class="modal-body" style="padding:0;height:calc(95vh - 60px);overflow:hidden">
                    <iframe id="docPreviewFrame" style="width:100%;height:100%;border:none"></iframe>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('documentPreviewModal')">Close</button>
                    <button class="btn btn-primary" onclick="previewPrint()"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
        </div>

        <!-- Quick Student Create Modal (on-the-fly) -->
        <div class="modal" id="quickCreateStudentModal">
            <div class="modal-dialog modal-sm">
                <div class="modal-header">
                    <h5><i class="fas fa-user-plus"></i> Create Student</h5>
                    <button class="close" onclick="closeModal('quickCreateStudentModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Student not found? Create one on the fly.</p>
                    <input type="hidden" id="quickCreateCallback" value="">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" id="qcFirstName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Surname *</label>
                            <input type="text" id="qcSurname" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Other Name</label>
                        <input type="text" id="qcOtherName" class="form-control">
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Gender</label>
                            <select id="qcGender" class="form-control">
                                <option>Male</option><option>Female</option><option>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Course/Program</label>
                            <input type="text" id="qcCourse" class="form-control" placeholder="e.g. Nursing">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" id="qcPhone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="qcEmail" class="form-control">
                        </div>
                    </div>
                    <div id="qcResult" style="display:none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('quickCreateStudentModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="quickCreateStudent()"><i class="fas fa-save"></i> Create &amp; Select</button>
                </div>
            </div>
        </div>

        <!-- View Student Modal -->
        <div class="modal" id="viewStudentModal">
            <div class="modal-dialog modal-lg" style="max-width:900px">
                <div class="modal-header">
                    <h5><i class="fas fa-user-graduate"></i> Student Profile</h5>
                    <button class="close" onclick="closeModal('viewStudentModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="viewStudentContent">
                        <div class="text-center" style="padding:40px"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('viewStudentModal')">Close</button>
                    <button class="btn btn-primary" id="viewPrintBtn" onclick="printCurrentStudent()"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
        </div>

        <!-- Trash Confirmation Modal -->
        <div class="modal" id="trashModal">
            <div class="modal-dialog modal-sm">
                <div class="modal-header">
                    <h5>Move to Trash</h5>
                    <button class="close" onclick="closeModal('trashModal')">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="trash_student">
                        <input type="hidden" name="id" id="trashStudentId">
                        <p>Are you sure you want to move <strong id="trashStudentName"></strong> to the recycle bin?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('trashModal')">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Move to Trash</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Modal functions
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.modal').forEach(function(m) {
                if (e.target === m) m.classList.remove('active');
            });
        });

        // Section switching (used by sidebar)
        var currentSection = 'overview';
        function switchToSection(section) {
            document.querySelectorAll('.dashboard-section').forEach(function(el) {
                el.classList.remove('active');
            });
            var target = document.querySelector('.dashboard-section[data-section="' + section + '"]');
            if (target) {
                target.classList.add('active');
                currentSection = section;
                window.location.hash = section;
            }
        }
        // On hash change
        window.addEventListener('hashchange', function() {
            var hash = window.location.hash.replace('#','');
            if (hash) switchToSection(hash);
        });
        // On load, check hash
        (function() {
            var hash = window.location.hash.replace('#','');
            if (hash) {
                var target = document.querySelector('.dashboard-section[data-section="' + hash + '"]');
                if (target) {
                    document.querySelectorAll('.dashboard-section').forEach(function(el) { el.classList.remove('active'); });
                    target.classList.add('active');
                    currentSection = hash;
                }
            }
        })();

        // Student search & filter
        function filterStudents() {
            var input = document.getElementById('studentSearch');
            var statusFilter = document.getElementById('statusFilter');
            var courseFilter = document.getElementById('courseFilter');
            var filter = (input ? input.value : '').toLowerCase();
            var statusVal = statusFilter ? statusFilter.value : '';
            var courseVal = courseFilter ? courseFilter.value : '';
            var table = document.getElementById('studentTable');
            if (!table) return;
            var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                if (cells.length < 2) continue;
                var text = '';
                for (var j = 0; j < cells.length; j++) {
                    text += cells[j].textContent.toLowerCase() + ' ';
                }
                var rowStatus = cells.length > 7 ? cells[7].textContent.trim() : '';
                var rowCourse = cells.length > 3 ? cells[3].textContent.trim() : '';
                var match = text.indexOf(filter) > -1;
                if (statusVal && rowStatus !== statusVal) match = false;
                if (courseVal && rowCourse !== courseVal) match = false;
                rows[i].style.display = match ? '' : 'none';
            }
        }

        // Generic table filter
        function filterTable(searchId, tableId) {
            var input = document.getElementById(searchId);
            var filter = input ? input.value.toLowerCase() : '';
            var table = document.getElementById(tableId);
            if (!table) return;
            var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                var text = rows[i].textContent.toLowerCase();
                rows[i].style.display = text.indexOf(filter) > -1 ? '' : 'none';
            }
        }

        // Edit student (populate modal)
        var studentsData = <?= json_encode($allStudents) ?>;
        function editStudent(id) {
            var s = studentsData.find(function(st) { return parseInt(st.id) === parseInt(id); });
            if (!s) { alert('Student not found'); return; }
            document.getElementById('editId').value = s.id;
            document.getElementById('editFirstName').value = s.first_name || '';
            document.getElementById('editOtherName').value = s.other_name || '';
            document.getElementById('editSurname').value = s.surname || '';
            document.getElementById('editCourse').value = s.course || '';
            document.getElementById('editYear').value = s.current_year || 1;
            document.getElementById('editSemester').value = s.current_semester || 'Semester 1';
            document.getElementById('editPhone').value = s.phone || '';
            document.getElementById('editEmail').value = s.email || '';
            document.getElementById('editStatus').value = s.status || 'Active';
            openModal('editStudentModal');
        }

        // Payment modal with student pre-selected
        function addPayment(id) {
            var sel = document.getElementById('payStudentId');
            if (sel) { sel.value = id; }
            openModal('addPaymentModal');
        }

        // Course registration with student pre-selected
        function courseReg(id) {
            var sel = document.querySelector('#courseRegModal select[name="student_id"]');
            if (sel) { sel.value = id; }
            openModal('courseRegModal');
        }

        // View student profile in modal
        var currentViewStudentId = 0;
        function viewStudent(id) {
            currentViewStudentId = id;
            document.getElementById('viewStudentContent').innerHTML = '<div class="text-center" style="padding:40px"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading...</div>';
            openModal('viewStudentModal');
            loadStudentView(id);
        }
        function loadStudentView(id) {
            var s = studentsData.find(function(st) { return parseInt(st.id) === parseInt(id); });
            if (!s) { document.getElementById('viewStudentContent').innerHTML = '<div class="text-center text-danger">Student not found.</div>'; return; }
            var html = '<div class="student-profile">';
            // Bio card
            html += '<div class="profile-bio" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;padding:16px;background:#f8f9fa;border-radius:8px;">';
            html += '<div><strong>Student No:</strong> ' + escHtml(s.student_number||'') + '</div>';
            html += '<div><strong>Reg No:</strong> ' + escHtml(s.registration_number||'') + '</div>';
            html += '<div><strong>Name:</strong> ' + escHtml(s.full_name||'') + '</div>';
            html += '<div><strong>Program:</strong> ' + escHtml(s.course||'') + '</div>';
            html += '<div><strong>Year:</strong> ' + (s.current_year||'') + '</div>';
            html += '<div><strong>Semester:</strong> ' + escHtml(s.current_semester||'') + '</div>';
            html += '<div><strong>Gender:</strong> ' + escHtml(s.gender||'') + '</div>';
            html += '<div><strong>Set:</strong> ' + escHtml(s.set_name||'') + '</div>';
            html += '<div><strong>Phone:</strong> ' + escHtml(s.phone||'') + '</div>';
            html += '<div><strong>Email:</strong> ' + escHtml(s.email||'') + '</div>';
            html += '<div><strong>Status:</strong> <span class="badge badge-' + (s.status==='Active'?'success':(s.status==='Pending'?'warning':'danger')) + '">' + escHtml(s.status||'') + '</span></div>';
            html += '<div><strong>Intake:</strong> ' + escHtml(s.intake_date||'') + '</div>';
            html += '</div>';
            // Tabs
            html += '<ul class="view-tabs" style="display:flex;gap:4px;list-style:none;padding:0;margin:0 0 16px;border-bottom:2px solid #e2e8f0;">';
            html += '<li class="view-tab active" data-tab="financial" onclick="switchViewTab(this,\'financial\','+id+')" style="padding:8px 16px;cursor:pointer;border-bottom:2px solid #3b82f6;margin-bottom:-2px;font-weight:600;color:#3b82f6;"><i class="fas fa-money-bill"></i> Financial</li>';
            html += '<li class="view-tab" data-tab="results" onclick="switchViewTab(this,\'results\','+id+')" style="padding:8px 16px;cursor:pointer;color:#64748b;"><i class="fas fa-chart-line"></i> Results</li>';
            html += '<li class="view-tab" data-tab="attendance" onclick="switchViewTab(this,\'attendance\','+id+')" style="padding:8px 16px;cursor:pointer;color:#64748b;"><i class="fas fa-calendar-check"></i> Attendance</li>';
            html += '<li class="view-tab" data-tab="courses" onclick="switchViewTab(this,\'courses\','+id+')" style="padding:8px 16px;cursor:pointer;color:#64748b;"><i class="fas fa-book"></i> Courses</li>';
            html += '<li class="view-tab" data-tab="documents" onclick="switchViewTab(this,\'documents\','+id+')" style="padding:8px 16px;cursor:pointer;color:#64748b;"><i class="fas fa-file-alt"></i> Documents</li>';
            html += '</ul><div id="viewTabContent"><div class="text-center text-muted" style="padding:20px"><i class="fas fa-spinner fa-spin"></i> Loading financial data...</div></div></div>';
            document.getElementById('viewStudentContent').innerHTML = html;
            loadViewTab('financial', id);
        }
        function escHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        function switchViewTab(el, tab, id) {
            document.querySelectorAll('.view-tab').forEach(function(t) { t.style.borderBottomColor = 'transparent'; t.style.color = '#64748b'; t.style.fontWeight = '400'; });
            el.style.borderBottomColor = '#3b82f6'; el.style.color = '#3b82f6'; el.style.fontWeight = '600';
            document.getElementById('viewTabContent').innerHTML = '<div class="text-center text-muted" style="padding:20px"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            loadViewTab(tab, id);
        }
        function loadViewTab(tab, id) {
            var urlMap = { financial: 'get_financial', results: 'get_results', attendance: 'get_attendance', courses: 'get_courses', documents: 'get_documents' };
            var endpoint = urlMap[tab] || 'get_financial';
            fetch('academic-registrar.php?ajax=' + endpoint + '&student_id=' + id)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var html = '';
                    if (tab === 'financial') {
                        if (data.invoices && data.invoices.length) {
                            html += '<h6 style="margin:0 0 8px;font-weight:600">Invoices</h6><table class="table"><thead><tr><th>Invoice</th><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th></tr></thead><tbody>';
                            data.invoices.forEach(function(inv) {
                                var bal = parseFloat(inv.total_amount) - parseFloat(inv.amount_paid);
                                html += '<tr><td>' + escHtml(inv.invoice_number) + '</td><td>' + escHtml(inv.fee_type) + '</td><td>' + formatNum(inv.total_amount) + '</td><td>' + formatNum(inv.amount_paid) + '</td><td>' + formatNum(bal) + '</td><td><span class="badge badge-' + (inv.status==='Paid'?'success':(inv.status==='Pending'?'warning':'danger')) + '">' + escHtml(inv.status) + '</span></td><td>' + (inv.due_date||'') + '</td></tr>';
                            });
                            html += '</tbody></table>';
                        } else { html += '<p class="text-muted">No invoice records.</p>'; }
                        if (data.payments && data.payments.length) {
                            html += '<h6 style="margin:12px 0 8px;font-weight:600">Payments</h6><table class="table"><thead><tr><th>Ref</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>';
                            data.payments.forEach(function(p) {
                                html += '<tr><td>' + escHtml(p.payment_reference) + '</td><td>' + formatNum(p.amount_received) + '</td><td>' + escHtml(p.payment_method) + '</td><td>' + (p.payment_date||'') + '</td><td><span class="badge badge-success">' + escHtml(p.status) + '</span></td></tr>';
                            });
                            html += '</tbody></table>';
                        }
                        html += '<div style="display:flex;gap:16px;margin-top:12px;padding:12px;background:#f0fdf4;border-radius:8px;"><div><strong>Total Invoiced:</strong> ' + formatNum(data.total_invoiced) + '</div><div><strong>Total Paid:</strong> ' + formatNum(data.total_paid) + '</div><div><strong>Balance:</strong> ' + formatNum(data.balance) + '</div></div>';
                    } else if (tab === 'results') {
                        if (data && data.length) {
                            html += '<table class="table"><thead><tr><th>Exam</th><th>Type</th><th>Course</th><th>CA</th><th>Exam</th><th>Marks</th><th>Grade</th><th>Status</th></tr></thead><tbody>';
                            data.forEach(function(r) {
                                html += '<tr><td>' + escHtml(r.exam_number||'') + '</td><td>' + escHtml(r.exam_type||'') + '</td><td>' + escHtml(r.course_code||'') + '</td><td>' + (r.continuous_assessment_marks||'-') + '</td><td>' + (r.final_exam_marks||'-') + '</td><td>' + (r.marks_obtained||'') + '/' + (r.total_marks||'') + '</td><td><strong>' + (r.grade||'-') + '</strong></td><td><span class="badge badge-' + (r.grade_status==='Approved'?'success':'info') + '">' + escHtml(r.grade_status||'Pending') + '</span></td></tr>';
                            });
                            html += '</tbody></table>';
                        } else { html = '<p class="text-muted">No results recorded.</p>'; }
                    } else if (tab === 'attendance') {
                        if (data && data.length) {
                            html += '<table class="table"><thead><tr><th>Date</th><th>Subject</th><th>Course</th><th>Status</th><th>Remarks</th></tr></thead><tbody>';
                            data.forEach(function(a) {
                                html += '<tr><td>' + (a.date||'') + '</td><td>' + escHtml(a.subject||'') + '</td><td>' + escHtml(a.course_code||'') + '</td><td><span class="badge badge-' + (a.status==='Present'?'success':(a.status==='Absent'?'danger':'warning')) + '">' + escHtml(a.status) + '</span></td><td>' + escHtml(a.remarks||'') + '</td></tr>';
                            });
                            html += '</tbody></table>';
                        } else { html = '<p class="text-muted">No attendance records.</p>'; }
                    } else if (tab === 'courses') {
                        if (data && data.length) {
                            html += '<table class="table"><thead><tr><th>Course Code</th><th>Course Name</th><th>Year</th><th>Semester</th><th>Status</th><th>Date</th></tr></thead><tbody>';
                            data.forEach(function(c) {
                                html += '<tr><td>' + escHtml(c.course_code||'') + '</td><td>' + escHtml(c.course_name||'') + '</td><td>' + escHtml(c.academic_year||'') + '</td><td>' + escHtml(c.semester||'') + '</td><td><span class="badge badge-' + (c.status==='Registered'?'success':'info') + '">' + escHtml(c.status) + '</span></td><td>' + (c.registration_date||'') + '</td></tr>';
                            });
                            html += '</tbody></table>';
                        } else { html = '<p class="text-muted">No course registrations.</p>'; }
                    } else if (tab === 'documents') {
                        if (data && data.length) {
                            html += '<table class="table"><thead><tr><th>Title</th><th>Type</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
                            data.forEach(function(d) {
                                var viewLink = d.file_path ? '<a href="' + escHtml(d.file_path) + '" target="_blank" class="btn btn-info btn-xs"><i class="fas fa-eye"></i> View</a>' : '';
                                html += '<tr><td>' + escHtml(d.document_title||'') + '</td><td>' + escHtml(d.document_type||'') + '</td><td>' + (d.generation_date||'') + '</td><td>' + viewLink + '</td></tr>';
                            });
                            html += '</tbody></table>';
                        } else { html = '<p class="text-muted">No documents uploaded.</p>'; }
                    }
                    document.getElementById('viewTabContent').innerHTML = html;
                })
                .catch(function(err) {
                    document.getElementById('viewTabContent').innerHTML = '<p class="text-danger">Failed to load data.</p>';
                });
        }
        function formatNum(n) {
            n = parseFloat(n||0);
            return Number(n).toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
        }
        // Print student
        function printStudent(id) {
            var s = studentsData.find(function(st) { return parseInt(st.id) === parseInt(id); });
            if (!s) { alert('Student not found'); return; }
            window.open('academic-registrar.php?report=print_student&student_id=' + id, '_blank');
        }
        function printCurrentStudent() {
            if (currentViewStudentId > 0) { printStudent(currentViewStudentId); }
        }

        // Export table
        function exportTable(tableId) {
            var table = document.getElementById(tableId);
            if (!table) return;
            var csv = [];
            var rows = table.querySelectorAll('thead tr, tbody tr');
            for (var i = 0; i < rows.length; i++) {
                var row = [];
                var cells = rows[i].querySelectorAll('th, td');
                for (var j = 0; j < cells.length; j++) {
                    row.push('"' + cells[j].textContent.trim().replace(/"/g,'""') + '"');
                }
                csv.push(row.join(','));
            }
            var blob = new Blob([csv.join('\n')], {type: 'text/csv;charset=utf-8;'});
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = tableId + '_export.csv';
            link.click();
        }

        // Export visible table as CSV
        function exportAsCSV() {
            var table = document.querySelector('.dashboard-section.active .table');
            if (!table) { alert('No active table found.'); return; }
            var csv = [];
            var rows = table.querySelectorAll('thead tr, tbody tr');
            for (var i = 0; i < rows.length; i++) {
                var row = [];
                var cells = rows[i].querySelectorAll('th, td');
                for (var j = 0; j < cells.length; j++) {
                    row.push('"' + cells[j].textContent.trim().replace(/"/g,'""') + '"');
                }
                csv.push(row.join(','));
            }
            var blob = new Blob(['\uFEFF' + csv.join('\n')], {type: 'text/csv;charset=utf-8;'});
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            var d = new Date();
            link.download = 'report_' + d.getFullYear() + (d.getMonth()+1) + d.getDate() + '.csv';
            link.click();
        }

        // Generate report (redirect)
        function generateReport() {
            var sel = document.getElementById('reportSelector');
            if (sel && sel.value) {
                window.location.href = '?report=' + sel.value;
            }
        }

        // Filter audit timeline
        function filterAudit() {
            var sel = document.getElementById('auditFilter');
            var val = sel ? sel.value.toLowerCase() : '';
            var items = document.querySelectorAll('#auditTimeline .timeline-item');
            items.forEach(function(item) {
                var text = item.textContent.toLowerCase();
                item.style.display = val ? (text.indexOf(val) > -1 ? '' : 'none') : '';
            });
        }

        // Grade approval modal data
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-exam-id]');
            if (btn) {
                var examId = btn.getAttribute('data-exam-id');
                var student = btn.getAttribute('data-student');
                if (document.getElementById('approveStudentId')) {
                    document.getElementById('approveStudentId').value = examId;
                }
                if (document.getElementById('approveStudentName')) {
                    document.getElementById('approveStudentName').textContent = student || '';
                }
            }
        });

        // Edit marks modal data
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-exam-id]');
            if (btn && btn.getAttribute('data-marks') !== null) {
                var examId = btn.getAttribute('data-exam-id');
                var marks = btn.getAttribute('data-marks');
                var grade = btn.getAttribute('data-grade');
                if (document.getElementById('editExamId')) {
                    document.getElementById('editExamId').value = examId;
                }
                if (document.getElementById('editTotalMarks')) {
                    document.getElementById('editTotalMarks').value = marks;
                }
                if (document.getElementById('editGrade')) {
                    document.getElementById('editGrade').value = grade || '';
                }
            }
        });

        // ============================================================
        // AJAX Document Generator Functions
        // ============================================================

        // ---- Student Lookup (Transcript) ----
        var transcriptLookupTimer = null;
        function transcriptLookup(q) {
            clearTimeout(transcriptLookupTimer);
            var resultsEl = document.getElementById('transcriptStudentResults');
            if (q.length < 2) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; return; }
            transcriptLookupTimer = setTimeout(function() {
                fetch('../ajax/registrar_documents_ajax.php?action=lookup_student&q=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!data || !data.length) {
                            resultsEl.innerHTML = '<div class="lookup-item lookup-none">No students found. <a href="#" onclick="openQuickCreate(\'transcript\');return false">Create on the fly?</a></div>';
                            resultsEl.style.display = 'block';
                            return;
                        }
                        var html = '';
                        data.forEach(function(s) {
                            html += '<div class="lookup-item" onclick="transcriptSelectStudent(' + s.id + ',\'' + escJs(s.full_name) + '\',\'' + escJs(s.registration_number||s.student_number) + '\')">';
                            html += '<strong>' + escHtml(s.full_name) + '</strong>';
                            html += '<span class="text-muted" style="font-size:11px;display:block">' + escHtml(s.registration_number||s.student_number) + ' | ' + escHtml(s.course) + '</span>';
                            html += '</div>';
                        });
                        html += '<div class="lookup-item lookup-create" onclick="openQuickCreate(\'transcript\')"><i class="fas fa-plus-circle"></i> Create new student...</div>';
                        resultsEl.innerHTML = html;
                        resultsEl.style.display = 'block';
                    });
            }, 300);
        }
        function transcriptSelectStudent(id, name, reg) {
            document.getElementById('transcriptStudentId').value = id;
            document.getElementById('transcriptStudentSearch').value = name;
            document.getElementById('transcriptStudentResults').style.display = 'none';
            document.getElementById('transcriptSelectedStudent').style.display = 'block';
            document.getElementById('transcriptSelectedName').textContent = name;
            document.getElementById('transcriptSelectedReg').textContent = reg;
            document.getElementById('transcriptGenerateBtn').disabled = false;
        }

        // ---- Student Lookup (Certificate) ----
        var certLookupTimer = null;
        function certLookup(q) {
            clearTimeout(certLookupTimer);
            var resultsEl = document.getElementById('certStudentResults');
            if (q.length < 2) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; return; }
            certLookupTimer = setTimeout(function() {
                fetch('../ajax/registrar_documents_ajax.php?action=lookup_student&q=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!data || !data.length) {
                            resultsEl.innerHTML = '<div class="lookup-item lookup-none">No students found. <a href="#" onclick="openQuickCreate(\'cert\');return false">Create on the fly?</a></div>';
                            resultsEl.style.display = 'block';
                            return;
                        }
                        var html = '';
                        data.forEach(function(s) {
                            html += '<div class="lookup-item" onclick="certSelectStudent(' + s.id + ',\'' + escJs(s.full_name) + '\',\'' + escJs(s.registration_number||s.student_number) + '\')">';
                            html += '<strong>' + escHtml(s.full_name) + '</strong>';
                            html += '<span class="text-muted" style="font-size:11px;display:block">' + escHtml(s.registration_number||s.student_number) + ' | ' + escHtml(s.course) + '</span>';
                            html += '</div>';
                        });
                        html += '<div class="lookup-item lookup-create" onclick="openQuickCreate(\'cert\')"><i class="fas fa-plus-circle"></i> Create new student...</div>';
                        resultsEl.innerHTML = html;
                        resultsEl.style.display = 'block';
                    });
            }, 300);
        }
        function certSelectStudent(id, name, reg) {
            document.getElementById('certStudentId').value = id;
            document.getElementById('certStudentSearch').value = name;
            document.getElementById('certStudentResults').style.display = 'none';
            document.getElementById('certSelectedStudent').style.display = 'block';
            document.getElementById('certSelectedName').textContent = name;
            document.getElementById('certSelectedReg').textContent = reg;
            document.getElementById('certGenerateBtn').disabled = false;
        }

        // ---- Quick Create Student (on-the-fly) ----
        var quickCreateTarget = '';
        function openQuickCreate(target) {
            quickCreateTarget = target;
            document.getElementById('qcFirstName').value = '';
            document.getElementById('qcSurname').value = '';
            document.getElementById('qcOtherName').value = '';
            document.getElementById('qcGender').value = 'Male';
            document.getElementById('qcCourse').value = '';
            document.getElementById('qcPhone').value = '';
            document.getElementById('qcEmail').value = '';
            document.getElementById('qcResult').style.display = 'none';
            // Pre-fill search term if available
            var searchVal = target === 'transcript'
                ? document.getElementById('transcriptStudentSearch').value
                : document.getElementById('certStudentSearch').value;
            var parts = searchVal.split(' ');
            if (parts.length >= 2) {
                document.getElementById('qcFirstName').value = parts[0];
                document.getElementById('qcSurname').value = parts.slice(1).join(' ');
            } else if (parts.length === 1) {
                document.getElementById('qcSurname').value = parts[0];
            }
            closeModal(target === 'transcript' ? 'transcriptModal' : 'certificateModal');
            openModal('quickCreateStudentModal');
        }
        function quickCreateStudent() {
            var fn = document.getElementById('qcFirstName').value.trim();
            var sn = document.getElementById('qcSurname').value.trim();
            if (!fn || !sn) { alert('First name and surname required.'); return; }
            var data = new URLSearchParams();
            data.append('first_name', fn);
            data.append('surname', sn);
            data.append('other_name', document.getElementById('qcOtherName').value.trim());
            data.append('gender', document.getElementById('qcGender').value);
            data.append('course', document.getElementById('qcCourse').value.trim());
            data.append('phone', document.getElementById('qcPhone').value.trim());
            data.append('email', document.getElementById('qcEmail').value.trim());
            document.getElementById('qcResult').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Creating...</div>';
            document.getElementById('qcResult').style.display = 'block';
            fetch('../ajax/registrar_documents_ajax.php?action=create_student', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: data.toString()
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    var s = res.student;
                    document.getElementById('qcResult').innerHTML = '<div class="alert alert-success">Created: ' + escHtml(s.full_name) + '</div>';
                    // Select in the appropriate modal
                    if (quickCreateTarget === 'transcript') {
                        transcriptSelectStudent(s.id, s.full_name, s.registration_number || s.student_number);
                        openModal('transcriptModal');
                    } else {
                        certSelectStudent(s.id, s.full_name, s.registration_number || s.student_number);
                        openModal('certificateModal');
                    }
                    closeModal('quickCreateStudentModal');
                } else {
                    document.getElementById('qcResult').innerHTML = '<div class="alert alert-danger">' + escHtml(res.error) + '</div>';
                }
            })
            .catch(function(err) {
                document.getElementById('qcResult').innerHTML = '<div class="alert alert-danger">Error creating student.</div>';
            });
        }

        // ---- AJAX Transcript Generation ----
        function generateTranscriptAjax() {
            var sid = document.getElementById('transcriptStudentId').value;
            if (!sid) { alert('Please select a student.'); return; }
            var btn = document.getElementById('transcriptGenerateBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            var resultEl = document.getElementById('transcriptGenerateResult');
            resultEl.style.display = 'block';
            resultEl.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Generating professional transcript...</div>';
            fetch('../ajax/registrar_documents_ajax.php?action=generate_transcript&student_id=' + sid)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        resultEl.innerHTML = '<div class="alert alert-success">'
                            + '<i class="fas fa-check-circle"></i> ' + escHtml(res.message)
                            + '<br><strong>Ref:</strong> ' + escHtml(res.transcript_number)
                            + '<br><button class="btn btn-sm btn-primary mt-2" onclick="previewDocument(' + res.doc_id + ')"><i class="fas fa-eye"></i> Preview</button>'
                            + ' <button class="btn btn-sm btn-success mt-2" onclick="window.open(\'../ajax/registrar_documents_ajax.php?action=preview_document&doc_id=' + res.doc_id + '\',\'_blank\')"><i class="fas fa-external-link-alt"></i> Open in New Tab</button>'
                            + '</div>';
                    } else {
                        resultEl.innerHTML = '<div class="alert alert-danger">' + escHtml(res.error||'Generation failed.') + '</div>';
                    }
                })
                .catch(function(err) {
                    resultEl.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-file-pdf"></i> Generate Transcript';
                });
        }

        // ---- AJAX Certificate Generation ----
        function generateCertificateAjax() {
            var sid = document.getElementById('certStudentId').value;
            if (!sid) { alert('Please select a student.'); return; }
            var btn = document.getElementById('certGenerateBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            var resultEl = document.getElementById('certGenerateResult');
            resultEl.style.display = 'block';
            resultEl.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Generating professional certificate...</div>';
            var certType = document.getElementById('certType').value;
            fetch('../ajax/registrar_documents_ajax.php?action=generate_certificate&student_id=' + sid + '&cert_type=' + encodeURIComponent(certType))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        resultEl.innerHTML = '<div class="alert alert-success">'
                            + '<i class="fas fa-check-circle"></i> ' + escHtml(res.message)
                            + '<br><strong>Ref:</strong> ' + escHtml(res.certificate_number)
                            + '<br><button class="btn btn-sm btn-primary mt-2" onclick="previewDocument(' + res.doc_id + ')"><i class="fas fa-eye"></i> Preview</button>'
                            + ' <button class="btn btn-sm btn-success mt-2" onclick="window.open(\'../ajax/registrar_documents_ajax.php?action=preview_document&doc_id=' + res.doc_id + '\',\'_blank\')"><i class="fas fa-external-link-alt"></i> Open in New Tab</button>'
                            + '</div>';
                    } else {
                        resultEl.innerHTML = '<div class="alert alert-danger">' + escHtml(res.error||'Generation failed.') + '</div>';
                    }
                })
                .catch(function(err) {
                    resultEl.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-award"></i> Generate Certificate';
                });
        }

        // ---- Preview Document ----
        function previewDocument(docId) {
            if (!docId) return;
            document.getElementById('docPreviewFrame').src = '../ajax/registrar_documents_ajax.php?action=preview_document&doc_id=' + docId;
            document.getElementById('docPreviewTitle').innerHTML = '<i class="fas fa-file-alt"></i> Document Preview';
            openModal('documentPreviewModal');
        }
        function previewPrint() {
            var frame = document.getElementById('docPreviewFrame');
            if (frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }
        }

        // ---- Auto-Generate All (both transcript and certificate) ----
        function autoGenerateAll(studentId) {
            if (!confirm('Generate both Transcript and Certificate for this student?')) return;
            fetch('../ajax/registrar_documents_ajax.php?action=auto_generate_all&student_id=' + studentId)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        var msg = res.message + '\nTranscript: ' + res.transcript_number + '\nCertificate: ' + res.certificate_number;
                        alert(msg);
                        location.reload();
                    } else {
                        alert('Error: ' + (res.error||'Generation failed'));
                    }
                })
                .catch(function() { alert('Network error.'); });
        }

        // ---- Escaping helpers ----
        function escJs(str) {
            if (!str) return '';
            return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
        }

        // Inject current section into all forms so redirects preserve context
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.tagName !== 'FORM') return;
            var existing = form.querySelector('input[name="_section"]');
            if (!existing) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_section';
                input.value = window.location.hash.replace('#', '') || 'overview';
                form.appendChild(input);
            }
        });

        // Close lookup dropdowns on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.student-lookup-wrapper')) {
                document.querySelectorAll('.lookup-results').forEach(function(el) {
                    el.style.display = 'none';
                });
            }
        });

        // Initialize DataTables on directory tables if available
        $(document).ready(function() {
            if ($.fn.DataTable) {
                $('.data-table').DataTable({ pageLength: 25, responsive: true });
            }
            flatpickr('input[type="date"]', { dateFormat: 'Y-m-d' });
        });
    </script>
    <?php include __DIR__ . '/../includes/dashboard_footer.php'; ?>
    <style>
        .dashboard-container { margin-left: 270px; width: calc(100vw - 270px); min-height: 100vh; padding: 20px 30px; box-sizing: border-box; background: #f8f9fa !important; display: block; }
        .main { margin-left: 0 !important; min-height: auto !important; flex: none !important; }
        .content-section { background: transparent !important; border: none !important; box-shadow: none !important; border-radius: 0 !important; padding: 0 !important; margin-bottom: 0 !important; }
        .content-section:hover { transform: none !important; }
        .content-section .page-header { margin-bottom: 24px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff; flex-shrink: 0; }
        .stat-card .stat-number { font-size: 1.8rem; font-weight: 700; color: #0f172a; line-height: 1; }
        .stat-card .stat-label { font-size: 0.75rem; color: #64748b; margin-top: 2px; }
        .section-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: none !important; }
        .section-card:hover { transform: none !important; box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
        .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        @media (max-width: 768px) { .dashboard-container { margin-left: 0; padding: 16px; } .grid-2 { grid-template-columns: 1fr; } }
        .btn-purple { background: #7c3aed; color: #fff; border: none; }
        .btn-purple:hover { background: #6d28d9; color: #fff; }
        /* Student Lookup Styles */
        .student-lookup-wrapper { position: relative; }
        .lookup-results { display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #d1d5db; border-radius: 0 0 8px 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); z-index: 1000; max-height: 250px; overflow-y: auto; }
        .lookup-item { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
        .lookup-item:hover { background: #eef2ff; }
        .lookup-item:last-child { border-bottom: none; border-radius: 0 0 8px 8px; }
        .lookup-none { color: #6b7280; font-size: 13px; }
        .lookup-create { color: #3b82f6; font-weight: 600; font-size: 13px; }
        .lookup-create i { margin-right: 6px; }
        .selected-student-info { animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
        /* Modal XL */
        .modal-dialog.modal-xl { max-width: 95%; width: 95%; }
    </style>
</body>
</html>
