<?php
/**
 * Requirements Portal Helper Functions
 * Functions for managing student requirements and clearance
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get all students from students_db
 */
function getStudentsList($limit = null, $offset = 0) {
    try {
        $conn = getStudentsConnection();
        
        $query = "SELECT id, index_number, full_name, phone FROM users WHERE role = 'student' AND is_active = 1 ORDER BY full_name ASC";
        
        if ($limit) {
            $query .= " LIMIT " . intval($offset) . ", " . intval($limit);
        }
        
        $result = $conn->query($query);
        $students = [];
        
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        return $students;
    } catch (Exception $e) {
        error_log("Error getting students list: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total count of students
 */
function getTotalStudentsCount() {
    try {
        $conn = getStudentsConnection();
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND is_active = 1");
        $row = $result->fetch_assoc();
        return (int) $row['count'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Search students by name, admission number, or phone
 */
function searchStudents($searchTerm) {
    try {
        $conn = getStudentsConnection();
        $searchTerm = '%' . addslashes($searchTerm) . '%';
        
        $query = "SELECT id, index_number, full_name, phone FROM users 
                  WHERE role = 'student' AND is_active = 1
                  AND (full_name LIKE ? OR index_number LIKE ? OR phone LIKE ?)
                  ORDER BY full_name ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        $stmt->close();
        return $students;
    } catch (Exception $e) {
        error_log("Error searching students: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all requirement items
 */
function getAllRequirementItems() {
    try {
        $conn = getStaffConnection();
        
        $result = $conn->query("
            SELECT id, name, category, status, created_at 
            FROM requirement_items 
            WHERE status = 'active'
            ORDER BY category ASC, name ASC
        ");
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    } catch (Exception $e) {
        error_log("Error getting requirement items: " . $e->getMessage());
        return [];
    }
}

/**
 * Get requirements for a specific student
 */
function getStudentRequirements($studentId) {
    try {
        $conn = getStaffConnection();
        
        $stmt = $conn->prepare("
            SELECT sr.*, ri.name, ri.category
            FROM student_requirements sr
            JOIN requirement_items ri ON sr.requirement_item_id = ri.id
            WHERE sr.student_id = ?
            ORDER BY ri.category ASC, ri.name ASC
        ");
        
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requirements = [];
        while ($row = $result->fetch_assoc()) {
            $requirements[] = $row;
        }
        
        $stmt->close();
        return $requirements;
    } catch (Exception $e) {
        error_log("Error getting student requirements: " . $e->getMessage());
        return [];
    }
}

/**
 * Initialize requirements for a student (create records for all items)
 */
function initializeStudentRequirements($studentId, $admissionNumber, $studentName, $studentPhone) {
    try {
        $conn = getStaffConnection();
        
        // Get all active requirement items
        $result = $conn->query("SELECT id FROM requirement_items WHERE status = 'active'");
        
        $stmt = $conn->prepare("
            INSERT IGNORE INTO student_requirements 
            (student_id, student_admission_number, student_name, student_phone, requirement_item_id, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $initialized = 0;
        while ($row = $result->fetch_assoc()) {
            $stmt->bind_param("isssl", $studentId, $admissionNumber, $studentName, $studentPhone, $row['id']);
            if ($stmt->execute()) {
                $initialized += $stmt->affected_rows;
            }
        }
        
        $stmt->close();
        return $initialized;
    } catch (Exception $e) {
        error_log("Error initializing student requirements: " . $e->getMessage());
        return 0;
    }
}

/**
 * Clear a specific requirement for a student
 */
function clearRequirement($studentId, $requirementId, $clearedBy) {
    try {
        $conn = getStaffConnection();
        
        $stmt = $conn->prepare("
            UPDATE student_requirements
            SET is_cleared = 1, cleared_by = ?, cleared_date = NOW(), updated_at = NOW()
            WHERE student_id = ? AND requirement_item_id = ?
        ");
        
        $stmt->bind_param("sii", $clearedBy, $studentId, $requirementId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error clearing requirement: " . $e->getMessage());
        return false;
    }
}

/**
 * Uncheck (reverse) a cleared requirement
 */
function unclearRequirement($studentId, $requirementId) {
    try {
        $conn = getStaffConnection();
        
        $stmt = $conn->prepare("
            UPDATE student_requirements
            SET is_cleared = 0, cleared_by = NULL, cleared_date = NULL, updated_at = NOW()
            WHERE student_id = ? AND requirement_item_id = ?
        ");
        
        $stmt->bind_param("ii", $studentId, $requirementId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error uncleaning requirement: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear all requirements for a student
 */
function clearAllRequirements($studentId, $clearedBy) {
    try {
        $conn = getStaffConnection();
        
        $stmt = $conn->prepare("
            UPDATE student_requirements
            SET is_cleared = 1, cleared_by = ?, cleared_date = NOW(), updated_at = NOW()
            WHERE student_id = ? AND is_cleared = 0
        ");
        
        $stmt->bind_param("si", $clearedBy, $studentId);
        $result = $stmt->execute();
        $cleared = $stmt->affected_rows;
        $stmt->close();
        
        return $cleared;
    } catch (Exception $e) {
        error_log("Error clearing all requirements: " . $e->getMessage());
        return false;
    }
}

/**
 * Add note to a requirement
 */
function addRequirementNote($studentId, $requirementId, $notes) {
    try {
        $conn = getStaffConnection();
        
        $stmt = $conn->prepare("
            UPDATE student_requirements
            SET notes = ?, updated_at = NOW()
            WHERE student_id = ? AND requirement_item_id = ?
        ");
        
        $stmt->bind_param("sii", $notes, $studentId, $requirementId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error adding note: " . $e->getMessage());
        return false;
    }
}

/**
 * Get requirements statistics
 */
function getRequirementStats() {
    try {
        $conn = getStaffConnection();
        $studentsConn = getStudentsConnection();
        
        // Total students
        $studentsResult = $studentsConn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND is_active = 1");
        $studentsRow = $studentsResult->fetch_assoc();
        $totalStudents = (int) $studentsRow['count'];
        
        // Students with requirements initialized
        $initResult = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM student_requirements");
        $initRow = $initResult->fetch_assoc();
        $studentsWithRequirements = (int) $initRow['count'];
        
        // Students with all requirements cleared
        $allClearedResult = $conn->query("
            SELECT COUNT(DISTINCT student_id) as count FROM (
                SELECT DISTINCT student_id FROM student_requirements
                GROUP BY student_id
                HAVING SUM(CASE WHEN is_cleared = 0 THEN 1 ELSE 0 END) = 0
            ) as cleared_students
        ");
        $allClearedRow = $allClearedResult->fetch_assoc();
        $studentsAllCleared = (int) $allClearedRow['count'];
        
        // Total requirements
        $totalReqResult = $conn->query("SELECT COUNT(*) as count FROM requirement_items WHERE status = 'active'");
        $totalReqRow = $totalReqResult->fetch_assoc();
        $totalRequirements = (int) $totalReqRow['count'];
        
        // Total cleared
        $totalClearedResult = $conn->query("SELECT COUNT(*) as count FROM student_requirements WHERE is_cleared = 1");
        $totalClearedRow = $totalClearedResult->fetch_assoc();
        $totalCleared = (int) $totalClearedRow['count'];
        
        // Total pending
        $totalPendingResult = $conn->query("SELECT COUNT(*) as count FROM student_requirements WHERE is_cleared = 0");
        $totalPendingRow = $totalPendingResult->fetch_assoc();
        $totalPending = (int) $totalPendingRow['count'];
        
        // Percentage complete
        $percentComplete = ($totalCleared + $totalPending) > 0 
            ? round(($totalCleared / ($totalCleared + $totalPending)) * 100, 2)
            : 0;
        
        return [
            'totalStudents' => $totalStudents,
            'studentsWithRequirements' => $studentsWithRequirements,
            'studentsAllCleared' => $studentsAllCleared,
            'studentsPending' => $studentsWithRequirements - $studentsAllCleared,
            'totalRequirements' => $totalRequirements,
            'totalCleared' => $totalCleared,
            'totalPending' => $totalPending,
            'percentComplete' => $percentComplete
        ];
    } catch (Exception $e) {
        error_log("Error getting requirement stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get student progress (count of cleared vs total requirements)
 */
function getStudentProgress($studentId) {
    try {
        $conn = getStaffConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_cleared = 1 THEN 1 ELSE 0 END) as cleared
            FROM student_requirements
            WHERE student_id = ?
        ");
        
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $total = (int) $row['total'];
        $cleared = (int) $row['cleared'];
        $percentage = $total > 0 ? round(($cleared / $total) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'cleared' => $cleared,
            'pending' => $total - $cleared,
            'percentage' => $percentage
        ];
    } catch (Exception $e) {
        error_log("Error getting student progress: " . $e->getMessage());
        return ['total' => 0, 'cleared' => 0, 'pending' => 0, 'percentage' => 0];
    }
}

/**
 * Filter students based on criteria
 */
function filterStudents($filterBy, $filterValue) {
    try {
        $studentsConn = getStudentsConnection();
        $staffConn = getStaffConnection();
        
        $query = "SELECT DISTINCT u.id, u.index_number, u.full_name, u.phone 
                  FROM users u";
        
        switch ($filterBy) {
            case 'all_cleared':
                $query = "SELECT DISTINCT u.id, u.index_number, u.full_name, u.phone
                          FROM users u
                          JOIN student_requirements sr ON u.id = sr.student_id
                          WHERE u.role = 'student' AND u.is_active = 1
                          GROUP BY u.id
                          HAVING SUM(CASE WHEN sr.is_cleared = 0 THEN 1 ELSE 0 END) = 0
                          ORDER BY u.full_name ASC";
                return $studentsConn->query($query)->fetch_all(MYSQLI_ASSOC);
                
            case 'pending':
                $query = "SELECT DISTINCT u.id, u.index_number, u.full_name, u.phone
                          FROM users u
                          JOIN student_requirements sr ON u.id = sr.student_id
                          WHERE u.role = 'student' AND u.is_active = 1 AND sr.is_cleared = 0
                          ORDER BY u.full_name ASC";
                return $studentsConn->query($query)->fetch_all(MYSQLI_ASSOC);
                
            case 'initialized':
                $query = "SELECT DISTINCT u.id, u.index_number, u.full_name, u.phone
                          FROM users u
                          JOIN student_requirements sr ON u.id = sr.student_id
                          WHERE u.role = 'student' AND u.is_active = 1
                          ORDER BY u.full_name ASC";
                return $studentsConn->query($query)->fetch_all(MYSQLI_ASSOC);
                
            default:
                $query .= " WHERE u.role = 'student' AND u.is_active = 1 ORDER BY u.full_name ASC";
                return $studentsConn->query($query)->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error filtering students: " . $e->getMessage());
        return [];
    }
}

/**
 * Export requirements to CSV
 */
function exportRequirementsToCSV() {
    try {
        $conn = getStudentsConnection();
        $staffConn = getStaffConnection();
        
        // Get all students
        $students = $conn->query("
            SELECT id, index_number, full_name, phone FROM users 
            WHERE role = 'student' AND is_active = 1 
            ORDER BY full_name
        ")->fetch_all(MYSQLI_ASSOC);
        
        // Get all requirement items
        $items = $staffConn->query("
            SELECT id, name, category FROM requirement_items 
            WHERE status = 'active'
            ORDER BY category, name
        ")->fetch_all(MYSQLI_ASSOC);
        
        // Create CSV content
        $csv = "Student Admission Number,Student Name,Student Phone";
        
        foreach ($items as $item) {
            $csv .= "," . str_replace(',', ' ', $item['name']);
        }
        $csv .= ",Overall Progress\n";
        
        // Add student data
        foreach ($students as $student) {
            $progress = getStudentProgress($student['id']);
            $csv .= $student['index_number'] . "," 
                  . str_replace(',', ' ', $student['full_name']) . "," 
                  . $student['phone'];
            
            $requirements = getStudentRequirements($student['id']);
            $reqMap = [];
            
            foreach ($requirements as $req) {
                $reqMap[$req['requirement_item_id']] = $req['is_cleared'] ? 'Yes' : 'No';
            }
            
            foreach ($items as $item) {
                $csv .= "," . ($reqMap[$item['id']] ?? 'No');
            }
            
            $csv .= "," . $progress['percentage'] . "%\n";
        }
        
        return $csv;
    } catch (Exception $e) {
        error_log("Error exporting CSV: " . $e->getMessage());
        return null;
    }
}
?>
