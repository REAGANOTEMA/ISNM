<?php
// Additional helper functions for ISNM Student Management System

// Sanitize user input to prevent XSS attacks
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Validate email address
if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Validate phone number
if (!function_exists('validatePhone')) {
    function validatePhone($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a valid Uganda phone number (10 digits starting with 0, or 12 digits starting with 256)
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            return true;
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '256') {
            return true;
        } elseif (strlen($phone) == 13 && substr($phone, 0, 4) == '+256') {
            return true;
        }
        
        return false;
    }
}

// Validate index number
if (!function_exists('validateIndexNumber')) {
    function validateIndexNumber($indexNumber) {
        // Basic validation - should be alphanumeric and reasonable length
        return !empty($indexNumber) && strlen($indexNumber) >= 5 && strlen($indexNumber) <= 50;
    }
}

// Generate unique ID for various records
function generateUniqueId($prefix, $table, $field) {
    $allowed_tables = ['students', 'staffs', 'users', 'courses', 'departments'];
    $allowed_fields = ['id', 'student_id', 'staff_id', 'registration_number', 'index_number'];
    if (!in_array($table, $allowed_tables, true) || !in_array($field, $allowed_fields, true)) {
        return uniqid();
    }

    $conn = null;
    if (in_array($table, ['staffs', 'users', 'departments'])) {
        $conn = getStaffConnection();
    } else {
        $conn = getStudentsConnection();
    }

    if (!$conn) {
        return uniqid();
    }

    do {
        $year = date('Y');
        $random = mt_rand(10000, 99999);
        $unique_id = "$prefix/$year/$random";

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `$table` WHERE `$field` = ?");
        if (!$stmt) return uniqid();
        $stmt->bind_param('s', $unique_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (empty($result) || !isset($result['count'])) {
            return uniqid();
        }
    } while ($result['count'] > 0);

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
if (!function_exists('calculateGPA')) { function calculateGPA($grades) {
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
} }

// Get class performance statistics
function getClassPerformance($program, $year, $semester) {
    $sql = "SELECT COUNT(*) as total_students, AVG(gpa) as avg_gpa, MAX(gpa) as max_gpa, MIN(gpa) as min_gpa 
            FROM academic_records 
            WHERE program = ? AND year = ? AND semester = ?";
    
    $result = DatabaseConnection::executeQuery('students', $sql, [$program, $year, $semester], 'sis');
    return $result[0] ?? null;
}

// Generate student report card
function generateReportCard($student_id, $year, $semester) {
    $sql = "SELECT ar.*, s.first_name, s.surname, s.program 
            FROM academic_records ar 
            JOIN students s ON ar.student_id = s.student_id 
            WHERE ar.student_id = ? AND ar.year = ? AND ar.semester = ?";
    
    $result = DatabaseConnection::executeQuery('students', $sql, [$student_id, $year, $semester], 'sis');
    return $result[0] ?? null;
}

// Check fee payment status
function checkFeeStatus($student_id, $academic_year) {
    $sql = "SELECT * FROM student_fee_accounts 
            WHERE student_id = ? AND academic_year = ? 
            ORDER BY year DESC, semester DESC";
    
    $result = DatabaseConnection::executeQuery('students', $sql, [$student_id, $academic_year], 'ss');
    return $result;
}

// Generate receipt number
if (!function_exists('generateReceiptNumber')) {
function generateReceiptNumber() {
    do {
        $receipt_no = 'RCP' . date('Y') . mt_rand(100000, 999999);
        $check_sql = "SELECT COUNT(*) as count FROM fee_payments WHERE receipt_number = ?";
        $check_result = DatabaseConnection::executeQuery('students', $check_sql, [$receipt_no], 's');
        if (empty($check_result) || !isset($check_result[0]['count'])) {
            return $receipt_no;
        }
    } while ($check_result[0]['count'] > 0);
    
    return $receipt_no;
}
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
    
    $result = DatabaseConnection::executeQuery('students', $sql, [$student_id, $course_id, $semester], 'sis');
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
    
    return DatabaseConnection::executeQuery('students', $sql, [$student_id, $semester], 'is');
}

// Check graduation eligibility
function checkGraduationEligibility($student_id) {
    $sql = "SELECT COUNT(*) as completed_courses, AVG(gpa) as cgpa 
            FROM academic_records 
            WHERE student_id = ? AND gpa IS NOT NULL";
    
    $result = DatabaseConnection::executeQuery('students', $sql, [$student_id], 's');
    $data = $result[0] ?? null;
    
    if ($data) {
        $program_sql = "SELECT program FROM students WHERE student_id = ?";
        $program_result = DatabaseConnection::executeQuery('students', $program_sql, [$student_id], 's');
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
    
    $host = defined('STAFF_DB_HOST') ? STAFF_DB_HOST : 'localhost';
    $user = defined('STAFF_DB_USER') ? STAFF_DB_USER : 'root';
    $pass = defined('STAFF_DB_PASS') ? STAFF_DB_PASS : '';
    $db   = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'isnm_school';
    
    $command = sprintf('mysqldump --user=%s --host=%s --password=%s %s > %s', escapeshellarg($user), escapeshellarg($host), escapeshellarg($pass), escapeshellarg($db), escapeshellarg($backup_file));
    exec($command);
    
    return file_exists($backup_file) ? $backup_file : false;
}

// System health check
function systemHealthCheck() {
    $checks = [];
    $conn = function_exists('getStaffConnection') ? getStaffConnection() : ($GLOBALS['conn'] ?? null);
    
    // Database connection
    $checks['database'] = [
        'status' => $conn && $conn->ping() ? 'OK' : 'ERROR',
        'message' => $conn && $conn->ping() ? 'Database connected' : 'Database connection failed'
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
    
    $sql = "INSERT INTO staff_audit_logs (staff_id, action, old_values, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $GLOBALS['conn']->prepare($sql);
    $stmt->bind_param("issss", $user_id, $action, $details, $ip_address, $user_agent);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $stmt->close();
}

// Validate user permissions for specific actions
if (!function_exists('hasPermission')) {
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
}

// Get system statistics
if (!function_exists('getSystemStatistics')) {
function getSystemStatistics() {
    $stats = [];
    
    // Student statistics
    $stats['students'] = [
        'total' => 0,
        'active' => 0,
        'graduated' => 0
    ];
    try {
        $stats['students']['total'] = executeQuery('students', "SELECT COUNT(*) as count FROM students")[0]['count'] ?? 0;
        $stats['students']['active'] = executeQuery('students', "SELECT COUNT(*) as count FROM students WHERE status = 'Active'")[0]['count'] ?? 0;
        $stats['students']['graduated'] = executeQuery('students', "SELECT COUNT(*) as count FROM students WHERE status = 'Graduated'")[0]['count'] ?? 0;
    } catch (Exception $e) {
        error_log('Student stats error: ' . $e->getMessage());
    }
    
    // User statistics
    $stats['users'] = [
        'total' => 0,
        'active' => 0
    ];
    try {
        $stats['users']['total'] = executeQuery('staffs', "SELECT COUNT(*) as count FROM staff")[0]['count'] ?? 0;
        $stats['users']['active'] = executeQuery('staffs', "SELECT COUNT(*) as count FROM staff WHERE status = 'Active'")[0]['count'] ?? 0;
    } catch (Exception $e) {
        error_log('Staff stats error: ' . $e->getMessage());
    }
    
    // Financial statistics (defensive - tables may not exist)
    $stats['finance'] = [
        'total_fees' => 0,
        'total_paid' => 0,
        'total_balance' => 0
    ];
    try {
        $stats['finance']['total_fees'] = executeQuery('staffs', "SELECT SUM(amount) as total FROM fee_accounts")[0]['total'] ?? 0;
        $stats['finance']['total_paid'] = executeQuery('staffs', "SELECT SUM(amount) as total FROM payment_records WHERE status = 'Completed'")[0]['total'] ?? 0;
        $stats['finance']['total_balance'] = executeQuery('staffs', "SELECT SUM(balance) as total FROM fee_accounts")[0]['total'] ?? 0;
    } catch (Exception $e) {
        error_log('Finance stats error: ' . $e->getMessage());
    }
    
    return $stats;
}
}

/**
 * Move an item to the recycle bin (soft delete)
 * 
 * @param mysqli $conn        Staff DB connection
 * @param int    $original_id The ID of the item being deleted
 * @param string $table       Original table name
 * @param string $id_column   Primary key column name in original table
 * @param string $title       Display title for the recycled item
 * @param string $description Optional description
 * @param int    $deleted_by  Staff ID who deleted it
 * @return bool
 */
if (!function_exists('moveToTrash')) {
function moveToTrash($conn, int $original_id, string $table, string $id_column, string $title, string $description = '', int $deleted_by = 0): bool {
    try {
        if (!$conn) return false;
        $deleted_by_name = '';
        if ($deleted_by) {
            $q = $conn->prepare("SELECT full_name FROM staff WHERE id = ?");
            if ($q) { $q->bind_param('i', $deleted_by); if (!$q->execute()) { error_log('$q execute failed: ' . ($q->error ?? 'unknown')); }; $r = $q->get_result()->fetch_assoc(); $q->close(); if ($r) $deleted_by_name = $r['full_name']; }
        }
        $stmt = $conn->prepare("INSERT INTO recycle_bin (original_table, original_id_column, original_id, item_title, item_description, deleted_by, deleted_by_name, deleted_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) return false;
        $stmt->bind_param('ssissis', $table, $id_column, $original_id, $title, $description, $deleted_by, $deleted_by_name);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (Exception $e) {
        error_log('moveToTrash error: ' . $e->getMessage());
        return false;
    }
}
}

/**
 * Permanently delete from recycle bin
 */
if (!function_exists('emptyTrash')) {
function emptyTrash($conn): bool {
    try {
        if (!$conn) return false;
        return $conn->query("DELETE FROM recycle_bin WHERE deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    } catch (Exception $e) {
        error_log('functions.php: ' . $e->getMessage());
        return false;
    }
}
}

/**
 * Fetch official duties for a specific staff role from the database.
 * Falls back to empty array if the table doesn't exist or DB unavailable.
 *
 * @param int    $roleId   The staff_roles.id to fetch duties for
 * @param mysqli $conn     Optional staff DB connection (will try global if null)
 * @return array           Array of duties with 'duty_title', 'duty_icon', 'sort_order'
 */
if (!function_exists('getOfficialDuties')) {
function getOfficialDuties(int $roleId, $conn = null): array {
    if (!$conn) {
        if (function_exists('getStaffConnection')) {
            $conn = getStaffConnection();
        }
    }
    if (!$conn) return [];
    try {
        $stmt = $conn->prepare("SELECT duty_title, duty_icon, sort_order FROM official_duties WHERE role_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        if (!$stmt) return [];
        $stmt->bind_param('i', $roleId);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $duties = [];
        while ($row = $result->fetch_assoc()) {
            $duties[] = $row;
        }
        $stmt->close();
        return $duties;
    } catch (Exception $e) {
        error_log('getOfficialDuties error: ' . $e->getMessage());
        return [];
    }
}
}

/**
 * Render the Official Duties & Responsibilities section as HTML.
 * Displays duties from the database, or a fallback message if none found.
 *
 * @param int    $roleId   The staff_roles.id
 * @param mysqli $conn     Staff DB connection
 */
if (!function_exists('renderOfficialDuties')) {
function renderOfficialDuties(int $roleId, $conn) {
    $duties = getOfficialDuties($roleId, $conn);
    if (empty($duties)) return;
    ?>
    <div class="duties-grid">
        <?php foreach ($duties as $duty): ?>
        <div class="duty-card">
            <i class="<?= htmlspecialchars($duty['duty_icon'] ?? 'fas fa-tasks') ?> text-primary"></i>
            <span><?= htmlspecialchars($duty['duty_title']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
?>
