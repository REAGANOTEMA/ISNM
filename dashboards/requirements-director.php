<?php
/**
 * Requirements Director Dashboard
 * Manages student requirements and clearance tracking
 */

require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/requirements_functions.php';

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csv = exportRequirementsToCSV();
    if ($csv) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="requirements_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $csv;
        exit();
    }
}

// Ensure director access
$ctx = bootstrapStaffDashboard(['director', 'requirement']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];

$user_id = (int) ($user['id'] ?? $_SESSION['user_id'] ?? 0);
$user_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Director';
$user_email = $user['email'] ?? $_SESSION['email'] ?? '';

// Get statistics
$stats = getRequirementStats();

// Get current page and search/filter parameters
$page = (int) ($_GET['page'] ?? 1);
$search = sanitizeInput($_GET['search'] ?? '');
$filter = sanitizeInput($_GET['filter'] ?? 'all');
$itemsPerPage = 20;
$offset = ($page - 1) * $itemsPerPage;

// Get students based on search and filter
if (!empty($search)) {
    $students = searchStudents($search);
} elseif ($filter !== 'all') {
    $students = filterStudents($filter, '');
} else {
    $students = getStudentsList($itemsPerPage, $offset);
}

// Get total count for pagination
$totalStudents = count($students) > 0 ? count($students) : getTotalStudentsCount();
$totalPages = ceil($totalStudents / $itemsPerPage);

// Get all requirement items
$requirementItems = getAllRequirementItems();

// Group items by category
$itemsByCategory = [];
foreach ($requirementItems as $item) {
    if (!isset($itemsByCategory[$item['category']])) {
        $itemsByCategory[$item['category']] = [];
    }
    $itemsByCategory[$item['category']][] = $item;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requirements Portal - Director Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../dashboards/dashboard-style.css">
    <style>
        .requirements-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .header-section h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }

        .header-section p {
            margin: 5px 0;
            font-size: 0.95em;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 0.9em;
            color: #666;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card .percent {
            font-size: 0.9em;
            color: #999;
        }

        .controls-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .controls-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            font-size: 0.9em;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 0.95em;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .btn-danger {
            background: #f56565;
            color: white;
        }

        .btn-danger:hover {
            background: #e53e3e;
        }

        .btn-secondary {
            background: #a0aec0;
            color: white;
        }

        .btn-secondary:hover {
            background: #8a99ac;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85em;
        }

        .students-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .student-item {
            border-bottom: 1px solid #eee;
            padding: 15px;
            transition: background 0.3s;
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .student-item:hover {
            background: #f9f9f9;
        }

        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .student-details {
            font-size: 0.9em;
            color: #666;
        }

        .student-progress {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-bar {
            width: 150px;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #48bb78, #38a169);
            transition: width 0.3s;
        }

        .progress-text {
            font-size: 0.9em;
            font-weight: 600;
            color: #666;
            min-width: 50px;
        }

        .requirements-checklist {
            margin-top: 15px;
        }

        .category-section {
            margin-bottom: 15px;
        }

        .category-title {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #f0f0f0;
            font-size: 0.95em;
            text-transform: uppercase;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #eee;
            transition: all 0.3s;
        }

        .requirement-item:hover {
            background: #f0f0f0;
            border-color: #ddd;
        }

        .requirement-item input[type="checkbox"] {
            margin-right: 10px;
            cursor: pointer;
            width: 18px;
            height: 18px;
            accent-color: #48bb78;
        }

        .requirement-item label {
            cursor: pointer;
            flex: 1;
            margin: 0;
            font-size: 0.9em;
        }

        .requirement-item input[type="checkbox"]:checked + label {
            color: #999;
            text-decoration: line-through;
        }

        .student-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
            padding: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #667eea;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 20px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
            display: block;
        }

        .alert.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
            display: block;
        }

        @media (max-width: 768px) {
            .controls-row {
                grid-template-columns: 1fr;
            }

            .items-grid {
                grid-template-columns: 1fr;
            }

            .student-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .requirements-container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="requirements-container">
        <!-- Header -->
        <div class="header-section">
            <h1>📋 Requirements Portal</h1>
            <p>Welcome, <?php echo htmlspecialchars($user_name); ?></p>
            <p style="font-size: 0.85em;">Manage student requirements and clearance tracking</p>
        </div>

        <!-- Statistics Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <div class="value"><?php echo $stats['totalStudents']; ?></div>
                <div class="percent">In system</div>
            </div>
            <div class="stat-card">
                <h3>All Cleared</h3>
                <div class="value"><?php echo $stats['studentsAllCleared']; ?></div>
                <div class="percent"><?php echo round(($stats['studentsAllCleared'] / max(1, $stats['totalStudents'])) * 100); ?>% complete</div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="value"><?php echo $stats['studentsPending']; ?></div>
                <div class="percent">Awaiting clearance</div>
            </div>
            <div class="stat-card">
                <h3>Overall Progress</h3>
                <div class="value"><?php echo $stats['percentComplete']; ?>%</div>
                <div class="percent"><?php echo $stats['totalCleared']; ?>/<?php echo ($stats['totalCleared'] + $stats['totalPending']); ?> items cleared</div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="controls-section">
            <form method="GET" style="margin-bottom: 15px;">
                <div class="controls-row">
                    <div class="form-group">
                        <label for="search">Search Student</label>
                        <input type="text" id="search" name="search" placeholder="Name, Admission #, or Phone" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="filter">Filter By</label>
                        <select id="filter" name="filter">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Students</option>
                            <option value="all_cleared" <?php echo $filter === 'all_cleared' ? 'selected' : ''; ?>>All Cleared</option>
                            <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Requirements</option>
                            <option value="initialized" <?php echo $filter === 'initialized' ? 'selected' : ''; ?>>Initialized</option>
                        </select>
                    </div>
                    <div></div>
                    <div>
                        <button type="submit" class="btn btn-primary">🔍 Search</button>
                    </div>
                </div>
            </form>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;">
                <a href="?page=1" class="btn btn-secondary btn-sm">↻ Reset</a>
                <a href="?export=csv" class="btn btn-secondary btn-sm" download>📥 Export CSV</a>
            </div>
        </div>

        <!-- Alert Messages -->
        <div id="alert" class="alert"></div>

        <!-- Students List -->
        <div class="students-list">
            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👤</div>
                    <h3>No students found</h3>
                    <p>Try adjusting your search criteria or filters</p>
                </div>
            <?php else: ?>
                <?php foreach ($students as $student): 
                    $progress = getStudentProgress($student['id']);
                    $requirements = getStudentRequirements($student['id']);
                    
                    // Initialize if not already done
                    if (empty($requirements)) {
                        initializeStudentRequirements($student['id'], $student['index_number'], $student['full_name'], $student['phone']);
                        $requirements = getStudentRequirements($student['id']);
                    }
                    
                    // Create map of requirement status
                    $reqMap = [];
                    foreach ($requirements as $req) {
                        $reqMap[$req['requirement_item_id']] = $req;
                    }
                ?>
                    <div class="student-item">
                        <div class="student-header">
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                <div class="student-details">
                                    <span>📚 <?php echo htmlspecialchars($student['index_number']); ?></span>
                                    <span> | 📱 <?php echo htmlspecialchars($student['phone']); ?></span>
                                </div>
                            </div>
                            <div class="student-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress['percentage']; ?>%;"></div>
                                </div>
                                <div class="progress-text"><?php echo $progress['percentage']; ?>%</div>
                            </div>
                        </div>

                        <div class="requirements-checklist">
                            <?php foreach ($itemsByCategory as $category => $items): ?>
                                <div class="category-section">
                                    <div class="category-title"><?php echo htmlspecialchars($category); ?></div>
                                    <div class="items-grid">
                                        <?php foreach ($items as $item): 
                                            $req = $reqMap[$item['id']] ?? null;
                                            $isCleared = $req ? (bool)$req['is_cleared'] : false;
                                        ?>
                                            <div class="requirement-item">
                                                <input type="checkbox" 
                                                       id="req_<?php echo $student['id']; ?>_<?php echo $item['id']; ?>"
                                                       <?php echo $isCleared ? 'checked' : ''; ?>
                                                       data-student-id="<?php echo $student['id']; ?>"
                                                       data-item-id="<?php echo $item['id']; ?>"
                                                       onchange="toggleRequirement(this)">
                                                <label for="req_<?php echo $student['id']; ?>_<?php echo $item['id']; ?>">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="student-actions">
                            <button class="btn btn-success btn-sm" onclick="clearAllForStudent(<?php echo $student['id']; ?>)">
                                ✓ Clear All
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="unclearAllForStudent(<?php echo $student['id']; ?>)">
                                ✗ Reset All
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1 && empty($search)): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1">« First</a>
                    <a href="?page=<?php echo $page - 1; ?>">‹ Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next ›</a>
                    <a href="?page=<?php echo $totalPages; ?>">Last »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleRequirement(checkbox) {
            const studentId = checkbox.getAttribute('data-student-id');
            const itemId = checkbox.getAttribute('data-item-id');
            const isCleared = checkbox.checked;

            const formData = new FormData();
            formData.append('action', isCleared ? 'requirement_clear' : 'requirement_unclear');
            formData.append('student_id', studentId);
            formData.append('item_id', itemId);

            fetch('../auth-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(isCleared ? 'Requirement cleared' : 'Requirement uncleared', 'success');
                    location.reload();
                } else {
                    showAlert(data.message || 'Error updating requirement', 'error');
                    checkbox.checked = !isCleared;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error updating requirement', 'error');
                checkbox.checked = !isCleared;
            });
        }

        function clearAllForStudent(studentId) {
            if (!confirm('Clear all requirements for this student?')) return;

            const formData = new FormData();
            formData.append('action', 'requirement_clear_all');
            formData.append('student_id', studentId);

            fetch('../auth-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('All requirements cleared', 'success');
                    location.reload();
                } else {
                    showAlert(data.message || 'Error clearing requirements', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error clearing requirements', 'error');
            });
        }

        function unclearAllForStudent(studentId) {
            if (!confirm('Reset all requirements for this student?')) return;

            const formData = new FormData();
            formData.append('action', 'requirement_unclear_all');
            formData.append('student_id', studentId);

            fetch('../auth-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('All requirements reset', 'success');
                    location.reload();
                } else {
                    showAlert(data.message || 'Error resetting requirements', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error resetting requirements', 'error');
            });
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = 'alert ' + type;
            alert.textContent = message;
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>
