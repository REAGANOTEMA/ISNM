<?php
/**
 * ISNM Student Full Profile View
 * Accessible by authorized staff to view complete student records.
 */

require_once '../../auth-service.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Security Check: Authenticated and Authorized
if (!$auth_service->isAuthenticated() || !$auth_service->canSearchStudentProfiles($_SESSION['role'])) {
    $_SESSION['error'] = "Unauthorized access attempt to student profiles.";
    header('Location: ../student-login.php');
    exit();
}

$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($studentId <= 0) {
    die("Invalid Student ID.");
}

try {
    $conn = getStudentsConnection();
    
    // 2. Fetch Comprehensive Student Data
    $sql = "SELECT s.*, 
            (SELECT SUM(amount) FROM student_fees WHERE student_id = s.id AND status != 'Paid') as outstanding_balance,
            (SELECT COUNT(*) FROM student_fees WHERE student_id = s.id AND status = 'Overdue') as overdue_count
            FROM students s 
            WHERE s.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $student = $stmt->get_result()->fetch_assoc();
    
    if (!$student) {
        die("Student record not found.");
    }

} catch (Exception $e) {
    die("System Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - <?php echo htmlspecialchars($student['full_name'] ?? ($student['first_name'] . ' ' . $student['surname'])); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .profile-header { background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%); color: white; padding: 40px 0; margin-bottom: 30px; }
        .avatar-placeholder { width: 150px; height: 150px; background-color: #e0e0e0; border: 5px solid white; display: flex; align-items: center; justify-content: center; font-size: 60px; color: #9e9e9e; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .card-title { color: #1A237E; font-weight: 700; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; margin-bottom: 20px; }
        .info-label { font-weight: 600; color: #6c757d; font-size: 0.85rem; text-transform: uppercase; }
        .info-value { font-weight: 500; color: #212529; font-size: 1.05rem; }
        .status-badge { padding: 8px 16px; border-radius: 50px; font-weight: 600; }
    </style>
</head>
<body>

<div class="profile-header shadow-sm">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-2 text-center text-md-start">
                <?php if (!empty($student['profile_picture'])): ?>
                    <img src="../uploads/students/<?php echo htmlspecialchars($student['profile_picture']); ?>" class="rounded-circle border border-4 border-white shadow" style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <div class="avatar-placeholder rounded-circle mx-auto mx-md-0 shadow">
                        <i class="bi bi-person-fill"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-7 text-center text-md-start">
                <h1 class="display-5 fw-bold mb-1"><?php echo htmlspecialchars($student['full_name'] ?? ($student['first_name'] . ' ' . $student['surname'])); ?></h1>
                <p class="lead mb-2"><?php echo htmlspecialchars($student['program']); ?> â€¢ Year <?php echo htmlspecialchars($student['current_year']); ?></p>
                <div>
                    <span class="status-badge <?php echo $student['status'] === 'Active' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                        <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($student['status']); ?>
                    </span>
                    <span class="ms-2 opacity-75"><i class="bi bi-hash"></i> <?php echo htmlspecialchars($student['student_number']); ?></span>
                </div>
            </div>
            <div class="col-md-3 text-center text-md-end mt-4 mt-md-0">
                <a href="search-students.php" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Back to Search</a>
                <button onclick="window.print()" class="btn btn-light ms-2"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Sidebar: Quick Actions & Financials -->
        <div class="col-lg-4">
            <div class="card bg-white">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-cash-stack me-2"></i>Financial Summary</h5>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="info-label">Current Balance</span>
                        <span class="info-value text-danger">UGX <?php echo number_format($student['outstanding_balance'] ?? 0); ?></span>
                    </div>
                    <?php if (($student['overdue_count'] ?? 0) > 0): ?>
                    <div class="alert alert-danger py-2">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $student['overdue_count']; ?> Overdue Invoices
                    </div>
                    <?php endif; ?>
                    <a href="view-fees.php?id=<?php echo $studentId; ?>" class="btn btn-primary w-100">View Full Statement</a>
                </div>
            </div>

            <div class="card bg-white">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-shield-lock me-2"></i>Account Security</h5>
                    <div class="mb-3">
                        <span class="info-label">Last Login</span><br>
                        <span class="info-value small"><?php echo $student['last_login'] ? date('M d, Y H:i', strtotime($student['last_login'])) : 'Never'; ?></span>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm w-100 mb-2">Reset Password</button>
                    <button class="btn btn-outline-danger btn-sm w-100">Deactivate Account</button>
                </div>
            </div>
        </div>

        <!-- Main Content: Detailed Information -->
        <div class="col-lg-8">
            <!-- Personal Info -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-person-badge me-2"></i>Personal Information</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value"><?php echo date('M d, Y', strtotime($student['date_of_birth'] ?? 'today')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Gender</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['gender'] ?? 'Not Specified'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Nationality</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['nationality'] ?? 'Ugandan'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">National ID / NIN</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['national_student_id_number'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Info -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-mortarboard-fill me-2"></i>Academic Details</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-label">Registration Number</div>
                            <div class="info-value fw-bold text-primary"><?php echo htmlspecialchars($student['registration_number']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Index Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['index_number']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Intake Set</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['set_name']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Enrollment Date</div>
                            <div class="info-value"><?php echo date('M Y', strtotime($student['intake_date'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-telephone-fill me-2"></i>Contact Information</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['phone'] ?? $student['mobile_number']); ?></div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">Residential Address</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($student['address'] ?? 'No address recorded')); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guardian Info -->
            <div class="card mb-5">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-people-fill me-2"></i>Guardian & Emergency Contact</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-label">Next of Kin / Guardian</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['guardian_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Guardian Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['guardian_phone'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Emergency Contact Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['emergency_contact_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Emergency Contact Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['emergency_contact_phone'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center text-muted py-4 small">
    &copy; <?php echo date('Y'); ?> Iganga School of Nursing and Midwifery. All student data is confidential.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>