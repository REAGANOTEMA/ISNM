<?php
/**
 * Global Search API Endpoint
 * GET /api/student-search.php?q=search_term&page=1
 *
 * Searches across ALL 4 databases: students, staffs, website (news), ICT (assets).
 * Returns unified JSON results grouped by type.
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

if ($query === '') {
    Response::json([
        'results' => [
            'students' => [],
            'staff'    => [],
            'news'     => [],
            'assets'   => [],
        ],
        'total' => 0,
        'query' => '',
    ]);
}

$searchTerm = '%' . $query . '%';
$results = [
    'students' => [],
    'staff'    => [],
    'news'     => [],
    'assets'   => [],
];
$total = 0;

// ── Students DB ──────────────────────────────────────────────────────
try {
    $conn = DatabaseConnection::getStudentsConnection();
    if ($conn) {
        $likeClause = "CONCAT_WS(' ', student_number, first_name, surname, other_name,
                        student_id, index_number, registration_number,
                        phone, mobile_number, email, program, set_name) LIKE ?";

        $stmt = $conn->prepare(
            "SELECT id, student_id, student_number, first_name, surname, other_name, program, email, phone
             FROM students
             WHERE $likeClause AND status != 'deleted'
             ORDER BY
                 CASE WHEN student_number LIKE ? THEN 0
                      WHEN student_id LIKE ? THEN 1
                      WHEN CONCAT(first_name, ' ', COALESCE(surname, '')) LIKE ? THEN 2
                      ELSE 3
                 END,
                 surname ASC, first_name ASC
             LIMIT 10"
        );

        if ($stmt) {
            $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $fullName = trim($row['first_name'] . ' ' . ($row['surname'] ?? ''));
                $results['students'][] = [
                    'type'     => 'student',
                    'id'       => (int) $row['id'],
                    'title'    => "<strong>" . htmlspecialchars($row['student_number'] ?? $row['student_id']) . "</strong> - " . htmlspecialchars($fullName),
                    'subtitle' => htmlspecialchars(implode(' | ', array_filter([$row['program'] ?? '', $row['email'] ?? '', $row['phone'] ?? '']))),
                    'url'      => '/modules/staff/students/view.php?id=' . (int) $row['id'],
                    'database' => 'students',
                ];
                $total++;
            }
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    error_log("[GlobalSearch] Students error: " . $e->getMessage());
}

// ── Staffs DB ────────────────────────────────────────────────────────
try {
    $conn = DatabaseConnection::getStaffConnection();
    if ($conn) {
        $likeClause = "CONCAT_WS(' ', id, full_name, email, phone, department, role, staff_id) LIKE ?";

        $stmt = $conn->prepare(
            "SELECT id, full_name, email, phone, department, role, staff_id
             FROM staff
             WHERE $likeClause
             ORDER BY
                 CASE WHEN staff_id LIKE ? THEN 0
                      WHEN full_name LIKE ? THEN 1
                      ELSE 2
                 END,
                 full_name ASC
             LIMIT 10"
        );

        if ($stmt) {
            $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $results['staff'][] = [
                    'type'     => 'staff',
                    'id'       => (int) $row['id'],
                    'title'    => "<strong>" . htmlspecialchars($row['full_name'] ?? '') . "</strong>",
                    'subtitle' => htmlspecialchars(implode(' | ', array_filter([$row['staff_id'] ?? '', $row['role'] ?? '', $row['department'] ?? '', $row['email'] ?? '']))),
                    'url'      => '/modules/staff/staffs/view.php?id=' . (int) $row['id'],
                    'database' => 'staffs',
                ];
                $total++;
            }
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    error_log("[GlobalSearch] Staff error: " . $e->getMessage());
}

// ── Website DB (News) ───────────────────────────────────────────────
try {
    $conn = DatabaseConnection::getWebsiteConnection();
    if ($conn) {
        $likeClause = "CONCAT_WS(' ', id, title, slug, summary, category, status) LIKE ?";

        $stmt = $conn->prepare(
            "SELECT id, title, slug, summary, category, status, published_at
             FROM news
             WHERE $likeClause AND status = 'published'
             ORDER BY
                 CASE WHEN title LIKE ? THEN 0
                      WHEN summary LIKE ? THEN 1
                      ELSE 2
                 END,
                 published_at DESC
             LIMIT 10"
        );

        if ($stmt) {
            $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $results['news'][] = [
                    'type'     => 'news',
                    'id'       => (int) $row['id'],
                    'title'    => "<strong>" . htmlspecialchars($row['title'] ?? '') . "</strong>",
                    'subtitle' => htmlspecialchars(implode(' | ', array_filter([$row['category'] ?? '', $row['status'] ?? '', $row['published_at'] ?? '']))),
                    'url'      => '/modules/staff/website/news/view.php?id=' . (int) $row['id'],
                    'database' => 'website',
                ];
                $total++;
            }
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    error_log("[GlobalSearch] News error: " . $e->getMessage());
}

// ── ICT DB (Assets) ─────────────────────────────────────────────────
try {
    $conn = DatabaseConnection::getICTConnection();
    if ($conn) {
        $likeClause = "CONCAT_WS(' ', id, asset_name, asset_tag, category, location, status) LIKE ?";

        $stmt = $conn->prepare(
            "SELECT id, asset_name, asset_tag, category, location, status
             FROM ict_assets
             WHERE $likeClause
             ORDER BY
                 CASE WHEN asset_tag LIKE ? THEN 0
                      WHEN asset_name LIKE ? THEN 1
                      ELSE 2
                 END,
                 asset_name ASC
             LIMIT 10"
        );

        if ($stmt) {
            $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $results['assets'][] = [
                    'type'     => 'asset',
                    'id'       => (int) $row['id'],
                    'title'    => "<strong>" . htmlspecialchars($row['asset_name'] ?? '') . "</strong>",
                    'subtitle' => htmlspecialchars(implode(' | ', array_filter([$row['asset_tag'] ?? '', $row['category'] ?? '', $row['location'] ?? '', $row['status'] ?? '']))),
                    'url'      => '/modules/staff/ict/assets/view.php?id=' . (int) $row['id'],
                    'database' => 'ict',
                ];
                $total++;
            }
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    error_log("[GlobalSearch] ICT error: " . $e->getMessage());
}

Response::json([
    'results' => $results,
    'total'   => $total,
    'query'   => $query,
]);
