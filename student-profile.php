<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: student-login.php'); exit;
}
$isStaff = ($_SESSION['type'] ?? '') === 'staff';
$isStudent = ($_SESSION['type'] ?? '') === 'student';
if (!$isStaff && !$isStudent) {
    header('Location: student-login.php'); exit;
}
$auth_service = new AuthenticationService();
$user = $auth_service->getCurrentUser();
$studentsDb = getStudentsConnection();
$userId = (int)($user['id'] ?? 0);

$studentInfo = [];
if ($studentsDb) {
    $sr = $studentsDb->query("SELECT * FROM students WHERE id=$userId LIMIT 1");
    $studentInfo = $sr ? $sr->fetch_assoc() : [];
}

$fullName = $studentInfo ? htmlspecialchars(($studentInfo['surname']??'') . ' ' . ($studentInfo['firstname'] ?? $studentInfo['first_name']??'')) : ($_SESSION['full_name'] ?? 'Student');
$indexNumber = htmlspecialchars($studentInfo['index_number'] ?? $_SESSION['index_number'] ?? '');
$program = htmlspecialchars($studentInfo['program'] ?? 'N/A');
$yearOfStudy = (int)($studentInfo['year_of_study'] ?? 1);
$email = htmlspecialchars($studentInfo['email'] ?? '');
$phone = htmlspecialchars($studentInfo['phone'] ?? $studentInfo['mobile_number'] ?? '');
$gender = htmlspecialchars($studentInfo['gender'] ?? '');
$level = htmlspecialchars($studentInfo['level'] ?? '');
$setName = htmlspecialchars($studentInfo['set_name'] ?? '');

$profilePic = '';
if (!empty($studentInfo['profile_picture'])) {
    $profilePic = $studentInfo['profile_picture'];
} elseif (!empty($studentInfo['passport_photo'])) {
    $profilePic = $studentInfo['passport_photo'];
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/dashboard_head.php';
include_once __DIR__ . '/includes/sidebar.php';
?>
<style>
:root{--primary:#2c5f8a;--accent:#1a9e6e}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif}
.profile-card{border:none;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08);margin-bottom:24px}
.profile-card .card-header{background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:20px 28px;border:none;color:#fff}
.profile-card .card-body{padding:24px 28px}
.profile-avatar{width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,.15)}
.profile-avatar-placeholder{width:120px;height:120px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:3rem;font-weight:700;color:#fff;background:linear-gradient(135deg,#2c5f8a,#1a9e6e);border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,.15)}
.info-row{display:flex;align-items:center;padding:12px 0;border-bottom:1px solid #f0f4f8}
.info-row:last-child{border-bottom:none}
.info-row .label{font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;font-weight:600;min-width:160px}
.info-row .value{font-size:.95rem;color:#1e293b;font-weight:500}
</style>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-user-graduate me-2" style="color:#2c5f8a"></i>My Profile</h2>
            <p class="text-muted mb-0">View your student profile information</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold"><?= $fullName ?></div>
            <small class="text-muted"><?= $indexNumber ?></small>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="profile-card">
                <div class="card-body text-center py-5">
                    <?php if ($profilePic): ?>
                        <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" class="profile-avatar mb-3">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder mx-auto mb-3">
                            <?= strtoupper(substr($studentInfo['first_name'] ?? $fullName, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <h4 class="fw-bold mb-1"><?= $fullName ?></h4>
                    <p class="text-muted mb-2"><?= $indexNumber ?></p>
                    <span class="badge" style="background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:6px 16px;border-radius:20px;font-size:.8rem"><?= $program ?></span>
                    <div class="mt-3">
                        <?php if ($email): ?>
                            <a href="mailto:<?= $email ?>" class="btn btn-sm btn-outline-primary me-1" style="border-radius:8px"><i class="fas fa-envelope"></i></a>
                        <?php endif; ?>
                        <?php if ($phone): ?>
                            <a href="tel:<?= $phone ?>" class="btn btn-sm btn-outline-success" style="border-radius:8px"><i class="fas fa-phone"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="profile-card">
                <div class="card-header">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-id-card me-2"></i>Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <div class="label">Full Name</div>
                        <div class="value"><?= $fullName ?></div>
                    </div>
                    <div class="info-row">
                        <div class="label">Index Number</div>
                        <div class="value"><?= $indexNumber ?></div>
                    </div>
                    <div class="info-row">
                        <div class="label">Gender</div>
                        <div class="value"><?= $gender ?: 'N/A' ?></div>
                    </div>
                    <div class="info-row">
                        <div class="label">Phone</div>
                        <div class="value"><?= $phone ?: 'N/A' ?></div>
                    </div>
                    <div class="info-row">
                        <div class="label">Email</div>
                        <div class="value"><?= $email ?: 'N/A' ?></div>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <div class="card-header">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <div class="label">Program</div>
                        <div class="value"><?= $program ?></div>
                    </div>
                    <div class="info-row">
                        <div class="label">Year of Study</div>
                        <div class="value">Year <?= $yearOfStudy ?></div>
                    </div>
                    <div class="info-row">
                        <div class="label">Level</div>
                        <div class="value"><?= $level ?: 'N/A' ?></div>
                    </div>
                    <div class="info-row">
                        <div class="label">Set</div>
                        <div class="value"><?= $setName ?: 'N/A' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/dashboard_footer.php'; ?>
