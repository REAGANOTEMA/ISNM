<?php
/**
 * Student Requirements Management Model
 * Handles admission requirements CRUD, status tracking, and document uploads.
 */

require_once __DIR__ . '/../config/database.php';

class StudentRequirements {
    private $conn;

    public function __construct() {
        $this->conn = getStudentsConnection();
    }

    /**
     * Get all admission requirements (master list).
     */
    public function getAllRequirements(): array {
        $stmt = $this->conn->prepare("SELECT * FROM admission_requirements WHERE is_active = 1 ORDER BY display_order ASC");
        if (!$stmt) return [];
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $r = $stmt->get_result();
        $reqs = [];
        while ($row = $r->fetch_assoc()) $reqs[] = $row;
        $stmt->close();
        return $reqs;
    }

    /**
     * Get requirements status for a specific student.
     */
    public function getStudentRequirements(int $studentId): array {
        $stmt = $this->conn->prepare("
            SELECT srs.*, ar.requirement_name, ar.type as requirement_type, ar.is_mandatory, ar.display_order
            FROM student_requirements_status srs
            JOIN admission_requirements ar ON srs.requirement_id = ar.id
            WHERE srs.student_id = ?
            ORDER BY ar.display_order ASC
        ");
        $stmt->bind_param('i', $studentId);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $r = $stmt->get_result();
        $reqs = [];
        while ($row = $r->fetch_assoc()) $reqs[] = $row;
        $stmt->close();
        return $reqs;
    }

    /**
     * Update requirement status for a student.
     */
    public function updateRequirementStatus(int $studentId, int $requirementId, string $status, string $remarks = '', ?int $verifiedBy = null, ?string $verifiedByName = null): bool {
        $validStatuses = ['Not Submitted', 'Pending', 'Submitted', 'Verified', 'Rejected', 'Missing', 'Received', 'Not Yet Given'];
        if (!in_array($status, $validStatuses)) return false;

        $verifiedAt = ($status === 'Verified') ? date('Y-m-d H:i:s') : null;
        $submissionDate = in_array($status, ['Submitted', 'Received', 'Verified']) ? date('Y-m-d') : null;

        $sql = "INSERT INTO student_requirements_status (student_id, requirement_id, status, remarks, verified_by, verified_by_name, submission_date, verified_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  status = VALUES(status),
                  remarks = VALUES(remarks),
                  verified_by = VALUES(verified_by),
                  verified_by_name = VALUES(verified_by_name),
                  submission_date = COALESCE(VALUES(submission_date), submission_date),
                  verified_at = COALESCE(VALUES(verified_at), verified_at),
                  updated_at = NOW()";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iissssss', $studentId, $requirementId, $status, $remarks, $verifiedBy, $verifiedByName, $submissionDate, $verifiedAt);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Upload a document for a requirement.
     */
    public function uploadRequirementDocument(int $studentId, int $requirementId, array $file): array {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }

        $uploadDir = __DIR__ . '/../studentUploads/requirements/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "req_{$studentId}_{$requirementId}_" . time() . ".{$ext}";
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $docPath = 'studentUploads/requirements/' . $filename;
            $docName = $file['name'];

            $stmt = $this->conn->prepare("UPDATE student_requirements_status SET document_path = ?, document_name = ?, status = 'Submitted', submission_date = CURDATE() WHERE student_id = ? AND requirement_id = ?");
            $stmt->bind_param('ssii', $docPath, $docName, $studentId, $requirementId);
            $result = $stmt->execute();
            $stmt->close();

            return $result ? ['success' => true, 'path' => $docPath, 'name' => $docName] : ['success' => false, 'error' => 'Database update failed'];
        }
        return ['success' => false, 'error' => 'File upload failed'];
    }

    /**
     * Get requirements completion summary for a student.
     */
    public function getRequirementsSummary(int $studentId): array {
        $reqs = $this->getStudentRequirements($studentId);

        $total = count($reqs);
        $verified = 0;
        $pending = 0;
        $missing = 0;
        $mandatory = 0;
        $mandatoryVerified = 0;

        foreach ($reqs as $r) {
            $st = $r['status'] ?? '';
            if (in_array($st, ['Verified', 'Received'])) $verified++;
            elseif (in_array($st, ['Not Submitted', 'Pending'])) $pending++;
            elseif ($st === 'Missing') $missing++;

            if ($r['is_mandatory'] ?? 0) {
                $mandatory++;
                if (in_array($st, ['Verified', 'Received'])) $mandatoryVerified++;
            }
        }

        $completionRate = $total > 0 ? round(($verified / $total) * 100, 1) : 0;
        $mandatoryCompletionRate = $mandatory > 0 ? round(($mandatoryVerified / $mandatory) * 100, 1) : 0;

        return [
            'total' => $total,
            'verified' => $verified,
            'pending' => $pending,
            'missing' => $missing,
            'mandatory' => $mandatory,
            'mandatory_verified' => $mandatoryVerified,
            'completion_rate' => $completionRate,
            'mandatory_completion_rate' => $mandatoryCompletionRate,
            'all_verified' => $verified === $total,
            'all_mandatory_verified' => $mandatoryVerified === $mandatory,
        ];
    }

    /**
     * Bulk update requirements status for a student.
     */
    public function bulkUpdateRequirements(int $studentId, array $updates, ?int $verifiedBy = null, ?string $verifiedByName = null): bool {
        $this->conn->begin_transaction();
        try {
            foreach ($updates as $update) {
                $reqId = intval($update['requirement_id'] ?? 0);
                $status = trim($update['status'] ?? '');
                $remarks = trim($update['remarks'] ?? '');

                if ($reqId < 1 || empty($status)) continue;

                if (!$this->updateRequirementStatus($studentId, $reqId, $status, $remarks, $verifiedBy, $verifiedByName)) {
                    throw new Exception("Failed to update requirement $reqId");
                }
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log('StudentRequirements bulk update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get requirements statistics across all students.
     */
    public function getRequirementsStatistics(): array {
        $stats = [];

        // Overall completion
        $r = $this->conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified FROM student_requirements_status");
        $row = $r ? $r->fetch_assoc() : ['total' => 0, 'verified' => 0];
        $stats['overall'] = [
            'total' => (int)($row['total'] ?? 0),
            'verified' => (int)($row['verified'] ?? 0),
            'completion_rate' => $row['total'] > 0 ? round(($row['verified'] / $row['total']) * 100, 1) : 0,
        ];

        // By requirement type
        $r = $this->conn->query("SELECT ar.requirement_name, ar.type, ar.is_mandatory, COUNT(srs.id) as total, SUM(CASE WHEN srs.status = 'Verified' THEN 1 ELSE 0 END) as verified FROM admission_requirements ar LEFT JOIN student_requirements_status srs ON ar.id = srs.requirement_id GROUP BY ar.id ORDER BY ar.display_order");
        $stats['by_requirement'] = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        // Students with all requirements complete
        $r = $this->conn->query("SELECT COUNT(DISTINCT student_id) as count FROM student_requirements_status WHERE status = 'Verified'");
        $stats['fully_complete'] = (int)($r->fetch_assoc()['count'] ?? 0);

        // Students with pending requirements
        $r = $this->conn->query("SELECT COUNT(DISTINCT student_id) as count FROM student_requirements_status WHERE status IN ('Not Submitted','Pending')");
        $stats['pending_count'] = (int)($r->fetch_assoc()['count'] ?? 0);

        return $stats;
    }
}
