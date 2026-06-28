<?php
/**
 * ISNM Department-Based Access Restrictions
 * Ensures users only see data assigned to their department
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Department-Based Data Access Restrictions
class DepartmentRestrictions {
    private $conn;
    private $user_id;
    private $user_role;
    private $user_department;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
        
        // Get user department
        if ($this->user_id) {
            $dept_stmt = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
            $dept_stmt->bind_param("i", $this->user_id);
            $dept_stmt->execute();
            $dept_result = $dept_stmt->get_result();
            $dept_data = $dept_result->fetch_assoc();
            $this->user_department = $dept_data['department_id'] ?? null;
        }
    }
    
    /**
     * Check if user has access to specific department data
     */
    public function canAccessDepartment($department_id) {
        // Director General and CEO can access all departments
        if (in_array($this->user_role, ['Director General', 'CEO'])) {
            return true;
        }
        
        // Users can access their own department
        return $this->user_department == $department_id;
    }
    
    /**
     * Get filtered student data based on department
     */
    public function getFilteredStudents($additional_conditions = '') {
        $base_query = "SELECT s.*, p.program_name, d.name as department_name 
                     FROM students s 
                     LEFT JOIN programs p ON s.program_id = p.id 
                     LEFT JOIN departments d ON p.department_id = d.id 
                     WHERE s.status = 'active'";
        
        // Apply department restrictions
        if (!in_array($this->user_role, ['Director General', 'CEO'])) {
            $base_query .= " AND p.department_id = " . intval($this->user_department);
        }
        
        // Apply additional conditions
        if ($additional_conditions) {
            $base_query .= " AND $additional_conditions";
        }
        
        $base_query .= " ORDER BY s.surname, s.first_name";
        
        return $this->conn->query($base_query);
    }
    
    /**
     * Get filtered staff data based on department
     */
    public function getFilteredStaff($additional_conditions = '') {
        $base_query = "SELECT u.*, d.name as department_name 
                     FROM users u 
                     LEFT JOIN departments d ON u.department_id = d.id 
                     WHERE u.role != 'Student' AND u.status = 'active'";
        
        // Apply department restrictions
        if (!in_array($this->user_role, ['Director General', 'CEO'])) {
            $base_query .= " AND u.department_id = " . intval($this->user_department);
        }
        
        // Apply additional conditions
        if ($additional_conditions) {
            $base_query .= " AND $additional_conditions";
        }
        
        $base_query .= " ORDER BY u.surname, u.first_name";
        
        return $this->conn->query($base_query);
    }
    
    /**
     * Get filtered financial data based on department
     */
    public function getFilteredFinancialData($additional_conditions = '') {
        $base_query = "SELECT fp.*, s.first_name, s.surname, s.student_id, p.program_name, d.name as department_name
                     FROM fee_payments fp
                     JOIN students s ON fp.student_id = s.id
                     LEFT JOIN programs p ON s.program_id = p.id
                     LEFT JOIN departments d ON p.department_id = d.id
                     WHERE fp.status = 'verified'";
        
        // Apply department restrictions
        if (!in_array($this->user_role, ['Director General', 'CEO', 'School Bursar'])) {
            $base_query .= " AND p.department_id = " . intval($this->user_department);
        }
        
        // Apply additional conditions
        if ($additional_conditions) {
            $base_query .= " AND $additional_conditions";
        }
        
        $base_query .= " ORDER BY fp.payment_date DESC";
        
        return $this->conn->query($base_query);
    }
    
    /**
     * Get filtered academic data based on department
     */
    public function getFilteredAcademicData($additional_conditions = '') {
        $base_query = "SELECT ar.*, s.first_name, s.surname, s.student_id, c.course_name, p.program_name, d.name as department_name
                     FROM academic_records ar
                     JOIN students s ON ar.student_id = s.id
                     JOIN courses c ON ar.course_id = c.id
                     LEFT JOIN programs p ON s.program_id = p.id
                     LEFT JOIN departments d ON p.department_id = d.id
                     WHERE 1=1";
        
        // Apply department restrictions
        if (!in_array($this->user_role, ['Director General', 'CEO', 'Academic Registrar'])) {
            $base_query .= " AND p.department_id = " . intval($this->user_department);
        }
        
        // Apply additional conditions
        if ($additional_conditions) {
            $base_query .= " AND $additional_conditions";
        }
        
        $base_query .= " ORDER BY ar.semester, ar.academic_year";
        
        return $this->conn->query($base_query);
    }
    
    /**
     * Get filtered course data based on department
     */
    public function getFilteredCourses($additional_conditions = '') {
        $base_query = "SELECT c.*, d.name as department_name 
                     FROM courses c 
                     LEFT JOIN departments d ON c.department_id = d.id 
                     WHERE c.status = 'active'";
        
        // Apply department restrictions
        if (!in_array($this->user_role, ['Director General', 'CEO', 'Academic Registrar'])) {
            $base_query .= " AND c.department_id = " . intval($this->user_department);
        }
        
        // Apply additional conditions
        if ($additional_conditions) {
            $base_query .= " AND $additional_conditions";
        }
        
        $base_query .= " ORDER BY c.course_code";
        
        return $this->conn->query($base_query);
    }
    
    /**
     * Get filtered activity logs based on department
     */
    public function getFilteredActivityLogs($additional_conditions = '') {
        $base_query = "SELECT al.*, u.first_name, u.surname, u.role as user_role, d.name as department_name
                     FROM activity_logs al
                     LEFT JOIN users u ON al.user_id = u.id
                     LEFT JOIN departments d ON u.department_id = d.id
                     WHERE 1=1";
        
        // Apply department restrictions
        if (!in_array($this->user_role, ['Director General', 'CEO'])) {
            $base_query .= " AND (u.department_id = " . intval($this->user_department) . " OR u.department_id IS NULL)";
        }
        
        // Apply additional conditions
        if ($additional_conditions) {
            $base_query .= " AND $additional_conditions";
        }
        
        $base_query .= " ORDER BY al.activity_date DESC";
        
        return $this->conn->query($base_query);
    }
    
    /**
     * Check if user can perform specific action on data
     */
    public function canPerformAction($action, $data_type, $data_id = null) {
        // Directors and CEO can perform all actions
        if (in_array($this->user_role, ['Director General', 'CEO'])) {
            return true;
        }
        
        // Check role-based permissions
        switch ($this->user_role) {
            case 'School Bursar':
                return in_array($action, ['view', 'edit', 'create']) && in_array($data_type, ['financial', 'student_fees', 'payments']);
            
            case 'Academic Registrar':
                return in_array($action, ['view', 'edit', 'create']) && in_array($data_type, ['academic', 'students', 'courses', 'records']);
            
            case 'HR Manager':
                return in_array($action, ['view', 'edit', 'create']) && in_array($data_type, ['staff', 'hr', 'payroll']);
            
            case 'Head of Nursing':
            case 'Head of Midwifery':
                return in_array($action, ['view', 'edit']) && in_array($data_type, ['students', 'courses', 'academic']) && $this->isDepartmentData($data_id, $data_type);
            
            case 'Lecturers':
            case 'Senior Lecturers':
                return in_array($action, ['view', 'edit']) && in_array($data_type, ['students', 'courses', 'grades']) && $this->isAssignedData($data_id, $data_type);
            
            case 'Matrons':
            case 'Wardens':
                return in_array($action, ['view', 'edit']) && in_array($data_type, ['students', 'welfare']) && $this->isDepartmentData($data_id, $data_type);
            
            default:
                return false;
        }
    }
    
    /**
     * Check if data belongs to user's department
     */
    private function isDepartmentData($data_id, $data_type) {
        if (!$data_id || !$this->user_department) {
            return false;
        }
        
        $data_id = intval($data_id);
        $dept_id = intval($this->user_department);
        
        switch ($data_type) {
            case 'students':
                $stmt = $this->conn->prepare("SELECT s.program_id FROM students s LEFT JOIN programs p ON s.program_id = p.id WHERE s.id = ? AND p.department_id = ?");
                if ($stmt) {
                    $stmt->bind_param("ii", $data_id, $dept_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->num_rows;
                    $stmt->close();
                    return $count > 0;
                }
                return false;
            case 'courses':
                $stmt = $this->conn->prepare("SELECT id FROM courses WHERE id = ? AND department_id = ?");
                if ($stmt) {
                    $stmt->bind_param("ii", $data_id, $dept_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->num_rows;
                    $stmt->close();
                    return $count > 0;
                }
                return false;
            case 'academic':
                $stmt = $this->conn->prepare("SELECT ar.id FROM academic_records ar JOIN students s ON ar.student_id = s.id LEFT JOIN programs p ON s.program_id = p.id WHERE ar.id = ? AND p.department_id = ?");
                if ($stmt) {
                    $stmt->bind_param("ii", $data_id, $dept_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->num_rows;
                    $stmt->close();
                    return $count > 0;
                }
                return false;
            default:
                return false;
        }
    }
    
    /**
     * Check if data is assigned to user
     */
    private function isAssignedData($data_id, $data_type) {
        if (!$data_id || !$this->user_id) {
            return false;
        }
        
        $data_id = intval($data_id);
        $uid = intval($this->user_id);
        
        switch ($data_type) {
            case 'courses':
                $stmt = $this->conn->prepare("SELECT id FROM course_assignments WHERE course_id = ? AND lecturer_id = ?");
                if ($stmt) {
                    $stmt->bind_param("ii", $data_id, $uid);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->num_rows;
                    $stmt->close();
                    return $count > 0;
                }
                return false;
            case 'students':
                $stmt = $this->conn->prepare("SELECT s.id FROM students s JOIN enrollments e ON s.id = e.student_id WHERE s.id = ? AND e.lecturer_id = ?");
                if ($stmt) {
                    $stmt->bind_param("ii", $data_id, $uid);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->num_rows;
                    $stmt->close();
                    return $count > 0;
                }
                return false;
            case 'grades':
                $stmt = $this->conn->prepare("SELECT id FROM academic_records WHERE id = ? AND lecturer_id = ?");
                if ($stmt) {
                    $stmt->bind_param("ii", $data_id, $uid);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->num_rows;
                    $stmt->close();
                    return $count > 0;
                }
                return false;
            default:
                return false;
        }
    }
    
    /**
     * Get department statistics
     */
    public function getDepartmentStatistics() {
        $stats = [];
        
        // Student count
        $student_query = "SELECT COUNT(*) as count FROM students s LEFT JOIN programs p ON s.program_id = p.id WHERE s.status = 'active' AND p.department_id = " . intval($this->user_department);
        $student_result = $this->conn->query($student_query);
        $stats['students'] = $student_result->fetch_assoc()['count'] ?? 0;
        
        // Staff count
        $staff_query = "SELECT COUNT(*) as count FROM users WHERE department_id = " . intval($this->user_department) . " AND status = 'active'";
        $staff_result = $this->conn->query($staff_query);
        $stats['staff'] = $staff_result->fetch_assoc()['count'] ?? 0;
        
        // Course count
        $course_query = "SELECT COUNT(*) as count FROM courses WHERE department_id = " . intval($this->user_department) . " AND status = 'active'";
        $course_result = $this->conn->query($course_query);
        $stats['courses'] = $course_result->fetch_assoc()['count'] ?? 0;
        
        // Revenue (if applicable)
        if (in_array($this->user_role, ['School Bursar', 'Director Finance', 'Director General', 'CEO'])) {
            $revenue_query = "SELECT COALESCE(SUM(fp.amount), 0) as total FROM fee_payments fp JOIN students s ON fp.student_id = s.id LEFT JOIN programs p ON s.program_id = p.id WHERE fp.status = 'verified' AND p.department_id = " . intval($this->user_department);
            $revenue_result = $this->conn->query($revenue_query);
            $stats['revenue'] = $revenue_result->fetch_assoc()['total'] ?? 0;
        }
        
        return $stats;
    }
    
    /**
     * Apply department filter to SQL query
     */
    public function applyDepartmentFilter($query, $table_alias = '') {
        if (in_array($this->user_role, ['Director General', 'CEO'])) {
            return $query;
        }
        
        $prefix = $table_alias ? $table_alias . '.' : '';
        $filter = " AND {$prefix}department_id = " . intval($this->user_department);
        
        return $query . $filter;
    }
    
    /**
     * Get user's department name
     */
    public function getUserDepartmentName() {
        if (!$this->user_department) {
            return 'Not Assigned';
        }
        
        $query = "SELECT name FROM departments WHERE id = " . intval($this->user_department);
        $result = $this->conn->query($query);
        $dept_data = $result->fetch_assoc();
        
        return $dept_data['name'] ?? 'Unknown';
    }
    
    /**
     * Log access attempt
     */
    public function logAccess($action, $resource_type, $resource_id = null, $access_granted = false) {
        $user_id = intval($this->user_id);
        $user_role = $this->user_role;
        $action_clean = $action;
        $resource_type_clean = $resource_type;
        $resource_id_clean = $resource_id ? intval($resource_id) : null;
        $granted = $access_granted ? 1 : 0;
        
        $stmt = $this->conn->prepare("INSERT INTO access_logs (user_id, user_role, action, resource_type, resource_id, access_granted, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("isssii", $user_id, $user_role, $action_clean, $resource_type_clean, $resource_id_clean, $granted);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Initialize department restrictions
if (isset($conn)) {
    $dept_restrictions = new DepartmentRestrictions($conn);
    
    // Auto-apply restrictions to common queries
    if (!in_array($_SESSION['user_role'] ?? '', ['Director General', 'CEO'])) {
        // Add department filter to session
        $_SESSION['department_filter'] = $dept_restrictions->getUserDepartmentName();
    }
}

/**
 * Helper function to check access before processing
 */
function checkDepartmentAccess($action, $resource_type, $resource_id = null) {
    global $dept_restrictions;
    
    if (!$dept_restrictions) {
        return false;
    }
    
    $access_granted = $dept_restrictions->canPerformAction($action, $resource_type, $resource_id);
    $dept_restrictions->logAccess($action, $resource_type, $resource_id, $access_granted);
    
    if (!$access_granted) {
        // Log unauthorized access attempt
        error_log("Unauthorized access attempt: User {$_SESSION['user_id']} ({$_SESSION['user_role']}) tried to $action $resource_type " . ($resource_id ? "ID: $resource_id" : ""));
        
        // Return error response
        header('HTTP/1.0 403 Forbidden');
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. You do not have permission to perform this action.'
        ]);
        exit();
    }
    
    return true;
}

/**
 * Apply department restrictions to dashboard data
 */
function getRestrictedDashboardData($data_type, $additional_conditions = '') {
    global $dept_restrictions;
    
    if (!$dept_restrictions) {
        return null;
    }
    
    switch ($data_type) {
        case 'students':
            return $dept_restrictions->getFilteredStudents($additional_conditions);
        case 'staff':
            return $dept_restrictions->getFilteredStaff($additional_conditions);
        case 'financial':
            return $dept_restrictions->getFilteredFinancialData($additional_conditions);
        case 'academic':
            return $dept_restrictions->getFilteredAcademicData($additional_conditions);
        case 'courses':
            return $dept_restrictions->getFilteredCourses($additional_conditions);
        case 'activities':
            return $dept_restrictions->getFilteredActivityLogs($additional_conditions);
        default:
            return null;
    }
}
?>
