<?php
// Additional helper functions for ISNM Student Management System

// Generate unique ID for various records
function generateUniqueId($prefix, $table, $field) {
    global $conn;
    
    do {
        $year = date('Y');
        $random = mt_rand(10000, 99999);
        $unique_id = "$prefix/$year/$random";
        
        $check_sql = "SELECT COUNT(*) as count FROM $table WHERE $field = ?";
        $check_result = executeQuery($check_sql, [$unique_id], 's');
    } while ($check_result[0]['count'] > 0);
    
    return $unique_id;
}

// Calculate age from date of birth
function calculateAge($date_of_birth) {
    if (empty($date_of_birth)) return '';
    
    $today = new DateTime();
    $dob = new DateTime($date_of_birth);
    $age = $today->diff($dob);
    
    return $age->y;
}

// Validate phone number (Uganda format)
function validatePhoneNumber($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it starts with Uganda country code or 0
    if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
        return '+256' . substr($phone, 1);
    } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '256') {
        return '+' . $phone;
    } elseif (strlen($phone) == 13 && substr($phone, 0, 4) == '+256') {
        return $phone;
    }
    
    return false;
}

// Send email notification
function sendEmail($to, $subject, $message, $from = 'iganganursingschool@gmail.com') {
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Generate academic calendar
function generateAcademicCalendar($year) {
    $calendar = [];
    
    // Define semesters
    $semesters = [
        'Semester 1' => ['start' => "$year-01-15", 'end' => "$year-05-15"],
        'Semester 2' => ['start' => "$year-08-15", 'end' => "$year-12-15"]
    ];
    
    foreach ($semesters as $semester => $dates) {
        $calendar[$semester] = [
            'start_date' => $dates['start'],
            'end_date' => $dates['end'],
            'exams_start' => date('Y-m-d', strtotime($dates['end'] . ' -2 weeks')),
            'exams_end' => $dates['end'],
            'break_start' => date('Y-m-d', strtotime($dates['end'] . ' +1 day')),
            'break_end' => date('Y-m-d', strtotime($dates['end'] . ' +2 weeks'))
        ];
    }
    
    return $calendar;
}

// Calculate GPA from grades
function calculateGPA($grades) {
    $grade_points = [
        'A' => 4.0,
        'B+' => 3.5,
        'B' => 3.0,
        'C+' => 2.5,
        'C' => 2.0,
        'D' => 1.0,
        'F' => 0.0
    ];
    
    $total_points = 0;
    $total_courses = 0;
    
    foreach ($grades as $grade) {
        if (isset($grade_points[$grade])) {
            $total_points += $grade_points[$grade];
            $total_courses++;
        }
    }
    
    return $total_courses > 0 ? round($total_points / $total_courses, 2) : 0.0;
}

// Get class performance statistics
function getClassPerformance($program, $year, $semester) {
    $sql = "SELECT COUNT(*) as total_students, AVG(gpa) as avg_gpa, MAX(gpa) as max_gpa, MIN(gpa) as min_gpa 
            FROM academic_records 
            WHERE program = ? AND year = ? AND semester = ?";
    
    $result = executeQuery($sql, [$program, $year, $semester], 'sis');
    return $result[0] ?? null;
}

// Generate student report card
function generateReportCard($student_id, $year, $semester) {
    $sql = "SELECT ar.*, s.first_name, s.surname, s.program 
            FROM academic_records ar 
            JOIN students s ON ar.student_id = s.student_id 
            WHERE ar.student_id = ? AND ar.year = ? AND ar.semester = ?";
    
    $result = executeQuery($sql, [$student_id, $year, $semester], 'sis');
    return $result[0] ?? null;
}

// Check fee payment status
function checkFeeStatus($student_id, $academic_year) {
    $sql = "SELECT * FROM student_fee_accounts 
            WHERE student_id = ? AND academic_year = ? 
            ORDER BY year DESC, semester DESC";
    
    $result = executeQuery($sql, [$student_id, $academic_year], 'ss');
    return $result;
}

// Generate receipt number
function generateReceiptNumber() {
    do {
        $receipt_no = 'RCP' . date('Y') . mt_rand(100000, 999999);
        $check_sql = "SELECT COUNT(*) as count FROM fee_payments WHERE receipt_number = ?";
        $check_result = executeQuery($check_sql, [$receipt_no], 's');
    } while ($check_result[0]['count'] > 0);
    
    return $receipt_no;
}

// Format currency
function formatCurrency($amount, $currency = 'UGX') {
    if ($currency === 'UGX') {
        return 'UGX ' . number_format($amount, 0);
    }
    return $currency . ' ' . number_format($amount, 2);
}

// Get attendance percentage
function calculateAttendance($student_id, $course_id, $semester) {
    $sql = "SELECT 
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
            COUNT(*) as total_sessions
            FROM attendance 
            WHERE student_id = ? AND course_id = ? AND semester = ?";
    
    $result = executeQuery($sql, [$student_id, $course_id, $semester], 'sis');
    $data = $result[0] ?? null;
    
    if ($data && $data['total_sessions'] > 0) {
        return round(($data['present'] / $data['total_sessions']) * 100, 1);
    }
    
    return 0;
}

// Get student timetable
function getStudentTimetable($student_id, $semester) {
    $sql = "SELECT t.*, c.course_name, c.course_code 
            FROM timetable t 
            JOIN courses c ON t.course_id = c.course_id 
            WHERE t.program = (SELECT program FROM students WHERE student_id = ?) 
            AND t.semester = ? 
            ORDER BY t.day_of_week, t.start_time";
    
    return executeQuery($sql, [$student_id, $semester], 'is');
}

// Check graduation eligibility
function checkGraduationEligibility($student_id) {
    $sql = "SELECT COUNT(*) as completed_courses, AVG(gpa) as cgpa 
            FROM academic_records 
            WHERE student_id = ? AND gpa IS NOT NULL";
    
    $result = executeQuery($sql, [$student_id], 's');
    $data = $result[0] ?? null;
    
    if ($data) {
        $program_sql = "SELECT program FROM students WHERE student_id = ?";
        $program_result = executeQuery($program_sql, [$student_id], 's');
        $program = $program_result[0]['program'] ?? '';
        
        $required_courses = getRequiredCourses($program);
        
        return [
            'eligible' => $data['completed_courses'] >= $required_courses && $data['cgpa'] >= 2.0,
            'completed_courses' => $data['completed_courses'],
            'required_courses' => $required_courses,
            'cgpa' => $data['cgpa']
        ];
    }
    
    return ['eligible' => false, 'completed_courses' => 0, 'required_courses' => 0, 'cgpa' => 0];
}

// Get required courses for a program
function getRequiredCourses($program) {
    $requirements = [
        'Certificate Nursing' => 12,
        'Certificate Midwifery' => 12,
        'Diploma Nursing' => 20,
        'Diploma Midwifery' => 20,
        'Diploma Nursing Extension' => 10,
        'Diploma Midwifery Extension' => 10
    ];
    
    return $requirements[$program] ?? 12;
}

// Create backup of database
function createDatabaseBackup() {
    $backup_file = 'backups/isnm_backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Create backup directory if it doesn't exist
    if (!is_dir('backups')) {
        mkdir('backups', 0755, true);
    }
    
    $command = "mysqldump --user=root --password= --host=localhost isnm_school > $backup_file";
    exec($command);
    
    return file_exists($backup_file) ? $backup_file : false;
}

// System health check
function systemHealthCheck() {
    $checks = [];
    
    // Database connection
    $checks['database'] = [
        'status' => $GLOBALS['conn']->ping() ? 'OK' : 'ERROR',
        'message' => $GLOBALS['conn']->ping() ? 'Database connected' : 'Database connection failed'
    ];
    
    // Session status
    $checks['session'] = [
        'status' => session_status() === PHP_SESSION_ACTIVE ? 'OK' : 'ERROR',
        'message' => 'Session ' . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive')
    ];
    
    // File permissions
    $checks['uploads'] = [
        'status' => is_writable('uploads/') ? 'OK' : 'ERROR',
        'message' => 'Uploads directory ' . (is_writable('uploads/') ? 'writable' : 'not writable')
    ];
    
    // Memory usage
    $checks['memory'] = [
        'status' => 'OK',
        'message' => 'Memory usage: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
    ];
    
    return $checks;
}

// Generate PDF report (requires mPDF library)
function generatePDFReport($data, $filename, $template) {
    // This would require mPDF or similar library
    // For now, return placeholder
    return [
        'status' => 'pending',
        'message' => 'PDF generation requires mPDF library installation',
        'filename' => $filename
    ];
}

// Send SMS notification (requires SMS gateway)
function sendSMS($phone, $message) {
    // This would require SMS gateway integration
    // For now, return placeholder
    return [
        'status' => 'pending',
        'message' => 'SMS sending requires gateway integration',
        'phone' => $phone
    ];
}

// Export data to Excel format
function exportToExcel($data, $filename) {
    // This would require PHPExcel or similar library
    // For now, return placeholder
    return [
        'status' => 'pending',
        'message' => 'Excel export requires PHPExcel library',
        'filename' => $filename
    ];
}

// Audit trail for sensitive operations
function auditTrail($user_id, $action, $details, $ip_address = null) {
    $ip_address = $ip_address ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $sql = "INSERT INTO audit_trail (user_id, action, details, ip_address, user_agent, timestamp) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $GLOBALS['conn']->prepare($sql);
    $stmt->bind_param("sssss", $user_id, $action, $details, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Validate user permissions for specific actions
function hasPermission($user_role, $permission) {
    $permissions = [
        'Director General' => ['all'],
        'Chief Executive Officer' => ['all'],
        'Director Academics' => ['students', 'academics', 'reports'],
        'Director ICT' => ['system', 'users', 'reports'],
        'Director Finance' => ['fees', 'finance', 'reports'],
        'School Principal' => ['students', 'academics', 'fees', 'reports'],
        'Deputy Principal' => ['students', 'academics'],
        'School Bursar' => ['fees', 'finance'],
        'Academic Registrar' => ['students', 'academics'],
        'HR Manager' => ['users', 'hr'],
        'Lecturers' => ['academics', 'students_view'],
        'Students' => ['profile', 'fees_view', 'academics_view']
    ];
    
    return in_array('all', $permissions[$user_role] ?? []) || 
           in_array($permission, $permissions[$user_role] ?? []);
}

// Get system statistics
function getSystemStatistics() {
    $stats = [];
    
    // Student statistics
    $stats['students'] = [
        'total' => executeQuery("SELECT COUNT(*) as count FROM students")[0]['count'],
        'active' => executeQuery("SELECT COUNT(*) as count FROM students WHERE status = 'active'")[0]['count'],
        'graduated' => executeQuery("SELECT COUNT(*) as count FROM students WHERE status = 'graduated'")[0]['count']
    ];
    
    // User statistics
    $stats['users'] = [
        'total' => executeQuery("SELECT COUNT(*) as count FROM users")[0]['count'],
        'active' => executeQuery("SELECT COUNT(*) as count FROM users WHERE status = 'active'")[0]['count']
    ];
    
    // Financial statistics
    $stats['finance'] = [
        'total_fees' => executeQuery("SELECT SUM(total_fees) as total FROM student_fee_accounts")[0]['total'] ?? 0,
        'total_paid' => executeQuery("SELECT SUM(amount_paid) as total FROM fee_payments")[0]['total'] ?? 0,
        'total_balance' => executeQuery("SELECT SUM(balance) as total FROM student_fee_accounts")[0]['total'] ?? 0
    ];
    
    return $stats;
}
?>
