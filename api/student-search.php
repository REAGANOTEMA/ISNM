<?php
/**
 * Student Search API Endpoint
 * GET /api/student-search.php?q=search_term&page=1
 *
 * Returns paginated JSON results searching across multiple student fields.
 * Requires authenticated staff session.
 */

session_start();

if (empty($_SESSION['user_id']) || (($_SESSION['type'] ?? '') !== 'staff')) {
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
$likeClause = "CONCAT_WS(' ', student_number, first_name, surname, other_name,
                student_id, index_number, registration_number,
                phone, mobile_number, email, program, set_name) LIKE ?";

try {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE $likeClause AND status != 'deleted'");
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
    $sql = "SELECT id, student_id, student_number, index_number, registration_number,
                   first_name, surname, other_name,
                   CONCAT(first_name, ' ', COALESCE(surname, '')) AS full_name,
                   email, phone, mobile_number, gender, program,
                   level, set_name, year_of_study, status,
                   date_of_birth, passport_photo
            FROM students
            WHERE $likeClause AND status != 'deleted'
            ORDER BY
                CASE WHEN student_number LIKE ? THEN 0
                     WHEN student_id LIKE ? THEN 1
                     WHEN CONCAT(first_name, ' ', COALESCE(surname, '')) LIKE ? THEN 2
                     ELSE 3
                END,
                surname ASC, first_name ASC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        Response::error('Query preparation failed', 500);
    }

    $exactNumber = $query;
    $limitInt = (int) $limit;
    $offsetInt = (int) $offset;
    $stmt->bind_param(
        'sssssii',
        $searchTerm,
        $exactNumber,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $limitInt,
        $offsetInt
    );

    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];

    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
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
