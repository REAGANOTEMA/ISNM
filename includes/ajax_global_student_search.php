<?php
/**
 * Global Student Search — AJAX bridge (backward-compatible)
 *
 * Callers expecting a flat array of results. Internally delegates to the
 * centralized endpoint logic but outputs the old format for global_search.php JS.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';
require_once __DIR__ . '/../views/student_data_loader.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', SESSION_COOKIE_PATH);
    session_start();
}

header('Content-Type: application/json');

/* ── CSRF validation ── */
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security token', 'students' => []]);
    exit;
}

/* ── Auth check ── */
$auth_service = new AuthenticationService();
if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'students' => []]);
    exit;
}

$role = $_SESSION['role'] ?? '';
if (!$auth_service->canSearchStudentProfiles($role)) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions', 'students' => []]);
    exit;
}

$q = trim($_POST['q'] ?? $_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];
$seen    = [];

$dedupKey = function ($row) {
    $num = trim($row['student_number'] ?? $row['index_number'] ?? $row['student_id'] ?? '');
    if ($num !== '') return 'n:' . strtolower($num);
    $name = strtolower(trim($row['full_name'] ?? ''));
    return 'e:' . $name;
};

/* ── Search DB ── */
$searchDb = function ($db, $source) use ($q, &$results, &$seen, $dedupKey) {
    if (!$db) return;
    $like = '%' . $q . '%';
    $stmt = $db->prepare(
        "SELECT id, student_id, student_number, index_number,
                CONCAT(first_name,' ',COALESCE(surname,'')) AS full_name,
                email, phone, program, level, set_name, status
         FROM students
         WHERE (first_name LIKE ? OR surname LIKE ?
                OR CONCAT(first_name,' ',COALESCE(surname,'')) LIKE ?
                OR student_id LIKE ? OR student_number LIKE ?
                OR registration_number LIKE ?
                OR phone LIKE ? OR email LIKE ?
                OR set_name LIKE ?)
         AND status != 'deleted'
         LIMIT 100"
    );
    if (!$stmt) return;
    $stmt->bind_param('sssssssss', $like, $like, $like, $like, $like, $like, $like, $like, $like);
    if (!$stmt->execute()) { error_log('global_search execute failed: ' . ($stmt->error ?? 'unknown')); }
    $rows = isnm_fetch_all($stmt->get_result());
    $stmt->close();

    foreach ($rows as $r) {
        $key = $dedupKey($r);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $r['_source'] = $source;
            $results[] = $r;
        }
    }
};

$stuConn = getStudentsConnection();
$staffDb = getStaffConnection();
$webConn = getWebsiteConnection();
$ictDb   = function_exists('getICTConnection') ? getICTConnection() : null;

$searchDb($stuConn, 'StudentsDB');
$searchDb($staffDb, 'StaffDB');
$searchDb($webConn, 'WebsiteDB');
$searchDb($ictDb,   'ICTDB');

/* ── Search Excel files ── */
try {
    $loader = new StudentDataLoader($stuConn);
    $excelResults = $loader->searchStudents($q);
    foreach ($excelResults as $er) {
        $num  = $er['student_number'] ?? $er['index_number'] ?? '';
        $name = strtolower(trim($er['full_name'] ?? ''));
        $key  = $num !== '' ? 'n:' . $num : 'e:' . $name;
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $results[] = [
                'id'             => 0,
                'student_id'     => $er['index_number'] ?? $er['student_number'] ?? '',
                'student_number' => $er['student_number'] ?? '',
                'index_number'   => $er['index_number'] ?? '',
                'full_name'      => $er['full_name'] ?? '',
                'email'          => $er['email'] ?? '',
                'phone'          => $er['phone'] ?? '',
                'program'        => $er['program'] ?? '',
                'level'          => $er['level'] ?? '',
                'set_name'       => $er['set_name'] ?? $er['set'] ?? '',
                'status'         => 'Active',
                '_source'        => 'Excel',
            ];
        }
    }
} catch (Exception $e) {
    error_log('global_search Excel: ' . $e->getMessage());
}

echo json_encode(array_values($results));
