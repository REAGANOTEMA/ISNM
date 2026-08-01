<?php
/**
 * ISNM Student Search Page
 * Uses the configured staff database connection.
 */

require_once __DIR__ . '/../../config/database.php';

$conn = getStaffConnection();
if (!$conn) {
    die('Database connection failed');
}

$searchTerm = isset($_GET['query']) ? trim($_GET['query']) : '';
$results = [];
$error = '';

if ($searchTerm !== '') {
    $stmt = $conn->prepare('CALL search_students(?)');
    if ($stmt) {
        $stmt->bind_param('s', $searchTerm);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        } else {
            $error = 'Search error: ' . $conn->error;
        }
        $stmt->close();
    } else {
        $error = 'Search procedure unavailable';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Search - ISNM Staff Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .search-container { margin-top: 50px; }
        .student-card { transition: transform 0.2s; }
        .student-card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container search-container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4"><i class="bi bi-people-fill text-primary"></i> Student Information Search</h2>
                    <form action="" method="GET" class="input-group input-group-lg">
                        <input type="text" name="query" class="form-control"
                               placeholder="Search by name, index number, or phone..."
                               value="<?php echo htmlspecialchars($searchTerm); ?>" required>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </form>
                    <div class="form-text text-center mt-2">
                        Enter any part of a student's name, their registration number, index number, or phone number.
                    </div>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($searchTerm !== ''): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Search Results for "<?php echo htmlspecialchars($searchTerm); ?>"</h4>
                    <span class="badge bg-secondary"><?php echo count($results); ?> records found</span>
                </div>

                <?php if (count($results) > 0): ?>
                    <div class="table-responsive bg-white rounded shadow-sm">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Full Name</th>
                                    <th>Student No.</th>
                                    <th>Index Number</th>
                                    <th>Program</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($student['email'] ?? ''); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['student_number'] ?? ''); ?></td>
                                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($student['index_number'] ?? ''); ?></span></td>
                                        <td><?php echo htmlspecialchars($student['program'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($student['phone'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($student['status'] ?? '') === 'Active' ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo htmlspecialchars($student['status'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view-student.php?id=<?php echo (int) ($student['id'] ?? 0); ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="bi bi-exclamation-circle fs-1 d-block mb-3"></i>
                        No students found matching your search criteria.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
