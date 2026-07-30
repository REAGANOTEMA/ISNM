<?php
/**
 * Student Finance Model for ISNM Student Management System
 */

require_once __DIR__ . '/../config/database.php';

class StudentFinance {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function __destruct() {
        closeConnection($this->conn);
    }
    
    /**
     * Ensure student_finance table exists
     */
    private function ensureTable() {
        try {
            $this->conn->query("CREATE TABLE IF NOT EXISTS `student_finance` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `student_id` int(11) NOT NULL,
                `tuition_fee` decimal(14,2) DEFAULT 0.00,
                `amount_paid` decimal(14,2) DEFAULT 0.00,
                `payment_method` varchar(50) DEFAULT NULL,
                `payment_date` date DEFAULT NULL,
                `payment_status` enum('pending','partial','paid','overdue') DEFAULT 'pending',
                `semester` varchar(50) DEFAULT NULL,
                `academic_year` varchar(20) DEFAULT NULL,
                `receipt_number` varchar(100) DEFAULT NULL,
                `received_by` int(11) DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `created_at` timestamp DEFAULT current_timestamp(),
                `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_sf_student` (`student_id`),
                KEY `idx_sf_status` (`payment_status`),
                KEY `idx_sf_year` (`academic_year`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $e) {
            error_log('StudentFinance ensureTable: ' . $e->getMessage());
        }
    }

    /**
     * Get student finance records
     */
    public function getStudentFinance($studentId) {
        try {
            $this->ensureTable();
            $query = "SELECT sf.*, s.full_name, s.registration_number 
                      FROM student_finance sf 
                      JOIN students s ON sf.student_id = s.id 
                      WHERE sf.student_id = ? 
                      ORDER BY sf.academic_year DESC, sf.semester DESC";
            
            $stmt = executePrepared($this->conn, $query, 'i', [$studentId]);
            $result = $stmt->get_result();
            $finance = isnm_fetch_all($result);
            $stmt->close();
            
            return ['success' => true, 'finance' => $finance];
            
        } catch (Exception $e) {
            error_log('StudentFinance getStudentFinance: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Create finance record
     */
    public function createFinanceRecord($data) {
        try {
            $query = "INSERT INTO student_finance (student_id, tuition_fee, amount_paid, payment_method, 
                      payment_date, payment_status, semester, academic_year, receipt_number) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['student_id'],
                $data['tuition_fee'],
                $data['amount_paid'],
                $data['payment_method'],
                $data['payment_date'],
                $data['payment_status'],
                $data['semester'],
                $data['academic_year'],
                $data['receipt_number'] ?? null
            ];
            
            $stmt = executePrepared($this->conn, $query, 'idsssssss', $params);
            $financeId = $stmt->insert_id;
            $stmt->close();
            
            return ['success' => true, 'finance_id' => $financeId];
            
        } catch (Exception $e) {
            error_log('StudentFinance createFinanceRecord: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update finance record
     */
    public function updateFinanceRecord($id, $data) {
        try {
            $query = "UPDATE student_finance SET tuition_fee = ?, amount_paid = ?, payment_method = ?, 
                      payment_date = ?, payment_status = ?, semester = ?, academic_year = ?, receipt_number = ? 
                      WHERE id = ?";
            
            $params = [
                $data['tuition_fee'],
                $data['amount_paid'],
                $data['payment_method'],
                $data['payment_date'],
                $data['payment_status'],
                $data['semester'],
                $data['academic_year'],
                $data['receipt_number'] ?? null,
                $id
            ];
            
            $stmt = executePrepared($this->conn, $query, 'dsssssssi', $params);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0, 'affected_rows' => $affectedRows];
            
        } catch (Exception $e) {
            error_log('StudentFinance updateFinanceRecord: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get finance summary for student
     */
    public function getFinanceSummary($studentId) {
        try {
            $query = "SELECT 
                        SUM(tuition_fee) as total_tuition,
                        SUM(amount_paid) as total_paid,
                        SUM(tuition_fee - amount_paid) as total_balance,
                        COUNT(*) as total_records,
                        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                        SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) as partial_count
                      FROM student_finance 
                      WHERE student_id = ?";
            
            $stmt = executePrepared($this->conn, $query, 'i', [$studentId]);
            $result = $stmt->get_result();
            $summary = $result->fetch_assoc();
            $stmt->close();
            
            return ['success' => true, 'summary' => $summary];
            
        } catch (Exception $e) {
            error_log('StudentFinance getFinanceSummary: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all students with finance info (for staff)
     */
    public function getAllStudentsFinance($page = 1, $search = '', $status = '') {
        try {
            $offset = ($page - 1) * 20;
            $limit = 20;
            
            $query = "SELECT s.id, s.full_name, s.registration_number, s.course, s.year, s.set_name,
                        sf.tuition_fee, sf.amount_paid, COALESCE(sf.tuition_fee,0) - COALESCE(sf.amount_paid,0) AS balance, sf.payment_status, sf.academic_year
                      FROM students s
                      LEFT JOIN student_finance sf ON s.id = sf.student_id
                      WHERE 1=1";
            
            $params = [];
            $types = '';
            
            if (!empty($search)) {
                $query .= " AND (s.full_name LIKE ? OR s.registration_number LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'ss';
            }
            
            if (!empty($status)) {
                $query .= " AND sf.payment_status = ?";
                $params[] = $status;
                $types .= 's';
            }
            
            $query .= " ORDER BY s.full_name LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = executePrepared($this->conn, $query, $types, $params);
            $result = $stmt->get_result();
            $students = isnm_fetch_all($result);
            $stmt->close();
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(DISTINCT s.id) as total FROM students s LEFT JOIN student_finance sf ON s.id = sf.student_id WHERE 1=1";
            $countParams = [];
            $countTypes = '';
            
            if (!empty($search)) {
                $countQuery .= " AND (s.full_name LIKE ? OR s.registration_number LIKE ?)";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countTypes .= 'ss';
            }
            
            if (!empty($status)) {
                $countQuery .= " AND sf.payment_status = ?";
                $countParams[] = $status;
                $countTypes .= 's';
            }
            
            $stmt = executePrepared($this->conn, $countQuery, $countTypes, $countParams);
            $result = $stmt->get_result();
            $total = $result->fetch_assoc()['total'];
            $stmt->close();
            
            $totalPages = ceil($total / $limit);
            
            return [
                'success' => true,
                'students' => $students,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ];
            
        } catch (Exception $e) {
            error_log('StudentFinance getAllStudentsFinance: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
