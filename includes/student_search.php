<?php
/**
 * ISNM Student Search API
 * All dashboards can search and find students
 */

require_once __DIR__ . '/../config/database.php';

class StudentSearch {
    private $conn;
    
    public function __construct() {
        $this->conn = getStudentsConnection();
        if (!$this->conn) {
            throw new Exception("Cannot connect to students database");
        }
    }
    
    /**
     * Search students by index number, name, or email
     */
    public function search($query, $limit = 10) {
        $query = $this->conn->real_escape_string($query);
        
        $sql = "SELECT id, index_number, first_name, surname, email, phone, program, year_of_study, status 
                FROM students 
                WHERE index_number LIKE '%$query%' 
                   OR CONCAT(first_name, ' ', surname) LIKE '%$query%' 
                   OR email LIKE '%$query%'
                ORDER BY first_name ASC
                LIMIT $limit";
        
        $result = $this->conn->query($sql);
        
        if (!$result) return [];
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        return $students;
    }
    
    /**
     * Get student details
     */
    public function getStudent($studentId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM students WHERE id = ?"
        );
        
        if (!$stmt) return null;
        
        $stmt->bind_param('i', $studentId);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
        
        return $student;
    }
    
    /**
     * Get student by index number
     */
    public function getByIndexNumber($indexNumber) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM students WHERE index_number = ?"
        );
        
        if (!$stmt) return null;
        
        $stmt->bind_param('s', $indexNumber);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
        
        return $student;
    }
    
    /**
     * Get student academic records
     */
    public function getAcademicRecords($studentId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM academic_records WHERE student_id = ? ORDER BY year DESC, semester DESC"
        );
        
        if (!$stmt) return [];
        
        $stmt->bind_param('i', $studentId);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        
        $stmt->close();
        return $records;
    }
    
    /**
     * Get student fee account
     */
    public function getFeeAccount($studentId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM student_fee_accounts WHERE student_id = ? ORDER BY academic_year DESC"
        );
        
        if (!$stmt) return [];
        
        $stmt->bind_param('i', $studentId);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        
        $fees = [];
        while ($row = $result->fetch_assoc()) {
            $fees[] = $row;
        }
        
        $stmt->close();
        return $fees;
    }
    
    /**
     * Advanced search with filters
     */
    public function advancedSearch($filters) {
        $sql = "SELECT id, index_number, first_name, surname, email, phone, program, year_of_study, status FROM students WHERE 1=1";
        
        if (isset($filters['program']) && !empty($filters['program'])) {
            $program = $this->conn->real_escape_string($filters['program']);
            $sql .= " AND program = '$program'";
        }
        
        if (isset($filters['year']) && !empty($filters['year'])) {
            $year = intval($filters['year']);
            $sql .= " AND year_of_study = $year";
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $status = $this->conn->real_escape_string($filters['status']);
            $sql .= " AND status = '$status'";
        }
        
        if (isset($filters['query']) && !empty($filters['query'])) {
            $query = $this->conn->real_escape_string($filters['query']);
            $sql .= " AND (index_number LIKE '%$query%' OR CONCAT(first_name, ' ', surname) LIKE '%$query%' OR email LIKE '%$query%')";
        }
        
        $limit = intval($filters['limit'] ?? 10);
        $offset = intval($filters['offset'] ?? 0);
        $sql .= " LIMIT $limit OFFSET $offset";
        
        $result = $this->conn->query($sql);
        
        if (!$result) return [];
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        return $students;
    }
    
    /**
     * Get total count of students
     */
    public function getTotalCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM students");
        $row = $result->fetch_assoc();
        return $row['count'] ?? 0;
    }
}

// API endpoint for student search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'search_students') {
    header('Content-Type: application/json');
    
    $query = $_GET['q'] ?? null;
    
    if (!$query || strlen($query) < 2) {
        echo json_encode(['success' => false, 'message' => 'Query too short']);
        exit;
    }
    
    try {
        $search = new StudentSearch();
        $students = $search->search($query);
        echo json_encode(['success' => true, 'students' => $students, 'count' => count($students)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API endpoint for advanced student search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'advanced_search') {
    header('Content-Type: application/json');
    
    $filters = [
        'query' => $_GET['q'] ?? null,
        'program' => $_GET['program'] ?? null,
        'year' => $_GET['year'] ?? null,
        'status' => $_GET['status'] ?? null,
        'limit' => $_GET['limit'] ?? 10,
        'offset' => $_GET['offset'] ?? 0,
    ];
    
    try {
        $search = new StudentSearch();
        $students = $search->advancedSearch($filters);
        echo json_encode(['success' => true, 'students' => $students, 'count' => count($students)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API endpoint for getting student details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_student') {
    header('Content-Type: application/json');
    
    $studentId = intval($_GET['id'] ?? 0);
    
    if (!$studentId) {
        echo json_encode(['success' => false, 'message' => 'Student ID required']);
        exit;
    }
    
    try {
        $search = new StudentSearch();
        $student = $search->getStudent($studentId);
        $academic = $search->getAcademicRecords($studentId);
        $fees = $search->getFeeAccount($studentId);
        
        echo json_encode([
            'success' => true,
            'student' => $student,
            'academic_records' => $academic,
            'fee_accounts' => $fees,
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

?>
