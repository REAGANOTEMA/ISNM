<?php
/**
 * Cross-database staff record synchronization.
 * Propagates staff INSERT/UPDATE across the staffs database
 * and creates activity log entries for auditing.
 */

if (!function_exists('syncStaffRecord')) {
    function syncStaffRecord(array $staff, string $mode = 'insert'): array {
        $results = [];
        $errors  = [];

        $conn = null;
        if (function_exists('getStaffConnection')) {
            $conn = getStaffConnection();
        }
        if (!$conn) {
            return ['success' => false, 'results' => [], 'errors' => ['db' => 'No staff connection']];
        }

        $staffIdField = !empty($staff['id']) ? 'id' : (!empty($staff['staff_id']) ? 'staff_id' : null);
        if (!$staffIdField && $mode === 'update') {
            return ['success' => false, 'results' => [], 'errors' => ['validation' => 'Missing staff identifier for update']];
        }

        $cols = ['staff_id','full_name','email','password','phone','position','department',
                 'role_id','staff_category','gender','highest_qualification','nin',
                 'year_of_experience','date_of_birth','status','hire_date','address',
                 'marital_status','nationality','religion'];

        $present = [];
        $placeholders = [];
        $types = '';
        $values = [];
        $updateParts = [];

        foreach ($cols as $c) {
            if (array_key_exists($c, $staff) && $staff[$c] !== null && $staff[$c] !== '') {
                $present[] = $c;
                $placeholders[] = '?';
                $types .= 's';
                $values[] = (string)$staff[$c];
                $updateParts[] = "$c = VALUES($c)";
            }
        }

        if (empty($present)) {
            return ['success' => false, 'results' => [], 'errors' => ['validation' => 'No data columns provided']];
        }

        $colsList = implode(',', $present);
        $phList   = implode(',', $placeholders);
        $updList  = implode(',', $updateParts);

        $tableExists = $conn->query("SHOW TABLES LIKE 'staff'");
        if (!$tableExists || $tableExists->num_rows === 0) {
            return ['success' => false, 'results' => [], 'errors' => ['db' => 'staff table not found']];
        }

        if ($mode === 'insert') {
            $sql = "INSERT INTO staff ($colsList) VALUES ($phList) ON DUPLICATE KEY UPDATE $updList";
        } else {
            $setParts = [];
            $whereVal = null;
            foreach ($cols as $c) {
                if (array_key_exists($c, $staff) && $staff[$c] !== null && $staff[$c] !== '') {
                    $setParts[] = "$c = ?";
                }
            }
            $idField = !empty($staff['id']) ? 'id' : 'staff_id';
            $idVal = $staff['id'] ?? $staff['staff_id'] ?? '';
            $values2 = [];
            $types2 = '';
            foreach ($cols as $c) {
                if (array_key_exists($c, $staff) && $staff[$c] !== null && $staff[$c] !== '') {
                    $values2[] = (string)$staff[$c];
                    $types2 .= 's';
                }
            }
            $values2[] = $idVal;
            $types2 .= is_numeric($idVal) ? 'i' : 's';
            $sql = "UPDATE staff SET " . implode(',', $setParts) . " WHERE $idField = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types2, ...$values2);
                $results['staff'] = $stmt->execute();
                if (!$results['staff']) $errors['staff'] = $stmt->error;
                $stmt->close();
            } else {
                $errors['staff'] = 'Prepare failed: ' . $conn->error;
                $results['staff'] = false;
            }
            return ['success' => ($results['staff'] ?? false), 'results' => $results, 'errors' => $errors];
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'results' => [], 'errors' => ['db' => 'Prepare failed: ' . $conn->error]];
        }

        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }

        $ok = $stmt->execute();
        $results['staff'] = $ok;
        if (!$ok) $errors['staff'] = $stmt->error;
        $stmt->close();

        return ['success' => $ok, 'results' => $results, 'errors' => $errors];
    }
}

if (!function_exists('deleteStaffAcrossDatabases')) {
    function deleteStaffAcrossDatabases(int $staffId): array {
        $conn = function_exists('getStaffConnection') ? getStaffConnection() : null;
        if (!$conn) return ['success' => false, 'errors' => ['db' => 'No connection']];

        $stmt = $conn->prepare("UPDATE staff SET status = 'Inactive' WHERE id = ? AND status = 'Active'");
        if (!$stmt) return ['success' => false, 'errors' => ['db' => 'Prepare failed']];
        $stmt->bind_param('i', $staffId);
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();
        return ['success' => $ok, 'errors' => $ok ? [] : ['db' => $err]];
    }
}
