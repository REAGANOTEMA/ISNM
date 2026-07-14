<?php
/**
 * ISNM Student Search API (Legacy — use ajax_student_search.php for new code)
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
     * Search students by name, ID, phone, or email (prepared statements)
     */
    public function search($query, $limit = 10) {
        $like = '%' . $query . '%';
        $limit = (int) $limit;

        $sql = "SELECT id, student_id, student_number, index_number, registration_number,
                       first_name, surname, other_name,
                       CONCAT(first_name, ' ', COALESCE(surname, '')) AS full_name,
                       email, phone, mobile_number, program, level, set_name,
                       year_of_study, gender, status
                FROM students
                WHERE (first_name LIKE ? OR surname LIKE ? OR other_name LIKE ?
                       OR CONCAT(first_name, ' ', COALESCE(surname, '')) LIKE ?
                       OR student_id LIKE ? OR student_number LIKE ? OR index_number LIKE ?
                       OR registration_number LIKE ?
                       OR phone LIKE ? OR mobile_number LIKE ? OR email LIKE ?)
                  AND status != 'deleted'
                ORDER BY first_name ASC
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $types = 'sssssssssssi';
        $stmt->bind_param($types, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $limit);
        if (!$stmt->execute()) {
            error_log('StudentSearch::search execute failed: ' . ($stmt->error ?? 'unknown'));
            $stmt->close();
            return [];
        }

        $result = $stmt->get_result();
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        $stmt->close();
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
        if (!$stmt->execute()) { error_log('StudentSearch::getStudent execute failed: ' . ($stmt->error ?? 'unknown')); };
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
            "SELECT * FROM students WHERE index_number = ? OR student_number = ? OR student_id = ?"
        );

        if (!$stmt) return null;

        $stmt->bind_param('sss', $indexNumber, $indexNumber, $indexNumber);
        if (!$stmt->execute()) { error_log('StudentSearch::getByIndexNumber execute failed: ' . ($stmt->error ?? 'unknown')); };
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
        if (!$stmt->execute()) { error_log('StudentSearch::getAcademicRecords execute failed: ' . ($stmt->error ?? 'unknown')); };
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
        if (!$stmt->execute()) { error_log('StudentSearch::getFeeAccount execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();

        $fees = [];
        while ($row = $result->fetch_assoc()) {
            $fees[] = $row;
        }

        $stmt->close();
        return $fees;
    }

    /**
     * Advanced search with filters (prepared statements)
     */
    public function advancedSearch($filters) {
        $conditions = ['1=1'];
        $params = [];
        $types = '';

        if (!empty($filters['program'])) {
            $conditions[] = 'program LIKE ?';
            $params[] = '%' . $filters['program'] . '%';
            $types .= 's';
        }

        if (!empty($filters['year'])) {
            $conditions[] = 'year_of_study = ?';
            $params[] = (int) $filters['year'];
            $types .= 'i';
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['query'])) {
            $like = '%' . $filters['query'] . '%';
            $conditions[] = '(first_name LIKE ? OR surname LIKE ? OR other_name LIKE ?
                             OR CONCAT(first_name, \' \', COALESCE(surname, \' \')) LIKE ?
                             OR student_id LIKE ? OR student_number LIKE ? OR index_number LIKE ?
                             OR registration_number LIKE ?
                             OR phone LIKE ? OR mobile_number LIKE ? OR email LIKE ?)';
            for ($i = 0; $i < 11; $i++) { $params[] = $like; }
            $types .= str_repeat('s', 11);
        }

        $limit  = max(1, (int)($filters['limit'] ?? 10));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $where = implode(' AND ', $conditions);
        $sql = "SELECT id, student_id, student_number, index_number, registration_number,
                       first_name, surname, other_name,
                       CONCAT(first_name, ' ', COALESCE(surname, '')) AS full_name,
                       email, phone, mobile_number, program, level, set_name,
                       year_of_study, gender, status
                FROM students
                WHERE $where
                ORDER BY first_name ASC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('StudentSearch::advancedSearch prepare failed: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            error_log('StudentSearch::advancedSearch execute failed: ' . ($stmt->error ?? 'unknown'));
            $stmt->close();
            return [];
        }

        $result = $stmt->get_result();
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        $stmt->close();
        return $students;
    }

    /**
     * Get total count of students
     */
    public function getTotalCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM students WHERE status != 'deleted'");
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
        'query'  => $_GET['q'] ?? null,
        'program'=> $_GET['program'] ?? null,
        'year'   => $_GET['year'] ?? null,
        'status' => $_GET['status'] ?? null,
        'limit'  => $_GET['limit'] ?? 10,
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
