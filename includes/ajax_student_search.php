<?php
/**
 * Centralized AJAX Student Search Endpoint — ISNM
 *
 * Single entry-point for all staff dashboards. Searches across:
 *   1. students table (students_db)
 *   2. Excel data files (students_data/)
 *
 * Accepts: GET or POST
 *   q       — search term (name, index number, student number, phone, email)
 *   program — filter by program name
 *   level   — filter by level (Certificate / Diploma / Degree)
 *   set     — filter by set_name
 *   gender  — filter by gender
 *   status  — filter by status (default: excludes 'deleted')
 *   limit   — max results (default 50, max 100)
 *
 * Returns JSON:
 *   { success: bool, students: [...], count: int }
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';
require_once __DIR__ . '/../views/student_data_loader.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/ISNM/');
    session_start();
}

/* ── Authentication ── */
$auth_service = new AuthenticationService();
if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access', 'students' => [], 'count' => 0]);
    exit;
}

$role = $_SESSION['role'] ?? '';
if (!$auth_service->canSearchStudentProfiles($role)) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to search student profiles', 'students' => [], 'count' => 0]);
    exit;
}

/* ── CSRF validation (POST only — GET used by some dashboards) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token', 'students' => [], 'count' => 0]);
        exit;
    }
}

/* ── Parse parameters ── */
$q       = trim($_REQUEST['q'] ?? $_REQUEST['term'] ?? $_REQUEST['query'] ?? '');
$program = trim($_REQUEST['program'] ?? '');
$level   = trim($_REQUEST['level'] ?? '');
$set     = trim($_REQUEST['set'] ?? '');
$gender  = trim($_REQUEST['gender'] ?? '');
$status  = trim($_REQUEST['status'] ?? '');
$limit   = min(max((int)($_REQUEST['limit'] ?? 50), 1), 100);

if (strlen($q) < 2 && empty($program) && empty($level) && empty($set) && empty($gender)) {
    echo json_encode(['success' => false, 'message' => 'Search term must be at least 2 characters', 'students' => [], 'count' => 0]);
    exit;
}

$results = [];
$seen    = [];

/* ── Helper: normalise a key for dedup ── */
$dedupKey = function ($row) {
    $num = trim($row['student_number'] ?? $row['index_number'] ?? $row['student_id'] ?? '');
    if ($num !== '') return 'n:' . strtolower($num);
    $name = strtolower(trim($row['full_name'] ?? ($row['surname'] ?? '') . ' ' . ($row['first_name'] ?? '')));
    $em   = strtolower(trim($row['email'] ?? ''));
    return 'e:' . $name . '|' . $em;
};

/* ── Helper: normalise a row into the standard shape ── */
$normalise = function ($row, $source) {
    $fn  = trim($row['first_name'] ?? '');
    $sn  = trim($row['surname'] ?? $row['last_name'] ?? '');
    $on  = trim($row['other_name'] ?? '');
    $full = trim($row['full_name'] ?? '');
    if ($full === '' && ($fn !== '' || $sn !== '')) {
        $full = trim($fn . ' ' . $on . ' ' . $sn);
    }
    return [
        'id'             => (int)($row['id'] ?? 0),
        'student_id'     => $row['student_id'] ?? $row['index_number'] ?? $row['student_number'] ?? '',
        'student_number' => $row['student_number'] ?? $row['student_id'] ?? '',
        'index_number'   => $row['index_number'] ?? $row['student_number'] ?? $row['student_id'] ?? '',
        'first_name'     => $fn,
        'surname'        => $sn,
        'other_name'     => $on,
        'full_name'      => $full,
        'program'        => $row['program'] ?? '',
        'level'          => $row['level'] ?? '',
        'set_name'       => $row['set_name'] ?? $row['set'] ?? '',
        'gender'         => $row['gender'] ?? '',
        'phone'          => $row['phone'] ?? $row['mobile_number'] ?? '',
        'email'          => $row['email'] ?? '',
        'status'         => $row['status'] ?? 'Active',
        'date_of_birth'  => $row['date_of_birth'] ?? '',
        'passport_photo' => $row['passport_photo'] ?? $row['profile_picture'] ?? '',
        'source'         => $source,
    ];
};

/* ── 1. Search the students DB ── */
$stuConn = getStudentsConnection();
if ($stuConn) {
    $conditions = [];
    $params     = [];
    $types      = '';

    if (strlen($q) >= 2) {
        $like = '%' . $q . '%';
        $conditions[] = '(first_name LIKE ? OR surname LIKE ? OR other_name LIKE ?
                          OR CONCAT(first_name," ",COALESCE(surname,"")) LIKE ?
                          OR student_id LIKE ? OR student_number LIKE ? OR index_number LIKE ?
                          OR registration_number LIKE ?
                          OR phone LIKE ? OR mobile_number LIKE ? OR email LIKE ?)';
        $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like]);
        $types .= str_repeat('s', 11);
    }

    if ($program !== '') { $conditions[] = 'program LIKE ?'; $params[] = '%' . $program . '%'; $types .= 's'; }
    if ($level !== '')   { $conditions[] = 'level = ?';       $params[] = $level;              $types .= 's'; }
    if ($set !== '')     { $conditions[] = 'set_name LIKE ?'; $params[] = '%' . $set . '%';    $types .= 's'; }
    if ($gender !== '')  { $conditions[] = 'gender = ?';      $params[] = $gender;             $types .= 's'; }

    if ($status !== '') {
        $conditions[] = 'status = ?';
        $params[]     = $status;
        $types       .= 's';
    } else {
        $conditions[] = "status != 'deleted'";
    }

    $where = implode(' AND ', $conditions);
    $sql   = "SELECT id, student_id, student_number, index_number, registration_number,
                     first_name, surname, other_name,
                     CONCAT(first_name,' ',COALESCE(surname,'')) AS full_name,
                     program, level, set_name, gender,
                     phone, mobile_number, email, date_of_birth,
                     passport_photo, profile_picture, status
              FROM students
              WHERE $where
              ORDER BY
                CASE WHEN student_id = ? THEN 0
                     WHEN student_number = ? THEN 1
                     WHEN index_number = ? THEN 2
                     ELSE 3 END,
                full_name ASC
              LIMIT ?";
    $params[] = $q;
    $params[] = $q;
    $params[] = $q;
    $params[] = $limit;
    $types   .= 'sss';

    $stmt = $stuConn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $norm   = $normalise($row, 'DB');
                    $key    = $dedupKey($norm);
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $results[]  = $norm;
                    }
                }
            }
        } else {
            error_log('ajax_student_search DB execute failed: ' . ($stmt->error ?? 'unknown'));
        }
        $stmt->close();
    }
}

/* ── 2. Search Excel files via StudentDataLoader ── */
try {
    $loader        = new StudentDataLoader($stuConn);
    $excelFilters  = [];
    if ($program !== '') $excelFilters['program'] = $program;
    if ($level !== '')   $excelFilters['level']   = $level;
    if ($set !== '')     $excelFilters['set']     = $set;
    if ($gender !== '')  $excelFilters['gender']  = $gender;

    $excelResults = $loader->searchStudents($q, $excelFilters);

    foreach ($excelResults as $er) {
        $norm = $normalise($er, 'Excel');
        $key  = $dedupKey($norm);
        if (!isset($seen[$key])) {
            $seen[$key]    = true;
            $norm['status'] = 'Active';
            $results[]     = $norm;
        }
    }
} catch (Exception $e) {
    error_log('ajax_student_search Excel loader: ' . $e->getMessage());
}

/* ── Trim to limit and return ── */
$results = array_slice($results, 0, $limit);

echo json_encode([
    'success'  => true,
    'students' => $results,
    'count'    => count($results),
], JSON_UNESCAPED_UNICODE);
