<?php
/**
 * Cross-database student record synchronization.
 * Propagates student INSERT/UPDATE across all 4 ISNM databases
 * so searches and cross-module references stay consistent.
 */

if (!function_exists('syncStudentRecord')) {
    /**
     * Synchronize a student record across all 4 databases.
     *
     * @param array $student Associative array of student fields
     * @param string $mode 'insert' for new records, 'update' for existing
     * @return array{success: bool, results: array<string, bool>, errors: array<string, string>}
     */
    function syncStudentRecord(array $student, string $mode = 'insert'): array {
        $results = [];
        $errors  = [];

        $requiredFields = ['student_number', 'first_name', 'surname'];
        foreach ($requiredFields as $f) {
            if (empty($student[$f])) {
                return ['success' => false, 'results' => [], 'errors' => ['validation' => "Missing required field: $f"]];
            }
        }

        $targets = [
            'staffs'   => ['conn' => null, 'getter' => 'getStaffConnection'],
            'students' => ['conn' => null, 'getter' => 'getStudentsConnection'],
            'website'  => ['conn' => null, 'getter' => 'getWebsiteConnection'],
            'ict'      => ['conn' => null, 'getter' => 'getICTConnection'],
        ];

        foreach ($targets as $label => &$t) {
            if (function_exists($t['getter'])) {
                $t['conn'] = call_user_func($t['getter']);
            }
        }
        unset($t);

        $cols = ['student_id', 'student_number', 'admission_number', 'index_number', 'registration_number',
                 'national_student_id_number', 'first_name', 'surname', 'other_name', 'full_name',
                 'email', 'phone', 'mobile_number', 'gender', 'date_of_birth',
                 'program', 'course', 'level', 'set_name', 'current_year', 'current_semester',
                 'intake', 'status', 'guardian_name', 'guardian_phone', 'guardian_email',
                 'emergency_contact_name', 'emergency_contact_phone',
                 'district', 'county', 'address', 'nationality', 'religion', 'marital_status',
                 'student_category', 'sponsor', 'stream', 'class_name',
                 'photo_path', 'application_id', 'password'];

        $present = [];
        $placeholders = [];
        $types = '';
        $values = [];
        $updateParts = [];

        foreach ($cols as $c) {
            if (array_key_exists($c, $student) && $student[$c] !== null && $student[$c] !== '') {
                $present[] = $c;
                $placeholders[] = '?';
                $types .= 's';
                $values[] = (string)$student[$c];
                $updateParts[] = "$c = VALUES($c)";
            }
        }

        if (empty($present)) {
            return ['success' => false, 'results' => [], 'errors' => ['validation' => 'No data columns provided']];
        }

        $colsList = implode(',', $present);
        $phList   = implode(',', $placeholders);
        $updList  = implode(',', $updateParts);

        foreach ($targets as $label => $t) {
            $conn = $t['conn'];
            if (!$conn) {
                $errors[$label] = 'No connection';
                $results[$label] = false;
                continue;
            }

            $tableExists = $conn->query("SHOW TABLES LIKE 'students'");
            if (!$tableExists || $tableExists->num_rows === 0) {
                $results[$label] = true;
                continue;
            }

            $sql = "INSERT INTO students ($colsList) VALUES ($phList) ON DUPLICATE KEY UPDATE $updList";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $errors[$label] = 'Prepare failed: ' . $conn->error;
                $results[$label] = false;
                continue;
            }

            if (!empty($values)) {
                $stmt->bind_param($types, ...$values);
            }

            if ($stmt->execute()) {
                $results[$label] = true;
            } else {
                $errors[$label] = 'Execute failed: ' . $stmt->error;
                $results[$label] = false;
            }
            $stmt->close();
        }

        $allOk = count(array_filter($results)) === count($results);
        return ['success' => $allOk, 'results' => $results, 'errors' => $errors];
    }
}

if (!function_exists('deleteStudentAcrossDatabases')) {
    /**
     * Soft-delete a student record across all databases by student_number.
     */
    function deleteStudentAcrossDatabases(string $studentNumber): array {
        $results = [];
        $errors  = [];

        $targets = [
            'staffs'   => 'getStaffConnection',
            'students' => 'getStudentsConnection',
            'website'  => 'getWebsiteConnection',
            'ict'      => 'getICTConnection',
        ];

        foreach ($targets as $label => $getter) {
            if (!function_exists($getter)) {
                $errors[$label] = 'Getter not found';
                $results[$label] = false;
                continue;
            }
            $conn = call_user_func($getter);
            if (!$conn) {
                $errors[$label] = 'No connection';
                $results[$label] = false;
                continue;
            }

            $tableExists = $conn->query("SHOW TABLES LIKE 'students'");
            if (!$tableExists || $tableExists->num_rows === 0) {
                $results[$label] = true;
                continue;
            }

            $stmt = $conn->prepare("UPDATE students SET status = 'deleted' WHERE student_number = ? AND status != 'deleted'");
            if (!$stmt) {
                $errors[$label] = 'Prepare failed';
                $results[$label] = false;
                continue;
            }
            $stmt->bind_param('s', $studentNumber);
            $results[$label] = $stmt->execute();
            if (!$results[$label]) {
                $errors[$label] = $stmt->error;
            }
            $stmt->close();
        }

        $allOk = count(array_filter($results)) === count($results);
        return ['success' => $allOk, 'results' => $results, 'errors' => $errors];
    }
}

if (!function_exists('syncStatusAcrossDatabases')) {
    /**
     * Synchronize student status across all databases.
     */
    function syncStatusAcrossDatabases(string $studentNumber, string $newStatus): array {
        $results = [];
        $errors  = [];

        $targets = [
            'staffs'   => 'getStaffConnection',
            'students' => 'getStudentsConnection',
            'website'  => 'getWebsiteConnection',
            'ict'      => 'getICTConnection',
        ];

        foreach ($targets as $label => $getter) {
            if (!function_exists($getter)) { $errors[$label] = 'Getter not found'; $results[$label] = false; continue; }
            $conn = call_user_func($getter);
            if (!$conn) { $errors[$label] = 'No connection'; $results[$label] = false; continue; }

            $tableExists = $conn->query("SHOW TABLES LIKE 'students'");
            if (!$tableExists || $tableExists->num_rows === 0) { $results[$label] = true; continue; }

            $stmt = $conn->prepare("UPDATE students SET status = ?, updated_at = NOW() WHERE student_number = ?");
            if (!$stmt) { $errors[$label] = 'Prepare failed'; $results[$label] = false; continue; }
            $stmt->bind_param('ss', $newStatus, $studentNumber);
            $results[$label] = $stmt->execute();
            if (!$results[$label]) { $errors[$label] = $stmt->error; }
            $stmt->close();
        }

        $allOk = count(array_filter($results)) === count($results);
        return ['success' => $allOk, 'results' => $results, 'errors' => $errors];
    }
}
