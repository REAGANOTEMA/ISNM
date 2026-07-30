<?php
/**
 * ISNM Cross-Dashboard Integration Helper
 * Provides shared functionality for all dashboards to ensure
 * consistent student data across the system.
 */

require_once __DIR__ . '/../config/database.php';

if (!function_exists('getCrossDashboardStudent')) {
    /**
     * Get a student by ID with all cross-dashboard data merged.
     * Queries the students DB as primary, cross-references staffs DB if needed.
     */
    function getCrossDashboardStudent(int $studentId): ?array {
        $conn = getStudentsConnection();
        if (!$conn) return null;

        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND status != 'deleted' LIMIT 1");
        $stmt->bind_param('i', $studentId);
        if (!$stmt->execute()) return null;
        $r = $stmt->get_result();
        $student = $r ? $r->fetch_assoc() : null;
        $stmt->close();

        if (!$student) return null;

        // Enrich with financial profile
        $stmt = $conn->prepare("SELECT * FROM student_financial_profiles WHERE student_id = ? LIMIT 1");
        $stmt->bind_param('i', $studentId);
        if ($stmt->execute()) {
            $r = $stmt->get_result();
            $student['_financial'] = $r ? $r->fetch_assoc() : null;
        }
        $stmt->close();

        // Enrich with academic profile
        $stmt = $conn->prepare("SELECT * FROM student_academic_profiles WHERE student_id = ? LIMIT 1");
        $stmt->bind_param('i', $studentId);
        if ($stmt->execute()) {
            $r = $stmt->get_result();
            $student['_academic'] = $r ? $r->fetch_assoc() : null;
        }
        $stmt->close();

        // Enrich with requirements status
        $stmt = $conn->prepare("SELECT srs.*, ar.requirement_name, ar.type as requirement_type, ar.is_mandatory FROM student_requirements_status srs JOIN admission_requirements ar ON srs.requirement_id = ar.id WHERE srs.student_id = ? ORDER BY ar.display_order");
        $stmt->bind_param('i', $studentId);
        if ($stmt->execute()) {
            $r = $stmt->get_result();
            $student['_requirements'] = $r ? isnm_fetch_all($r) : [];
        }
        $stmt->close();

        // Enrich with recent payments
        $stmt = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC LIMIT 10");
        $stmt->bind_param('i', $studentId);
        if ($stmt->execute()) {
            $r = $stmt->get_result();
            $student['_payments'] = $r ? isnm_fetch_all($r) : [];
        }
        $stmt->close();

        // Calculate total paid
        $totalPaid = 0;
        foreach (($student['_payments'] ?? []) as $p) {
            if (($p['status'] ?? '') === 'completed') {
                $totalPaid += floatval($p['amount_received'] ?? 0);
            }
        }
        $student['_total_paid'] = $totalPaid;

        // Enrich with documents
        $stmt = $conn->prepare("SELECT * FROM student_documents WHERE student_id = ? AND status = 'Active' ORDER BY created_at DESC");
        $stmt->bind_param('i', $studentId);
        if ($stmt->execute()) {
            $r = $stmt->get_result();
            $student['_documents'] = $r ? isnm_fetch_all($r) : [];
        }
        $stmt->close();

        // Enrich with status history
        $stmt = $conn->prepare("SELECT * FROM student_status_history WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->bind_param('i', $studentId);
        if ($stmt->execute()) {
            $r = $stmt->get_result();
            $student['_status_history'] = $r ? isnm_fetch_all($r) : [];
        }
        $stmt->close();

        return $student;
    }
}

if (!function_exists('getCrossDashboardStudentByNumber')) {
    /**
     * Get a student by student_number with all cross-dashboard data.
     */
    function getCrossDashboardStudentByNumber(string $studentNumber): ?array {
        $conn = getStudentsConnection();
        if (!$conn) return null;

        $stmt = $conn->prepare("SELECT id FROM students WHERE student_number = ? AND status != 'deleted' LIMIT 1");
        $stmt->bind_param('s', $studentNumber);
        if (!$stmt->execute()) return null;
        $r = $stmt->get_result();
        $row = $r ? $r->fetch_assoc() : null;
        $stmt->close();

        if (!$row) return null;

        return getCrossDashboardStudent((int)$row['id']);
    }
}

if (!function_exists('getCrossDashboardStudentByIndex')) {
    /**
     * Get a student by index_number.
     */
    function getCrossDashboardStudentByIndex(string $indexNumber): ?array {
        $conn = getStudentsConnection();
        if (!$conn) return null;

        $stmt = $conn->prepare("SELECT id FROM students WHERE index_number = ? AND status != 'deleted' LIMIT 1");
        $stmt->bind_param('s', $indexNumber);
        if (!$stmt->execute()) return null;
        $r = $stmt->get_result();
        $row = $r ? $r->fetch_assoc() : null;
        $stmt->close();

        if (!$row) return null;

        return getCrossDashboardStudent((int)$row['id']);
    }
}

if (!function_exists('dashboardSyncStatus')) {
    /**
     * Check sync status of a student across all databases.
     */
    function dashboardSyncStatus(string $studentNumber): array {
        if (!function_exists('getStaffConnection') || !function_exists('getStudentsConnection')) {
            return ['synced' => false, 'databases' => [], 'error' => 'Connection functions not available'];
        }

        $results = [];
        $dbs = [
            'students' => 'getStudentsConnection',
            'staffs'   => 'getStaffConnection',
            'website'  => 'getWebsiteConnection',
            'ict'      => 'getICTConnection',
        ];

        foreach ($dbs as $label => $getter) {
            if (!function_exists($getter)) {
                $results[$label] = ['exists' => false, 'error' => 'Getter not available'];
                continue;
            }

            $conn = call_user_func($getter);
            if (!$conn) {
                $results[$label] = ['exists' => false, 'error' => 'Connection failed'];
                continue;
            }

            $tableCheck = $conn->query("SHOW TABLES LIKE 'students'");
            if (!$tableCheck || $tableCheck->num_rows === 0) {
                $results[$label] = ['exists' => false, 'error' => 'Table not found'];
                continue;
            }

            $stmt = $conn->prepare("SELECT id, status, updated_at FROM students WHERE student_number = ? LIMIT 1");
            $stmt->bind_param('s', $studentNumber);
            if ($stmt->execute()) {
                $r = $stmt->get_result();
                $row = $r ? $r->fetch_assoc() : null;
                $results[$label] = [
                    'exists' => $row !== null,
                    'status' => $row['status'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                ];
            } else {
                $results[$label] = ['exists' => false, 'error' => $stmt->error];
            }
            $stmt->close();
        }

        $syncedCount = 0;
        foreach ($results as $r) {
            if ($r['exists']) $syncedCount++;
        }

        return [
            'synced' => $syncedCount >= 2,
            'databases' => $results,
            'synced_count' => $syncedCount,
            'total_databases' => count($results),
        ];
    }
}

if (!function_exists('logDashboardAction')) {
    /**
     * Log an action taken from any dashboard.
     */
    function logDashboardAction(string $action, string $entityType = 'student', ?int $entityId = null, string $description = ''): bool {
        $conn = getStudentsConnection();
        if (!$conn) return false;

        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? '';
        $role = $_SESSION['role'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, username, role, action, entity_type, entity_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssssiss', $userId, $username, $role, $action, $entityType, $entityId, $description, $ip, $ua);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}

if (!function_exists('getDashboardCounts')) {
    /**
     * Get quick counts for any dashboard sidebar/badges.
     */
    function getDashboardCounts(): array {
        $conn = getStudentsConnection();
        if (!$conn) return ['error' => 'Database connection failed'];

        $counts = [];

        $r = $conn->query("SELECT COUNT(*) as c FROM students WHERE status = 'Active'");
        $counts['active_students'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

        $r = $conn->query("SELECT COUNT(*) as c FROM students WHERE status != 'deleted'");
        $counts['total_students'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

        $r = $conn->query("SELECT COUNT(*) as c FROM students WHERE status = 'Pending' OR registration_status = 'Pending'");
        $counts['pending_registrations'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

        $r = $conn->query("SELECT COUNT(*) as c FROM students WHERE status = 'Graduated'");
        $counts['graduated'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

        $r = $conn->query("SELECT COUNT(*) as c FROM payments WHERE status = 'completed' AND payment_date = CURDATE()");
        $counts['payments_today'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

        $r = $conn->query("SELECT COALESCE(SUM(amount_received),0) as c FROM payments WHERE status = 'completed' AND payment_date = CURDATE()");
        $counts['revenue_today'] = $r ? (float)$r->fetch_assoc()['c'] : 0;

        $r = $conn->query("SELECT COUNT(*) as c FROM student_requirements_status WHERE status IN ('Not Submitted','Pending')");
        $counts['pending_requirements'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

        return $counts;
    }
}

if (!function_exists('getRoleDashboardConfig')) {
    /**
     * Get the dashboard configuration for a given role.
     * Returns the allowed sections, default view, and sync settings.
     */
    function getRoleDashboardConfig(string $role): array {
        $configs = [
            'admin' => [
                'sections' => ['overview', 'students', 'finance', 'reports', 'settings', 'users'],
                'can_edit_students' => true,
                'can_delete_students' => true,
                'can_manage_fees' => true,
                'can_view_reports' => true,
                'can_manage_users' => true,
                'sync_enabled' => true,
            ],
            'bursar' => [
                'sections' => ['overview', 'students', 'finance', 'reports'],
                'can_edit_students' => false,
                'can_delete_students' => false,
                'can_manage_fees' => true,
                'can_view_reports' => true,
                'can_manage_users' => false,
                'sync_enabled' => true,
            ],
            'school principal' => [
                'sections' => ['overview', 'students', 'academic', 'reports', 'announcements'],
                'can_edit_students' => true,
                'can_delete_students' => false,
                'can_manage_fees' => false,
                'can_view_reports' => true,
                'can_manage_users' => false,
                'sync_enabled' => true,
            ],
            'registrar' => [
                'sections' => ['overview', 'students', 'admissions', 'requirements'],
                'can_edit_students' => true,
                'can_delete_students' => false,
                'can_manage_fees' => false,
                'can_view_reports' => true,
                'can_manage_users' => false,
                'sync_enabled' => true,
            ],
            'director general' => [
                'sections' => ['overview', 'students', 'finance', 'academic', 'reports', 'announcements'],
                'can_edit_students' => true,
                'can_delete_students' => true,
                'can_manage_fees' => true,
                'can_view_reports' => true,
                'can_manage_users' => true,
                'sync_enabled' => true,
            ],
        ];

        $roleLower = strtolower($role);

        foreach ($configs as $key => $config) {
            if (strtolower($key) === $roleLower) {
                return $config;
            }
        }

        // Default for unknown roles
        return [
            'sections' => ['overview'],
            'can_edit_students' => false,
            'can_delete_students' => false,
            'can_manage_fees' => false,
            'can_view_reports' => false,
            'can_manage_users' => false,
            'sync_enabled' => false,
        ];
    }
}
