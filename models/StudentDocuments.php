<?php
/**
 * Student Documents Model for ISNM Student Management System
 */

require_once __DIR__ . '/../config/database.php';

class StudentDocuments {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function __destruct() {
        closeConnection($this->conn);
    }
    
    /**
     * Get student documents
     */
    public function getStudentDocuments($studentId) {
        try {
            $query = "SELECT sd.*, u.full_name as uploaded_by_name 
                      FROM student_documents sd 
                      LEFT JOIN users u ON sd.uploaded_by = u.id 
                      WHERE sd.student_id = ? 
                      ORDER BY sd.upload_date DESC";
            
            $stmt = executePrepared($this->conn, $query, 'i', [$studentId]);
            $result = $stmt->get_result();
            $documents = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return ['success' => true, 'documents' => $documents];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Upload student document
     */
    public function uploadDocument($data) {
        try {
            $query = "INSERT INTO student_documents (student_id, document_type, document_name, file_path, 
                      file_size, file_type, uploaded_by, expiry_date, description) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['student_id'],
                $data['document_type'],
                $data['document_name'],
                $data['file_path'],
                $data['file_size'],
                $data['file_type'],
                $data['uploaded_by'],
                $data['expiry_date'] ?? null,
                $data['description'] ?? null
            ];
            
            $stmt = executePrepared($this->conn, $query, 'issssiss', $params);
            $documentId = $stmt->insert_id;
            $stmt->close();
            
            return ['success' => true, 'document_id' => $documentId];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update document status
     */
    public function updateDocumentStatus($id, $status) {
        try {
            $query = "UPDATE student_documents SET status = ? WHERE id = ?";
            $stmt = executePrepared($this->conn, $query, 'si', [$status, $id]);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0, 'affected_rows' => $affectedRows];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Delete document
     */
    public function deleteDocument($id, $studentId) {
        try {
            // Get file path before deleting
            $query = "SELECT file_path FROM student_documents WHERE id = ? AND student_id = ?";
            $stmt = executePrepared($this->conn, $query, 'ii', [$id, $studentId]);
            $result = $stmt->get_result();
            $document = $result->fetch_assoc();
            $stmt->close();
            
            if (!$document) {
                return ['success' => false, 'error' => 'Document not found'];
            }
            
            // Delete from database
            $query = "DELETE FROM student_documents WHERE id = ? AND student_id = ?";
            $stmt = executePrepared($this->conn, $query, 'ii', [$id, $studentId]);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            if ($affectedRows > 0) {
                // Delete physical file
                if (file_exists($document['file_path'])) {
                    unlink($document['file_path']);
                }
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => 'Failed to delete document'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get document types
     */
    public function getDocumentTypes() {
        try {
            $query = "SELECT DISTINCT document_type FROM student_documents ORDER BY document_type";
            $stmt = executePrepared($this->conn, $query, '', []);
            $result = $stmt->get_result();
            $types = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return ['success' => true, 'types' => array_column($types, 'document_type')];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all students with documents (for staff)
     */
    public function getAllStudentsDocuments($page = 1, $search = '', $documentType = '') {
        try {
            $offset = ($page - 1) * 20;
            $limit = 20;
            
            $query = "SELECT s.id, s.full_name, s.registration_number, s.course, s.year, s.set_name,
                        COUNT(sd.id) as document_count,
                        GROUP_CONCAT(DISTINCT sd.document_type) as document_types
                      FROM students s
                      LEFT JOIN student_documents sd ON s.id = sd.student_id
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
            
            if (!empty($documentType)) {
                $query .= " AND sd.document_type = ?";
                $params[] = $documentType;
                $types .= 's';
            }
            
            $query .= " GROUP BY s.id ORDER BY s.full_name LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = executePrepared($this->conn, $query, $types, $params);
            $result = $stmt->get_result();
            $students = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(DISTINCT s.id) as total FROM students s LEFT JOIN student_documents sd ON s.id = sd.student_id WHERE 1=1";
            $countParams = [];
            $countTypes = '';
            
            if (!empty($search)) {
                $countQuery .= " AND (s.full_name LIKE ? OR s.registration_number LIKE ?)";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countTypes .= 'ss';
            }
            
            if (!empty($documentType)) {
                $countQuery .= " AND sd.document_type = ?";
                $countParams[] = $documentType;
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
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
