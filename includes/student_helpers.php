<?php
/**
 * Universal student lookup â€” searches igangaschool_students
 * and falls back to Excel data files in students_data/
 */

if (!function_exists('findStudents')) {
    function findStudents(string $term, int $limit = 50): array {
        $results = [];
        $term = trim($term);

        // â”€â”€ 1. Search students DB â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        try {
            $conn = getStudentsConnection();
            if ($conn) {
                $like = '%' . $term . '%';
                $sql  = "SELECT
                            id,
                            COALESCE(student_number, index_number) AS student_number,
                            index_number,
                            TRIM(CONCAT(first_name,' ',COALESCE(other_name,''),' ',surname)) AS full_name,
                            first_name, surname, other_name,
                            email, phone,
                            COALESCE(program, course)  AS program,
                            COALESCE(level, year_of_study) AS level,
                            COALESCE(set_name, intake_period) AS set_name,
                            status, created_at
                        FROM students
                        WHERE status = 'Active'
                          AND (
                            index_number LIKE ?
                            OR student_number LIKE ?
                            OR first_name LIKE ?
                            OR surname LIKE ?
                            OR other_name LIKE ?
                            OR CONCAT(first_name,' ',surname) LIKE ?
                            OR email LIKE ?
                            OR phone LIKE ?
                          )
                        ORDER BY surname, first_name
                        LIMIT ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $types = 'ssssssssi';
                    $params = [$like, $like, $like, $like, $like, $like, $like, $like, $limit];
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res) {
                        while ($row = $res->fetch_assoc()) {
                            $results[] = $row;
                        }
                    }
                    $stmt->close();
                }
            }
        } catch (Throwable $e) {
            error_log('findStudents DB: ' . $e->getMessage());
        }

        // â”€â”€ 2. Supplement from Excel files if DB returned nothing â”€
        if (empty($results)) {
            try {
                $loaderFile = __DIR__ . '/../views/student_data_loader.php';
                if (file_exists($loaderFile)) {
                    require_once $loaderFile;
                    $loader = new StudentDataLoader();
                    $fileResults = $loader->searchStudents($term);
                    foreach (array_slice($fileResults, 0, $limit) as $s) {
                        $results[] = [
                            'id'             => null,
                            'student_number' => $s['index_number'] ?? '',
                            'index_number'   => $s['index_number'] ?? '',
                            'full_name'      => $s['full_name'] ?? '',
                            'first_name'     => explode(' ', trim($s['full_name'] ?? ''))[0] ?? '',
                            'surname'        => implode(' ', array_slice(explode(' ', trim($s['full_name'] ?? '')), 1)) ?: '',
                            'other_name'     => '',
                            'email'          => $s['email'] ?? '',
                            'phone'          => $s['phone'] ?? '',
                            'program'        => $s['program'] ?? '',
                            'level'          => $s['level'] ?? '',
                            'set_name'       => $s['set'] ?? '',
                            'status'         => 'Active',
                            'created_at'     => null,
                            'source'         => 'file',
                        ];
                    }
                }
            } catch (Throwable $e) {
                error_log('findStudents files: ' . $e->getMessage());
            }
        }

        return $results;
    }
}

if (!function_exists('getStudentByIndex')) {
    function getStudentByIndex(string $indexNumber): ?array {
        $rows = findStudents($indexNumber, 1);
        foreach ($rows as $r) {
            if (strcasecmp(trim($r['index_number'] ?? ''), trim($indexNumber)) === 0) {
                return $r;
            }
        }
        return $rows[0] ?? null;
    }
}

if (!function_exists('getAllStudents')) {
    function getAllStudents(int $limit = 500): array {
        return findStudents('', $limit);
    }
}

if (!function_exists('getStudentCount')) {
    function getStudentCount(): int {
        try {
            $conn = getStudentsConnection();
            if ($conn) {
                $r = $conn->query("SELECT COUNT(*) AS c FROM students WHERE status='Active'");
                if ($r) return (int)($r->fetch_assoc()['c'] ?? 0);
            }
        } catch (Throwable $e) { error_log('student_helpers getRecords: ' . $e->getMessage()); }

        // Fallback: count from files
        try {
            $loaderFile = __DIR__ . '/../views/student_data_loader.php';
            if (file_exists($loaderFile)) {
                require_once $loaderFile;
                $loader = new StudentDataLoader();
                return count($loader->loadAllStudents());
            }
        } catch (Throwable $e) { error_log('student_helpers fallback: ' . $e->getMessage()); }

        return 0;
    }
}
