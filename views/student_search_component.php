<?php
/**
 * Student Search Component for Staff Dashboards
 * Reusable component to search and display students from Excel data
 */

require_once __DIR__ . '/../auth-service.php';
require_once __DIR__ . '/student_data_loader.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth_service = new AuthenticationService();
$canSearchStudents = $auth_service->isAuthenticated()
    && ($_SESSION['type'] ?? '') === 'staff'
    && $auth_service->canSearchStudentProfiles($_SESSION['role'] ?? '');

if (!$canSearchStudents) {
    return;
}

$dataLoader = new StudentDataLoader();

// Handle search
$searchTerm = $_GET['student_search'] ?? '';
$filters = [
    'program' => $_GET['program'] ?? '',
    'level' => $_GET['level'] ?? '',
    'set' => $_GET['set'] ?? '',
    'gender' => $_GET['gender'] ?? '',
    'year' => $_GET['year'] ?? ''
];

$students = $dataLoader->searchStudents($searchTerm, $filters);
$filterOptions = $dataLoader->getFilterOptions();
?>

<!-- Student Search Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-search me-2"></i>Student Search
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="student_search" class="form-control" 
                       placeholder="Search by name, index number..." 
                       value="<?php echo htmlspecialchars($searchTerm); ?>">
            </div>
            <div class="col-md-2">
                <select name="program" class="form-select">
                    <option value="">All Programs</option>
                    <?php foreach ($filterOptions['programs'] as $program): ?>
                        <option value="<?php echo $program; ?>" 
                                <?php echo $filters['program'] === $program ? 'selected' : ''; ?>>
                            <?php echo $program; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="level" class="form-select">
                    <option value="">All Levels</option>
                    <?php foreach ($filterOptions['levels'] as $level): ?>
                        <option value="<?php echo $level; ?>" 
                                <?php echo $filters['level'] === $level ? 'selected' : ''; ?>>
                            <?php echo $level; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="set" class="form-select">
                    <option value="">All Sets</option>
                    <?php foreach ($filterOptions['sets'] as $set): ?>
                        <option value="<?php echo $set; ?>" 
                                <?php echo $filters['set'] === $set ? 'selected' : ''; ?>>
                            <?php echo $set; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="gender" class="form-select">
                    <option value="">All Genders</option>
                    <?php foreach ($filterOptions['genders'] as $gender): ?>
                        <option value="<?php echo $gender; ?>" 
                                <?php echo $filters['gender'] === $gender ? 'selected' : ''; ?>>
                            <?php echo $gender; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Student Results -->
<?php if (!empty($searchTerm) || !empty(array_filter($filters))): ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>Search Results
            <span class="badge bg-secondary ms-2"><?php echo count($students); ?> students</span>
        </h5>
        <button class="btn btn-sm btn-outline-secondary" onclick="clearStudentSearch()">
            <i class="fas fa-times me-1"></i>Clear
        </button>
    </div>
    <div class="card-body">
        <?php if (!empty($students)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Index Number</th>
                            <th>Program</th>
                            <th>Level</th>
                            <th>Set</th>
                            <th>Gender</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name'] . ' ' . $student['other_name']); ?></strong>
                                </td>
                                <td><code><?php echo htmlspecialchars($student['index_number']); ?></code></td>
                                <td><?php echo htmlspecialchars($student['program']); ?></td>
                                <td><?php echo htmlspecialchars($student['level']); ?></td>
                                <td><?php echo htmlspecialchars($student['set']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo strtolower($student['gender']) === 'male' ? 'primary' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($student['gender']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewStudentDetails('<?php echo htmlspecialchars($student['index_number']); ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-search fa-3x mb-3"></i>
                <p>No students found matching your search criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function clearStudentSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('student_search');
    url.searchParams.delete('program');
    url.searchParams.delete('level');
    url.searchParams.delete('set');
    url.searchParams.delete('gender');
    url.searchParams.delete('year');
    window.location.href = url.toString();
}

function viewStudentDetails(indexNumber) {
    alert('Viewing details for student: ' + indexNumber);
    // You can implement a modal or redirect to a detailed view
}
</script>
<?php endif; ?>
