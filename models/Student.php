<?php
/**
 * Student Model for ISNM Student Management System
 */

require_once __DIR__ . '/../config/database.php';

class Student {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function __destruct() {
        closeConnection($this->conn);
    }
    
    /**
     * Create a new student record
     */
    public function create($data) {
        try {
            $query = "INSERT INTO students (full_name, registration_number, national_student_id_number, 
                      index_number, mobile_number, course, year, set_name, gender, passport_photo, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            
            $params = [
                $data['full_name'],
                $data['registration_number'],
                $data['national_student_id_number'],
                $data['index_number'],
                $data['mobile_number'],
                $data['course'],
                $data['year'],
                $data['set_name'],
                $data['gender'],
                $data['passport_photo'] ?? null
            ];
            
            $stmt = executePrepared($this->conn, $query, 'ssssssisss', $params);
            $studentId = $stmt->insert_id;
            $stmt->close();
            
            return ['success' => true, 'student_id' => $studentId];
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => false, 'error' => 'Registration number already exists'];
            }
            return ['success' => false, 'error' => 'Failed to create student: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get student by ID
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM students WHERE id = ? AND status != 'deleted'";
            $stmt = executePrepared($this->conn, $query, 'i', [$id]);
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();
            
            return ['success' => true, 'student' => $student];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get student by registration number
     */
    public function getByRegistrationNumber($regNumber) {
        try {
            $query = "SELECT * FROM students WHERE registration_number = ? AND status != 'deleted'";
            $stmt = executePrepared($this->conn, $query, 's', [$regNumber]);
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();
            
            return ['success' => true, 'student' => $student];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update student record
     */
    public function update($id, $data) {
        try {
            $query = "UPDATE students SET full_name = ?, national_student_id_number = ?, 
                      index_number = ?, mobile_number = ?, course = ?, year = ?, set_name = ?, gender = ?";
            
            $params = [
                $data['full_name'],
                $data['national_student_id_number'],
                $data['index_number'],
                $data['mobile_number'],
                $data['course'],
                $data['year'],
                $data['set_name'],
                $data['gender']
            ];
            
            $types = 'sssssis';
            
            // Add passport photo if provided
            if (isset($data['passport_photo'])) {
                $query .= ", passport_photo = ?";
                $params[] = $data['passport_photo'];
                $types .= 's';
            }
            
            $query .= " WHERE id = ? AND status != 'deleted'";
            $params[] = $id;
            $types .= 'i';
            
            $stmt = executePrepared($this->conn, $query, $types, $params);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0, 'affected_rows' => $affectedRows];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to update student: ' . $e->getMessage()];
        }
    }
    
    /**
     * Soft delete student (mark as inactive)
     */
    public function softDelete($id) {
        try {
            $query = "UPDATE students SET status = 'deleted' WHERE id = ?";
            $stmt = executePrepared($this->conn, $query, 'i', [$id]);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0, 'affected_rows' => $affectedRows];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to delete student: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all students with pagination and filtering
     */
    public function getAll($page = 1, $search = '', $course = '', $year = '', $setName = '') {
        try {
            $offset = ($page - 1) * ITEMS_PER_PAGE;
            $whereConditions = ["status != 'deleted'"];
            $params = [];
            $types = '';
            
            // Add search conditions
            if (!empty($search)) {
                $whereConditions[] = "(full_name LIKE ? OR registration_number LIKE ? OR national_student_id_number LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'sss';
            }
            
            if (!empty($course)) {
                $whereConditions[] = "course = ?";
                $params[] = $course;
                $types .= 's';
            }
            
            if (!empty($year)) {
                $whereConditions[] = "year = ?";
                $params[] = $year;
                $types .= 'i';
            }
            
            if (!empty($setName)) {
                $whereConditions[] = "set_name = ?";
                $params[] = $setName;
                $types .= 's';
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM students $whereClause";
            $stmt = executePrepared($this->conn, $countQuery, $types, $params);
            $result = $stmt->get_result();
            $total = $result->fetch_assoc()['total'];
            $stmt->close();
            
            // Get students with pagination
            $query = "SELECT * FROM students $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = ITEMS_PER_PAGE;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = executePrepared($this->conn, $query, $types, $params);
            $result = $stmt->get_result();
            $students = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            $totalPages = ceil($total / ITEMS_PER_PAGE);
            
            return [
                'success' => true,
                'students' => $students,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $total,
                    'items_per_page' => ITEMS_PER_PAGE
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to fetch students: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get unique courses for filtering
     */
    public function getCourses() {
        try {
            $query = "SELECT DISTINCT course FROM students WHERE status != 'deleted' AND course IS NOT NULL ORDER BY course";
            $stmt = executePrepared($this->conn, $query, '', []);
            $result = $stmt->get_result();
            $courses = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return ['success' => true, 'courses' => array_column($courses, 'course')];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get unique years for filtering
     */
    public function getYears() {
        try {
            $query = "SELECT DISTINCT year FROM students WHERE status != 'deleted' AND year IS NOT NULL ORDER BY year";
            $stmt = executePrepared($this->conn, $query, '', []);
            $result = $stmt->get_result();
            $years = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return ['success' => true, 'years' => array_column($years, 'year')];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get unique sets for filtering
     */
    public function getSets() {
        try {
            $query = "SELECT DISTINCT set_name FROM students WHERE status != 'deleted' AND set_name IS NOT NULL ORDER BY set_name";
            $stmt = executePrepared($this->conn, $query, '', []);
            $result = $stmt->get_result();
            $sets = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return ['success' => true, 'sets' => array_column($sets, 'set_name')];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get statistics
     */
    public function getStatistics() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_students,
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_students,
                        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_students,
                        COUNT(DISTINCT course) as total_courses,
                        COUNT(DISTINCT year) as total_years,
                        COUNT(DISTINCT set_name) as total_sets
                      FROM students WHERE status != 'deleted'";
            
            $stmt = executePrepared($this->conn, $query, '', []);
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
            
            return ['success' => true, 'statistics' => $stats];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
