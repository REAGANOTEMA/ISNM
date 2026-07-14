<?php
/**
 * Student Search API Endpoint
 * GET /api/student-search.php?q=search_term&page=1
 *
 * Returns paginated JSON results searching across multiple student fields.
 * Requires authenticated staff session.
 */

session_start();

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['type'] ?? '') !== 'staff') {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../includes/Response.php';
require_once __DIR__ . '/../includes/database_connections.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::methodNotAllowed('GET');
}

$query = trim($_GET['q'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

if ($query === '') {
    Response::json(['students' => [], 'total' => 0, 'page' => 1, 'per_page' => $limit, 'total_pages' => 0]);
}

$conn = DatabaseConnection::getStudentsConnection();
if (!$conn) {
    Response::error('Database connection failed', 500);
}

$searchTerm = '%' . $query . '%';
$likeClause = "CONCAT_WS(' ', student_number, first_name, last_name, national_student_id_number, registration_number, phone, email, program) LIKE ?";

try {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE $likeClause");
    if (!$countStmt) {
        Response::error('Query preparation failed', 500);
    }
    $countStmt->bind_param('s', $searchTerm);
    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
} catch (Throwable $e) {
    error_log("[StudentSearch] Count error: " . $e->getMessage());
    Response::error('Search query failed', 500);
}

$totalPages = (int)ceil($total / $limit);

if ($total === 0) {
    Response::json(['students' => [], 'total' => 0, 'page' => $page, 'per_page' => $limit, 'total_pages' => 0]);
}

try {
    $sql = "SELECT id, student_number, first_name, last_name, email, phone, gender,
                   national_student_id_number, registration_number, program,
                   current_semester, year, status, current_level, date_of_birth,
                   address, emergency_contact, emergency_phone, created_at, updated_at
            FROM students
            WHERE $likeClause
            ORDER BY
                CASE WHEN student_number LIKE ? THEN 0
                     WHEN CONCAT(first_name, ' ', last_name) LIKE ? THEN 1
                     WHEN student_number LIKE ? THEN 2
                     ELSE 3
                END,
                last_name ASC, first_name ASC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        Response::error('Query preparation failed', 500);
    }

    $matchNumber  = $query;
    $matchName    = '%' . $query . '%';
    $matchPartial = '%' . $query . '%';
    $stmt->bind_param(
        'ssssii',
        $searchTerm,
        $matchNumber,
        $matchName,
        $matchPartial,
        $limit,
        $offset
    );

    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];

    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['current_semester'] = (int)$row['current_semester'];
        $row['year'] = (int)$row['year'];
        $students[] = $row;
    }

    $stmt->close();

    Response::json([
        'students'    => $students,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $limit,
        'total_pages' => $totalPages,
    ]);

} catch (Throwable $e) {
    error_log("[StudentSearch] Query error: " . $e->getMessage());
    Response::error('Search query failed', 500);
}
